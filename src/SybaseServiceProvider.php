<?php

namespace Uepg\LaravelSybase;

use Illuminate\Database\Connection as IlluminateConnection;
use Illuminate\Support\ServiceProvider;
use Uepg\LaravelSybase\Database\Connection as SybaseConnection;
use Uepg\LaravelSybase\Database\Connector;

class SybaseServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        IlluminateConnection::resolverFor('sybasease', function (
            $connection,
            $database,
            $prefix,
            $config
        ) {
            return new SybaseConnection(
                $connection,
                $database,
                $prefix,
                $config
            );
        });

        $this->app->bind('db.connector.sybasease', function ($app) {
            return new Connector;
        });
    }
}
