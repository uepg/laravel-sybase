<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * Get package providers.
     *
     * @param Application $app
     *
     * @return array<int, class-string<ServiceProvider>>
     */
    protected function getPackageProviders($app)
    {
        return ['SybaseServiceProvider',];
    }

    /**
     * Ignore package discovery from.
     *
     * @return array<int, string>
     */
    public function ignorePackageDiscoveriesFrom()
    {
        return [];
    }


}
