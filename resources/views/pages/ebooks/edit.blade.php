<?php

use App\Models\Ebook;
use App\Models\Spreadsheet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use function Livewire\Volt\{state, rules, usesFileUploads, mount, layout, title, with};
use Flux\Flux;
use App\Support\Thailand;

layout('layouts.app');
title(__('Edit E-book'));

usesFileUploads();

state([
    'ebook' => null,
    'title' => '',
    'description' => '',
    'pdf' => null,
    'cover' => null,
    'spreadsheet_id' => null,
    'status' => 'published',
    'category' => '',
    'province' => '',
    'fiscal_year' => null,
]);

mount(function (Ebook $ebook) {
    Gate::authorize('update', $ebook);

    $this->ebook = $ebook;
    $this->title = $ebook->title;
    $this->description = $ebook->description ?? '';
    $this->spreadsheet_id = $ebook->spreadsheet_id;
    $this->status = $ebook->status;
    $this->category = $ebook->category ?? '';
    $this->province = $ebook->province ?? '';
    $this->fiscal_year = $ebook->fiscal_year;
});

with(fn () => [
    'spreadsheets' => Spreadsheet::where('user_id', Auth::id())->get(),
]);

rules([
    'title' => ['required', 'string', 'max:255'],
    'description' => ['nullable', 'string'],
    'pdf' => ['nullable', 'file', 'mimes:pdf', 'max:51200'],
    'cover' => ['nullable', 'image', 'max:2048'],
    'spreadsheet_id' => ['nullable', 'exists:spreadsheets,id'],
    'status' => ['required', 'in:draft,published'],
    'category' => ['nullable', 'string', 'in:sme,otop,startup'],
    'province' => ['nullable', 'string'],
    'fiscal_year' => ['nullable', 'integer'],
]);

$save = function () {
    $this->validate();

    $data = [
        'title' => $this->title,
        'description' => $this->description,
        'spreadsheet_id' => $this->spreadsheet_id,
        'status' => $this->status,
        'category' => $this->category ?: null,
        'province' => $this->province ?: null,
        'fiscal_year' => $this->fiscal_year ?: null,
    ];

    if ($this->pdf) {
        $data['pdf_path'] = $this->pdf->store('ebooks/pdfs', 'public');
    }

    if ($this->cover) {
        $data['cover_path'] = $this->cover->store('ebooks/covers', 'public');
    }

    $this->ebook->update($data);

    Flux::toast(variant: 'success', text: __('E-book updated successfully.'));

    return $this->redirectRoute('ebooks.index', navigate: true);
};

?>

<div class="max-w-2xl mx-auto space-y-6">
    <flux:heading size="xl">{{ __('Edit E-book') }}</flux:heading>

    <flux:card>
        <form wire:submit="save" class="space-y-6">
            <flux:input wire:model="title" :label="__('Title')" placeholder="{{ __('Enter e-book title') }}" required />

            <flux:textarea wire:model="description" :label="__('Description')" placeholder="{{ __('Briefly describe the e-book') }}" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>{{ __('Update PDF (Optional)') }}</flux:label>
                    <input type="file" wire:model="pdf" class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-zinc-800 dark:file:text-zinc-300 cursor-pointer" accept="application/pdf">
                    <flux:error name="pdf" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Update Cover (Optional)') }}</flux:label>
                    <input type="file" wire:model="cover" class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-zinc-800 dark:file:text-zinc-300 cursor-pointer" accept="image/*">
                    <flux:error name="cover" />
                    @if ($cover)
                        <div class="mt-4">
                            <p class="text-sm text-zinc-500 mb-2">{{ __('New Cover Preview:') }}</p>
                            <img src="{{ $cover->temporaryUrl() }}" class="h-48 w-auto object-cover rounded-lg border border-zinc-200 dark:border-zinc-800">
                        </div>
                    @elseif ($ebook && $ebook->cover_path)
                        <div class="mt-4">
                            <p class="text-sm text-zinc-500 mb-2">{{ __('Current Cover:') }}</p>
                            <img src="{{ Storage::url($ebook->cover_path) }}" class="h-48 w-auto object-cover rounded-lg border border-zinc-200 dark:border-zinc-800">
                        </div>
                    @endif
                </flux:field>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <flux:select wire:model="category" :label="__('Category (Optional)')" placeholder="{{ __('Select a category') }}">
                    <flux:select.option value="">{{ __('None') }}</flux:select.option>
                    <flux:select.option value="sme">{{ __('SME') }}</flux:select.option>
                    <flux:select.option value="otop">{{ __('OTOP') }}</flux:select.option>
                    <flux:select.option value="startup">{{ __('Startup') }}</flux:select.option>
                </flux:select>

                <flux:select wire:model="province" :label="__('Province (Optional)')" placeholder="{{ __('Select a province') }}">
                    <flux:select.option value="">{{ __('None') }}</flux:select.option>
                    @foreach (Thailand::provinces() as $prov)
                        <flux:select.option :value="$prov">{{ $prov }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="fiscal_year" :label="__('Fiscal Year (Optional)')" placeholder="{{ __('Select a fiscal year') }}">
                    <flux:select.option value="">{{ __('None') }}</flux:select.option>
                    @foreach (Thailand::fiscalYears() as $year)
                        <flux:select.option :value="$year">{{ $year }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <flux:radio.group wire:model="status" :label="__('Status')" variant="cards" class="flex-row">
                <flux:radio value="published" :label="__('Published')" :description="__('Visible to everyone')" />
                <flux:radio value="draft" :label="__('Draft')" :description="__('Only visible to you')" />
            </flux:radio.group>

            <flux:select wire:model="spreadsheet_id" :label="__('Link Data Table (Optional)')" placeholder="{{ __('Select a table to link') }}">
                <flux:select.option value="">{{ __('None') }}</flux:select.option>
                @foreach ($spreadsheets as $spreadsheet)
                    <flux:select.option :value="$spreadsheet->id">{{ $spreadsheet->title }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex items-center gap-3 justify-end">
                <flux:button :href="route('ebooks.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ __('Save Changes') }}</span>
                    <span wire:loading>{{ __('Saving...') }}</span>
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>
