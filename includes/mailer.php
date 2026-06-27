<?php

require_once __DIR__ . '/env.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../PHPMailer/src/PHPMailer.php')) {
    require_once __DIR__ . '/../PHPMailer/src/Exception.php';
    require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
}

function meeting_mail_admin_email(): string
{
    $email = getenv('MEETING_MAIL_ADMIN') ?: getenv('MAIL_ADMIN_ADDRESS');
    return is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : 'okprincesingh@gmail.com';
}

function meeting_mail_from_email(): string
{
    $email = getenv('MEETING_MAIL_FROM') ?: getenv('MAIL_FROM_ADDRESS');
    return is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : 'website@jaikvik.com';
}

function meeting_google_meet_link(): string
{
    $link = getenv('MEETING_GOOGLE_MEET_LINK');
    if (!is_string($link) || trim($link) === '' || trim($link) === 'https://meet.google.com/') {
        $fallbackCandidates = [
            getenv('MEETING_GOOGLE_MEET_FALLBACK_LINK') ?: '',
            getenv('GOOGLE_MEET_FALLBACK_LINK') ?: '',
        ];
        foreach ($fallbackCandidates as $candidate) {
            if (is_string($candidate) && filter_var(trim($candidate), FILTER_VALIDATE_URL)) {
                $link = trim($candidate);
                break;
            }
        }
    }

    $link = is_string($link) ? trim($link) : '';
    if ($link === '' || $link === 'https://meet.google.com/' || $link === 'https://meet.google.com') {
        return '';
    }

    return $link;
}

function meeting_mail_last_error(?string $set = null): string
{
    static $lastError = '';
    if ($set !== null) {
        $lastError = $set;
    }
    return $lastError;
}

function meeting_mail_sender_name(): string
{
    $fromName = trim((string) getenv('MAIL_FROM_NAME'));
    return $fromName !== '' ? $fromName : 'MyBrandPlease';
}

function meeting_mail_transport_configs(): array
{
    $configs = [];

    $meetingHost = trim((string) (getenv('MEETING_SMTP_HOST') ?: ''));
    $meetingUser = trim((string) (getenv('MEETING_SMTP_USERNAME') ?: ''));
    $meetingPass = (string) (getenv('MEETING_SMTP_PASSWORD') ?: '');
    if ($meetingHost !== '' && $meetingUser !== '' && $meetingPass !== '') {
        $configs[] = [
            'label' => 'meeting',
            'host' => $meetingHost,
            'username' => $meetingUser,
            'password' => $meetingPass,
            'port' => (int) (getenv('MEETING_SMTP_PORT') ?: 587),
            'encryption' => (string) (getenv('MEETING_SMTP_ENCRYPTION') ?: PHPMailer::ENCRYPTION_STARTTLS),
            'relax_tls' => meeting_mail_allows_relaxed_tls_for_env('MEETING_SMTP_RELAX_TLS'),
        ];
    }

    $defaultHost = trim((string) (getenv('SMTP_HOST') ?: ''));
    $defaultUser = trim((string) (getenv('SMTP_USERNAME') ?: ''));
    $defaultPass = (string) (getenv('SMTP_PASSWORD') ?: '');
    if ($defaultHost !== '' && $defaultUser !== '' && $defaultPass !== '') {
        $duplicate = false;
        foreach ($configs as $config) {
            if (
                strcasecmp((string) $config['host'], $defaultHost) === 0
                && strcasecmp((string) $config['username'], $defaultUser) === 0
            ) {
                $duplicate = true;
                break;
            }
        }
        if (!$duplicate) {
            $configs[] = [
                'label' => 'default',
                'host' => $defaultHost,
                'username' => $defaultUser,
                'password' => $defaultPass,
                'port' => (int) (getenv('SMTP_PORT') ?: 587),
                'encryption' => (string) (getenv('SMTP_ENCRYPTION') ?: PHPMailer::ENCRYPTION_STARTTLS),
                'relax_tls' => meeting_mail_allows_relaxed_tls_for_env('SMTP_RELAX_TLS'),
            ];
        }
    }

    return $configs;
}

function meeting_mail_password_candidates(string $password): array
{
    $candidates = [];
    $trimmed = trim($password);
    if ($trimmed !== '') {
        $candidates[] = $trimmed;
    }

    $collapsedWhitespace = preg_replace('/\s+/', '', $trimmed);
    if (is_string($collapsedWhitespace) && $collapsedWhitespace !== '' && !in_array($collapsedWhitespace, $candidates, true)) {
        $candidates[] = $collapsedWhitespace;
    }

    return $candidates;
}

function meeting_mail_human_error(string $message): string
{
    $normalized = strtolower(trim($message));
    if ($normalized === '') {
        return 'Email could not be sent due to an unknown mail server issue.';
    }

    if (str_contains($normalized, 'could not authenticate')) {
        $smtpUser = trim((string) (getenv('MEETING_SMTP_USERNAME') ?: getenv('SMTP_USERNAME') ?: ''));
        $target = $smtpUser !== '' ? $smtpUser : 'the configured mailbox';
        return 'Email could not be sent because SMTP login failed for ' . $target . '. Please check the SMTP username/password in `.env`.';
    }

    if (str_contains($normalized, 'daily user sending limit exceeded')) {
        return 'Email could not be sent because the Gmail daily sending limit has been exceeded. Please wait or switch to another SMTP account.';
    }

    if (str_contains($normalized, 'invalid recipient email')) {
        return 'Email could not be sent because the recipient email address is invalid.';
    }

    if (str_contains($normalized, 'no smtp transport is configured')) {
        return 'Email could not be sent because SMTP is not configured in `.env`.';
    }

    return $message;
}

