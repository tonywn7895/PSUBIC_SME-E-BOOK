<?php

use App\Models\Ebook;
use Illuminate\Support\Facades\Gate;
use function Livewire\Volt\{with, layout, title};

layout('layouts.app');
title(__('Manage E-books'));

with(fn () => [
    'ebooks' => Ebook::query()->where('user_id', auth()->id())->with('user')->latest()->get(),
]);

$delete = function (Ebook $ebook) {
    Gate::authorize('delete', $ebook);

    $ebook->delete();

    $this->dispatch('ebook-deleted');
};

?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Manage E-books') }}</flux:heading>
            <flux:subheading>{{ __('รีวิว, แก้ไข, และเผยแพร่ e-books บนแพลตฟอร์ม') }}</flux:subheading>
        </div>
        <flux:button :href="route('ebooks.create')" wire:navigate variant="primary" icon="plus">
            {{ __('Upload New') }}
        </flux:button>
    </div>

    <flux:card class="p-0 overflow-hidden">
        <flux:table>
            <flux:table.columns>
                <flux:table.column class="first:ps-6">{{ __('Title') }}</flux:table.column>
                <flux:table.column>{{ __('Uploader') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Uploaded') }}</flux:table.column>
                <flux:table.column />
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($ebooks as $ebook)
                    <flux:table.row :key="$ebook->id">
                        <flux:table.cell class="first:ps-6 font-medium">{{ $ebook->title }}</flux:table.cell>
                        <flux:table.cell class="text-zinc-500">{{ $ebook->user?->name ?? __('Unknown admin') }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge :color="$ebook->status === 'published' ? 'green' : 'zinc'" size="sm">
                                {{ ucfirst($ebook->status) }}
                            </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="text-zinc-500">{{ $ebook->created_at->format('M j, Y') }}</flux:table.cell>
                        <flux:table.cell class="flex justify-end gap-2">
                            @if($ebook->slug)
                                <flux:button variant="ghost" size="sm" icon="eye" :href="route('ebooks.view', ['ebook' => $ebook->slug])" target="_blank" />
                            @endif
                            <flux:button variant="ghost" size="sm" icon="pencil-square" :href="route('ebooks.edit', $ebook)" wire:navigate />
                            <flux:modal.trigger name="delete-ebook-{{ $ebook->id }}">
                                <flux:button variant="ghost" size="sm" icon="trash" color="red" />
                            </flux:modal.trigger>

                            <flux:modal name="delete-ebook-{{ $ebook->id }}" class="max-w-md">
                                <div class="space-y-6">
                                    <div>
                                        <flux:heading size="lg">{{ __('Delete E-book') }}</flux:heading>
                                        <flux:subheading>{{ __('Are you sure you want to delete this e-book? This action cannot be undone.') }}</flux:subheading>
                                    </div>

                                    <div class="flex justify-end gap-2">
                                        <flux:modal.close>
                                            <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                                        </flux:modal.close>
                                        <flux:button wire:click="delete({{ $ebook->id }})" variant="primary" color="red">
                                            {{ __('Delete E-book') }}
                                        </flux:button>
                                    </div>
                                </div>
                            </flux:modal>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="text-center py-10 text-zinc-500 italic">
                            {{ __('No e-books found.') }}
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>
</div>
