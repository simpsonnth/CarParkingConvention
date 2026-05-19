<?php

test('login page shows session expired message when query param is set', function () {
    $response = $this->get(route('login', ['session_expired' => 1]));

    $response->assertOk();
    $response->assertSee(__('auth.session_expired'), false);
});

test('login page shows session expired flash from redirect', function () {
    $response = $this->withSession(['status' => __('auth.session_expired')])
        ->get(route('login', ['session_expired' => 1]));

    $response->assertOk();
    $response->assertSee(__('auth.session_expired'), false);
});

test('419 error page offers sign in link', function () {
    $html = view('errors.419')->render();

    expect($html)->toContain(__('errors.419_login'));
});
