<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Volt::route('/', 'pages.public.index')->name('home');
Volt::route('/explore', 'pages.public.explore')->name('explore');
Volt::route('/insights', 'pages.public.insights')->name('insights');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::prefix('dashboard/ebooks')->name('ebooks.')->group(function () {
        Volt::route('/', 'pages.ebooks.index')->name('index');
        Volt::route('/create', 'pages.ebooks.create')->name('create');
        Volt::route('/{ebook}/edit', 'pages.ebooks.edit')->name('edit');
    });

    Route::prefix('dashboard/spreadsheets')->name('spreadsheets.')->group(function () {
        Volt::route('/', 'pages.spreadsheets.index')->name('index');
        Volt::route('/create', 'pages.spreadsheets.create')->name('create');
        Volt::route('/{spreadsheet}/edit', 'pages.spreadsheets.edit')->name('edit');
    });

    Route::prefix('dashboard/charts')->name('charts.')->group(function () {
        Volt::route('/', 'pages.charts.index')->name('index');
        Volt::route('/create', 'pages.charts.create')->name('create');
        Volt::route('/{chart}/edit', 'pages.charts.edit')->name('edit');
    });

    Volt::route('dashboard/carousel', 'pages.carousel.index')->name('carousel.index');
});

Volt::route('/read/{ebook:slug}', 'pages.reader.show')->name('ebooks.view');
Volt::route('/table/{spreadsheet:slug}', 'pages.spreadsheets.show')->name('spreadsheets.view');

require __DIR__.'/settings.php';
