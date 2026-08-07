<?php

declare(strict_types=1);

namespace App\Actions\TicketChangeRequests;

use App\Models\CarPark;
use App\Models\ParkingRegistration;
use App\Models\TicketChangeRequest;
use App\Models\User;
use App\Support\DeferredTicketMail;
use App\Support\VehicleRegistrationNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveTicketChangeRequest
{
    public function execute(
        TicketChangeRequest $request,
        User $actor,
        ?int $carParkId = null,
        ?string $adminNotes = null,
    ): TicketChangeRequest {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'approve' => __('management.ticket_change_requests.already_completed'),
            ]);
        }

        if (! $request->requiresApproval()) {
            throw ValidationException::withMessages([
                'approve' => __('management.ticket_change_requests.approve_not_required'),
            ]);
        }

        return match ($request->request_type) {
            TicketChangeRequest::TYPE_CANCELLATION => $this->approveCancellation($request, $actor, $adminNotes),
            TicketChangeRequest::TYPE_CAR_PARK_CHANGE => $this->approveCarParkChange($request, $actor, $carParkId, $adminNotes),
            TicketChangeRequest::TYPE_ADDITION => $this->approveAddition($request, $actor, $carParkId, $adminNotes),
            default => throw ValidationException::withMessages([
                'approve' => __('management.ticket_change_requests.approve_not_required'),
            ]),
        };
    }

    private function approveCancellation(
        TicketChangeRequest $request,
        User $actor,
        ?string $adminNotes,
    ): TicketChangeRequest {
        $result = DB::transaction(function () use ($request, $actor, $adminNotes): array {
            /** @var TicketChangeRequest $locked */
            $locked = TicketChangeRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw ValidationException::withMessages([
                    'approve' => __('management.ticket_change_requests.already_completed'),
                ]);
            }

            /** @var ParkingRegistration|null $registration */
            $registration = ParkingRegistration::query()
                ->whereKey($locked->parking_registration_id)
                ->lockForUpdate()
                ->first();

            if ($registration === null) {
                throw ValidationException::withMessages([
                    'approve' => __('management.ticket_change_requests.registration_missing'),
                ]);
            }

            $ticketNumber = $registration->ticketNumber();
            $congregation = (string) $registration->congregation;
            $driverName = (string) $registration->name;

            $registration->update(['cancelled_via' => 'change_request']);
            $registration->delete();

            $locked->update([
                'status' => TicketChangeRequest::STATUS_COMPLETED,
                'actioned_at' => now(),
                'actioned_by' => $actor->id,
                'admin_notes' => $this->resolveAdminNotes($adminNotes, $locked),
            ]);

            return [
                'request' => $locked->fresh() ?? $locked,
                'ticketNumber' => $ticketNumber,
                'congregation' => $congregation,
                'driverName' => $driverName,
            ];
        });

        /** @var TicketChangeRequest $completed */
        $completed = $result['request'];

        if (filled($completed->notification_email)) {
            DeferredTicketMail::sendCancellation(
                toEmail: (string) $completed->notification_email,
                ticketNumber: $result['ticketNumber'],
                congregation: $result['congregation'],
                driverName: $result['driverName'],
            );
        }

        return $completed;
    }

    private function approveCarParkChange(
        TicketChangeRequest $request,
        User $actor,
        ?int $carParkId,
        ?string $adminNotes,
    ): TicketChangeRequest {
        if ($carParkId === null || ! CarPark::query()->whereKey($carParkId)->exists()) {
            throw ValidationException::withMessages([
                'approveCarParkId' => __('management.ticket_change_requests.car_park_required'),
            ]);
        }

        $completed = DB::transaction(function () use ($request, $actor, $carParkId, $adminNotes): TicketChangeRequest {
            /** @var TicketChangeRequest $locked */
            $locked = TicketChangeRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw ValidationException::withMessages([
                    'approve' => __('management.ticket_change_requests.already_completed'),
                ]);
            }

            /** @var ParkingRegistration $registration */
            $registration = ParkingRegistration::query()
                ->whereKey($locked->parking_registration_id)
                ->lockForUpdate()
                ->firstOrFail();

            $before = ['car_park_id' => $registration->car_park_id];
            $registration->update(['car_park_id' => $carParkId]);

            $payload = is_array($locked->payload) ? $locked->payload : [];
            $payload['approved_car_park_id'] = $carParkId;

            $locked->update([
                'before_snapshot' => $before,
                'payload' => $payload,
                'status' => TicketChangeRequest::STATUS_COMPLETED,
                'actioned_at' => now(),
                'actioned_by' => $actor->id,
                'admin_notes' => $this->resolveAdminNotes($adminNotes, $locked),
            ]);

            return $locked->fresh() ?? $locked;
        });

        if (filled($completed->notification_email) && $completed->parking_registration_id !== null) {
            DeferredTicketMail::sendCarParkTickets(
                [$completed->parking_registration_id],
                (string) $completed->notification_email,
            );
        }

        return $completed;
    }

    private function approveAddition(
        TicketChangeRequest $request,
        User $actor,
        ?int $carParkId,
        ?string $adminNotes,
    ): TicketChangeRequest {
        if ($carParkId === null || ! CarPark::query()->whereKey($carParkId)->exists()) {
            throw ValidationException::withMessages([
                'approveCarParkId' => __('management.ticket_change_requests.car_park_required'),
            ]);
        }

        $completed = DB::transaction(function () use ($request, $actor, $carParkId, $adminNotes): TicketChangeRequest {
            /** @var TicketChangeRequest $locked */
            $locked = TicketChangeRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw ValidationException::withMessages([
                    'approve' => __('management.ticket_change_requests.already_completed'),
                ]);
            }

            $payload = is_array($locked->payload) ? $locked->payload : [];
            $addition = is_array($payload['addition'] ?? null) ? $payload['addition'] : [];

            $vehicleType = (string) ($addition['vehicle_type'] ?? 'car');
            $vehicleReg = VehicleRegistrationNormalizer::normalize(
                isset($addition['vehicle_registration']) ? (string) $addition['vehicle_registration'] : null,
                $vehicleType,
            );

            $days = is_array($addition['days'] ?? null) ? $addition['days'] : [];

            $registration = ParkingRegistration::query()->create([
                'name' => (string) ($addition['name'] ?? $locked->name),
                'congregation' => $locked->congregation,
                'car_park_id' => $carParkId,
                'vehicle_type' => $vehicleType,
                'vehicle_registration' => $vehicleReg,
                'contact_number' => $addition['contact_number'] ?? null,
                'email' => $addition['email'] ?? $locked->notification_email,
                'days' => $days,
                'elderly_infirm_parking' => (bool) ($addition['elderly_infirm_parking'] ?? false),
                'sharing_with_other_congregations' => false,
                'sharing_congregations_notes' => null,
                'coach_captain_to_be_assigned' => false,
                'is_circuit_overseer' => false,
            ]);

            $payload['approved_car_park_id'] = $carParkId;
            $payload['created_parking_registration_id'] = $registration->id;

            $locked->update([
                'parking_registration_id' => $registration->id,
                'payload' => $payload,
                'name' => $registration->name,
                'status' => TicketChangeRequest::STATUS_COMPLETED,
                'actioned_at' => now(),
                'actioned_by' => $actor->id,
                'admin_notes' => $this->resolveAdminNotes($adminNotes, $locked),
            ]);

            return $locked->fresh() ?? $locked;
        });

        if (filled($completed->notification_email) && $completed->parking_registration_id !== null) {
            DeferredTicketMail::sendCarParkTickets(
                [$completed->parking_registration_id],
                (string) $completed->notification_email,
            );
        }

        return $completed;
    }

    private function resolveAdminNotes(?string $adminNotes, TicketChangeRequest $request): ?string
    {
        if ($adminNotes === null) {
            return $request->admin_notes;
        }

        $trimmed = trim($adminNotes);

        return $trimmed !== '' ? $trimmed : null;
    }
}
