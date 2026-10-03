<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\DonationCategory;
use App\Services\SslCommerzService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class DonationController extends Controller
{
    public function categories(): JsonResponse
    {
        return response()->json([
            'categories' => DonationCategory::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'description']),
        ]);
    }

    public function store(Request $request, SslCommerzService $gateway): JsonResponse
    {
        $data = $request->validate([
            'donation_category_id' => ['required', 'integer', Rule::exists('donation_categories', 'id')->where('is_active', true)],
            'amount' => ['required', 'numeric', 'min:10', 'max:500000'],
            'donor_name' => ['required', 'string', 'max:120'],
            'donor_email' => ['nullable', 'email', 'max:255'],
            'donor_phone' => ['required', 'string', 'max:30'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = auth('sanctum')->user();

        $donation = DB::transaction(function () use ($data, $user) {
            return Donation::create([
                'user_id' => $user?->id,
                'donation_category_id' => $data['donation_category_id'],
                'tran_id' => 'BAF-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(8)),
                'amount' => $data['amount'],
                'currency' => 'BDT',
                'status' => 'pending',
                'donor_name' => trim($data['donor_name']),
                'donor_email' => isset($data['donor_email']) ? strtolower(trim($data['donor_email'])) : null,
                'donor_phone' => trim($data['donor_phone']),
                'message' => $data['message'] ?? null,
            ]);
        });

        try {
            $payload = $gateway->initiate($donation);
            $donation->update([
                'session_key' => $payload['sessionkey'] ?? null,
                'status' => 'processing',
                'gateway_payload' => $payload,
            ]);

            return response()->json([
                'message' => 'পেমেন্ট পেজ প্রস্তুত হয়েছে।',
                'donation' => $this->summary($donation->fresh('category')),
                'checkout_url' => $payload['GatewayPageURL'],
            ], 201);
        } catch (Throwable $e) {
            report($e);
            $donation->update(['status' => 'failed', 'failed_at' => now()]);
            return response()->json([
                'message' => 'পেমেন্ট শুরু করা যায়নি। কিছুক্ষণ পরে আবার চেষ্টা করুন।',
            ], 502);
        }
    }

    public function history(Request $request): JsonResponse
    {
        $donations = Donation::with('category')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return response()->json([
            'donations' => $donations->through(fn (Donation $donation) => $this->summary($donation)),
        ]);
    }

    public function status(Request $request, string $tranId): JsonResponse
    {
        $donation = Donation::with('category')->where('tran_id', $tranId)->firstOrFail();

        if ($donation->user_id && $donation->user_id !== $request->user()?->id) {
            return response()->json(['message' => 'এই অনুদানের তথ্য দেখার অনুমতি নেই।'], 403);
        }

        return response()->json(['donation' => $this->summary($donation)]);
    }

    public function success(Request $request, SslCommerzService $gateway): RedirectResponse
    {
        $donation = $this->settleFromGateway($request->all(), $gateway);
        return redirect()->away($this->frontendUrl('/donate/success?tran_id=' . urlencode($donation->tran_id)));
    }

    public function fail(Request $request): RedirectResponse
    {
        $donation = Donation::where('tran_id', $request->string('tran_id')->toString())->first();
        if ($donation) {
            $donation->update(['status' => 'failed', 'failed_at' => now(), 'gateway_payload' => $request->all()]);
        }
        return redirect()->away($this->frontendUrl('/donate/failed'));
    }

    public function cancel(Request $request): RedirectResponse
    {
        $donation = Donation::where('tran_id', $request->string('tran_id')->toString())->first();
        if ($donation) {
            $donation->update(['status' => 'cancelled', 'cancelled_at' => now(), 'gateway_payload' => $request->all()]);
        }
        return redirect()->away($this->frontendUrl('/donate/cancelled'));
    }

    public function ipn(Request $request, SslCommerzService $gateway): JsonResponse
    {
        try {
            $donation = $this->settleFromGateway($request->all(), $gateway);
            return response()->json(['ok' => true, 'status' => $donation->status]);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['ok' => false], 422);
        }
    }

    private function settleFromGateway(array $data, SslCommerzService $gateway): Donation
    {
        $tranId = (string) ($data['tran_id'] ?? '');
        $valId = (string) ($data['val_id'] ?? '');

        if ($tranId === '') {
            throw new \RuntimeException('Missing transaction ID.');
        }

        $donation = Donation::where('tran_id', $tranId)->firstOrFail();

        if ($donation->status === 'completed') {
            return $donation;
        }

        if ($valId === '') {
            throw new \RuntimeException('Missing validation ID.');
        }

        $validated = $gateway->validate($valId);
        $validatedTranId = (string) ($validated['tran_id'] ?? '');
        $validatedStatus = strtoupper((string) ($validated['status'] ?? ''));
        $validatedAmount = (float) ($validated['amount'] ?? 0);

        if ($validatedTranId !== $donation->tran_id) {
            throw new \RuntimeException('Transaction ID mismatch.');
        }

        if (abs($validatedAmount - (float) $donation->amount) > 0.009) {
            throw new \RuntimeException('Payment amount mismatch.');
        }

        $riskLevel = (int) ($validated['risk_level'] ?? 0);

        if (!in_array($validatedStatus, ['VALID', 'VALIDATED'], true)) {
            $donation->update([
                'status' => 'failed',
                'failed_at' => now(),
                'gateway_payload' => $validated,
            ]);
            return $donation->fresh();
        }

        $donation->update([
            'status' => 'completed',
            'validation_id' => $valId,
            'bank_tran_id' => $validated['bank_tran_id'] ?? null,
            'card_type' => $validated['card_type'] ?? null,
            'card_brand' => $validated['card_brand'] ?? null,
            'card_issuer' => $validated['card_issuer'] ?? null,
            'payment_method' => $validated['card_type'] ?? $validated['card_brand'] ?? null,
            'store_amount' => $validated['store_amount'] ?? null,
            'paid_at' => $riskLevel > 0 ? null : now(),
            'gateway_payload' => $validated,
        ]);

        return $donation->fresh('category');
    }

    private function frontendUrl(string $path = ''): string
    {
        return rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/') . $path;
    }

    private function summary(Donation $donation): array
    {
        return [
            'id' => $donation->id,
            'tran_id' => $donation->tran_id,
            'category' => [
                'id' => $donation->category?->id,
                'name' => $donation->category?->name,
            ],
            'amount' => (string) $donation->amount,
            'currency' => $donation->currency,
            'status' => $donation->status,
            'donor_name' => $donation->donor_name,
            'payment_method' => $donation->payment_method,
            'risk_level' => $donation->risk_level,
            'paid_at' => $donation->paid_at,
            'created_at' => $donation->created_at,
        ];
    }
}