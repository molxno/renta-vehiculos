<?php

declare(strict_types=1);

test('trusts x-forwarded-proto so guest redirects stay on https behind a proxy', function () {
    $response = $this
        ->withServerVariables([
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.10',
            'HTTP_X_FORWARDED_HOST' => 'demo.onrender.com',
            'HTTP_X_FORWARDED_PORT' => '443',
        ])
        ->get('/cuenta');

    $response->assertRedirect();
    expect((string) $response->headers->get('Location'))->toStartWith('https://');
});

test('does not force the https scheme outside production', function () {
    expect(app()->isProduction())->toBeFalse()
        ->and(url('/up'))->toStartWith('http://');
});
