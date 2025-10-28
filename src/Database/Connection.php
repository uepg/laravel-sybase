<?php

namespace Uepg\LaravelSybase\Database;

use Closure;
use Doctrine\DBAL\Driver\PDOSqlsrv\Driver as DoctrineDriver;
use Exception;
use Illuminate\Database\Connection as IlluminateConnection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Schema;
use PDO;
use Uepg\LaravelSybase\Database\Query\Grammar as QueryGrammar;
use Uepg\LaravelSybase\Database\Query\Processor;
use Uepg\LaravelSybase\Database\Schema\Blueprint;
use Uepg\LaravelSybase\Database\Schema\Grammar as SchemaGrammar;

class Connection extends IlluminateConnection
{
    /**
     * All types without quotes in Sybase's query.
     *
     * @var array
     */
    private $numeric = [
        'int',
        'numeric',
        'bigint',
        'integer',
        'smallint',
        'tinyint',
        'decimal',
        'double',
        'float',
        'real',
        'bit',
        'binary',
        'varbinary',
        'timestamp',
        'money',
    ];


    /**
     * @var string The application charset
     */
    private $applicationCharset;

    /**
     * @var string The database charset
     */
    private $databaseCharset;

    private $applicationEncoding = false;

    /**
     * Create a new database connection instance.
     *
     * @param  \PDO|\Closure  $pdo
     * @param  string  $database
     * @param  string  $tablePrefix
     * @param  array  $config
     * @return void
     */
    public function __construct($pdo, $database = '', $tablePrefix = '', array $config = [])
    {
        parent::__construct($pdo, $database, $tablePrefix, $config);
        $this->configureExtraSettings($config);
    }

    /**
     * Configures the encoding for the connection.
     *
     * @param array $config
     * @return void
     */
    public function configureExtraSettings($config = [])
    {
        $this->applicationEncoding = key_exists('application_encoding',$config) ? $config['application_encoding'] : false;
        if ($this->applicationEncoding)
        {
            if (!key_exists('application_charset',$config,) || !key_exists('database_charset',$config))
            {
                throw new \Exception('When application encoding is configured, you need to set up application_charset and database_charset');
            }
            $this->applicationCharset = $config['application_charset'];
            $this->databaseCharset = $config['database_charset'];
        }
    }

    /**
     * Execute a Closure within a transaction.
     *
     * @param  \Closure  $callback
     * @return mixed
     *
     * @throws \Exception
     */
    public function transaction(Closure $callback, $attempts = 1)
    {
        if ($this->getDriverName() === 'sybasease') {
            return parent::transaction($callback);
        }

        $this->pdo->exec('BEGIN TRAN');

        // We'll simply execute the given callback within a try / catch block
        // and if we catch any exception we can rollback the transaction
        // so that none of the changes are persisted to the database.
        try {
            $result = $callback($this);

            $this->pdo->exec('COMMIT TRAN');
        }

        // If we catch an exception, we will roll back so nothing gets messed
        // up in the database. Then we'll re-throw the exception so it can
        // be handled how the developer sees fit for their applications.
        catch (\Exception|\PDOException $e) {
            $this->pdo->exec('ROLLBACK TRAN');

            throw $e;
        }

        return $result;
    }

    /**
     * Get the default query grammar instance.
     *
     * @return \Uepg\LaravelSybase\Database\Query\Grammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new QueryGrammar);
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \Uepg\LaravelSybase\Database\Schema\Grammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return $this->withTablePrefix(new SchemaGrammar);
    }

    /**
     * Get the default post processor instance.
     *
     * @return \Uepg\LaravelSybase\Database\Query\Processor
     */
    protected function getDefaultPostProcessor()
    {
        return new Processor;
    }

    /**
     * Get the Doctrine DBAL Driver.
     *
     * @return \Doctrine\DBAL\Driver\PDOSqlsrv\Driver
     */
    protected function getDoctrineDriver()
    {
        return new DoctrineDriver;
    }

