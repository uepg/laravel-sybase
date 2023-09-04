# Sybase ASE based Eloquent module extension for Laravel 

[![Packagist Version](https://img.shields.io/packagist/v/jcrodriguezt/laravel-sybase.svg)](https://packagist.org/packages/jcrodriguezt/laravel-sybase)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/jcrodriguezt/laravel-sybase.svg)](https://packagist.org/packages/jcrodriguezt/laravel-sybase)
[![Packagist](https://img.shields.io/packagist/dt/jcrodriguezt/laravel-sybase.svg)](https://packagist.org/packages/jcrodriguezt/laravel-sybase/stats)
[![GitHub contributors](https://img.shields.io/github/contributors-anon/jcrodriguezt/laravel-sybase.svg)](https://github.com/jcrodriguezt/laravel-sybase/graphs/contributors)
[![GitHub](https://img.shields.io/github/license/jcrodriguezt/laravel-sybase.svg)](https://github.com/jcrodriguezt/laravel-sybase/blob/master/LICENSE)

* Original codebase https://github.com/uepg/laravel-sybase.
* Use default eloquent: works with odbc and dblib!
* Improvements in delete and insert statements when using array based clauses like whereIn, whereBetween, etc
* Works with MS-SQL connections too via FreeTds
* Support for Laravel versions 7.x, 8.x, 9.x, 10.x


## Install
```
composer require jcrodriguezt/laravel-sybase
```

## Update
Update the following in the require section of your **composer.json**:
```json
"jcrodriguezt/laravel-sybase": "~2.0"
```

Update the package dependencies executing:

```shell
#> composer update
```

## Install

Update your **config/database.php's** default driver with the settings for your **sybase server** or your custom **odbc**. See the following example: (please note the *sybasease* driver name)

```
<?php
...
return [
    ...
    'connections' => [
        ...
        'sybase' => [
            'driver' => 'sybasease',
            'host' => env('DB_HOST', 'sybase.myserver.com'),
            'port' => env('DB_PORT', '5000'),
            //'dsn' => env('DB_DSN'),  // remove comment in case you define an odbc connection in your env
            'database' => env('DB_DATABASE', 'mydatabase'),
            'username' => env('DB_USERNAME', 'user'),
            'password' => env('DB_PASSWORD', 'password'),
            'charset' => 'utf8',
            'prefix' => '',
        ],
        ...
    ],
    ...
]
```

Update your **.env** with the settings for the **sybase** connection. See the following example:

```text
...

DB_CONNECTION=sybase
DB_HOST=sybase.mycompany.com
DB_PORT=5000
#remove comment on next line to use odbc
#DB_DSN="odbc:\\\\sybase_odbc_name"
DB_DATABASE=mydatabase
DB_USERNAME=user
DB_PASSWORD=password

...
```

## Configuration of freetds driver

In Linux systems the driver version must be set in **freetds.conf** file to the right use of charset pages.

The file is usualy found in **/etc/freetds/freetds.conf**. Set the configuration at global section as the following example:

```text
[sybase]
    host = sybase.mycompany.com
    # port is important
    port = 6000
    # TDS protocol version
    tds version = 5.0

[sqlserver]
    host = mssql.mycompany.com
    # When connecting to an instance you specify it, and there's no need for the port directive
    instance = sqlexpress
    tds version = 7.3
    
[sqlserverexpress]
    host = myssqlexpress.mycompany.com
    port = 1433
    tds version = 7.3
```
## Issues
Feel free to ask in https://github.com/jcrodriguezt/laravel-sybase/issues
