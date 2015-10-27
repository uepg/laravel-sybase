# sybase-eloquent
Eloquent module to run Sybase with minimal problems using Laravel 5.x.
- Enables use of multiple kinds of fields.
- Use default eloquent: works with odbc and dblib!

### Install
- Require in your **composer.json** this package: ``"mainginski/sybase-eloquent": "dev-master"``
- Run ``composer update``
- Add to your providers in **./config./app.php**:
``Mainginski\SybaseEloquent\Database\SybaseServiceProvider::class``

### Known Issues
- Please don't use ``DB::table('your_table')->select('your_field as alias')`` with ``->where()``, I'm working to resolve it.
- Error 247 when working with a format different dates accepted by Sybase.
- No solution to operate the Laravel's offset() function.
- No migrations for now.