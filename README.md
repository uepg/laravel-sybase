# Sybase ASE based Eloquent module extension for Laravel 5.x

[![Packagist Version](https://img.shields.io/packagist/v/uepg/laravel-sybase.svg)](https://packagist.org/packages/uepg/laravel-sybase)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/uepg/laravel-sybase.svg)](https://packagist.org/packages/uepg/laravel-sybase)
[![Packagist](https://img.shields.io/packagist/dt/uepg/laravel-sybase.svg)](https://packagist.org/packages/uepg/laravel-sybase/stats)
[![GitHub contributors](https://img.shields.io/github/contributors-anon/uepg/laravel-sybase.svg)](https://github.com/uepg/laravel-sybase/graphs/contributors)
[![GitHub](https://img.shields.io/github/license/uepg/laravel-sybase.svg)](https://github.com/uepg/laravel-sybase/blob/master/LICENSE)

* Enables use of multiple kinds of fields.
* Use default eloquent: works with odbc and dblib!
* Migrations! (WIP - Work in Progress)

## Install

Add the following in the require section of your **composer.json**:

### Laravel 5.1, 5.2, 5.3

```json
"uepg/laravel-sybase": "~1.0"
```
### Laravel 5.4, 5.5, 5.6, 5.7

```json
"uepg/laravel-sybase": "~2.0"
```

Update the package dependencies executing:

```shell
composer update
```

Add the following entry to your providers array in **config/app.php** file:

```php
Uepg\LaravelSybase\Database\SybaseServiceProvider::class
```

Update your **config/database.php's** default driver with the settings for the **sqlsrv** or your custom odbc. See the following example:

```php
<?php

...

return [
    ...

    'connections' => [
        ...

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'host' => env('DB_HOST', 'sybase.myserver.com'),
            'port' => env('DB_PORT', '5000'),
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

Update your **.env** with the settings for the **sqlsrv** or your custom odbc. See the following example:

```text
...

DB_CONNECTION=sqlsrv
DB_HOST=sybase.myserver.com
DB_PORT=5000
DB_DATABASE=mydatabase
DB_USERNAME=user
DB_PASSWORD=password

...
```

## Configuration of freetds driver

In Linux systems the driver version must be set in **freetds.conf** file to the right use of charset pages.

The file is usualy found in **/etc/freetds/freetds.conf**. Set the configuration at global section as the following example:

```text
[global]
    # TDS protocol version
    tds version = 5.0
```

## Setting to use numeric data type

In the migration file you must replace `use Illuminate\Database\Schema\Blueprint;` with `use Uepg\LaravelSybase\Database\Schema\Blueprint;`. See the following example:

```php
<?php

use Illuminate\Support\Facades\Schema;
// use Illuminate\Database\Schema\Blueprint;
use Uepg\LaravelSybase\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('table_name', function (Blueprint $table) {
            $table->numeric('column_name', length, autoIncrement);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('table_name');
    }
}
```
