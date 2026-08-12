<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/car-park-capacities', App\Livewire\Public\CarParkCapacities::class)
    ->name('parking.capacities');

Route::post('/webhooks/resend', App\Http\Controllers\ResendWebhookController::class)
    ->name('webhooks.resend');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified', 'permission:dashboard.view'])
    ->name('dashboard');

Route::get('/scan/ticket/{registration}', App\Livewire\Attendant\Scan::class)
    ->middleware(['auth', 'permission:scan.access'])
    ->name('attendant.scan.ticket');
Route::get('/scan/walk-in/coach', App\Livewire\Attendant\Scan::class)
    ->middleware(['auth', 'permission:scan.access'])
    ->name('attendant.scan.walk-in.coach');
Route::get('/scan/walk-in', App\Livewire\Attendant\Scan::class)
    ->middleware(['auth', 'permission:scan.access'])
    ->name('attendant.scan.walk-in');
Route::get('/scan/{code?}', App\Livewire\Attendant\Scan::class)
    ->middleware(['auth', 'permission:scan.access'])
    ->name('attendant.scan');
Route::get('/toolbox-talk', App\Livewire\Attendant\ToolboxTalkPicker::class)
    ->middleware(['auth', 'permission:scan.access'])
    ->name('attendant.toolbox-talk');
Route::get('/toolbox-talk/present/{date}/{carPark?}', App\Livewire\Attendant\ToolboxTalkPresent::class)
    ->middleware(['auth', 'permission:scan.access'])
    ->name('attendant.toolbox-talk.present');

