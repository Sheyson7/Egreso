<?php
declare(strict_types=1);

namespace App\Controller;

use Throwable;

final class WebhookController
{
    public function handle(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            return;
        }

        $secret = \config('MERCADOPAGO_WEBHOOK_SECRET');
        $signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
        $requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';
        $dataId = (string) ($_GET['data_id'] ?? '');
        if (!$secret || !$signature || !$requestId || !$dataId) {
            http_response_code(401);
            return;
        }

        $parts = [];
        foreach (explode(',', $signature) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            if ($key !== '') {
                $parts[$key] = $value;
            }
        }
        $manifest = "id:{$dataId};request-id:{$requestId};";
        if (isset($parts['ts'])) {
            $manifest .= 'ts:' . $parts['ts'] . ';';
        }
        $expected = hash_hmac('sha256', $manifest, $secret);
        if (empty($parts['v1']) || !hash_equals($expected, $parts['v1'])) {
            http_response_code(401);
            return;
        }

        try {
            if (($_GET['type'] ?? '') === 'payment' || ($_GET['topic'] ?? '') === 'payment') {
                \verify_and_apply_payment($dataId);
            }
            http_response_code(200);
        } catch (Throwable $error) {
            error_log($error->__toString());
            http_response_code(500);
        }
    }
}
