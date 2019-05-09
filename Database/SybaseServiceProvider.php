<?php

namespace Uepg\LaravelSybase\Database;

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
