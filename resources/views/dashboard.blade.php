<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <flux:heading size="xl">{{ __('Dashboard') }}</flux:heading>
                <flux:subheading>{{ __('ตรวจสอบสถิติและจัดการเนื้อหาของคุณบนแพลตฟอร์ม.') }}</flux:subheading>
            </div>

            <flux:button :href="route('explore')" target="_blank" icon="eye" variant="ghost">
                {{ __('Open Public Site') }}
            </flux:button>
        </div>

        {{-- Stats --}}
        <livewire:dashboard.stats />

        {{-- Main Content --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 flex-1">
            {{-- Quick Actions --}}
            <flux:card class="space-y-4">
                <div>
                    <flux:heading size="lg">{{ __('Quick Actions') }}</flux:heading>
                    <flux:subheading>{{ __('สร้างและจัดการเนื้อหาของคุณขึ้นสู่แพลตฟอร์ม.') }}</flux:subheading>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <flux:button :href="route('ebooks.create')" wire:navigate variant="primary" icon="plus" class="justify-start">
                        {{ __('Upload New E-book') }}
                    </flux:button>
                    <flux:button :href="route('spreadsheets.create')" wire:navigate icon="table-cells" class="justify-start">
                        {{ __('Create New Table') }}
                    </flux:button>
                    
                    @if (auth()->user()->is_admin)
                        <flux:button :href="route('charts.create')" wire:navigate icon="chart-bar" class="justify-start">
                            {{ __('Create New Chart') }}
                        </flux:button>
                        <flux:button :href="route('charts.index')" wire:navigate icon="chart-pie" class="justify-start">
                            {{ __('Manage Charts') }}
                        </flux:button>
                    @else
                        <flux:button :href="route('ebooks.index')" wire:navigate icon="book-open" class="justify-start">
                            {{ __('Manage E-books') }}
                        </flux:button>
                        <flux:button :href="route('profile.edit')" wire:navigate icon="user" class="justify-start">
                            {{ __('Profile Settings') }}
                        </flux:button>
                    @endif
                </div>
            </flux:card>

            {{-- Recent Uploads --}}
            <livewire:dashboard.recent-uploads />
        </div>
    </div>
</x-layouts::app>
