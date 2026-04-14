<?php

it('renders checkout address form with email and russian phone input masks', function () {
    $rendered = view('shop::checkout.onepage.address.form')->render();

    expect($rendered)
        ->toContain('data-mask-email="true"')
        ->toContain('data-mask-phone-ru="true"');
});

it('renders checkout login form with email input mask', function () {
    $rendered = view('shop::checkout.login')->render();

    expect($rendered)->toContain('data-mask-email="true"');
});
