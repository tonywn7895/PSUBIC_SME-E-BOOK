<?php

use App\Models\CarouselImage;
use Illuminate\Support\Facades\Storage;
use function Livewire\Volt\{state, rules, usesFileUploads, layout, title, computed};
use Flux\Flux;

layout('layouts.app');
title(__('Manage Carousel'));

usesFileUploads();

state([
    'newImage' => null,
    'order' => 1,
]);

$images = computed(fn () => CarouselImage::orderBy('order')->get());

$uploadImage = function () {
    $this->validate([
        'newImage' => ['required', 'image', 'max:5120'], // 5MB max
    ]);

    $path = $this->newImage->store('carousel', 'public');

    CarouselImage::create([
        'image_path' => $path,
        'order' => CarouselImage::max('order') + 1,
        'is_active' => true,
    ]);

    $this->newImage = null;
    Flux::toast(variant: 'success', text: __('Image uploaded successfully.'));
};

$deleteImage = function (CarouselImage $image) {
    Storage::disk('public')->delete($image->image_path);
    $image->delete();
    Flux::toast(variant: 'success', text: __('Image deleted successfully.'));
};

$toggleActive = function (CarouselImage $image) {
    $image->update(['is_active' => !$image->is_active]);
    Flux::toast(variant: 'success', text: $image->is_active ? __('Image activated.') : __('Image deactivated.'));
};

$updateOrder = function ($id, $newOrder) {
    CarouselImage::find($id)->update(['order' => $newOrder]);
    Flux::toast(variant: 'success', text: __('Order updated.'));
};

?>

<div class="space-y-8">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Manage Carousel Images') }}</flux:heading>
    </div>

    <flux:card>
        <form wire:submit="uploadImage" class="space-y-6">
            <flux:field>
                <flux:label>{{ __('Upload New Image') }} <span class="text-xs text-zinc-400">({{ __('ขนาดไฟล์สูงสุด 5MB, ขนาดที่แนะนำ: 2000x800px') }})</span></flux:label>
                <div class="flex items-center gap-4">
                    <input type="file" wire:model="newImage" class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-zinc-800 dark:file:text-zinc-300 cursor-pointer" accept="image/*">
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" :disabled="!$newImage">
                        <span wire:loading.remove>{{ __('Upload') }}</span>
                        <span wire:loading>{{ __('Uploading...') }}</span>
                    </flux:button>
                </div>
                <flux:error name="newImage" />
            </flux:field>

            @if ($newImage)
                <div class="mt-4">
                    <p class="text-sm text-zinc-500 mb-2">{{ __('Preview:') }}</p>
                    <img src="{{ $newImage->temporaryUrl() }}" class="h-48 w-full object-cover rounded-lg border border-zinc-200 dark:border-zinc-800">
                </div>
            @endif
        </form>
    </flux:card>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse ($this->images as $image)
            <flux:card class="relative flex flex-col p-0 overflow-hidden">
                <img src="{{ Storage::url($image->image_path) }}" class="h-48 w-full object-cover">
                
                <div class="p-4 space-y-4 flex-1">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <flux:label>{{ __('Order:') }}</flux:label>
                            <input type="number" 
                                value="{{ $image->order }}" 
                                wire:change="updateOrder({{ $image->id }}, $event.target.value)"
                                class="w-16 px-2 py-1 text-sm rounded border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900"
                            >
                        </div>
                        <flux:badge :color="$image->is_active ? 'green' : 'zinc'">
                            {{ $image->is_active ? __('Active') : __('Inactive') }}
                        </flux:badge>
                    </div>

                    <div class="flex items-center gap-2">
                        <flux:button size="sm" class="flex-1" wire:click="toggleActive({{ $image->id }})">
                            {{ $image->is_active ? __('Deactivate') : __('Activate') }}
                        </flux:button>
                        
                        <flux:modal.trigger name="delete-modal-{{ $image->id }}">
                            <flux:button size="sm" variant="danger" icon="trash" />
                        </flux:modal.trigger>

                        <flux:modal name="delete-modal-{{ $image->id }}" class="min-w-[22rem]">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">{{ __('Delete image?') }}</flux:heading>
                                    <flux:subheading>{{ __('This action cannot be undone.') }}</flux:subheading>
                                </div>
                                <div class="flex gap-2">
                                    <flux:spacer />
                                    <flux:modal.close>
                                        <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                                    </flux:modal.close>
                                    <flux:button wire:click="deleteImage({{ $image->id }})" variant="danger">{{ __('Delete') }}</flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </div>
                </div>
            </flux:card>
        @empty
            <div class="col-span-full py-12 flex flex-col items-center justify-center border-2 border-dashed border-zinc-200 dark:border-zinc-800 rounded-xl">
                <flux:icon name="photo" class="size-12 text-zinc-300 mb-4" />
                <p class="text-zinc-500">{{ __('No images uploaded yet.') }}</p>
            </div>
        @endforelse
    </div>
</div>