    /**
     * Compile the bindings for select/insert/update/delete.
     *
     * @param  \Illuminate\Database\Query\Builder  $builder
     * @return array
     */
    private function compile(Builder $builder)
    {
        $arrTables = [];

        array_push($arrTables, $builder->from);
        if (! empty($builder->joins)) {
            foreach ($builder->joins as $join) {
                array_push($arrTables, $join->table);
            }
        }

        $wheres = [];

        foreach ($builder->wheres as $w) {
            switch ($w['type']) {
                case 'Nested':
                    $wheres += $w['query']->wheres;
                    break;
                default:
                    array_push($wheres, $w);
                    break;
            }
        }

        $cache = key_exists('cache_tables',$builder->connection->config) ? $builder->connection->config['cache_tables'] : false;
        $types = [];

        foreach ($arrTables as $tables) {
            preg_match(
                "/(?:(?'table'.*)(?: as )(?'alias'.*))|(?'tables'.*)/",
                strtolower($tables),
                $alias
            );

            if (empty($alias['alias'])) {
                $tables = $alias['tables'];
            } else {
                $tables = $alias['table'];
            }

            if ($cache) {
                $cacheTime = key_exists('cache_time', $builder->connection->config) ? $builder->connection->config['cache_time'] : 3600;
                $aux = cache()->remember("sybase_columns.$tables.columns_info", $cacheTime, function () use ($tables) {
                    $queryString = $this->queryString($tables);
                    $queryRes = $this->getPdo()->query($queryString);

                    return $queryRes->fetchAll(PDO::FETCH_NAMED);
                });
            } else {
                $queryString = $this->queryString($tables);
                $queryRes = $this->getPdo()->query($queryString);
                $aux = $queryRes->fetchAll(PDO::FETCH_NAMED);
            }

            foreach ($aux as &$row) {
                $types[strtolower($row['name'])] = $row['type'];
                $types[strtolower($tables.'.'.$row['name'])] = $row['type'];

                if (! empty($alias['alias'])) {
                    $types[
                    strtolower($alias['alias'].'.'.$row['name'])
                    ] = $row['type'];
                }
            }
        }

        $keys = [];

        $convert = function ($column, $v) use ($types) {
            if (is_null($v)) {
                return null;
            }

            $variable_type = $types[strtolower($column)];

            if (in_array($variable_type, $this->numeric)) {
                return $v / 1;
            } else {
                return (string) $v;
            }
        };

        if (isset($builder->values)) {
            foreach ($builder->values as $key => $value) {
                if (gettype($value) === 'array') {
                    foreach ($value as $k => $v) {
                        $keys[] = $convert($k, $v);
                    }
                } else {
                    $keys[] = $convert($key, $value);
                }
            }
        }

        if (isset($builder->set)) {
            foreach ($builder->set as $k => $v) {
                $keys[] = $convert($k, $v);
            }
        }

        foreach ($wheres as $w) {
            if ($w['type'] == 'Basic') {
                if (gettype($w['value']) !== 'object') {
                    $keys[] = $convert($w['column'], $w['value']);
                }
            } elseif ($w['type'] == 'In' || $w['type'] == 'NotIn') {
                foreach ($w['values'] as $v) {
                    if (gettype($v) !== 'object') {
                        $keys[] = $convert($w['column'], $v);
                    }
                }
            } elseif ($w['type'] == 'between') {
                if (count($w['values']) != 2) {
                    return [];
                }
                foreach ($w['values'] as $v) {
                    if (gettype($v) !== 'object') {
                        $keys[] = $convert($k, $v);
                    }
                }
            }
        }
        return $keys;
    }

    /**
     * Query string.
     *
     * @param  string  $tables
     * @return string
     */
    private function queryString($tables)
    {
        $tables = str_replace('..', '.dbo.', $tables);
        $explicitDB = explode('.dbo.', $tables);

//        Has domain.table
        if (isset($explicitDB[1])) {
            return <<<SQL
            SELECT
                syscolumns.name,
                systypes.name AS type
            FROM
                {$explicitDB[0]}..syscolumns as syscolumns noholdlock
            JOIN
                {$explicitDB[0]}..systypes as systypes noholdlock ON systypes.usertype = syscolumns.usertype
            WHERE
                object_name(syscolumns.id, db_id('{$explicitDB[0]}')) = '{$explicitDB[1]}'
            SQL;
        } else {
            return <<<SQL
                    SELECT
                syscolumns.name,
                systypes.name AS type
            FROM
                syscolumns noholdlock
            JOIN
                systypes noholdlock ON systypes.usertype = syscolumns.usertype
            WHERE object_name(syscolumns.id) = '{$tables}'
            SQL;
        }
    }

    /**
     * Set new bindings with specified column types to Sybase.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return mixed $newBinds
     */
    private function compileBindings($query, $bindings)
    {
        if (count($bindings) == 0) {
            return [];
        }

        $bindings = $this->prepareBindings($bindings);
        $builder = $this->queryGrammar->getBuilder();

        if ($builder != null) {
            return $this->compile($builder);
        } else {
            return $bindings;
        }
    }

