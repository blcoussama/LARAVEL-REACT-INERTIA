<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests render the Inertia root view: they must not depend on
        // a front-end build (public/build/manifest.json).
        $this->withoutVite();
    }
}
