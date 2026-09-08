<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PayMongoService
{
    protected ?string $secretKey;

    protected ?string $publicKey;

    protected ?string $webhookSecret;

    public function __construct()
    {
        $this->secretKey = config('paymongo.secret_key');
        $this->publicKey = config('paymongo.public_key');
        $this->webhookSecret = config('paymongo.webhook_secret');
    }

    protected function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withBasicAuth($this->secretKey, '')
            ->withHeaders(['Accept' => 'application/json'])
            ->baseUrl('https://api.paymongo.com/v1');
    }

    public function createCheckoutSession(array $params): array
    {
        $billing = array_filter([
            'name' => $params['billing_name'] ?? null,
            'email' => $params['billing_email'] ?? null,
            'phone' => $params['billing_phone'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        $payload = [
            'data' => [
                'attributes' => [
                    'billing' => $billing,
                    'line_items' => [
                        [
                            'name' => $params['description'],
                            'amount' => (int) round((float) $params['amount'] * 100),
                            'currency' => 'PHP',
                            'quantity' => 1,
                        ],
                    ],
                    'payment_method_types' => $params['payment_method_types'] ?? ['gcash', 'card', 'qrph'],
                    'success_url' => $params['success_url'],
                    'cancel_url' => $params['cancel_url'],
                    'description' => $params['description'],
                    'metadata' => [
                        'payment_id' => $params['payment_id'],
                        'citation_number' => $params['citation_number'] ?? '',
                        'receipt_number' => $params['receipt_number'] ?? '',
                    ],
                ],
            ],
        ];

        $response = $this->client()->post('/checkout_sessions', $payload);

        if ($response->failed()) {
            throw new \RuntimeException('PayMongo checkout creation failed: '.$response->body());
        }

        $data = $response->json('data');

        return [
            'id' => $data['id'],
            'checkout_url' => $data['attributes']['checkout_url'],
            'status' => $data['attributes']['status'],
        ];
    }

    public function retrieveCheckoutSession(string $id): array
    {
        $response = $this->client()->get("/checkout_sessions/{$id}");

        if ($response->failed()) {
            throw new \RuntimeException('PayMongo checkout retrieval failed: '.$response->body());
        }

        return $response->json('data');
    }

    public function retrievePayment(string $id): array
    {
        $response = $this->client()->get("/payments/{$id}");

        if ($response->failed()) {
            throw new \RuntimeException('PayMongo payment retrieval failed: '.$response->body());
        }

        return $response->json('data');
    }

    public function isAvailable(): bool
    {
        return ! empty($this->secretKey) && ! empty($this->publicKey);
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        if (! $this->webhookSecret) {
            return true;
        }

        $parts = [];

        foreach (explode(',', $signature) as $kv) {
            [$key, $value] = array_pad(explode('=', $kv, 2), 2, '');
            $parts[$key] = $value;
        }

        $timestamp = $parts['t'] ?? '';

        // PayMongo sends two signatures per event: `te` for test-mode events
        // and `li` for live-mode events. Only one is populated.
        $received = '';

        if (! empty($parts['te'])) {
            $received = $parts['te'];
        } elseif (! empty($parts['li'])) {
            $received = $parts['li'];
        }

        if ($timestamp === '' || $received === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $this->webhookSecret);

        return hash_equals($expected, $received);
    }
}
