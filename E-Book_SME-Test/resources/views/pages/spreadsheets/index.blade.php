<?php

use App\Models\Spreadsheet;
use Illuminate\Support\Facades\Gate;
use function Livewire\Volt\{with, layout, title};

layout('layouts.app');
title(__('Manage Data Tables'));

with(fn () => [
    'spreadsheets' => Spreadsheet::query()->where('user_id', auth()->id())->with('user')->latest()->get(),
]);

$delete = function (Spreadsheet $spreadsheet) {
    Gate::authorize('delete', $spreadsheet);
    $spreadsheet->delete();
    $this->dispatch('spreadsheet-deleted');
};

?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Manage Data Tables') }}</flux:heading>
            <flux:subheading>{{ __('รีวิวและจัดการตารางข้อมูลบนแพลตฟอร์ม') }}</flux:subheading>
        </div>
        <flux:button :href="route('spreadsheets.create')" wire:navigate variant="primary" icon="plus">
            {{ __('Create New Table') }}
        </flux:button>
    </div>

    <flux:card class="p-0 overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column class="first:ps-6">{{ __('Title') }}</flux:table.column>
                <flux:table.column>{{ __('Owner') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Created') }}</flux:table.column>
                <flux:table.column />
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($spreadsheets as $spreadsheet)
                    <flux:table.row :key="$spreadsheet->id">
                        <flux:table.cell class="first:ps-6 font-medium">
                            <div class="flex items-center gap-3">
                                <flux:icon name="table-cells" class="size-5 text-zinc-400" />
                                <flux:link :href="route('spreadsheets.edit', $spreadsheet)" wire:navigate>{{ $spreadsheet->title }}</flux:link>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell class="text-zinc-500">{{ $spreadsheet->user?->name ?? __('Unknown admin') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" :color="$spreadsheet->status === 'published' ? 'green' : 'zinc'">
                                {{ ucfirst($spreadsheet->status) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-zinc-500">{{ $spreadsheet->created_at->format('M j, Y') }}</flux:table.cell>
                        <flux:table.cell class="flex justify-end gap-2">
                            @if($spreadsheet->slug)
                                <flux:button variant="ghost" size="sm" icon="eye" :href="route('spreadsheets.view', ['spreadsheet' => $spreadsheet->slug])" target="_blank" />
                            @endif
                            <flux:button variant="ghost" size="sm" icon="pencil-square" :href="route('spreadsheets.edit', $spreadsheet)" wire:navigate />
                            
                            <flux:modal.trigger name="delete-spreadsheet-{{ $spreadsheet->id }}">
                                <flux:button variant="ghost" size="sm" icon="trash" color="red" />
                            </flux:modal.trigger>

                            <flux:modal name="delete-spreadsheet-{{ $spreadsheet->id }}" class="max-w-md">
                                <div class="space-y-6">
                                    <div>
                                        <flux:heading size="lg">{{ __('Delete Table') }}</flux:heading>
                                        <flux:subheading>{{ __('Are you sure you want to delete this table? This action cannot be undone.') }}</flux:subheading>
                                    </div>

                                    <div class="flex justify-end gap-2">
                                        <flux:modal.close>
                                            <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                                        </flux:modal.close>
                                        <flux:button wire:click="delete({{ $spreadsheet->id }})" variant="primary" color="red">
                                            {{ __('Delete Table') }}
                                        </flux:button>
                                    </div>
                                </div>
                            </flux:modal>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center py-10 text-zinc-500 italic">
                            <div class="flex flex-col items-center gap-2">
                                <flux:icon name="table-cells" class="size-10 text-zinc-200 dark:text-zinc-800" />
                                {{ __('No tables created yet.') }}
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
