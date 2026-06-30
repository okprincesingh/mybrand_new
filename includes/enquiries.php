<?php
require_once __DIR__ . '/db.php';

function enquiries_ensure_table(?PDO $pdo = null): bool
{
    $pdo = $pdo ?: db();
    if (!$pdo) {
        return false;
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS contact_enquiries (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            enquiry_type VARCHAR(40) NOT NULL DEFAULT 'contact',
            name VARCHAR(190) NOT NULL,
            email VARCHAR(190) NOT NULL,
            phone VARCHAR(80) NULL,
            country VARCHAR(120) NULL,
            subject VARCHAR(255) NULL,
            message TEXT NULL,
            requirements TEXT NULL,
            product_id VARCHAR(190) NULL,
            address TEXT NULL,
            bulk_quantity VARCHAR(80) NULL,
            admin_mail_sent TINYINT(1) NOT NULL DEFAULT 0,
            user_mail_sent TINYINT(1) NOT NULL DEFAULT 0,
            mail_status TEXT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'new',
            ip_address VARCHAR(64) NULL,
            user_agent VARCHAR(255) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_contact_enquiries_type_status (enquiry_type, status),
            INDEX idx_contact_enquiries_created (created_at),
            INDEX idx_contact_enquiries_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    return true;
}

function enquiries_create(array $data): int
{
    $pdo = db();
    if (!$pdo || !enquiries_ensure_table($pdo)) {
        return 0;
    }

    $stmt = $pdo->prepare("
        INSERT INTO contact_enquiries (
            enquiry_type, name, email, phone, country, subject, message, requirements,
            product_id, address, bulk_quantity, admin_mail_sent, user_mail_sent, mail_status,
            status, ip_address, user_agent
        ) VALUES (
            :enquiry_type, :name, :email, :phone, :country, :subject, :message, :requirements,
            :product_id, :address, :bulk_quantity, :admin_mail_sent, :user_mail_sent, :mail_status,
            :status, :ip_address, :user_agent
        )
    ");

    $stmt->execute([
        ':enquiry_type' => (string) ($data['enquiry_type'] ?? 'contact'),
        ':name' => (string) ($data['name'] ?? ''),
        ':email' => (string) ($data['email'] ?? ''),
        ':phone' => enquiries_nullable($data['phone'] ?? null),
        ':country' => enquiries_nullable($data['country'] ?? null),
        ':subject' => enquiries_nullable($data['subject'] ?? null),
        ':message' => enquiries_nullable($data['message'] ?? null),
        ':requirements' => enquiries_nullable($data['requirements'] ?? null),
        ':product_id' => enquiries_nullable($data['product_id'] ?? null),
        ':address' => enquiries_nullable($data['address'] ?? null),
        ':bulk_quantity' => enquiries_nullable($data['bulk_quantity'] ?? null),
        ':admin_mail_sent' => !empty($data['admin_mail_sent']) ? 1 : 0,
        ':user_mail_sent' => !empty($data['user_mail_sent']) ? 1 : 0,
        ':mail_status' => enquiries_nullable($data['mail_status'] ?? null),
        ':status' => (string) ($data['status'] ?? 'new'),
        ':ip_address' => enquiries_nullable($_SERVER['REMOTE_ADDR'] ?? null),
        ':user_agent' => enquiries_nullable(substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255)),
    ]);

    return (int) $pdo->lastInsertId();
}

function enquiries_update_mail_status(int $id, bool $adminSent, bool $userSent, string $mailStatus): void
{
    if ($id <= 0) {
        return;
    }
    $pdo = db();
    if (!$pdo || !enquiries_ensure_table($pdo)) {
        return;
    }

    $stmt = $pdo->prepare('UPDATE contact_enquiries SET admin_mail_sent = :admin_sent, user_mail_sent = :user_sent, mail_status = :mail_status WHERE id = :id');
    $stmt->execute([
        ':admin_sent' => $adminSent ? 1 : 0,
        ':user_sent' => $userSent ? 1 : 0,
        ':mail_status' => $mailStatus,
        ':id' => $id,
    ]);
}

function enquiries_nullable($value): ?string
{
    $value = is_string($value) || is_numeric($value) ? trim((string) $value) : '';
    return $value === '' ? null : $value;
}
