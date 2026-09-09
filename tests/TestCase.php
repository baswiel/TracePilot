<?php

namespace Tests;

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function asInertiaRequest(): static
    {
        return $this
            ->withHeader('X-Inertia', 'true')
            ->withHeader(
                'X-Inertia-Version',
                app(HandleInertiaRequests::class)->version(request()),
            );
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