function meeting_send_html_mail(string $to, string $subject, string $htmlBody, ?string $replyTo = null, ?string $replyToName = null): bool
{
    meeting_mail_last_error('');
    $to = trim($to);
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        meeting_mail_last_error('Invalid recipient email.');
        meeting_mail_log($to, $subject, false, 'Invalid recipient email.');
        return false;
    }

    if (!class_exists(PHPMailer::class)) {
        meeting_mail_last_error('PHPMailer is not available in vendor/autoload.php.');
        meeting_mail_log($to, $subject, false, 'PHPMailer is not available in vendor/autoload.php.');
        return false;
    }

    $replyTo = is_string($replyTo) ? trim($replyTo) : '';
    $replyToName = trim((string) $replyToName);
    $transports = meeting_mail_transport_configs();
    if (!$transports) {
        meeting_mail_last_error('No SMTP transport is configured.');
        meeting_mail_log($to, $subject, false, 'No SMTP transport is configured.');
        return false;
    }

    $attemptErrors = [];
    foreach ($transports as $transport) {
        $fromName = meeting_mail_sender_name();
        $smtpUser = trim((string) ($transport['username'] ?? ''));
        $configuredFrom = meeting_mail_from_email();
        $from = filter_var($configuredFrom, FILTER_VALIDATE_EMAIL) ? $configuredFrom : $smtpUser;

        if (
            $smtpUser !== ''
            && filter_var($smtpUser, FILTER_VALIDATE_EMAIL)
            && strcasecmp((string) ($transport['host'] ?? ''), 'smtp.gmail.com') !== 0
        ) {
            $from = $smtpUser;
        }

        $passwordCandidates = meeting_mail_password_candidates((string) ($transport['password'] ?? ''));
        foreach ($passwordCandidates as $passwordIndex => $passwordCandidate) {
            try {
                $mail = new PHPMailer(true);
                $mail->Timeout = 20;
                $mail->isSMTP();
                $mail->Host = (string) ($transport['host'] ?? '');
                $mail->SMTPAuth = true;
                $mail->Username = $smtpUser;
                $mail->Password = $passwordCandidate;
                $mail->Port = (int) ($transport['port'] ?? 587);
                $mail->SMTPSecure = (string) ($transport['encryption'] ?? PHPMailer::ENCRYPTION_STARTTLS);
                $mail->SMTPAutoTLS = true;
                if (!empty($transport['relax_tls'])) {
                    $mail->SMTPOptions = [
                        'ssl' => [
                            'verify_peer' => false,
                            'verify_peer_name' => false,
                            'allow_self_signed' => true,
                        ],
                    ];
                }
                $mail->CharSet = 'UTF-8';
                $mail->Encoding = 'base64';

                $mail->setFrom($from, $fromName);
                $mail->Sender = $from;
                $mail->addAddress($to);
                if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                    $mail->addReplyTo($replyTo, $replyToName !== '' ? $replyToName : $replyTo);
                } else {
                    $mail->addReplyTo($from, $fromName);
                }
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $htmlBody;
                $mail->AltBody = meeting_mail_plain_text($htmlBody);
                $mail->send();
                $message = 'SMTP accepted via ' . (string) ($transport['label'] ?? 'smtp') . '.';
                if ($passwordIndex > 0) {
                    $message .= ' Password whitespace was normalized automatically.';
                }
                meeting_mail_last_error($message);
                meeting_mail_log($to, $subject, true, $message);
                return true;
            } catch (Exception $e) {
                $msg = $e->getMessage();
                if (isset($mail) && $mail instanceof PHPMailer && trim((string) $mail->ErrorInfo) !== '') {
                    $msg .= ' | ' . trim((string) $mail->ErrorInfo);
                }
                $suffix = $passwordIndex > 0 ? ' [password-normalized]' : '';
                $attemptErrors[] = strtoupper((string) ($transport['label'] ?? 'smtp')) . $suffix . ': ' . $msg;
            }
        }
    }

    $finalMessage = implode(' || ', $attemptErrors);
    meeting_mail_last_error($finalMessage);
    error_log('[meeting_mailer] send failed to ' . $to . ': ' . $finalMessage);
    meeting_mail_log($to, $subject, false, $finalMessage);
    return false;
}

function meeting_mail_plain_text(string $htmlBody): string
{
    $text = str_replace(['<br>', '<br/>', '<br />', '</p>', '</li>'], "\n", $htmlBody);
    $text = str_replace(['</h1>', '</h2>', '</h3>', '</div>'], "\n", $text);
    $text = trim(html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8'));
    $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;
    $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
    return trim($text);
}

function meeting_mail_log(string $to, string $subject, bool $sent, string $message): void
{
    $dir = __DIR__ . '/../storage';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }

    $line = sprintf(
        "[%s] %s to=%s subject=%s message=%s\n",
        date('c'),
        $sent ? 'sent' : 'failed',
        $to,
        str_replace(["\r", "\n"], ' ', $subject),
        str_replace(["\r", "\n"], ' ', $message)
    );

    @file_put_contents($dir . '/meeting-mail.log', $line, FILE_APPEND | LOCK_EX);
}

function meeting_mail_allows_relaxed_tls(): bool
{
    return meeting_mail_allows_relaxed_tls_for_env('MEETING_SMTP_RELAX_TLS')
        || meeting_mail_allows_relaxed_tls_for_env('SMTP_RELAX_TLS');
}

function meeting_mail_allows_relaxed_tls_for_env(string $envKey): bool
{
    $value = getenv($envKey);
    if (is_string($value) && $value !== '') {
        return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }

    $server = strtolower((string) ($_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? ''));
    return in_array($server, ['localhost', '127.0.0.1', '::1'], true)
        || str_starts_with($server, 'localhost:')
        || str_starts_with($server, '127.0.0.1:');
}
