<?php

use App\Models\Spreadsheet;
use Illuminate\Support\Facades\Auth;
use function Livewire\Volt\{state, rules, layout, title};
use Flux\Flux;

use App\Support\Thailand;

layout('layouts.app');
title(__('Create New Table'));

state([
    'title' => '',
    'status' => 'published',
    'category' => '',
    'province' => '',
    'fiscal_year' => null,
]);

rules([
    'title' => ['required', 'string', 'max:255'],
    'status' => ['required', 'in:draft,published'],
    'category' => ['nullable', 'string', 'in:sme,otop,startup'],
    'province' => ['nullable', 'string'],
    'fiscal_year' => ['nullable', 'integer'],
]);

$save = function () {
    $this->validate();

    $spreadsheet = Auth::user()->spreadsheets()->create([
        'title' => $this->title,
        'status' => $this->status,
        'category' => $this->category ?: null,
        'province' => $this->province ?: null,
        'fiscal_year' => $this->fiscal_year ?: null,
    ]);

    Flux::toast(variant: 'success', text: __('Table created successfully.'));

    return $this->redirectRoute('spreadsheets.edit', $spreadsheet, navigate: true);
};

?>

<div class="max-w-2xl mx-auto space-y-6">
    <flux:heading size="xl">{{ __('Create New Table') }}</flux:heading>

    <flux:card>
        <form wire:submit="save" class="space-y-6">
            <flux:input wire:model="title" :label="__('Table Title')" placeholder="{{ __('Enter table title (e.g., Sales Data 2026)') }}" required />

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

            <flux:radio.group wire:model="status" :label="__('Visibility')" variant="cards" class="flex-row">
                <flux:radio value="published" :label="__('Public')" :description="__('Visible via public link')" />
                <flux:radio value="draft" :label="__('Private')" :description="__('Only visible to you')" />
            </flux:radio.group>

            <div class="flex items-center gap-3 justify-end">
                <flux:button :href="route('spreadsheets.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">
                    {{ __('Create & Open Editor') }}
                </flux:button>
            </div>
        </form>
    </flux:card>
</div>
