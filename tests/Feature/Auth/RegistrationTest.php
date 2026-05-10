<?php

test('account registration is disabled', function () {
    $this->get('/register')->assertNotFound();
});
