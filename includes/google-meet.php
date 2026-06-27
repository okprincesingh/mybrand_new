<?php

require_once __DIR__ . '/env.php';

function meeting_google_meet_last_error(?string $set = null): string
{
    static $lastError = '';
    if ($set !== null) {
        $lastError = $set;
    }
    return $lastError;
}

function meeting_google_calendar_is_configured(): bool
{
    return trim((string) getenv('GOOGLE_CALENDAR_CLIENT_ID')) !== ''
        && trim((string) getenv('GOOGLE_CALENDAR_CLIENT_SECRET')) !== ''
        && trim((string) getenv('GOOGLE_CALENDAR_REFRESH_TOKEN')) !== '';
}

function meeting_create_google_meet_link(
    DateTimeInterface $start,
    DateTimeInterface $end,
    string $timezone,
    string $inviteeName,
    string $inviteeEmail,
    array $guestEmails,
    string $notes
): ?string {
    meeting_google_meet_last_error('');

    if (!meeting_google_calendar_is_configured()) {
        meeting_google_meet_last_error('Google Calendar OAuth is not configured.');
        return null;
    }

    $accessToken = meeting_google_calendar_access_token();
    if ($accessToken === null) {
        return null;
    }

    $attendees = [];
    $inviteeEmail = trim($inviteeEmail);
    if (filter_var($inviteeEmail, FILTER_VALIDATE_EMAIL)) {
        $attendees[] = ['email' => $inviteeEmail, 'displayName' => trim($inviteeName) ?: $inviteeEmail];
    }
    foreach ($guestEmails as $guestEmail) {
        $guestEmail = trim((string) $guestEmail);
        if (filter_var($guestEmail, FILTER_VALIDATE_EMAIL)) {
            $attendees[] = ['email' => $guestEmail];
        }
    }

    $event = [
        'summary' => '30 Minute Meeting',
        'description' => trim($notes) !== '' ? $notes : 'Meeting scheduled from MyBrandPlease website.',
        'start' => [
            'dateTime' => $start->format(DateTimeInterface::RFC3339),
            'timeZone' => $timezone,
        ],
        'end' => [
            'dateTime' => $end->format(DateTimeInterface::RFC3339),
            'timeZone' => $timezone,
        ],
        'attendees' => $attendees,
        'conferenceData' => [
            'createRequest' => [
                'requestId' => 'mbp-' . bin2hex(random_bytes(12)),
                'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
            ],
        ],
    ];

    $calendarId = trim((string) getenv('GOOGLE_CALENDAR_ID'));
    if ($calendarId === '') {
        $calendarId = 'primary';
    }

    $url = 'https://www.googleapis.com/calendar/v3/calendars/'
        . rawurlencode($calendarId)
        . '/events?conferenceDataVersion=1&sendUpdates=all';

    $response = meeting_google_json_request('POST', $url, $event, $accessToken);
    if (!is_array($response)) {
        return null;
    }

    $meetLink = meeting_google_extract_meet_link($response);
    if ($meetLink !== null) {
        return $meetLink;
    }

    $eventId = isset($response['id']) && is_string($response['id']) ? $response['id'] : '';
    if ($eventId === '') {
        meeting_google_meet_last_error('Calendar event created but no Google Meet link was returned.');
        return null;
    }

    for ($i = 0; $i < 3; $i++) {
        sleep(1);
        $pollUrl = 'https://www.googleapis.com/calendar/v3/calendars/'
            . rawurlencode($calendarId)
            . '/events/'
            . rawurlencode($eventId)
            . '?conferenceDataVersion=1';
        $pollResponse = meeting_google_json_request('GET', $pollUrl, null, $accessToken);
        if (is_array($pollResponse)) {
            $meetLink = meeting_google_extract_meet_link($pollResponse);
            if ($meetLink !== null) {
                return $meetLink;
            }
        }
    }

    meeting_google_meet_last_error('Calendar event created but Google Meet link is still pending.');
    return null;
}

function meeting_google_calendar_access_token(): ?string
{
    $clientId = trim((string) getenv('GOOGLE_CALENDAR_CLIENT_ID'));
    $clientSecret = trim((string) getenv('GOOGLE_CALENDAR_CLIENT_SECRET'));
    $refreshToken = trim((string) getenv('GOOGLE_CALENDAR_REFRESH_TOKEN'));

    $response = meeting_google_form_request('https://oauth2.googleapis.com/token', [
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'refresh_token' => $refreshToken,
        'grant_type' => 'refresh_token',
    ]);

    if (!is_array($response) || empty($response['access_token']) || !is_string($response['access_token'])) {
        meeting_google_meet_last_error('Could not get Google Calendar access token.');
        return null;
    }

    return $response['access_token'];
}

function meeting_google_extract_meet_link(array $event): ?string
{
    if (!empty($event['hangoutLink']) && is_string($event['hangoutLink'])) {
        return $event['hangoutLink'];
    }

    $entryPoints = $event['conferenceData']['entryPoints'] ?? [];
    if (!is_array($entryPoints)) {
        return null;
    }

    foreach ($entryPoints as $entryPoint) {
        if (
            is_array($entryPoint)
            && ($entryPoint['entryPointType'] ?? '') === 'video'
            && !empty($entryPoint['uri'])
            && is_string($entryPoint['uri'])
        ) {
            return $entryPoint['uri'];
        }
    }

    return null;
}

function meeting_google_form_request(string $url, array $fields): ?array
{
    return meeting_google_http_request('POST', $url, http_build_query($fields), [
        'Content-Type: application/x-www-form-urlencoded',
    ]);
}

function meeting_google_json_request(string $method, string $url, ?array $payload, string $accessToken): ?array
{
    $headers = [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json',
    ];
    $body = $payload === null ? null : json_encode($payload);

    return meeting_google_http_request($method, $url, $body, $headers);
}

function meeting_google_http_request(string $method, string $url, ?string $body, array $headers): ?array
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => $headers,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        return meeting_google_decode_response($raw, $status, $error);
    }

    $context = stream_context_create([
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'content' => $body ?? '',
            'timeout' => 20,
            'ignore_errors' => true,
        ],
    ]);
    $raw = @file_get_contents($url, false, $context);
    $status = 0;
    foreach (($http_response_header ?? []) as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $matches)) {
            $status = (int) $matches[1];
            break;
        }
    }

    return meeting_google_decode_response($raw, $status, '');
}

function meeting_google_decode_response($raw, int $status, string $transportError): ?array
{
    if ($raw === false || !is_string($raw)) {
        meeting_google_meet_last_error($transportError !== '' ? $transportError : 'Google API request failed.');
        return null;
    }

    $data = json_decode($raw, true);
    if ($status < 200 || $status >= 300) {
        $message = is_array($data)
            ? (string) ($data['error_description'] ?? $data['error']['message'] ?? $data['error'] ?? 'Google API request failed.')
            : 'Google API request failed.';
        meeting_google_meet_last_error($message);
        return null;
    }

    if (!is_array($data)) {
        meeting_google_meet_last_error('Google API returned invalid JSON.');
        return null;
    }

    return $data;
}
