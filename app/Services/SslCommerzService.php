<?php

namespace App\Services;

use App\Models\Donation;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SslCommerzService
{
    private function baseUrl(): string
    {
        return config('payments.sslcommerz.sandbox')
            ? 'https://sandbox.sslcommerz.com'
            : 'https://securepay.sslcommerz.com';
    }

    public function initiate(Donation $donation): array
    {
        $storeId = config('payments.sslcommerz.store_id');
        $storePassword = config('payments.sslcommerz.store_password');

        if (blank($storeId) || blank($storePassword)) {
            throw new RuntimeException('SSLCOMMERZ credentials are not configured.');
        }

        $apiUrl = $this->baseUrl() . '/gwprocess/v4/api.php';
        $backendUrl = rtrim((string) config('app.url'), '/');

        $response = Http::asForm()
            ->timeout(config('payments.sslcommerz.timeout', 15))
            ->post($apiUrl, [
                'store_id' => $storeId,
                'store_passwd' => $storePassword,
                'total_amount' => number_format((float) $donation->amount, 2, '.', ''),
                'currency' => $donation->currency,
                'tran_id' => $donation->tran_id,
                'product_category' => 'donation',
                'product_name' => $donation->category->name,
                'product_profile' => 'non-physical-goods',
                'cus_name' => $donation->donor_name,
                'cus_email' => $donation->donor_email ?: 'donor@example.com',
                'cus_phone' => $donation->donor_phone ?: '0000000000',
                'cus_add1' => 'Bangladesh',
                'shipping_method' => 'NO',
                'success_url' => $backendUrl . '/api/donations/payment/success',
                'fail_url' => $backendUrl . '/api/donations/payment/fail',
                'cancel_url' => $backendUrl . '/api/donations/payment/cancel',
                'ipn_url' => $backendUrl . '/api/donations/payment/ipn',
                'value_a' => (string) $donation->id,
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('Payment gateway could not be reached.');
        }

        $payload = $response->json();

        if (!is_array($payload) || blank($payload['GatewayPageURL'] ?? null)) {
            throw new RuntimeException('Payment gateway did not return a checkout URL.');
        }

        return $payload;
    }

    public function validate(string $valId): array
    {
        $response = Http::timeout(config('payments.sslcommerz.timeout', 15))
            ->get($this->baseUrl() . '/validator/api/validationserverAPI.php', [
                'val_id' => $valId,
                'store_id' => config('payments.sslcommerz.store_id'),
                'store_passwd' => config('payments.sslcommerz.store_password'),
                'v' => 1,
                'format' => 'json',
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('Payment validation request failed.');
        }

        $payload = $response->json();

        if (!is_array($payload)) {
            throw new RuntimeException('Invalid payment validation response.');
        }

        return $payload;
    }
}