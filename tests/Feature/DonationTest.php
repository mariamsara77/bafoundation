<?php

namespace Tests\Feature;

use App\Models\DonationCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_start_a_donation(): void
    {
        $category = DonationCategory::create([
            'name' => 'শিক্ষা সহায়তা',
            'slug' => 'education-support',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        config([
            'payments.sslcommerz.store_id' => 'test-store',
            'payments.sslcommerz.store_password' => 'test-password',
            'payments.sslcommerz.sandbox' => true,
        ]);

        Http::fake([
            'https://sandbox.sslcommerz.com/*' => Http::response([
                'status' => 'SUCCESS',
                'sessionkey' => 'session-test',
                'GatewayPageURL' => 'https://sandbox.sslcommerz.com/EasyCheckOut/test',
            ]),
        ]);

        $response = $this->postJson('/api/donations', [
            'donation_category_id' => $category->id,
            'amount' => 1000,
            'donor_name' => 'Test Donor',
            'donor_phone' => '01700000000',
            'donor_email' => 'donor@example.com',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('donation.status', 'processing')
            ->assertJsonPath('donation.amount', '1000.00');

        $this->assertDatabaseHas('donations', [
            'tran_id' => $response->json('donation.tran_id'),
            'status' => 'processing',
            'donation_category_id' => $category->id,
        ]);
    }

    public function test_ipn_validates_amount_and_marks_donation_completed(): void
    {
        $category = DonationCategory::create([
            'name' => 'সাধারণ ফান্ড',
            'slug' => 'general-fund',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $donation = $category->donations()->create([
            'tran_id' => 'BAF-TEST-001',
            'amount' => 1500,
            'currency' => 'BDT',
            'status' => 'processing',
            'donor_name' => 'Test Donor',
            'donor_phone' => '01700000000',
        ]);

        config([
            'payments.sslcommerz.store_id' => 'test-store',
            'payments.sslcommerz.store_password' => 'test-password',
            'payments.sslcommerz.sandbox' => true,
        ]);

        Http::fake([
            'https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php*' => Http::response([
                'status' => 'VALID',
                'tran_id' => 'BAF-TEST-001',
                'val_id' => 'VAL-001',
                'amount' => '1500.00',
                'bank_tran_id' => 'BANK-001',
                'card_type' => 'MOBILEBANKING',
                'card_brand' => 'bKash',
                'store_amount' => '1462.50',
            ]),
        ]);

        $response = $this->postJson('/api/donations/payment/ipn', [
            'tran_id' => $donation->tran_id,
            'val_id' => 'VAL-001',
        ]);

        $response->assertOk()->assertJsonPath('status', 'completed');

        $this->assertDatabaseHas('donations', [
            'id' => $donation->id,
            'status' => 'completed',
            'validation_id' => 'VAL-001',
            'bank_tran_id' => 'BANK-001',
        ]);
    }
}
