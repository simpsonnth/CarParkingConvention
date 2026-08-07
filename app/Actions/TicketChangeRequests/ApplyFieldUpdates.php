<?php

declare(strict_types=1);

namespace App\Actions\TicketChangeRequests;

use App\Models\ParkingRegistration;
use App\Models\TicketChangeRequest;
use App\Support\DeferredTicketMail;
use App\Support\VehicleRegistrationNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplyFieldUpdates
{
    /**
     * Apply field updates from a pending field_update request, mark completed, email ticket.
     */
    public function execute(TicketChangeRequest $request, bool $sendEmail = true): TicketChangeRequest
    {
        if ($request->request_type !== TicketChangeRequest::TYPE_FIELD_UPDATE) {
            throw ValidationException::withMessages([
                'request_type' => 'Only field update requests can be auto-applied.',
            ]);
        }

        if ($request->parking_registration_id === null) {
            throw ValidationException::withMessages([
                'parking_registration_id' => 'A registration is required for field updates.',
            ]);
        }

        $payload = is_array($request->payload) ? $request->payload : [];
        $changes = is_array($payload['changes'] ?? null) ? $payload['changes'] : [];

        if ($changes === []) {
            throw ValidationException::withMessages([
                'changes' => __('ticket_change_request.validation.changes_required'),
            ]);
        }

        $request = DB::transaction(function () use ($request, $changes): TicketChangeRequest {
            /** @var ParkingRegistration $registration */
            $registration = ParkingRegistration::query()
                ->whereKey($request->parking_registration_id)
                ->lockForUpdate()
                ->firstOrFail();

            $before = [];
            $updates = [];

            foreach (TicketChangeRequest::AUTO_APPLY_FIELDS as $field) {
                if (! array_key_exists($field, $changes)) {
                    continue;
                }

                $newValue = $changes[$field];
                if ($field === 'vehicle_registration') {
                    $vehicleType = (string) ($changes['vehicle_type'] ?? $registration->vehicle_type ?? 'car');
                    $newValue = VehicleRegistrationNormalizer::normalize(
                        is_string($newValue) ? $newValue : null,
                        $vehicleType
                    );
                } elseif (is_string($newValue)) {
                    $newValue = trim($newValue);
                }

                $before[$field] = $registration->{$field};
                $updates[$field] = $newValue;
            }

            if ($updates === []) {
                throw ValidationException::withMessages([
                    'changes' => __('ticket_change_request.validation.changes_required'),
                ]);
            }

            $registration->update($updates);

            $request->update([
                'before_snapshot' => $before,
                'name' => (string) ($updates['name'] ?? $registration->name),
                'status' => TicketChangeRequest::STATUS_COMPLETED,
                'actioned_at' => now(),
                'actioned_by' => null,
            ]);

            return $request->fresh() ?? $request;
        });

        if ($sendEmail && filled($request->notification_email) && $request->parking_registration_id !== null) {
            DeferredTicketMail::sendCarParkTickets(
                [$request->parking_registration_id],
                (string) $request->notification_email,
            );
        }

        return $request;
    }
}