Route::middleware('public.route')->group(function () {
    Route::get('/parking-registration', App\Livewire\Public\Register::class)->name('parking.register');
    Route::get('/register-simple', App\Livewire\Public\CongregationNumbers::class)->name('parking.register-simple');
    Route::get('/register-circuit-overseer', App\Livewire\Public\CircuitOverseerRegister::class)->name('parking.register-circuit-overseer');
    Route::get('/congregation-portal', App\Livewire\Public\CongregationPortal::class)->name('parking.congregation-portal');
    Route::get('/congregation-portal/download-ticket/{token}', function (string $token) {
        @set_time_limit(120);

        $auth = app(\App\Services\CongregationPortalAuth::class);
        $congregation = $auth->authenticatedCongregation();
        if ($congregation === null) {
            return redirect()->route('parking.congregation-portal')
                ->with('error', __('congregation_portal.download_login_required'));
        }

        $cacheKey = 'portal-pass-pdf:'.$token;
        $payload = cache()->pull($cacheKey);
        if (! is_array($payload)
            || ! isset($payload['registration_id'], $payload['congregation_id'])
            || (int) $payload['congregation_id'] !== (int) $congregation->id
        ) {
            return redirect()->route('parking.congregation-portal')
                ->with('error', __('congregation_portal.download_link_expired'));
        }

        $label = trim((string) $congregation->name);
        $registration = \App\Models\ParkingRegistration::query()
            ->with('carPark')
            ->whereKey((int) $payload['registration_id'])
            ->whereRaw('TRIM(congregation) = ?', [$label])
            ->where('is_circuit_overseer', false)
            ->first();

        if ($registration === null) {
            return redirect()->route('parking.congregation-portal')
                ->with('error', __('congregation_portal.download_not_found'));
        }

        try {
            $generator = app(\App\Services\MasterPassPdfGenerator::class);
            $usedNames = [];
            $filename = $generator->uniquePersonFilename($registration, $usedNames);
            $content = $generator->generatePdf($registration);

            return response()->streamDownload(
                static function () use ($content): void {
                    echo $content;
                },
                $filename,
                ['Content-Type' => 'application/pdf']
            );
        } catch (\Throwable $e) {
            return redirect()->route('parking.congregation-portal')
                ->with('error', $e->getMessage());
        }
    })->name('parking.congregation-portal.download-ticket');
    Route::middleware('throttle:10,1')->group(function () {
        Route::get('/parking-incidents', App\Livewire\Public\ParkingIncidentReport::class)->name('management.parking-incidents');
        Route::get('/toolbox-feedback', App\Livewire\Public\ToolboxFeedback::class)->name('management.toolbox-feedback');
        Route::get('/lessons-learned', App\Livewire\Public\LessonLearned::class)->name('management.lessons-learned');
        Route::get('/ticket-change-request', App\Livewire\Public\TicketChangeRequest::class)->name('management.ticket-change-request');
        Route::get('/radisson-guest-parking', App\Livewire\Public\RadissonGuestParking::class)->name('management.radisson-guest-parking');
        Route::get('/radisson-parking-check', App\Livewire\Public\RadissonParkingCheck::class)->name('management.radisson-parking-check');
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

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', App\Livewire\Admin\Dashboard::class)
            ->middleware('permission:dashboard.view')
            ->name('dashboard');
        Route::get('/parks', App\Livewire\Admin\CarParks::class)
            ->middleware('permission:car-parks.view')
            ->name('car-parks');
        Route::get('/parks/{carPark}', App\Livewire\Admin\CarParkDetail::class)
            ->middleware('permission:car-parks.view')
            ->name('car-parks.show');
        Route::get('/congregations', App\Livewire\Admin\Congregations::class)
            ->middleware('permission:congregations.view')
            ->name('congregations');
        Route::get('/congregations/{congregation}', App\Livewire\Admin\CongregationDetail::class)
            ->middleware('permission:congregations.view')
            ->name('congregations.show');
        Route::get('/congregations/{congregation}/print', function (App\Models\Congregation $congregation) {
            return view('admin.print-pass', ['congregation' => $congregation]);
        })
            ->middleware('permission:congregations.view')
            ->name('congregations.print');
        Route::get('/users', App\Livewire\Admin\Users::class)
            ->middleware('permission:users.manage')
            ->name('users');
        Route::get('/registrations', App\Livewire\Admin\Registrations::class)
            ->middleware('permission:registrations.view')
            ->name('registrations');
        Route::get('/extras', App\Livewire\Admin\Extras::class)
            ->middleware('permission:extras.view')
            ->name('extras');
        Route::get('/coaches', App\Livewire\Admin\Coaches::class)
            ->middleware('permission:coaches.view')
            ->name('coaches');
        Route::get('/coaches/export', function () {
            $filename = 'coaches-'.now()->format('Y-m-d-His').'.xlsx';

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\CoachesExport,
                $filename,
                \Maatwebsite\Excel\Excel::XLSX
            );
        })
            ->middleware('permission:coaches.export')
            ->name('coaches.export');
        Route::get('/registrations/attendance-by-day', App\Livewire\Admin\RegistrationAttendanceByDayReport::class)
            ->middleware('permission:reports.view')
            ->name('registrations.attendance-by-day');
        Route::get('/congregation-numbers', App\Livewire\Admin\CongregationNumbers::class)
            ->middleware('permission:congregation-numbers.view')
            ->name('congregation-numbers');
        Route::get('/congregation-numbers/export-missing', function () {
            $filename = 'register-simple-not-submitted-'.now()->format('Y-m-d-His').'.xlsx';

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\CongregationsMissingNumbersExport,
                $filename,
                \Maatwebsite\Excel\Excel::XLSX
            );
        })
            ->middleware('permission:congregation-numbers.view')
            ->name('congregation-numbers.export-missing');
        Route::get('/congregation-numbers/export', function () {
            $filename = 'register-simple-all-responses-'.now()->format('Y-m-d-His').'.xlsx';

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\CongregationNumbersResponsesExport,
                $filename,
                \Maatwebsite\Excel\Excel::XLSX
            );
        })
            ->middleware('permission:congregation-numbers.view')
            ->name('congregation-numbers.export');
        Route::get('/congregation-numbers/trash', App\Livewire\Admin\CongregationNumbersTrash::class)
            ->middleware('permission:congregation-numbers.manage')
            ->name('congregation-numbers.trash');
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
        })
            ->middleware('permission:registrations.print')
            ->name('registrations.print');
        Route::get('/registrations/trash', App\Livewire\Admin\RegistrationsTrash::class)
            ->middleware('permission:registrations.manage')
            ->name('registrations.trash');
        Route::get('/registrations/download-master-passes-zip/{token}', function (string $token) {
            // Bulk Chrome PDF generation for large selections can take several minutes.
            @set_time_limit(300);
            ini_set('max_execution_time', '300');

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
        })
            ->middleware('permission:registrations.print')
            ->name('registrations.download-passes-zip');
        Route::get('/registrations/download-master-pass-pdf/{token}', function (string $token) {
            @set_time_limit(120);
            ini_set('max_execution_time', '120');

            $cacheKey = 'master-pass-pdf:'.$token;
            $registrationId = cache()->pull($cacheKey);
            if (! is_numeric($registrationId)) {
                return redirect()->route('admin.registrations')
                    ->with('error', __('registrations.download_link_expired'));
            }

            try {
                $generator = app(\App\Services\MasterPassPdfGenerator::class);
                $attachments = $generator->generateForIds([(int) $registrationId]);
                $attachment = $attachments[0];

                return response()->streamDownload(
                    static function () use ($attachment): void {
                        echo $attachment['content'];
                    },
                    $attachment['filename'],
                    ['Content-Type' => 'application/pdf']
                );
            } catch (\Throwable $e) {
                return redirect()->route('admin.registrations')
                    ->with('error', $e->getMessage());
            }
        })
            ->middleware('permission:registrations.print')
            ->name('registrations.download-pass-pdf');
        Route::get('/registrations/export', function (\App\Http\Requests\Admin\ExportParkingRegistrationsRequest $request) {
            $filters = $request->filters();
            $suffix = $filters->hasActiveConstraints() ? 'filtered' : 'all';
            $filename = 'parking-registrations-'.$suffix.'-'.now()->format('Y-m-d-His').'.xlsx';

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\ParkingRegistrationsExport($filters),
                $filename,
                \Maatwebsite\Excel\Excel::XLSX
            );
        })
            ->middleware('permission:registrations.export')
            ->name('registrations.export');
        Route::get('/settings', App\Livewire\Admin\Settings::class)
            ->middleware('permission:settings.manage')
            ->name('settings');
        Route::get('/reports', App\Livewire\Admin\Reports::class)
            ->middleware('permission:reports.view')
            ->name('reports');
        Route::get('/survey-vs-registrations', App\Livewire\Admin\SurveyVsRegistrationReport::class)
            ->middleware('permission:reports.view')
            ->name('survey-vs-registrations');
        Route::get('/circuit-overseer-parking', App\Livewire\Admin\CircuitOverseerParking::class)
            ->middleware('permission:reports.view')
            ->name('circuit-overseer-parking');
        Route::get('/parking-incidents', App\Livewire\Admin\ParkingIncidents::class)
            ->middleware('permission:parking-incidents.view')
            ->name('parking-incidents');
        Route::get('/parking-incidents/export', function () {
            $filename = 'parking-incidents-'.now()->format('Y-m-d-His').'.xlsx';

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\ParkingIncidentsExport,
                $filename,
                \Maatwebsite\Excel\Excel::XLSX
            );
        })
            ->middleware('permission:parking-incidents.view')
            ->name('parking-incidents.export');
        Route::get('/toolbox-feedback', App\Livewire\Admin\ToolboxFeedbackAdmin::class)
            ->middleware('permission:toolbox-feedback.view')
            ->name('toolbox-feedback');
        Route::get('/toolbox-feedback/export', function () {
            $filename = 'toolbox-feedback-'.now()->format('Y-m-d-His').'.xlsx';

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\ToolboxFeedbackExport,
                $filename,
                \Maatwebsite\Excel\Excel::XLSX
            );
        })
            ->middleware('permission:toolbox-feedback.view')
            ->name('toolbox-feedback.export');
        Route::get('/toolbox-talks', App\Livewire\Admin\ToolboxTalks::class)
            ->middleware('permission:toolbox-talks.view')
            ->name('toolbox-talks');
        Route::get('/toolbox-talks/download-pptx/{date}/{carPark?}', App\Http\Controllers\Admin\ToolboxTalkPptxDownloadController::class)
            ->middleware('permission:toolbox-talks.view')
            ->name('toolbox-talks.download-pptx');
        Route::get('/lessons-learned', App\Livewire\Admin\LessonsLearned::class)
            ->middleware('permission:lessons-learned.view')
            ->name('lessons-learned');
        Route::get('/lessons-learned/attachments/{attachment}/download', App\Http\Controllers\Admin\LessonLearnedAttachmentDownloadController::class)
            ->middleware('permission:lessons-learned.view')
            ->name('lessons-learned.attachments.download');
        Route::get('/lessons-learned/attachments/{attachment}/stream', App\Http\Controllers\Admin\LessonLearnedAttachmentStreamController::class)
            ->middleware('permission:lessons-learned.view')
            ->name('lessons-learned.attachments.stream');
        Route::get('/ticket-change-requests', App\Livewire\Admin\TicketChangeRequests::class)
            ->middleware('permission:ticket-change-requests.view')
            ->name('ticket-change-requests');
        Route::get('/ticket-change-requests/{ticketChangeRequest}', App\Livewire\Admin\TicketChangeRequestDetail::class)
            ->middleware('permission:ticket-change-requests.view')
            ->name('ticket-change-requests.show');
        Route::get('/hotel-guest-parking', App\Livewire\Admin\HotelGuestParkingRequests::class)
            ->middleware('permission:hotel-guest-parking.view')
            ->name('hotel-guest-parking');
        Route::get('/hotel-guest-parking/{hotelGuestParkingRequest}', App\Livewire\Admin\HotelGuestParkingRequestDetail::class)
            ->middleware('permission:hotel-guest-parking.view')
            ->name('hotel-guest-parking.show');
        Route::get('/outbound-emails', App\Livewire\Admin\OutboundEmails::class)
            ->middleware('permission:outbound-emails.view')
            ->name('outbound-emails');
        Route::get('/parking-qr-codes', App\Livewire\Admin\GenericParkingQrCodes::class)
            ->middleware('permission:parking-qr.view')
            ->name('parking-qr-codes');
        Route::get('/parking-qr-codes/print-walk-in', function () {
            return view('admin.print-walk-in-qr', ['walkInType' => 'car']);
        })
            ->middleware('permission:parking-qr.view')
            ->name('parking-qr-codes.print-walk-in');
        Route::get('/parking-qr-codes/print-walk-in-coach', function () {
            return view('admin.print-walk-in-qr', ['walkInType' => 'coach']);
        })
            ->middleware('permission:parking-qr.view')
            ->name('parking-qr-codes.print-walk-in-coach');
        Route::get('/parking-qr-codes/print-guest/{carPark}', function (App\Models\CarPark $carPark) {
            return view('admin.print-guest-handout', ['carPark' => $carPark]);
        })
            ->middleware('permission:parking-qr.view')
            ->name('parking-qr-codes.print-guest');
        Route::get('/parking-qr-codes/print-radisson-info', function () {
            return view('admin.print-radisson-info-sheet');
        })
            ->middleware('permission:parking-qr.view')
            ->name('parking-qr-codes.print-radisson-info');
        Route::get('/routes-list', App\Livewire\Admin\PublicRoutesList::class)
            ->middleware('permission:routes.view')
            ->name('routes-list');
    });

});
