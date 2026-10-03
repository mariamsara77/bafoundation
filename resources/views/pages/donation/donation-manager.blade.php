<?php

use App\Models\Donation;
use App\Models\DonationCategory;
use Livewire\Component;
use Livewire\WithPagination;
use Flux\Flux;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $category = '';
    public ?Donation $viewingDonation = null;

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatus(): void { $this->resetPage(); }
    public function updatingCategory(): void { $this->resetPage(); }

    public function view(int $id): void
    {
        $this->viewingDonation = Donation::with(['category', 'user'])->findOrFail($id);
        Flux::modal('donation-details')->show();
    }

    public function with(): array
    {
        $query = Donation::query()
            ->with('category')
            ->when($this->search, function ($query) {
                $term = '%' . $this->search . '%';
                $query->where(function ($q) use ($term) {
                    $q->where('tran_id', 'like', $term)
                        ->orWhere('donor_name', 'like', $term)
                        ->orWhere('donor_email', 'like', $term)
                        ->orWhere('donor_phone', 'like', $term)
                        ->orWhere('bank_tran_id', 'like', $term);
                });
            })
            ->when($this->status, fn ($query) => $query->where('status', $this->status))
            ->when($this->category, fn ($query) => $query->where('donation_category_id', $this->category));

        return [
            'donations' => $query->latest()->paginate(15),
            'categories' => DonationCategory::orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'totalCompleted' => Donation::where('status', 'completed')->sum('amount'),
            'completedCount' => Donation::where('status', 'completed')->count(),
            'pendingCount' => Donation::whereIn('status', ['pending', 'processing'])->count(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="xl">Donation Management</flux:heading>
        <flux:subheading>অনলাইন অনুদান, পেমেন্ট স্ট্যাটাস ও ট্রানজেকশন তথ্য এক জায়গা থেকে দেখুন।</flux:subheading>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <flux:card>
            <flux:text>মোট সম্পন্ন অনুদান</flux:text>
            <flux:heading size="lg" class="mt-2">৳ {{ number_format((float) $totalCompleted, 2) }}</flux:heading>
        </flux:card>
        <flux:card>
            <flux:text>সম্পন্ন লেনদেন</flux:text>
            <flux:heading size="lg" class="mt-2">{{ number_format($completedCount) }}</flux:heading>
        </flux:card>
        <flux:card>
            <flux:text>পেমেন্ট অপেক্ষমাণ</flux:text>
            <flux:heading size="lg" class="mt-2">{{ number_format($pendingCount) }}</flux:heading>
        </flux:card>
    </div>

    <div class="grid gap-3 md:grid-cols-[1fr_220px_220px]">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="নাম, ট্রানজেকশন বা ফোন খুঁজুন..." />
        <flux:select wire:model.live="status" placeholder="সব স্ট্যাটাস">
            <flux:select.option value="">সব স্ট্যাটাস</flux:select.option>
            <flux:select.option value="completed">সম্পন্ন</flux:select.option>
            <flux:select.option value="processing">প্রক্রিয়াধীন</flux:select.option>
            <flux:select.option value="pending">অপেক্ষমাণ</flux:select.option>
            <flux:select.option value="failed">ব্যর্থ</flux:select.option>
            <flux:select.option value="cancelled">বাতিল</flux:select.option>
        </flux:select>
        <flux:select wire:model.live="category" placeholder="সব বিভাগ">
            <flux:select.option value="">সব বিভাগ</flux:select.option>
            @foreach($categories as $item)
                <flux:select.option value="{{ $item->id }}">{{ $item->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <flux:card class="p-0 overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>দাতা</flux:table.column>
                <flux:table.column>বিভাগ</flux:table.column>
                <flux:table.column>পরিমাণ</flux:table.column>
                <flux:table.column>পেমেন্ট</flux:table.column>
                <flux:table.column>স্ট্যাটাস</flux:table.column>
                <flux:table.column align="end">অ্যাকশন</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse($donations as $donation)
                    <flux:table.row :key="$donation->id">
                        <flux:table.cell>
                            <div class="font-medium">{{ $donation->donor_name }}</div>
                            <div class="text-xs text-zinc-500">{{ $donation->donor_phone }}</div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $donation->category?->name }}</flux:table.cell>
                        <flux:table.cell class="font-semibold">৳ {{ number_format((float) $donation->amount, 2) }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="text-sm">{{ $donation->payment_method ?: '—' }}</div>
                            <div class="text-xs text-zinc-500">{{ $donation->tran_id }}</div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge color="{{ $donation->status === 'completed' ? 'green' : ($donation->status === 'failed' ? 'red' : 'yellow') }}">
                                {{ $donation->status }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:button size="sm" variant="ghost" icon="eye" wire:click="view({{ $donation->id }})" />
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="py-8 text-center text-zinc-500">কোনো অনুদান পাওয়া যায়নি।</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{ $donations->links() }}

    <flux:modal name="donation-details" class="md:w-[42rem] space-y-6">
        @if($viewingDonation)
            <div>
                <flux:heading size="lg">অনুদানের বিস্তারিত</flux:heading>
                <flux:subheading>{{ $viewingDonation->tran_id }}</flux:subheading>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 text-sm">
                <div><span class="text-zinc-500">দাতা</span><div class="font-medium">{{ $viewingDonation->donor_name }}</div></div>
                <div><span class="text-zinc-500">ফোন</span><div class="font-medium">{{ $viewingDonation->donor_phone }}</div></div>
                <div><span class="text-zinc-500">ইমেইল</span><div class="font-medium">{{ $viewingDonation->donor_email ?: '—' }}</div></div>
                <div><span class="text-zinc-500">বিভাগ</span><div class="font-medium">{{ $viewingDonation->category?->name }}</div></div>
                <div><span class="text-zinc-500">পরিমাণ</span><div class="font-semibold">৳ {{ number_format((float) $viewingDonation->amount, 2) }}</div></div>
                <div><span class="text-zinc-500">স্ট্যাটাস</span><div class="font-medium">{{ $viewingDonation->status }}</div></div>
                <div><span class="text-zinc-500">Payment</span><div class="font-medium">{{ $viewingDonation->payment_method ?: '—' }}</div></div>
                <div><span class="text-zinc-500">Bank Transaction</span><div class="font-medium">{{ $viewingDonation->bank_tran_id ?: '—' }}</div></div>
                <div><span class="text-zinc-500">Paid at</span><div class="font-medium">{{ $viewingDonation->paid_at?->format('d M Y, h:i A') ?: '—' }}</div></div>
                <div><span class="text-zinc-500">Created</span><div class="font-medium">{{ $viewingDonation->created_at?->format('d M Y, h:i A') }}</div></div>
            </div>
            @if($viewingDonation->message)
                <div class="rounded-xl bg-zinc-50 p-4 text-sm dark:bg-zinc-800/60">{{ $viewingDonation->message }}</div>
            @endif
            <div class="flex justify-end">
                <flux:modal.close><flux:button variant="primary">বন্ধ করুন</flux:button></flux:modal.close>
            </div>
        @endif
    </flux:modal>
</div>