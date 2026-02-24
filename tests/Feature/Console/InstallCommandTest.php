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

it('skips migrations when config is disabled', function (): void {
    config()->set('montonio.migrations', false);

    $this->artisan('montonio:install')
        ->assertSuccessful()
        ->expectsOutputToContain('Skipping migrations.')
        ->doesntExpectOutputToContain('php artisan migrate');
});

it('skips migrations with --skip-migrations flag', function (): void {
    $this->artisan('montonio:install --skip-migrations')
        ->assertSuccessful()
        ->expectsOutputToContain('Skipping migrations.')
        ->doesntExpectOutputToContain('php artisan migrate');
});

it('skips sync next step when sync commands are disabled', function (): void {
    config()->set('montonio.sync_commands', false);

    $this->artisan('montonio:install')
        ->doesntExpectOutputToContain('montonio:sync-methods');
});
