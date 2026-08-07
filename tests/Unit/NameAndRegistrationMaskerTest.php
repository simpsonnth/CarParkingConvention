<?php

declare(strict_types=1);

use App\Support\PersonNameMasker;
use App\Support\VehicleRegistrationMasker;

test('person name masker hides middle characters of each word', function () {
    expect(PersonNameMasker::mask('Nathan Simpson'))->toBe('N****n S*****n')
        ->and(PersonNameMasker::mask('Jo'))->toBe('J*')
        ->and(PersonNameMasker::mask('A'))->toBe('A')
        ->and(PersonNameMasker::mask(''))->toBe('');
});

test('vehicle registration masker lightly hides middle characters', function () {
    expect(VehicleRegistrationMasker::mask('HG12ABC'))->toBe('HG1*ABC')
        ->and(VehicleRegistrationMasker::mask('AB12 CDE'))->toBe('AB1*CDE')
        ->and(VehicleRegistrationMasker::mask('AB12'))->toBe('AB12')
        ->and(VehicleRegistrationMasker::mask('AB'))->toBe('AB');
});
