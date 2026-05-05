# Sybase ASE based Eloquent module extension for Laravel 

[![Packagist Version](https://img.shields.io/packagist/v/uepg/laravel-sybase.svg)](https://packagist.org/packages/uepg/laravel-sybase)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/uepg/laravel-sybase.svg)](https://packagist.org/packages/uepg/laravel-sybase)
[![Packagist](https://img.shields.io/packagist/dt/uepg/laravel-sybase.svg)](https://packagist.org/packages/uepg/laravel-sybase/stats)
[![GitHub contributors](https://img.shields.io/github/contributors-anon/uepg/laravel-sybase.svg)](https://github.com/uepg/laravel-sybase/graphs/contributors)
[![GitHub](https://img.shields.io/github/license/uepg/laravel-sybase.svg)](https://github.com/uepg/laravel-sybase/blob/master/LICENSE)

* Enables use of multiple kinds of fields.
* Use default eloquent: works with odbc and dblib!

## Install

Add the following in the require section of your **composer.json**:

### Laravel 7 <=
"uepg/laravel-sybase": "~2"

### Laravel 8 >= and <= 10
"uepg/laravel-sybase": "~4"

### Laravel 11 >=
```json
"uepg/laravel-sybase": "~5"
```

Update the package dependencies executing:

```shell
composer update
```

Add the following entry to your providers array in **config/app.php** file, optional in Laravel 5.5 or above:

```php
Uepg\LaravelSybase\SybaseServiceProvider::class,
```

Add the following entry to your aliases array in **config/app.php** file, optional in Laravel 5.5 or above:

```php
'UepgBlueprint' => Uepg\LaravelSybase\Database\Schema\Blueprint::class,
```

Update your **config/database.php's** default driver with the settings for the **sybase** or your custom odbc. See the following example:

```php
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
            'database' => env('DB_DATABASE', 'mydatabase'),
            'username' => env('DB_USERNAME', 'user'),
            'password' => env('DB_PASSWORD', 'password'),
            'charset' => 'utf8',
            'prefix' => '',
            'cache_tables' => true,
            'cache_time' => 3600,
             'application_encoding' => false,
             'application_charset' => '',
        ],

        ...
    ],

    ...
]
```

Update your **.env** with the settings for the **sybase** or your custom odbc. See the following example:

```text
...

DB_CONNECTION=sybase
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

## Configuring the charset conversion
This package offers to method to charset conversion, it can be converted in application layer or in database layer, we offered both methods because it can be useful for debugging, to config the application layer conversion you need to set up the following entries on the `database.php` file. You can view an example of the application encoding setup below:

```
To use the database layer conversion add the property charset to connection configuration on the sybase configuration array

```charset
     'charset' => 'utf8',
     'application_encoding' => false,
     'application_charset' => '',
```



## Stored procedures (`rpc()`)

Use `rpc()` on the Sybase connection to run stored procedures that return **exactly one final result set** (multiple trailing `SELECT` statements are not supported by this helper).

### Result set convention (status columns)

Design procedures that participate in this flow so the **only** result set returned to the client **always** includes at least these columns (names matched case-insensitively):

| Column         | Role |
|----------------|------|
| **`cd_retorno`** | `0` = success; **`> 0`** = application / business error code expected by PHP (`throwOnError()`, `ProcedureExecutionException`). |
| **`msg_retorno`**| Human-readable message for errors (and optional context on success); surfaced on the exception when `cd_retorno` ≠ 0. |

**`throwOnError()`** inspects the **first row** only. **`get()`** / **`first()`** return whatever columns the procedure selects, but callers and tooling should rely on the same contract so every call can distinguish OK vs application error without ad hoc parsing.

If a procedure omits `cd_retorno` (or returns no rows), `throwOnError()` cannot validate the outcome and will throw `InvalidArgumentException` as documented below.

```php
use Illuminate\Support\Facades\DB;

$rows = DB::connection('sybase')
    ->rpc('dbo.sp_exemplo')
    ->with(['cd_pessoa_p' => $id]) // keys may include or omit a leading `@`
    ->get(); // same row shape as Connection::select()

// Positional arguments (values only), in the same order as the procedure parameters:
$rows = DB::connection('sybase')
    ->rpc('dbo.sp_exemplo')
    ->with([$id, $nome])
    ->get();

$first = DB::connection('sybase')
    ->rpc('dbo.sp_exemplo')
    ->with(['@cd_pessoa_p' => $id])
    ->first();

DB::connection('sybase')
    ->rpc('dbo.sp_exemplo')
    ->with($dto) // Illuminate\Contracts\Support\Arrayable or associative array
    ->throwOnError() // throws ProcedureExecutionException if cd_retorno != 0
    ->first();
```

### Exceptions

Execution still goes through `Connection::select()`, so **SQL and driver failures** surface as Laravel’s usual `Illuminate\Database\QueryException` (and related PDO errors), like any other query.

**`Uepg\LaravelSybase\Database\ProcedureExecutionException`** (extends `RuntimeException`) is thrown by **`throwOnError()`** when the first row of the result set has **`cd_retorno` ≠ 0** (application-level error returned by the procedure). Use it to branch on business errors without inspecting the row manually:

- **`$e->getMessage()`** — prefers `msg_retorno` when present, otherwise a short default message.
- **`$e->getCode()`** — the numeric `cd_retorno` (also available as **`$e->cdRetorno`**).
- **`$e->msgRetorno`** — optional string from the `msg_retorno` column (may be `null`).

**`InvalidArgumentException`** is thrown by `RpcCall` for **invalid usage before or during `throwOnError()`**, including:

- **Procedure name** passed to `rpc()` does not match the allowed pattern (empty or disallowed characters).
- **Named parameters**: non-string keys, empty parameter name, or name not matching `^[a-zA-Z_][a-zA-Z0-9_]*$` (after stripping an optional leading `@`).
- **`throwOnError()`**: result set is **empty**, or the first row has **no `cd_retorno` column** (case-insensitive match on column names).

```php
use Illuminate\Database\QueryException;
use Uepg\LaravelSybase\Database\ProcedureExecutionException;

try {
    DB::connection('sybase')->rpc('dbo.sp_exemplo')->with(['id' => $id])->throwOnError();
} catch (ProcedureExecutionException $e) {
    // $e->cdRetorno, $e->msgRetorno, $e->getMessage()
} catch (QueryException $e) {
    // syntax, connectivity, Sybase errors, etc.
}
```




### Hydrating results into DTOs

Implement `Uepg\LaravelSybase\Contracts\RpcResultDto` on a class with `fromArray(array $row): static`, then use `firstAs()` for a single row or `getAs()` for every row as an `Illuminate\Support\Collection`:

```php
use Illuminate\Support\Facades\DB;
use Uepg\LaravelSybase\Contracts\RpcResultDto;

final class LoginRow implements RpcResultDto
{
    public function __construct(
        public readonly int $cdRetorno,
        public readonly ?string $msgRetorno,
    ) {}

    public static function fromArray(array $row): static
    {
        return new self(
            cdRetorno: (int) ($row['cd_retorno'] ?? 0),
            msgRetorno: isset($row['msg_retorno']) ? (string) $row['msg_retorno'] : null,
        );
    }
}

$row = DB::connection('sybase')
    ->rpc('dbo.sp_exemplo')
    ->with(['cd_pessoa_p' => $id])
    ->throwOnError()
    ->firstAs(LoginRow::class); // LoginRow|null

$rows = DB::connection('sybase')
    ->rpc('dbo.sp_lista')
    ->with(['p' => 1])
    ->getAs(LoginRow::class); // Collection<int, LoginRow>
```







Optional read path and fetch mode follow `select()`:

```php
DB::connection('sybase')
    ->rpc('dbo.sp_exemplo')
    ->with(['p' => 1])
    ->useReadPdo(false)
    ->fetchUsing([\PDO::FETCH_ASSOC])
    ->get();
```

`toStatement()` returns the built SQL (`EXEC ... @name = ?, ...` or `EXEC ... ?, ?, ...`) and bindings (for logging or tests) without executing the procedure. A new `with()` that switches between a list and an associative array replaces the previous argument style for that call chain.

This API does **not** cover `OUTPUT` parameters or procedures that return multiple result sets unless you handle them with a raw `select()` yourself.

## Configuring the cache
As the library consults table information whenever it receives a request, caching can be used to avoid excessive queries

To use the cache, add the property `cache_tables` to the database.php connection configuration, you can customize the time of the cache with the property `cache_time` in the same configuration
```dotenv
        'cache_tables' => true,
        'cache_time' => 3600
```

## Setting to use numeric data type

In the migration file you must replace `use Illuminate\Database\Schema\Blueprint;` with `use Uepg\LaravelSybase\Database\Schema\Blueprint;`. See the following example:

```php
<?php

use Illuminate\Support\Facades\Schema;
// use Illuminate\Database\Schema\Blueprint;
use Uepg\LaravelSybase\Database\Schema\Blueprint; // or "use UepgBlueprint as Blueprint"
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
