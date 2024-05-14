# Release Notes

## [3.2.2 (2024-02-12)](https://github.com/jcrodriguezt/laravel-sybase/compare/3.2.1...3.2.2)

Fixed use of SYBASE_CACHE_COLUMNS and SYBASE_CACHE_COLUMNS_TIME variables inside compile function. They are called now from database.connections.yourConnection using the config helper.

## [3.2.1 (2024-02-12)](https://github.com/jcrodriguezt/laravel-sybase/compare/3.2.0...3.2.1)

Removing use of strtolower in compile function for column names. This is causing issues when given that column names are case sensitive

## [3.2.0 (2024-02-12)](https://github.com/jcrodriguezt/laravel-sybase/compare/3.1.2...3.2.0)

Implementing support for Laravel Framework 11

## [3.1.2 (2024-28-12)](https://github.com/jcrodriguezt/laravel-sybase/compare/3.1.1...3.1.2)

Removing use of strtolower in compile function for table names. It's causing me issues with tables that have lower and capital letters as well in its name.

## [3.1.1 (2024-02-28)](https://github.com/jcrodriguezt/laravel-sybase/compare/3.1.0...3.1.1)

Implementing latest xBu3n0 changes. Thanks again!!

## [3.1.0 (2024-01-16)](https://github.com/jcrodriguezt/laravel-sybase/compare/3.0.0...3.1.0)

Implementing code improvements by xBu3n0. Thanks a lot!!

## [3.0.0 (2023-09-04)](https://github.com/jcrodriguezt/laravel-sybase/compare/2.6.8...3.0.0)

Changes in config/database.php default driver name to sybasease, replacing sqlsrv. This is to avoid conflicts when connecting to SQL Server and Sybase ASE simultaneously in the same project.
Changes in readme file to match this change.

## [2.6.8 (2023-08-22)](https://github.com/jcrodriguezt/laravel-sybase/compare/2.6.7...2.6.8)

Updates to composer.json
- Minimum PHP version:  >=8.1
- Minimum doctrine/dbal version:  ^3.5

## [2.6.7 (2023-02-15)](https://github.com/jcrodriguezt/laravel-sybase/compare/2.6.6...2.6.7)

Adding support for Laravel 10 framework.

## [2.6.6 (2022-09-29)](https://github.com/jcrodriguezt/laravel-sybase/compare/2.6.5...2.6.6)

RE-uploaded changes supposed to be on 2.6.5, dont know what happened.

## [2.6.5 (2022-09-09)](https://github.com/jcrodriguezt/laravel-sybase/compare/2.6.4...2.6.5)

Significant performance uplift in data types query while gathering data types.

## [2.6.4 (2022-09-09)](https://github.com/jcrodriguezt/laravel-sybase/compare/2.6.3...2.6.4)

Bugfix to 2.6.3 implementation.

## [2.6.3 (2022-08-22)](https://github.com/jcrodriguezt/laravel-sybase/compare/2.6.2...2.6.3)

Bugfix to 2.6.2 implementation.

## [2.6.2 (2022-08-22)](https://github.com/jcrodriguezt/laravel-sybase/compare/2.6.1...2.6.2)

Bugfix to 2.6.1 implementation. There was a typo in $table variable inside function queryStringForCompileBindings.

## [2.6.1 (2022-08-22)](https://github.com/jcrodriguezt/laravel-sybase/compare/2.6.0...2.6.1)

Bugfixes to 2.6.0 implementation

## [2.6.0 (2022-08-19)](https://github.com/jcrodriguezt/laravel-sybase/compare/2.5.5...2.6.0)

Improvements in delete and insert statements when using array based clauses like whereIn, whereBetween, etc

## [2.5.5 (2022-03-03)](https://github.com/jcrodriguezt/laravel-sybase/compare/2.5.4...2.5.5)

Added support for Laravel 9.x

Fixed PHP deprecation warning on Connection.php in compileOffset function parameters

## [2.2.3 (2019-06-03)](https://github.com/uepg/laravel-sybase/compare/2.2.2...2.2.3)

Fix #49 count must be an array

## [2.2.2 (2019-05-26)](https://github.com/uepg/laravel-sybase/compare/2.2.1...2.2.2)

Fix Connection class

## [2.2.1 (2019-05-26)](https://github.com/uepg/laravel-sybase/compare/2.2...2.2.1)

Fix #48, it was breaking extension - @nunomazer

It's not necessary, no new behavior, ref #48 - @nunomazer


## [2.2 (2019-05-13)](https://github.com/uepg/laravel-sybase/compare/2.1.2...2.2)

