@php
    $hasDarkLogo = file_exists(public_path('logo-dark.png'));
@endphp

@if ($hasDarkLogo)
    <img src="{{ asset('logo.png') }}" {{ $attributes->merge(['class' => 'object-contain dark:hidden']) }} alt="Logo">
    <img src="{{ asset('logo-dark.png') }}" {{ $attributes->merge(['class' => 'object-contain hidden dark:block']) }} alt="Logo">
@else
    <img src="{{ asset('logo.png') }}" {{ $attributes->merge(['class' => 'object-contain dark:invert dark:brightness-125']) }} alt="Logo">
@endif