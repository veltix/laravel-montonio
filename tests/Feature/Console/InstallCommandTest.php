<?php

declare(strict_types=1);

it('exits with success and contains installed message', function (): void {
    $this->artisan('montonio:install')
        ->assertSuccessful()
        ->expectsOutputToContain('installed successfully');
});

it('outputs next steps', function (): void {
    $this->artisan('montonio:install')
        ->expectsOutputToContain('Next steps');
});
