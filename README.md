# laravel-sybase
Sybase ASE based Eloquent module extension for Laravel 5.x.
- Enables use of multiple kinds of fields.
- Use default eloquent: works with odbc and dblib!
- Migrations! (WIP - Work in Progress)

### Install

Add the following in the require section of your **composer.json**: 

```json
"uepg/laravel-sybase": "1.*"
```

Update the package dependencies executing:

```shell
composer update
```

Add the following entry to your providers array in **./config./app.php** file: 

```php
Uepg\LaravelSybase\Database\SybaseServiceProvider::class
```

Update your ./config./database.php's default driver with the settings for the **sqlsrv** or your custom odbc. See the following example:

```php
    'connections' => [
        
        ...

        'sybaseuepg-aluno' => [
            'driver'   => 'sqlsrv',
            'host'     => env('DB_HOST', 'sybase.myserver.br:5000'),
            'database' => env('DB_DATABASE', 'mydatabase'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', 'secret'),
            'charset'  => 'utf8',
            'prefix'   => '',
        ],
```

 
### Configuration of freetds driver

In Linux systems the driver version must be set in `freetds.conf` file to the right use of charset pages.

The file is usualy found in `/etc/freetds/freetds.conf`. Set the configuration at global section as the following example:

    [global]
        # TDS protocol version
        tds version = 5.0
