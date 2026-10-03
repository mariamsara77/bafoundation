<?php

use App\Models\DonationCategory;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;
use Flux\Flux;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public ?int $editingId = null;
    public string $name = '';
    public string $description = '';
    public bool $is_active = true;
    public int $sort_order = 0;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        Flux::modal('donation-category-form')->show();
    }

    public function edit(int $id): void
    {
        $category = DonationCategory::findOrFail($id);
        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->description = $category->description ?? '';
        $this->is_active = $category->is_active;
        $this->sort_order = $category->sort_order;

        Flux::modal('donation-category-form')->show();
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);

        $slug = Str::slug($data['name']);
        if ($slug === '') {
            $slug = 'category-' . Str::random(8);
        }

        $category = $this->editingId
            ? DonationCategory::findOrFail($this->editingId)
            : new DonationCategory();

        $category->fill([...$data, 'slug' => $slug]);
        $category->save();

        Flux::modal('donation-category-form')->close();
        Flux::toast('অনুদান বিভাগ সংরক্ষণ হয়েছে।', variant: 'success');
        $this->resetForm();
    }

    public function toggle(int $id): void
    {
        $category = DonationCategory::findOrFail($id);
        $category->update(['is_active' => !$category->is_active]);

        Flux::toast('অনুদান বিভাগের অবস্থা আপডেট হয়েছে।', variant: 'success');
    }

    public function delete(int $id): void
    {
        $category = DonationCategory::findOrFail($id);

        if ($category->donations()->exists()) {
            Flux::toast('এই বিভাগে অনুদান আছে, তাই এটি মুছে ফেলা যাবে না। আগে নিষ্ক্রিয় করুন।', variant: 'warning');
            return;
        }

        $category->delete();
        Flux::toast('অনুদান বিভাগ মুছে ফেলা হয়েছে।', variant: 'success');
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'description']);
        $this->is_active = true;
        $this->sort_order = 0;
    }

    public function with(): array
    {
        return [
            'categories' => DonationCategory::query()
                ->when($this->search, fn ($query) => $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('slug', 'like', '%' . $this->search . '%'))
                ->withCount(['donations as completed_donations_count' => fn ($query) => $query->where('status', 'completed')])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(12),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <flux:heading size="xl">Donation Categories</flux:heading>
            <flux:subheading>অনুদানের বিভাগ তৈরি, সাজানো ও সক্রিয়/নিষ্ক্রিয় করুন।</flux:subheading>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="create">নতুন বিভাগ</flux:button>
    </div>

    <div class="max-w-md">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="বিভাগ খুঁজুন..." />
    </div>

    <flux:card class="p-0 overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column>বিভাগ</flux:table.column>
                <flux:table.column>ক্রম</flux:table.column>
                <flux:table.column>অনুদান</flux:table.column>
                <flux:table.column>অবস্থা</flux:table.column>
                <flux:table.column align="end">অ্যাকশন</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($categories as $category)
                    <flux:table.row :key="$category->id">
                        <flux:table.cell>
                            <div class="font-medium">{{ $category->name }}</div>
                            <div class="text-xs text-zinc-500">{{ $category->slug }}</div>
                            @if($category->description)
                                <div class="mt-1 text-xs text-zinc-500">{{ Str::limit($category->description, 80) }}</div>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $category->sort_order }}</flux:table.cell>
                        <flux:table.cell>{{ number_format($category->completed_donations_count) }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$category->is_active ? 'green' : 'zinc'">
                                {{ $category->is_active ? 'সক্রিয়' : 'নিষ্ক্রিয়' }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <div class="flex justify-end gap-1">
                                <flux:button size="sm" variant="ghost" icon="pencil-square" wire:click="edit({{ $category->id }})" />
                                <flux:button size="sm" variant="ghost" icon="power" wire:click="toggle({{ $category->id }})" />
                                <flux:button size="sm" variant="ghost" color="danger" icon="trash" wire:confirm="এই বিভাগটি মুছে ফেলবেন?" wire:click="delete({{ $category->id }})" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="py-8 text-center text-zinc-500">কোনো অনুদান বিভাগ পাওয়া যায়নি।</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

    {{ $categories->links() }}

    <flux:modal name="donation-category-form" class="md:w-[32rem] space-y-6">
        <div>
            <flux:heading size="lg">{{ $editingId ? 'অনুদান বিভাগ সম্পাদনা' : 'নতুন অনুদান বিভাগ' }}</flux:heading>
            <flux:subheading>নাম ও ক্রম নির্ধারণ করুন।</flux:subheading>
        </div>
        <form wire:submit="save" class="space-y-4">
            <flux:input wire:model="name" label="বিভাগের নাম" />
            <flux:textarea wire:model="description" label="বিবরণ" rows="3" />
            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="sort_order" type="number" min="0" label="ক্রম" />
                <flux:select wire:model="is_active" label="অবস্থা">
                    <flux:select.option :value="true">সক্রিয়</flux:select.option>
                    <flux:select.option :value="false">নিষ্ক্রিয়</flux:select.option>
                </flux:select>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">বাতিল</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">সংরক্ষণ</flux:button>
            </div>
        </form>
    </flux:modal>
</div>