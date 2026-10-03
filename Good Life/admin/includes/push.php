<?php

// On Windows, set OPENSSL_CONF to PHP's openssl.cnf before generating keys; then set the three VAPID
// environment variables. Keep the private key secret. Web Push requires HTTPS except on localhost.
require_once __DIR__ . '/bootstrap.php';
require_once ADMIN_ROOT . 'vendor/autoload.php';

use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\VAPID;
use Minishlink\WebPush\WebPush;

function admin_push_vapid_config()
{
    $publicKey = getenv('VAPID_PUBLIC_KEY');
    $privateKey = getenv('VAPID_PRIVATE_KEY');
    $subject = getenv('VAPID_SUBJECT');
    if (!is_string($publicKey) || trim($publicKey) === '' ||
        !is_string($privateKey) || trim($privateKey) === '' ||
        !is_string($subject) || trim($subject) === '') {
        throw new RuntimeException('VAPID_PUBLIC_KEY, VAPID_PRIVATE_KEY, and VAPID_SUBJECT must be configured.');
    }

    $config = [
        'subject' => trim($subject),
        'publicKey' => trim($publicKey),
        'privateKey' => trim($privateKey),
    ];
    VAPID::validate($config);
    return $config;
}

function admin_push_validate_endpoint($endpoint)
{
    if (!is_string($endpoint) || strlen($endpoint) > 2048) {
        return false;
    }
    $parts = parse_url($endpoint);
    return is_array($parts) && strtolower($parts['scheme'] ?? '') === 'https' &&
        !empty($parts['host']) && !isset($parts['user']) && !isset($parts['pass']);
}

function admin_push_send_new_order($order)
{
    $subscriptionsPath = ADMIN_ROOT . 'data/push_subscriptions.json';
    if (!is_file($subscriptionsPath)) {
        return;
    }

    $subscriptions = admin_read_json('data/push_subscriptions.json');
    if (!$subscriptions) {
        return;
    }

    $vapid = admin_push_vapid_config();
    $webPush = new WebPush(
        ['VAPID' => $vapid],
        ['TTL' => 60, 'urgency' => 'high'],
        8
    );
    $payload = json_encode([
        'title' => 'Pesanan Baru Diterima!!',
        'body' => 'ID Pesanan: ' . (string)($order['id'] ?? ''),
        'tag' => 'new-order-' . (string)($order['id'] ?? ''),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($payload === false) {
        throw new RuntimeException('Payload notifikasi pesanan gagal dibuat.');
    }

    $expiredEndpoints = [];
    foreach ($subscriptions as $record) {
        if (!is_array($record) || !admin_push_validate_endpoint($record['endpoint'] ?? null) ||
            !is_array($record['keys'] ?? null)) {
            error_log('Web Push skipped an invalid stored subscription.');
            continue;
        }
        $webPush->queueNotification(
            Subscription::create([
                'endpoint' => $record['endpoint'],
                'keys' => $record['keys'],
                'contentEncoding' => 'aes128gcm',
            ]),
            $payload
        );
    }

    foreach ($webPush->flush() as $report) {
        if ($report->isSubscriptionExpired()) {
            $expiredEndpoints[] = $report->getEndpoint();
        } elseif (!$report->isSuccess()) {
            error_log('Web Push delivery failed: ' . $report->getReason());
        }
    }

    if ($expiredEndpoints) {
        admin_mutate_json('data/push_subscriptions.json', function (&$records) use ($expiredEndpoints) {
            $records = array_values(array_filter($records, function ($record) use ($expiredEndpoints) {
                if (!is_array($record) || !is_string($record['endpoint'] ?? null)) {
                    return true;
                }
                foreach ($expiredEndpoints as $endpoint) {
                    if (hash_equals($record['endpoint'], $endpoint)) {
                        return false;
                    }
                }
                return true;
            }));
        });
    }
}
