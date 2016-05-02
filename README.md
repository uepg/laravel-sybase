# laravel-sybase
Sybase ASE based Eloquent module extension for Laravel 5.x.
- Enables use of multiple kinds of fields.
- Use default eloquent: works with odbc and dblib!
- Migrations! (alpha)

### Install
- Require in your **composer.json** this package: ``"uepg/laravel-sybase": "1.*"``
- Run ``composer update``
- Add to your providers in **./config./app.php**: ``Uepg\LaravelSybase\Database\SybaseServiceProvider::class``
- Update your **./config./database.php**'s default driver to **sqlsrv** or your custom odbc.
 
### Configuration of freetds driver

In Linux systems the driver version must be set in `freetds.conf` to the right use of charset pages.

The file is usualy found in `/etc/freetds/freetds.conf`. Set the configuration at global section as the following example:

    [global]
        # TDS protocol version
        tds version = 5.0
