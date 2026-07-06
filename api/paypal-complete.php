<?php
declare(strict_types=1);

header('Content-Type: application/json');

$notificationEmail = 'paypal@inzra.com';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'message' => 'Method not allowed.'
    ]);
    exit;
}

$rawBody = file_get_contents('php://input');
$payload = json_decode($rawBody ?: '', true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'message' => 'Invalid JSON payload.'
    ]);
    exit;
}

$orderId = trim((string) ($payload['orderId'] ?? ''));
$captureId = trim((string) ($payload['captureId'] ?? ''));
$captureStatus = trim((string) ($payload['captureStatus'] ?? ''));
$payer = is_array($payload['payer'] ?? null) ? $payload['payer'] : [];
$product = is_array($payload['product'] ?? null) ? $payload['product'] : [];

$itemNumber = trim((string) ($product['itemNumber'] ?? ''));
$title = trim((string) ($product['title'] ?? ''));
$price = trim((string) ($product['price'] ?? ''));
$currency = trim((string) ($product['currency'] ?? 'USD'));

if ($orderId === '' || $itemNumber === '' || $title === '' || $price === '') {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'message' => 'Missing required purchase data.'
    ]);
    exit;
}

$storageRoot = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage';
$ordersRoot = $storageRoot . DIRECTORY_SEPARATOR . 'orders';

if (!is_dir($ordersRoot) && !mkdir($ordersRoot, 0775, true) && !is_dir($ordersRoot)) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Could not create order storage.'
    ]);
    exit;
}

$safeOrderId = preg_replace('/[^A-Za-z0-9_-]/', '_', $orderId) ?: 'order';
$orderFile = $ordersRoot . DIRECTORY_SEPARATOR . $safeOrderId . '.json';

$record = [
    'orderId' => $orderId,
    'captureId' => $captureId,
    'captureStatus' => $captureStatus,
    'recordedAtUtc' => gmdate('c'),
    'payer' => [
        'name' => trim((string) ($payer['name'] ?? 'Customer')),
        'email' => trim((string) ($payer['email'] ?? '')),
    ],
    'product' => [
        'itemNumber' => $itemNumber,
        'title' => $title,
        'price' => $price,
        'currency' => $currency,
    ],
];

$encoded = json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if ($encoded === false || file_put_contents($orderFile, $encoded . PHP_EOL, LOCK_EX) === false) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Could not save the purchase record.'
    ]);
    exit;
}

$payerName = trim((string) ($payer['name'] ?? 'Customer'));
$payerEmail = trim((string) ($payer['email'] ?? ''));
$mailHeaders = [
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'From: Inzra Checkout <paypal@inzra.com>',
];

if ($payerEmail !== '' && filter_var($payerEmail, FILTER_VALIDATE_EMAIL)) {
    $mailHeaders[] = 'Reply-To: ' . $payerEmail;
}

$mailSubject = sprintf('New Inzra order %s for item %s', $orderId, $itemNumber);
$mailBody = implode(PHP_EOL, [
    'A new purchase has been recorded on inzra.com.',
    '',
    'Order details',
    '-------------',
    'Order ID: ' . $orderId,
    'Capture ID: ' . ($captureId !== '' ? $captureId : 'Not provided'),
    'Capture status: ' . ($captureStatus !== '' ? $captureStatus : 'Not provided'),
    'Recorded at UTC: ' . $record['recordedAtUtc'],
    '',
    'Payer details',
    '-------------',
    'Name: ' . $payerName,
    'Email: ' . ($payerEmail !== '' ? $payerEmail : 'Not provided'),
    '',
    'Product details',
    '---------------',
    'Item number: ' . $itemNumber,
    'Title: ' . $title,
    'Price: ' . $price . ' ' . $currency,
]);

$mailSent = @mail($notificationEmail, $mailSubject, $mailBody, implode("\r\n", $mailHeaders));

$record['notifications'] = [
    'email' => [
        'to' => $notificationEmail,
        'attemptedAtUtc' => gmdate('c'),
        'sent' => $mailSent,
    ],
];

$encoded = json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if ($encoded !== false) {
    @file_put_contents($orderFile, $encoded . PHP_EOL, LOCK_EX);
}

echo json_encode([
    'ok' => true,
    'orderId' => $orderId,
    'captureId' => $captureId,
    'mailSent' => $mailSent,
]);