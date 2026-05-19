<?php

return [
    'title' => 'Parking Registration',
    'subtitle' => 'Please fill in your details to register your vehicle.',
    'congregation_portal_link' => 'Congregation portal',
    'congregation_portal_hint' => 'Already registered? View or correct your congregation\'s entries.',
    'co_title' => 'Circuit Overseer Parking Registration',
    'co_subtitle' => 'Please fill in your details. Congregation is not required.',
    'registration_complete' => 'Registration Complete!',
    'co_registration_complete' => 'Circuit Overseer registration complete!',
    'thank_you' => 'Thank you for registering. You can now close this page.',
    'register_another' => 'Register another vehicle',
    'car_or_coach' => 'Is this for a Car or a Coach?',
    'car' => 'Car',
    'coach' => 'Coach',

    // Coach captain explainer (shown when Coach is selected)
    'coach_captain_intro_title' => 'Coach Captain Details',
    'coach_captain_intro_body' => 'The coach details (size, sharing arrangements, and any congregations sharing this coach) you provided in the parking survey are already on file. Please give us the coach captain\'s contact details below so we can reach you about the parking arrangements.',
    'coach_captain_name' => 'Coach Captain Name',
    'coach_captain_contact_number' => 'Coach Captain Contact Number',
    'coach_captain_email_address' => 'Coach Captain Email Address',

    // Coach captain "to be assigned" toggle and the secretary-as-stand-in labels it swaps to.
    'coach_captain_to_be_assigned' => 'Coach captain to be assigned',
    'coach_captain_to_be_assigned_help' => 'Tick this if the congregation has not yet appointed a coach captain. We will use the congregation secretary\'s details below as a temporary point of contact.',
    'coach_captain_intro_body_tba' => 'The coach details (size, sharing arrangements, and any congregations sharing this coach) you provided in the parking survey are already on file. While a coach captain is being appointed, please give us the congregation secretary\'s contact details below so we can reach the congregation about the parking arrangements.',
    'secretary_name' => 'Secretary Name',
    'secretary_contact_number' => 'Secretary Contact Number',
    'secretary_email_address' => 'Secretary Email Address',

    'congregation_code' => 'Please enter your congregation code',
    'congregation_code_placeholder' => 'e.g. 9d4e2a1b-...',
    'congregation_label' => 'Congregation',
    'no_congregation_found' => 'No congregation found for this code.',
    'invalid_congregation_code' => 'Please enter a valid congregation code.',

    'full_name' => 'Full Name',
    'full_name_placeholder' => 'e.g. John Doe',
    'contact_number' => 'Contact Number',
    'contact_placeholder' => '07123 456789',
    'email_address' => 'Email Address',
    'email_placeholder' => 'john@example.com',
    'vehicle_registration' => 'Vehicle Registration',
    'vehicle_reg_placeholder' => 'AB12 CDE',
    'elderly_infirm' => 'Do you need parking for Elderly and Infirm?',
    'yes' => 'Yes',
    'no' => 'No',
    'attending_days' => 'Attending Days',
    'select_all_days' => 'Select all days',
    'friday' => 'Friday',
    'saturday' => 'Saturday',
    'sunday' => 'Sunday',
    'submit' => 'Submit Registration',
    'co_submit' => 'Submit Circuit Overseer Registration',
    'processing' => 'Processing...',
    'footer' => 'Convention Parking System',

    // Quota: proactive UI when congregation code is entered; same messages used at submit where relevant
    'quota_preview_allocation_full' => 'Every parking allocation for :congregation from the congregation survey is already registered. You cannot register another vehicle here.',

    // Server-side quota errors (submit validation)
    'quota_no_survey' => 'Your congregation has not submitted the parking survey yet, so no allocation has been set. Please ask your congregation coordinator to complete the parking survey first, then return here to register.',
    'quota_car_full' => 'All :limit car parking tickets allocated to :congregation have already been registered.',
    'quota_disabled_full' => 'All :limit disabled parking spaces allocated to :congregation have already been registered.',
    'quota_disabled_not_requested' => ':congregation did not request disabled parking in the parking survey, so it cannot be selected here.',
    'quota_coach_not_organised' => 'Your congregation has not registered a coach in the parking survey, so coach registrations are not available.',
    'quota_coach_taken' => 'A coach has already been registered for your congregation.',

    'duplicate_vehicle_registration' => 'This vehicle registration is already registered to :name (:congregation). If that is not you, check the registration number. Otherwise you do not need to register again.',
    'duplicate_vehicle_registration_live_title' => 'This registration number is already on file',
    'duplicate_vehicle_registration_live_body' => 'We already have a registration for this vehicle under the name :name (:congregation). You cannot submit again with the same registration number.',
    'duplicate_email_warning_title' => 'This email is already on file',
    'duplicate_email_warning_body' => 'We already have a registration using this email for :name (:congregation). If you are registering for someone else who shares this email, you can still continue — otherwise you may not need to register again.',
];
