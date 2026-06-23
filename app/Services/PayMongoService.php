<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PayMongoService
{
    protected string $secretKey;

    protected string $publicKey;

    public function __construct()
    {
        $this->secretKey = config('paymongo.secret_key');
        $this->publicKey = config('paymongo.public_key');
    }

    protected function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withBasicAuth($this->secretKey, '')
            ->withHeaders(['Accept' => 'application/json'])
            ->baseUrl('https://api.paymongo.com/v1');
    }

    public function createCheckoutSession(array $params): array
    {
        $payload = [
            'data' => [
                'attributes' => [
                    'billing' => [
                        'name' => $params['billing_name'],
                        'email' => $params['billing_email'],
                        'phone' => $params['billing_phone'] ?? null,
                    ],
                    'line_items' => [
                        [
                            'name' => $params['description'],
                            'amount' => (int) round((float) $params['amount'] * 100),
                            'currency' => 'PHP',
                            'quantity' => 1,
                        ],
                    ],
                    'payment_method_types' => ['gcash', 'maya', 'card', 'grab_pay'],
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

    public function isAvailable(): bool
    {
        return ! empty($this->secretKey) && ! empty($this->publicKey);
    }
}
