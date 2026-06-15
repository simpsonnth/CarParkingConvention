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

Route::get('/scan/ticket/{registration}', App\Livewire\Attendant\Scan::class)->middleware('auth')->name('attendant.scan.ticket');
Route::get('/scan/walk-in', App\Livewire\Attendant\Scan::class)->middleware('auth')->name('attendant.scan.walk-in');
Route::get('/scan/{code?}', App\Livewire\Attendant\Scan::class)->middleware('auth')->name('attendant.scan');

Route::middleware('public.route')->group(function () {
    Route::get('/parking-registration', App\Livewire\Public\Register::class)->name('parking.register');
    Route::get('/register-simple', App\Livewire\Public\CongregationNumbers::class)->name('parking.register-simple');
    Route::get('/register-circuit-overseer', App\Livewire\Public\CircuitOverseerRegister::class)->name('parking.register-circuit-overseer');
    Route::get('/congregation-portal', App\Livewire\Public\CongregationPortal::class)->name('parking.congregation-portal');
    Route::middleware('throttle:10,1')->group(function () {
        Route::get('/parking-incidents', App\Livewire\Public\ParkingIncidentReport::class)->name('management.parking-incidents');
        Route::get('/toolbox-feedback', App\Livewire\Public\ToolboxFeedback::class)->name('management.toolbox-feedback');
        Route::get('/lessons-learned', App\Livewire\Public\LessonLearned::class)->name('management.lessons-learned');
    });
});
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
        Route::get('/coaches', App\Livewire\Admin\Coaches::class)->name('coaches');
        Route::get('/coaches/export', function () {
            $filename = 'coaches-'.now()->format('Y-m-d-His').'.xlsx';

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\CoachesExport,
                $filename,
                \Maatwebsite\Excel\Excel::XLSX
            );
        })->name('coaches.export');
        Route::get('/registrations/attendance-by-day', App\Livewire\Admin\RegistrationAttendanceByDayReport::class)->name('registrations.attendance-by-day');
        Route::get('/congregation-numbers', App\Livewire\Admin\CongregationNumbers::class)->name('congregation-numbers');
        Route::get('/congregation-numbers/export-missing', function () {
            $filename = 'register-simple-not-submitted-'.now()->format('Y-m-d-His').'.xlsx';

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\CongregationsMissingNumbersExport,
                $filename,
                \Maatwebsite\Excel\Excel::XLSX
            );
        })->name('congregation-numbers.export-missing');
        Route::get('/congregation-numbers/export', function () {
            $filename = 'register-simple-all-responses-'.now()->format('Y-m-d-His').'.xlsx';

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\CongregationNumbersResponsesExport,
                $filename,
                \Maatwebsite\Excel\Excel::XLSX
            );
        })->name('congregation-numbers.export');
        Route::get('/congregation-numbers/trash', App\Livewire\Admin\CongregationNumbersTrash::class)->name('congregation-numbers.trash');
        Route::get('/registrations/{registration}/print', function (App\Models\ParkingRegistration $registration) {
            $registration->load('carPark');
            if ($registration->is_circuit_overseer) {
                $effectiveCarPark = $registration->carPark;

                return view('admin.print-pass', [
                    'congregation' => null,
                    'registration' => $registration,
                    'effectiveCarPark' => $effectiveCarPark,
                ]);
            }

            $congregation = \App\Models\Congregation::with('carPark')
                ->where('name', $registration->congregation)
                ->first();
            if (! $congregation) {
                abort(404, 'Congregation not found for this registration.');
            }

            $effectiveCarPark = $registration->carPark ?? $congregation->carPark;

            return view('admin.print-pass', [
                'congregation' => $congregation,
                'registration' => $registration,
                'effectiveCarPark' => $effectiveCarPark,
            ]);
        })->name('registrations.print');
        Route::get('/registrations/trash', App\Livewire\Admin\RegistrationsTrash::class)->name('registrations.trash');
        Route::get('/registrations/download-master-passes-zip/{token}', function (string $token) {
            $cacheKey = 'master-passes-zip:'.$token;
            $registrationIds = cache()->get($cacheKey);
            if (! is_array($registrationIds) || empty($registrationIds)) {
                return redirect()->route('admin.registrations')
                    ->with('error', __('registrations.download_link_expired'));
            }
            cache()->forget($cacheKey);
            try {
                $service = app(\App\Services\MasterPassZipService::class);
                [$zipPath, $downloadName] = $service->buildZip($registrationIds);

                return response()->download($zipPath, $downloadName, [
                    'Content-Type' => 'application/zip',
                ])->deleteFileAfterSend(true);
            } catch (\Throwable $e) {
                return redirect()->route('admin.registrations')
                    ->with('error', $e->getMessage());
            }
        })->name('registrations.download-passes-zip');
        Route::get('/registrations/export', function () {
            $filename = 'parking-registrations-'.now()->format('Y-m-d-His').'.xlsx';

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\ParkingRegistrationsExport,
                $filename,
                \Maatwebsite\Excel\Excel::XLSX
            );
        })->name('registrations.export');
        Route::get('/settings', App\Livewire\Admin\Settings::class)->name('settings');
        Route::get('/reports', App\Livewire\Admin\Reports::class)->name('reports');
        Route::get('/survey-vs-registrations', App\Livewire\Admin\SurveyVsRegistrationReport::class)->name('survey-vs-registrations');
        Route::get('/circuit-overseer-parking', App\Livewire\Admin\CircuitOverseerParking::class)->name('circuit-overseer-parking');
        Route::get('/parking-incidents', App\Livewire\Admin\ParkingIncidents::class)->name('parking-incidents');
        Route::get('/parking-incidents/export', function () {
            $filename = 'parking-incidents-'.now()->format('Y-m-d-His').'.xlsx';

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\ParkingIncidentsExport,
                $filename,
                \Maatwebsite\Excel\Excel::XLSX
            );
        })->name('parking-incidents.export');
        Route::get('/toolbox-feedback', App\Livewire\Admin\ToolboxFeedbackAdmin::class)->name('toolbox-feedback');
        Route::get('/toolbox-feedback/export', function () {
            $filename = 'toolbox-feedback-'.now()->format('Y-m-d-His').'.xlsx';

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\ToolboxFeedbackExport,
                $filename,
                \Maatwebsite\Excel\Excel::XLSX
            );
        })->name('toolbox-feedback.export');
        Route::get('/lessons-learned', App\Livewire\Admin\LessonsLearned::class)->name('lessons-learned');
        Route::get('/parking-qr-codes', App\Livewire\Admin\GenericParkingQrCodes::class)->name('parking-qr-codes');
        Route::get('/parking-qr-codes/print-walk-in', function () {
            return view('admin.print-walk-in-qr');
        })->name('parking-qr-codes.print-walk-in');
        Route::get('/routes-list', App\Livewire\Admin\PublicRoutesList::class)->name('routes-list');
    });

});
