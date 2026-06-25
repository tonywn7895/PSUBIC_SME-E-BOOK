<?php

use App\Models\Chart;
use Illuminate\Support\Facades\Gate;
use function Livewire\Volt\{with, layout, title, mount};
use Flux\Flux;

layout('layouts.app');
title(__('Manage Charts'));

mount(function () {
    Gate::authorize('create', Chart::class);
});

with(fn () => [
    'charts' => Chart::query()->with('spreadsheet')->latest()->get(),
]);

$delete = function (Chart $chart) {
    Gate::authorize('delete', $chart);
    $chart->delete();
    Flux::toast(variant: 'success', text: __('Chart deleted successfully.'));
};

?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Manage Charts') }}</flux:heading>
            <flux:subheading>{{ __('รีวิว, แก้ไข, และเผยแพร่กราฟการวิเคราะห์ข้อมูล') }}</flux:subheading>
        </div>
        <flux:button :href="route('charts.create')" wire:navigate variant="primary" icon="plus">
            {{ __('Create New Chart') }}
        </flux:button>
    </div>

    <flux:card class="p-0 overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column class="first:ps-6">{{ __('Title') }}</flux:table.column>
                <flux:table.column>{{ __('Spreadsheet') }}</flux:table.column>
                <flux:table.column>{{ __('Chart Type') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Created') }}</flux:table.column>
                <flux:table.column />
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($charts as $chart)
                    <flux:table.row :key="$chart->id">
                        <flux:table.cell class="first:ps-6 font-medium">{{ $chart->title }}</flux:table.cell>
                        <flux:table.cell class="text-zinc-500">{{ $chart->spreadsheet?->title ?? __('Unknown') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" color="blue">{{ ucfirst($chart->chart_type) }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$chart->is_public ? 'green' : 'zinc'" size="sm">
                                {{ $chart->is_public ? __('Public') : __('Private') }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-zinc-500">{{ $chart->created_at->format('M j, Y') }}</flux:table.cell>
                        <flux:table.cell class="flex justify-end gap-2">
                            <flux:button variant="ghost" size="sm" icon="pencil-square" :href="route('charts.edit', $chart)" wire:navigate />
                            
                            <flux:modal.trigger name="delete-chart-{{ $chart->id }}">
                                <flux:button variant="ghost" size="sm" icon="trash" color="red" />
                            </flux:modal.trigger>

                            <flux:modal name="delete-chart-{{ $chart->id }}" class="max-w-md">
                                <div class="space-y-6">
                                    <div>
                                        <flux:heading size="lg">{{ __('Delete Chart') }}</flux:heading>
                                        <flux:subheading>{{ __('Are you sure you want to delete this chart? This action cannot be undone.') }}</flux:subheading>
                                    </div>
                                    <div class="flex justify-end gap-2">
                                        <flux:modal.close>
                                            <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                                        </flux:modal.close>
                                        <flux:button wire:click="delete({{ $chart->id }})" variant="danger">{{ __('Delete Chart') }}</flux:button>
                                    </div>
                                </div>
                            </flux:modal>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="text-center py-8 text-zinc-500">
                            {{ __('No charts created yet.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