    /**
     * Set new bindings with specified column types to Sybase.
     *
     * It could compile again from bindings using PDO::PARAM, however, it has
     * no constants that deal with decimals, so the only way would be to put
     * PDO::PARAM_STR, which would put quotes.
     *
     * @link http://stackoverflow.com/questions/2718628/pdoparam-for-type-decimal
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return string $query
     */
    private function compileNewQuery($query, $bindings)
    {
        $newQuery = '';

        $bindings = $this->compileBindings($query, $bindings);
        $partQuery = explode('?', $query);

        $bindings = array_map(fn ($v) => gettype($v) === 'string' ? str_replace('\'', '\'\'', $v) : $v, $bindings);
        $bindings = array_map(fn ($v) => gettype($v) === 'string' ? "'{$v}'" : $v, $bindings);
        $bindings = array_map(fn ($v) => gettype($v) === 'NULL' ? 'NULL' : $v, $bindings);

        $newQuery = join(array_map(fn ($k1, $k2) => $k1.$k2, $partQuery, $bindings));
        $newQuery = str_replace('[]', '', $newQuery);
        $application_encoding = $this->applicationEncoding;
        if (is_null($application_encoding) || $application_encoding == false) {
            return $newQuery;
        }
        $database_charset = $this->databaseCharset;
        $application_charset = $this->applicationCharset;
        if (is_null($database_charset) || is_null($application_charset)) {
            throw new \Exception('[SYBASE] Database Charset and App Charset not set');
        }
        $newQuery = mb_convert_encoding($newQuery, $database_charset, $application_charset);

        return $newQuery;
    }

    /**
     * Run a select statement against the database.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @return array
     */
    public function select($query, $bindings = [], $useReadPdo = true)
    {
        return $this->run($query, $bindings, function (
            $query,
            $bindings
        ) {
            if ($this->pretending()) {
                return [];
            }

            $statement = $this->getPdo()->prepare($this->compileNewQuery(
                $query,
                $bindings
            ));

            $statement->execute();

            $result = [];

            do {
                $result += $statement->fetchAll($this->getFetchMode());
            } while ($statement->nextRowset());

            $result = [...$result];

            $application_encoding = $this->applicationEncoding;
            if (is_null($application_encoding) || $application_encoding == false) {
                return $result;
            }
            $database_charset = $this->databaseCharset;
            $application_charset = $this->applicationCharset;
            if (is_null($database_charset) || is_null($application_charset)) {
                throw new \Exception('[SYBASE] Database Charset and App Charset not set');
            }
            foreach ($result as &$r) {
                foreach ($r as $k => &$v) {
                    $v = gettype($v) === 'string' ? mb_convert_encoding($v, $application_charset, $database_charset) : $v;
                }
            }

            return $result;
        });
    }

    /**
     * Get the statement.
     *
     * @param  string  $query
     * @param  mixed|array  $bindings
     * @return bool
     */
    public function statement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return true;
            }

            return $this->getPdo()->query($this->compileNewQuery(
                $query,
                $bindings
            ));
        });
    }

    /**
     * Affecting statement.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @return int
     */
    public function affectingStatement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return 0;
            }

            return $this->getPdo()->query($this->compileNewQuery(
                $query,
                $bindings
            ))->rowCount();
        });
    }

    /**
     * Get the default fetch mode for the connection.
     *
     * @return int
     */
    public function getFetchMode()
    {
        return $this->fetchMode;
    }

    /**
     * Get SchemaBuilder.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        $builder = new \Illuminate\Database\Schema\Builder($this);

        $builder->blueprintResolver(function ($table, $callback) {
            return new Blueprint($table, $callback);
        });

        return $builder;
    }

    /**
     * Run a SQL statement.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  \Closure  $callback
     * @return mixed
     *
     * @throws \Illuminate\Database\QueryException
     */
    protected function runQueryCallback($query, $bindings, Closure $callback)
    {
        try {
            $result = $callback($query, $bindings);

            if ($result instanceof \PDOStatement) {
                $errorInfo = $result->errorInfo();
                if (isset($errorInfo[0]) && $errorInfo[0] !== '00000') {
                    $finalErrorMessage = sprintf(
                        'SQLSTATE[%s] [%d] %s',
                        $errorInfo[0],
                        (int)$errorInfo[1],
                        trim(preg_replace(['/^\[\d+\]\s\(severity\s\d+\)\s/', '/\s+/'], ['', ' '], $errorInfo[2]))
                    );
                    throw new \PDOException($finalErrorMessage, (int)$errorInfo[1]);
                }
            }
            return $result;

        } catch (Throwable $e) {
            throw new QueryException(
                $this->getName(),
                $query,
                $this->prepareBindings($bindings),
                $e
            );
        }
    }
}
