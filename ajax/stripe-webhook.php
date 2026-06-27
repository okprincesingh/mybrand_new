<?php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/stripe-config.php';

$payload = file_get_contents('php://input');
$signature = (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '');

$config = stripe_get_config();
if (!$config) {
    http_response_code(400);
    echo 'Stripe config missing';
    exit;
}

$endpointSecret = trim((string) getenv('STRIPE_WEBHOOK_SECRET'));
if ($endpointSecret === '') {
    http_response_code(400);
    echo 'Webhook secret missing';
    exit;
}

if (!stripe_require_sdk()) {
    http_response_code(500);
    echo 'Stripe SDK missing';
    exit;
}

try {
    $event = \Stripe\Webhook::constructEvent($payload, $signature, $endpointSecret);
} catch (Throwable $e) {
    payment_log_event('stripe', 'webhook_invalid', null, $payload, ['error' => $e->getMessage()], 'failed');
    http_response_code(400);
    echo 'Invalid payload';
    exit;
}

$eventId = (string) ($event->id ?? '');
$eventType = (string) ($event->type ?? 'unknown');

if ($eventId !== '' && !stripe_mark_webhook_event_processed($eventId, $eventType)) {
    payment_log_event('stripe', 'webhook_duplicate', null, ['event_id' => $eventId], ['event_type' => $eventType], 'info', null, $eventId);
    http_response_code(200);
    echo 'ok';
    exit;
}

$pdo = db();
$intentId = null;
$currency = null;
$amount = null;

if ($eventType === 'payment_intent.succeeded' || $eventType === 'payment_intent.processing' || $eventType === 'payment_intent.payment_failed') {
    $intent = $event->data->object;
    $intentId = (string) ($intent->id ?? '');
    $currency = strtolower((string) ($intent->currency ?? 'usd'));
    $amount = payment_minor_units_to_amount((int) ($intent->amount_received ?? $intent->amount ?? 0), $currency);

    if ($pdo && $intentId !== '') {
        $paymentStatus = 'pending';
        if ($eventType === 'payment_intent.succeeded') $paymentStatus = 'paid';
        if ($eventType === 'payment_intent.payment_failed') $paymentStatus = 'failed';

        $sql = 'UPDATE orders
                SET payment_status = :payment_status,
                    transaction_id = :transaction_id,
                    stripe_customer_id = COALESCE(:stripe_customer_id, stripe_customer_id),
                    status = CASE
                        WHEN :payment_status = "paid" AND status = "pending" THEN "processing"
                        WHEN :payment_status = "failed" THEN "pending"
                        ELSE status
                    END
                WHERE transaction_id = :transaction_id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':payment_status' => $paymentStatus,
            ':transaction_id' => $intentId,
            ':stripe_customer_id' => (string) ($intent->customer ?? ''),
        ]);
    }
}

if ($eventType === 'charge.refunded') {
    $charge = $event->data->object;
    $intentId = (string) ($charge->payment_intent ?? '');
    $currency = strtolower((string) ($charge->currency ?? 'usd'));
    $amount = payment_minor_units_to_amount((int) ($charge->amount_refunded ?? 0), $currency);

    if ($pdo && $intentId !== '') {
        $stmt = $pdo->prepare('UPDATE orders SET payment_status = :payment_status, status = CASE WHEN status IN ("pending","processing") THEN "refunded" ELSE status END WHERE transaction_id = :transaction_id');
        $stmt->execute([
            ':payment_status' => 'refunded',
            ':transaction_id' => $intentId,
        ]);
    }
}

payment_log_event('stripe', 'webhook_' . $eventType, $intentId, $payload, ['event_id' => $eventId], 'success', null, $eventId, $currency, $amount);
http_response_code(200);
echo 'ok';
