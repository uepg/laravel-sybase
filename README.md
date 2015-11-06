# laravel-sybase
Sybase based Eloquent module extension for Laravel 5.x.
- Enables use of multiple kinds of fields.
- Use default eloquent: works with odbc and dblib!

### Install
- Require in your **composer.json** this package: ``"uepg/laravel-sybase": "dev-master"``
- Run ``composer update``
- Add to your providers in **./config./app.php**: ``Uepg\LaravelSybase\Database\SybaseServiceProvider::class``
- Update your **./config./database.php**'s default driver to **sqlsrv** or your custom odbc.

### Known Issues
- Error 247 when working with a format different dates accepted by Sybase.
- No solution to operate the Laravel's offset() function.
- Don't use ``$table->text('')``in your migration schema. ( http://infocenter.sybase.com/help/index.jsp?topic=/com.sybase.infocenter.dc78260.1520/html/rax152rb/rax152rb21.htm )