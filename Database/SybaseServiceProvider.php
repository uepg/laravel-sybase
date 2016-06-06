<?php namespace Uepg\LaravelSybase\Database;

use Uepg\LaravelSybase\Database\SybaseConnection;
use Illuminate\Support\ServiceProvider;

class SybaseServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('db.connection.sqlsrv', function ($app, $parameters) {
            list($connection, $database, $prefix, $config) = $parameters;
            return new SybaseConnection($connection, $database, $prefix, $config);
        });
    }
}