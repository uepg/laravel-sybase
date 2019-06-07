<?php

namespace Uepg\LaravelSybase;

use Illuminate\Support\ServiceProvider;
use Uepg\LaravelSybase\Database\Connector;
use Illuminate\Database\Connection as IlluminateConnection;
use Uepg\LaravelSybase\Database\Connection as SybaseConnection;

class SybaseServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        IlluminateConnection::resolverFor('sqlsrv', function (
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

        $this->app->bind('db.connector.sqlsrv', function ($app) {
            return new Connector();
        });

        // $this->app->bind('db.connection.sqlsrv', function (
        //     $app,
        //     $parameters
        // ) {
        //     list($connection, $database, $prefix, $config) = $parameters;
        //     return new SybaseConnection(
        //         $connection,
        //         $database,
        //         $prefix,
        //         $config
        //     );
        // });
    }
}