Merge pull request [#47](https://github.com/uepg/laravel-sybase/pull/47) from afgloeden/master

Package refactored


## [2.1.2 (2019-04-19)](https://github.com/uepg/laravel-sybase/compare/2.1.1...2.1.2)

Merge pull request [#43](https://github.com/uepg/laravel-sybase/pull/43) from afgloeden/master

Problem with constraint length of the primary key fixed


## [2.1.1 (2019-04-05)](https://github.com/uepg/laravel-sybase/compare/2.1.0...2.1.1)

Merge pull request [#42](https://github.com/uepg/laravel-sybase/pull/42) from afgloeden/master

Changes related to PSR's


## [2.1.0 (2019-01-23)](https://github.com/uepg/laravel-sybase/compare/2.0...2.1.0)

See README


## [2.0 (2017-08-02)](https://github.com/uepg/laravel-sybase/compare/1.3.2...2.0)

Merge pull request [#36](https://github.com/uepg/laravel-sybase/pull/36) from marcelovsantos/master

Correction test for 5.3 and 5.4


## [1.3.2 (2017-03-14)](https://github.com/uepg/laravel-sybase/compare/1.3.1...1.3.2)

Better identention


## [1.3.1 (2017-03-14)](https://github.com/uepg/laravel-sybase/compare/1.3...1.3.1)

Fix [#29](https://github.com/uepg/laravel-sybase/issues/29)


## [1.3 (2017-01-24)](https://github.com/uepg/laravel-sybase/compare/1.2.1...1.3)

Merging dev in master


## [1.2.1 (2016-09-16)](https://github.com/uepg/laravel-sybase/compare/1.2.0.7...1.2.1)

Added support to multiples resultset


## [1.2.0.7 (2016-06-09)](https://github.com/uepg/laravel-sybase/compare/1.2.0.6...1.2.0.7)

Merge branch 'case_insensitive'


## [1.2.0.6 (2016-06-06)](https://github.com/uepg/laravel-sybase/compare/1.2.0.5...1.2.0.6)

Merge branch 'multiple_connections'


## [1.2.0.5 (2016-06-02)](https://github.com/uepg/laravel-sybase/compare/1.2.0.4...1.2.0.5)

Fix a offset problem in joins


## [1.2.0.4 (2016-05-23)](https://github.com/uepg/laravel-sybase/compare/1.2.0.3...1.2.0.4)

Fix [#13](https://github.com/uepg/laravel-sybase/issues/13)


## [1.2.0.3 (2016-05-19)](https://github.com/uepg/laravel-sybase/compare/1.2.0.2...1.2.0.3)

Fix [#13](https://github.com/uepg/laravel-sybase/issues/13) for insert, remove and update


## [1.2.0.2 (2016-05-18)](https://github.com/uepg/laravel-sybase/compare/1.2.0.1...1.2.0.2)

Fix [#14](https://github.com/uepg/laravel-sybase/issues/14)


## [1.2.0.1 (2016-05-12)](https://github.com/uepg/laravel-sybase/compare/1.2...1.2.0.1)

Add money to


## [1.2 (2016-05-03)](https://github.com/uepg/laravel-sybase/compare/1.1...1.2)

Merge branch 'dev'


## [1.1 (2016-03-16)](https://github.com/uepg/laravel-sybase/compare/1.0.3...1.1)

Probably fixed [#11](https://github.com/uepg/laravel-sybase/issues/11) and possible other problems with querys builded without query builder (but all binds will be considered strings by default)


## [1.0.3 (2016-02-18)](https://github.com/uepg/laravel-sybase/compare/1.0.2...1.0.3)

Fix [#8](https://github.com/uepg/laravel-sybase/issues/8)


## [1.0.2 (2015-12-21)](https://github.com/uepg/laravel-sybase/compare/1.0.1...1.0.2)

Minor fixes and better stability.


## [1.0.1 (2015-12-21)](https://github.com/uepg/laravel-sybase/compare/1.0...1.0.1)

Now offset works, but it is slow.


## [1.0 (2015-12-21)](https://github.com/uepg/laravel-sybase/compare/0.3...1.0)

Now offset works, but it is slow.


## [0.3 (2015-12-16)](https://github.com/uepg/laravel-sybase/compare/0.2.4...0.3)

Probably fixed [#4](https://github.com/uepg/laravel-sybase/issues/4) and [#5](https://github.com/uepg/laravel-sybase/issues/5)


## [0.2.4 (2015-12-10)](https://github.com/uepg/laravel-sybase/compare/0.2.3...0.2.4)

This fix [#3](https://github.com/uepg/laravel-sybase/issues/3) (workaround).


## [0.2.3 (2015-12-10)](https://github.com/uepg/laravel-sybase/compare/0.2.2...0.2.3)

Improving functions.


## [0.2.2 (2015-12-08)](https://github.com/uepg/laravel-sybase/compare/0.2.1...0.2.2)

Update Readme.


## [0.2.1 (2015-12-07)](https://github.com/uepg/laravel-sybase/compare/0.2...0.2.1)

This finally fix [#2](https://github.com/uepg/laravel-sybase/issues/2).


## [0.2 (2015-12-04)](https://github.com/uepg/laravel-sybase/compare/0.1.1...0.2)

This fix [#2](https://github.com/uepg/laravel-sybase/issues/2)


## [0.1.1 (2015-11-27)](https://github.com/uepg/laravel-sybase/compare/0.1.0...0.1.1)

Improvement in query seeking types.


## [0.1.0 (2015-11-18)](https://github.com/uepg/laravel-sybase/compare/fd48f2b402acbfd72c3a2e903dabdb2df0a8cbc6...0.1.0)

Single quote problem solved.
