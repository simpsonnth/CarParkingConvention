<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/scan/{code?}', App\Livewire\Attendant\Scan::class)->middleware('auth')->name('attendant.scan');
Route::get('/register', App\Livewire\Public\Register::class)->name('parking.register');
Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, ['en', 'pt', 'es'], true)) {
        session(['locale' => $locale]);
    }
    return redirect()->back();
})->name('locale.set');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');

    // Admin Routes (auth + admin role only)
    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', App\Livewire\Admin\Dashboard::class)->name('dashboard');
        Route::get('/parks', App\Livewire\Admin\CarParks::class)->name('car-parks');
        Route::get('/parks/{carPark}', App\Livewire\Admin\CarParkDetail::class)->name('car-parks.show');
        Route::get('/congregations', App\Livewire\Admin\Congregations::class)->name('congregations');
        Route::get('/congregations/{congregation}', App\Livewire\Admin\CongregationDetail::class)->name('congregations.show');
        Route::get('/congregations/{congregation}/print', function (App\Models\Congregation $congregation) {
            return view('admin.print-pass', ['congregation' => $congregation]);
        })->name('congregations.print');
        Route::get('/users', App\Livewire\Admin\Users::class)->name('users');
        Route::get('/registrations', App\Livewire\Admin\Registrations::class)->name('registrations');
        Route::get('/registrations/trash', App\Livewire\Admin\RegistrationsTrash::class)->name('registrations.trash');
        Route::get('/registrations/export', function () {
            $filename = 'parking-registrations-' . now()->format('Y-m-d-His') . '.xlsx';
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\ParkingRegistrationsExport,
                $filename,
                \Maatwebsite\Excel\Excel::XLSX
            );
        })->name('registrations.export');
        Route::get('/settings', App\Livewire\Admin\Settings::class)->name('settings');
    });

});
