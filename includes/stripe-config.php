<?php

require_once __DIR__ . '/db.php';

function payment_ensure_tables(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS payment_methods (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      method_name VARCHAR(120) NOT NULL,
      method_type VARCHAR(50) NOT NULL,
      stripe_publishable_key VARCHAR(255) NULL,
      stripe_secret_key VARCHAR(255) NULL,
      mode ENUM('test','live') NOT NULL DEFAULT 'test',
      status ENUM('active','inactive') NOT NULL DEFAULT 'inactive',
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX idx_payment_methods_status (status),
      INDEX idx_payment_methods_type (method_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS payment_logs (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      order_id BIGINT UNSIGNED NULL,
      payment_method VARCHAR(50) NOT NULL,
      event_type VARCHAR(80) NOT NULL,
      provider_event_id VARCHAR(255) NULL,
      transaction_id VARCHAR(255) NULL,
      currency VARCHAR(10) NULL,
      amount DECIMAL(12,2) NULL,
      request_payload LONGTEXT NULL,
      response_payload LONGTEXT NULL,
      status VARCHAR(50) NOT NULL DEFAULT 'info',
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_payment_logs_order (order_id),
      INDEX idx_payment_logs_transaction (transaction_id),
      INDEX idx_payment_logs_provider_event (provider_event_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS payment_customers (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      user_id BIGINT UNSIGNED NOT NULL,
      payment_method VARCHAR(50) NOT NULL,
      provider_customer_id VARCHAR(255) NOT NULL,
      email VARCHAR(255) NULL,
      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      UNIQUE KEY uq_payment_customers_user_method (user_id, payment_method),
      UNIQUE KEY uq_payment_customers_provider_customer_id (provider_customer_id),
      INDEX idx_payment_customers_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS stripe_webhook_events (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      event_id VARCHAR(255) NOT NULL,
      event_type VARCHAR(120) NOT NULL,
      processed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY uq_stripe_webhook_event_id (event_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function payment_get_supported_currencies(): array
{
    return ['usd', 'eur', 'gbp', 'inr', 'cad', 'aud'];
}

function payment_currency_is_supported(string $currency): bool
{
    return in_array(strtolower(trim($currency)), payment_get_supported_currencies(), true);
}

function payment_currency_normalize(string $currency): string
{
    $currency = strtolower(trim($currency));
    return payment_currency_is_supported($currency) ? $currency : 'usd';
}

function payment_is_zero_decimal_currency(string $currency): bool
{
    $currency = strtolower(trim($currency));
    $zeroDecimal = ['bif','clp','djf','gnf','jpy','kmf','krw','mga','pyg','rwf','ugx','vnd','vuv','xaf','xof','xpf'];
    return in_array($currency, $zeroDecimal, true);
}

function payment_amount_to_minor_units(float $amount, string $currency = 'usd'): int
{
    $currency = payment_currency_normalize($currency);
    $amount = max(0, $amount);
    if (payment_is_zero_decimal_currency($currency)) {
        return (int) round($amount);
    }
    return (int) round($amount * 100);
}

function payment_minor_units_to_amount(int $minorUnits, string $currency = 'usd'): float
{
    $currency = payment_currency_normalize($currency);
    if (payment_is_zero_decimal_currency($currency)) {
        return (float) $minorUnits;
    }
    return ((float) $minorUnits) / 100;
}

function payment_get_active_methods(): array
{
    $pdo = db();
    if (!$pdo) {
        return [];
    }

    payment_ensure_tables($pdo);

    $stmt = $pdo->query("SELECT id, method_name, method_type, stripe_publishable_key, mode, status FROM payment_methods WHERE status = 'active' ORDER BY id ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function payment_get_method_by_type(string $methodType): ?array
{
    $pdo = db();
    if (!$pdo) {
        return null;
    }

    payment_ensure_tables($pdo);

    $stmt = $pdo->prepare("SELECT * FROM payment_methods WHERE method_type = :method_type AND status = 'active' LIMIT 1");
    $stmt->execute([':method_type' => strtolower(trim($methodType))]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function stripe_get_config(): ?array
{
    $method = payment_get_method_by_type('stripe');
    if (!$method) {
        return null;
    }

    $publishable = trim((string) ($method['stripe_publishable_key'] ?? ''));
    $secret = trim((string) ($method['stripe_secret_key'] ?? ''));
    $mode = (string) ($method['mode'] ?? 'test');

    if ($publishable === '' || $secret === '') {
        return null;
    }

    return [
        'id' => (int) ($method['id'] ?? 0),
        'publishable_key' => $publishable,
        'secret_key' => $secret,
        'mode' => $mode === 'live' ? 'live' : 'test',
        'method_name' => (string) ($method['method_name'] ?? 'Stripe'),
        'method_type' => 'stripe',
    ];
}

function payment_log_event(string $paymentMethod, string $eventType, ?string $transactionId, $requestPayload, $responsePayload, string $status = 'info', ?int $orderId = null, ?string $providerEventId = null, ?string $currency = null, ?float $amount = null): void
{
    $pdo = db();
    if (!$pdo) {
        return;
    }

    payment_ensure_tables($pdo);

    $stmt = $pdo->prepare('INSERT INTO payment_logs (order_id, payment_method, event_type, provider_event_id, transaction_id, currency, amount, request_payload, response_payload, status) VALUES (:order_id, :payment_method, :event_type, :provider_event_id, :transaction_id, :currency, :amount, :request_payload, :response_payload, :status)');
    $stmt->execute([
        ':order_id' => $orderId,
        ':payment_method' => $paymentMethod,
        ':event_type' => $eventType,
        ':provider_event_id' => $providerEventId,
        ':transaction_id' => $transactionId,
        ':currency' => $currency,
        ':amount' => $amount,
        ':request_payload' => is_string($requestPayload) ? $requestPayload : json_encode($requestPayload, JSON_UNESCAPED_UNICODE),
        ':response_payload' => is_string($responsePayload) ? $responsePayload : json_encode($responsePayload, JSON_UNESCAPED_UNICODE),
        ':status' => $status,
    ]);
}

function stripe_require_sdk(): bool
{
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    if (!is_file($autoloadPath)) {
        return false;
    }

    require_once $autoloadPath;
    return class_exists('\Stripe\StripeClient');
}

function stripe_get_or_create_customer(int $userId, string $email = '', string $name = '', array $metadata = []): ?string
{
    if ($userId <= 0) {
        return null;
    }

    $pdo = db();
    if (!$pdo) {
        return null;
    }

    payment_ensure_tables($pdo);

    $stmt = $pdo->prepare('SELECT provider_customer_id FROM payment_customers WHERE user_id = :user_id AND payment_method = :payment_method LIMIT 1');
    $stmt->execute([':user_id' => $userId, ':payment_method' => 'stripe']);
    $existing = (string) ($stmt->fetchColumn() ?: '');
    if ($existing !== '') {
        return $existing;
    }

    $config = stripe_get_config();
    if (!$config || !stripe_require_sdk()) {
        return null;
    }

    $client = new \Stripe\StripeClient($config['secret_key']);
    $customer = $client->customers->create([
        'email' => $email !== '' ? $email : null,
        'name' => $name !== '' ? $name : null,
        'metadata' => $metadata,
    ]);

    $providerCustomerId = (string) ($customer->id ?? '');
    if ($providerCustomerId === '') {
        return null;
    }

    $ins = $pdo->prepare('INSERT INTO payment_customers (user_id, payment_method, provider_customer_id, email) VALUES (:user_id, :payment_method, :provider_customer_id, :email) ON DUPLICATE KEY UPDATE provider_customer_id = VALUES(provider_customer_id), email = VALUES(email), updated_at = CURRENT_TIMESTAMP');
    $ins->execute([
        ':user_id' => $userId,
        ':payment_method' => 'stripe',
        ':provider_customer_id' => $providerCustomerId,
        ':email' => $email !== '' ? $email : null,
    ]);

    payment_log_event('stripe', 'customer_created', null, ['user_id' => $userId, 'email' => $email], ['customer_id' => $providerCustomerId], 'success');

    return $providerCustomerId;
}

function stripe_mark_webhook_event_processed(string $eventId, string $eventType): bool
{
    $eventId = trim($eventId);
    if ($eventId === '') {
        return false;
    }

    $pdo = db();
    if (!$pdo) {
        return false;
    }

    payment_ensure_tables($pdo);

    try {
        $stmt = $pdo->prepare('INSERT INTO stripe_webhook_events (event_id, event_type) VALUES (:event_id, :event_type)');
        $stmt->execute([':event_id' => $eventId, ':event_type' => $eventType]);
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function createPaymentIntent(float $amount, string $currency = 'usd', array $metadata = [], ?string $stripeCustomerId = null): array
{
    $config = stripe_get_config();
    if (!$config) {
        throw new RuntimeException('Stripe payment method is not configured or active.');
    }

    if (!stripe_require_sdk()) {
        throw new RuntimeException('Stripe SDK not found. Run composer require stripe/stripe-php');
    }

    $currency = payment_currency_normalize($currency);
    $amountMinor = payment_amount_to_minor_units($amount, $currency);
    if ($amountMinor <= 0) {
        throw new InvalidArgumentException('Amount must be greater than zero.');
    }

    $payload = [
        'amount' => $amountMinor,
        'currency' => $currency,
        'automatic_payment_methods' => ['enabled' => true],
        'metadata' => $metadata,
    ];

    if ($stripeCustomerId) {
        $payload['customer'] = $stripeCustomerId;
        $payload['setup_future_usage'] = 'off_session';
    }

    $client = new \Stripe\StripeClient($config['secret_key']);
    $intent = $client->paymentIntents->create($payload);

    payment_log_event('stripe', 'payment_intent_created', (string) $intent->id, $payload, ['status' => $intent->status], 'success', null, null, $currency, payment_minor_units_to_amount((int) $intent->amount, $currency));

    return [
        'id' => (string) $intent->id,
        'client_secret' => (string) $intent->client_secret,
        'amount' => (int) $intent->amount,
        'currency' => (string) $intent->currency,
        'status' => (string) $intent->status,
        'customer' => (string) ($intent->customer ?? ''),
    ];
}

function confirmPayment(string $paymentIntentId): array
{
    $config = stripe_get_config();
    if (!$config) {
        throw new RuntimeException('Stripe payment method is not configured or active.');
    }

    if (!stripe_require_sdk()) {
        throw new RuntimeException('Stripe SDK not found. Run composer require stripe/stripe-php');
    }

    $paymentIntentId = trim($paymentIntentId);
    if ($paymentIntentId === '') {
        throw new InvalidArgumentException('Payment Intent ID is required.');
    }

    $client = new \Stripe\StripeClient($config['secret_key']);
    $intent = $client->paymentIntents->retrieve($paymentIntentId, []);
    $currency = (string) ($intent->currency ?? 'usd');

    payment_log_event('stripe', 'payment_intent_retrieved', (string) $intent->id, ['payment_intent_id' => $paymentIntentId], ['status' => $intent->status, 'amount_received' => $intent->amount_received], $intent->status === 'succeeded' ? 'success' : 'info', null, null, $currency, payment_minor_units_to_amount((int) ($intent->amount_received ?? 0), $currency));

    return [
        'id' => (string) $intent->id,
        'status' => (string) $intent->status,
        'amount' => (int) $intent->amount,
        'amount_received' => (int) $intent->amount_received,
        'currency' => (string) $intent->currency,
        'customer' => (string) ($intent->customer ?? ''),
    ];
}

function stripe_refund_payment(string $paymentIntentId, ?int $amountMinor = null, array $metadata = []): array
{
    $config = stripe_get_config();
    if (!$config) {
        throw new RuntimeException('Stripe payment method is not configured or active.');
    }

    if (!stripe_require_sdk()) {
        throw new RuntimeException('Stripe SDK not found. Run composer require stripe/stripe-php');
    }

    $paymentIntentId = trim($paymentIntentId);
    if ($paymentIntentId === '') {
        throw new InvalidArgumentException('Payment Intent ID is required for refund.');
    }

    $client = new \Stripe\StripeClient($config['secret_key']);
    $payload = ['payment_intent' => $paymentIntentId, 'metadata' => $metadata];
    if ($amountMinor !== null && $amountMinor > 0) {
        $payload['amount'] = $amountMinor;
    }

    $refund = $client->refunds->create($payload);
    $currency = (string) ($refund->currency ?? 'usd');
    payment_log_event('stripe', 'refund_created', $paymentIntentId, $payload, ['refund_id' => $refund->id, 'status' => $refund->status], $refund->status === 'succeeded' ? 'success' : 'info', null, null, $currency, payment_minor_units_to_amount((int) ($refund->amount ?? 0), $currency));

    return [
        'id' => (string) $refund->id,
        'status' => (string) $refund->status,
        'amount' => (int) $refund->amount,
        'currency' => (string) $refund->currency,
        'payment_intent' => (string) $refund->payment_intent,
    ];
}

