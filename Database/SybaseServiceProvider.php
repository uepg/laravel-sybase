<?php namespace Uepg\LaravelSybase\Database;

use Uepg\LaravelSybase\Database\SybaseConnection;
use Uepg\LaravelSybase\Database\SybaseConnector;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Connection;

class SybaseServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {

        Connection::resolverFor('sqlsrv', function ($connection, $database, $prefix, $config) {
            return new SybaseConnection($connection, $database, $prefix, $config);
        });
        
        $this->app->bind('db.connector.sqlsrv', function ($app) {
            return new SybaseConnector();
        });

        // $this->app->bind('db.connection.sqlsrv', function ($app, $parameters) {
        //     list($connection, $database, $prefix, $config) = $parameters;
        //     return new SybaseConnection($connection, $database, $prefix, $config);
        // });
    }
}