<?php

use App\Models\Ebook;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use function Livewire\Volt\{state, rules, usesFileUploads, layout, title};
use Flux\Flux;
use App\Support\Thailand;

layout('layouts.app');
title(__('Upload New E-book'));

usesFileUploads();

state([
    'title' => '',
    'description' => '',
    'pdf' => null,
    'cover' => null,
    'status' => 'published',
    'category' => '',
    'province' => '',
    'fiscal_year' => null,
]);

rules([
    'title' => ['required', 'string', 'max:255'],
    'description' => ['nullable', 'string'],
    'pdf' => ['required', 'file', 'mimes:pdf', 'max:51200'], // 50MB max 
    'cover' => ['nullable', 'image', 'max:2048'], // 2MB max
    'status' => ['required', 'in:draft,published'],
    'category' => ['nullable', 'string', 'in:sme,otop,startup'],
    'province' => ['nullable', 'string'],
    'fiscal_year' => ['nullable', 'integer'],
]);

$save = function () {
    $this->validate();

    $pdfPath = $this->pdf->store('ebooks/pdfs', 'public');
    ///$coverPath = $this->cover ? $this->cover->store('ebooks/covers', 'public') : null;

    $coverPath = null;
    if ($this->cover) {
        $manager = new ImageManager(new Driver());
        $image = $manager->decode($this->cover->get());

        $image->scale(width: 600);

        $name = 'ebooks/covers/' .bin2hex(random_bytes(10)) . '.webp';
        Storage::disk('public')->put($name, $image->encode(new WebpEncoder(80)));
        $coverPath = $name;
    }
    Auth::user()->ebooks()->create([
        'title' => $this->title,
        'description' => $this->description,
        'pdf_path' => $pdfPath,
        'cover_path' => $coverPath,
        'status' => $this->status,
        'category' => $this->category ?: null,
        'province' => $this->province ?: null,
        'fiscal_year' => $this->fiscal_year ?: null,
    ]);

    Flux::toast(variant: 'success', text: __('E-book uploaded successfully.'));

    return $this->redirectRoute('ebooks.index', navigate: true);
};

?>

<div class="max-w-2xl mx-auto space-y-6">
    <flux:heading size="xl">{{ __('Upload New E-book') }}</flux:heading>

    <flux:card>
        <form wire:submit="save" class="space-y-6">
            <flux:input wire:model="title" :label="__('Title')" placeholder="{{ __('Enter e-book title') }}" required />

            <flux:textarea wire:model="description" :label="__('Description')" placeholder="{{ __('Briefly describe the e-book') }}" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <flux:field>
                    <flux:label>{{ __('PDF File') }} <span class="text-xs text-zinc-400">(Max 50MB)</span></flux:label>
                    <input type="file" wire:model="pdf" class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-zinc-800 dark:file:text-zinc-300 cursor-pointer" accept="application/pdf">
                    <div wire:loading wire:target="pdf" class="text-xs text-blue-500 mt-1">{{ __('Processing PDF...') }}</div>
                    <flux:error name="pdf" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Cover Image') }} <span class="text-xs text-zinc-400">(Max 2MB)</span></flux:label>
                    <input type="file" wire:model="cover" class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-zinc-800 dark:file:text-zinc-300 cursor-pointer" accept="image/*">
                    <div wire:loading wire:target="cover" class="text-xs text-blue-500 mt-1">{{ __('Uploading image...') }}</div>
                    <flux:error name="cover" />
                    @if ($cover)
                        <div class="mt-4">
                            <p class="text-sm text-zinc-500 mb-2">{{ __('Preview:') }}</p>
                            <img src="{{ $cover->temporaryUrl() }}" class="h-48 w-auto object-cover rounded-lg border border-zinc-200 dark:border-zinc-800">
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

            <div class="flex items-center gap-3 justify-end">
                <flux:button :href="route('ebooks.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ __('Upload E-book') }}</span>
                    <span wire:loading>{{ __('Uploading...') }}</span>
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>
