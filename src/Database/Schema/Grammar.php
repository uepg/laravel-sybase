<?php

namespace Uepg\LaravelSybase\Database\Schema;

use Illuminate\Database\Schema\Grammars\Grammar as IlluminateGrammar;
use Illuminate\Support\Fluent;

class Grammar extends IlluminateGrammar
{
    /**
     * The possible column modifiers.
     *
     * @var array
     */
    protected $modifiers = [
        'Increment', 'Nullable', 'Default',
    ];

    /**
     * The columns available as serials.
     */
    protected array $serials = [
        'bigInteger', 'integer', 'numeric',
    ];

    /**
     * Compile the query to determine if a table exists.
     *
     * @param  string|null  $schema
     * @param  string  $table
     * @return string
     */
    public function compileTableExists($schema, $table)
    {
        return sprintf('
            SELECT
                COUNT(*) AS [exists]
            FROM
                sysobjects
            WHERE
                type = \'U\'
            AND
                name = \'%s\'', $table);
    }

    /**
     * Compile the query to determine the list of columns.
     *
     * @return string
     */
    public function compileColumnExists(string $table)
    {
        return "
                   SELECT
            col.name
        FROM
            syscolumns col noholdlock
        JOIN
            sysobjects obj noholdlock
        ON
            col.id = obj.id
        WHERE
            obj.type = 'U' AND
            obj.name = '$table';
";
    }

    /**
     * Compile a create table command.
     *
     * @return string
     */
    public function compileCreate(Blueprint $blueprint, Fluent $command)
    {
        $columns = implode(', ', $this->getColumns($blueprint));

        return 'CREATE TABLE '.$this->wrapTable($blueprint)." (
            $columns
        )";
    }

    /**
     * Compile a create table command.
     *
     * @return string
     */
    public function compileAdd(Blueprint $blueprint, Fluent $command)
    {
        $table = $this->wrapTable($blueprint);

        $columns = $this->getColumns($blueprint);

        return 'ALTER TABLE '.$table.' ADD '.implode(', ', $columns);
    }

    /**
     * @param  string|null  $schema
     * @return string
     *                Functions do compile the columns of a given table
     */
    public function compileColumns($schema, $table)
    {
        return sprintf("SELECT
           col.name,
           type.name AS type_name,
           col.length AS length,
           col.prec AS precision,
           col.scale AS places,
           CASE WHEN (col.status & 0x08) = 0x08 THEN 'YES' ELSE 'NO' END AS nullable,
           def.text AS [default],
           CASE WHEN (col.status & 0x80) = 0x80 THEN 'YES' ELSE 'NO' END AS autoincrement,
           NULL AS collation, -- Sybase não fornece suporte direto para collation em colunas
           com.text AS comment -- Comentários associados à coluna, se existirem
       FROM
           sysobjects obj noholdlock
               JOIN
           syscolumns col noholdlock ON obj.id = col.id
               JOIN
           systypes type noholdlock ON col.usertype = type.usertype
               LEFT JOIN
           syscomments def noholdlock ON col.cdefault = def.id -- Valores padrão da coluna
               LEFT JOIN
           syscomments com  noholdlock ON col.colid = com.colid -- Comentários associados às colunas (se habilitados)
       WHERE
           obj.type IN ('U', 'V') -- 'U' para tabelas, 'V' para visões
         AND obj.name = '%s'
         AND user_name(obj.uid) = user_name()
       ORDER BY
           col.colid", $table);
    }

    /**
     * @param  string|null  $schema
     * @return string
     *                Functions that return the indexes of a given table
     */
    public function compileIndexes($schema, $table)
    {
        return sprintf("SELECT
        DISTINCT i.name,
                 index_col(o.name, i.indid, c.colid) AS column_name,
                 CASE WHEN i.status & 2048 = 2048 THEN 'YES' ELSE 'NO' END AS is_primary,
                 CASE WHEN i.status & 2 = 2 THEN 'YES' ELSE 'NO' END AS is_unique
    FROM
        sysobjects o noholdlock
            INNER JOIN sysindexes i noholdlock ON i.id = o.id
            INNER JOIN syscolumns c noholdlock ON c.id = o.id
    WHERE
        o.type = 'U'  -- Apenas tabelas de usuário
      AND o.name = '%s'  -- Nome da tabela alvo
      AND i.indid > 0  -- Índices não-triviais
      AND i.status & 2 = 2  -- Apenas índices do sistema (ajuste se necessário)
      AND index_col(o.name, i.indid, c.colid) IS NOT NULL  -- Verifica colunas válidas associadas ao índice
    ORDER BY
        i.name, column_name", $table);
    }

    /**
     * Compile a primary key command.
     *
     * @return string
     */
    public function compilePrimary(Blueprint $blueprint, Fluent $command)
    {
        $columns = $this->columnize($command->columns);

        $table = $this->wrapTable($blueprint);

        $constraint = $this->limit30Characters($command->index);

        return "
            ALTER TABLE {$table}
            ADD CONSTRAINT {$constraint}
            PRIMARY KEY ($columns)";
    }

    /**
     * Verify if $str length is lower to 30 characters.
     *
     * @return string
     */
    public function limit30Characters(string $str)
    {
        if (strlen($str) > 30) {
            $result = substr($str, 0, 30);
        } else {
            $result = $str;
        }

        return $result;
    }

    /**
     * Compile a unique key command.
     *
     * @return string
     */
    public function compileUnique(Blueprint $blueprint, Fluent $command)
    {
        $columns = $this->columnize($command->columns);

        $table = $this->wrapTable($blueprint);

        $index = $this->limit30Characters($command->index);

        return "CREATE UNIQUE INDEX $index ON $table ($columns)";
    }

    /**
     * Compile a plain index key command.
     *
     * @return string
     */
    public function compileIndex(Blueprint $blueprint, Fluent $command)
    {
        $columns = $this->columnize($command->columns);

        $table = $this->wrapTable($blueprint);

        $index = $this->limit30Characters($command->index);

        return "CREATE INDEX $index ON $table ($columns)";
    }

    /**
     * Compile a drop table command.
     *
     * @return string
     */
    public function compileDrop(Blueprint $blueprint, Fluent $command)
    {
        return 'DROP TABLE '.$this->wrapTable($blueprint);
    }

    /**
     * Compile a drop table (if exists) command.
     *
     * @return string
     */
    public function compileDropIfExists(Blueprint $blueprint, Fluent $command)
    {
        return "
            IF EXISTS (
                SELECT
                    *
                FROM
                    sysobjects
                WHERE
                    type = 'U'
                AND
                    name = '".$blueprint->getTable()."'
            ) DROP TABLE ".$blueprint->getTable();
    }

    /**
     * Compile a drop column command.
     *
     * @return string
     */
    public function compileDropColumn(Blueprint $blueprint, Fluent $command)
    {
        $columns = $this->wrapArray($command->columns);

        $table = $this->wrapTable($blueprint);

        return 'ALTER TABLE '.$table.' DROP COLUMN '.implode(', ', $columns);
    }

    /**
     * Compile a drop primary key command.
     *
     * @return string
     */
    public function compileDropPrimary(Blueprint $blueprint, Fluent $command)
    {
        $table = $this->wrapTable($blueprint);

        return "ALTER TABLE $table DROP CONSTRAINT $command->index";
    }

    /**
     * Compile a drop unique key command.
     *
     * @return string
     */
    public function compileDropUnique(Blueprint $blueprint, Fluent $command)
    {
        $table = $this->wrapTable($blueprint);

        return "DROP INDEX $command->index ON $table";
    }

    /**
     * Compile a drop index command.
     *
     * @return string
     */
    public function compileDropIndex(Blueprint $blueprint, Fluent $command)
    {
        $table = $this->wrapTable($blueprint);

        return "DROP INDEX $command->index ON $table";
    }

    /**
     * Compile a drop foreign key command.
     *
     * @return string
     */
    public function compileDropForeign(\Illuminate\Database\Schema\Blueprint $blueprint, Fluent $command)
    {
        // Laravel expects Illuminate's blueprint as parameter to this method. Instead, we are using our own blueprint
        // might cause error
        $table = $this->wrapTable($blueprint);

        return "ALTER TABLE $table DROP CONSTRAINT $command->index";
    }

    /**
     * Compile a rename table command.
     *
     * @return string
     */
    public function compileRename(Blueprint $blueprint, Fluent $command)
    {
        $from = $this->wrapTable($blueprint);

        return "sp_rename $from, ".$this->wrapTable($command->to);
    }

    /**
     * Create the column definition for a char type.
     *
     * @return string
     */
    protected function typeChar(Fluent $column)
    {
        return "nchar($column->length)";
    }

    /**
     * Create the column definition for a string type.
     *
     * @return string
     */
    protected function typeString(Fluent $column)
    {
        return "nvarchar($column->length)";
    }

    /**
     * Create the column definition for a text type.
     *
     * @return string
     */
    protected function typeText(Fluent $column)
    {
        return 'text';
    }

    /**
     * Create the column definition for a medium text type.
     *
     * @return string
     */
    protected function typeMediumText(Fluent $column)
    {
        return 'text';
    }

    /**
     * Create the column definition for a long text type.
     *
     * @return string
     */
    protected function typeLongText(Fluent $column)
    {
        return 'text';
    }

    /**
     * Create the column definition for an integer type.
     *
     * @return string
     */
    protected function typeInteger(Fluent $column)
    {
        return 'int';
    }

    /**
     * Create the column definition for a big integer type.
     *
     * @return string
     */
    protected function typeBigInteger(Fluent $column)
    {
        return 'bigint';
    }

    /**
     * Create the column definition for a medium integer type.
     *
     * @return string
     */
    protected function typeMediumInteger(Fluent $column)
    {
        return 'int';
    }

    /**
     * Create the column definition for a tiny integer type.
     *
     * @return string
     */
    protected function typeTinyInteger(Fluent $column)
    {
        return 'tinyint';
    }

    /**
     * Create the column definition for a small integer type.
     *
     * @return string
     */
    protected function typeSmallInteger(Fluent $column)
    {
        return 'smallint';
    }

    /**
     * Create the column definition for a float type.
     *
     * @return string
     */
    protected function typeFloat(Fluent $column)
    {
        return 'float';
    }

    /**
     * Create the column definition for a double type.
     *
     * @return string
     */
    protected function typeDouble(Fluent $column)
    {
        return 'float';
    }

    /**
     * Create the column definition for a decimal type.
     *
     * @return string
     */
    protected function typeDecimal(Fluent $column)
    {
        return "decimal($column->total, $column->places)";
    }

    /**
     * Create the column definition for a numeric type.
     *
     * @return string
     */
    protected function typeNumeric(Fluent $column)
    {
        return "numeric($column->total, 0)";
    }

    /**
     * Create the column definition for a boolean type.
     *
     * @return string
     */
    protected function typeBoolean(Fluent $column)
    {
        return 'bit';
    }

    /**
     * Create the column definition for an enum type.
     *
     * @return string
     */
    protected function typeEnum(Fluent $column)
    {
        return 'nvarchar(255)';
    }

    /**
     * Create the column definition for a json type.
     *
     * @return string
     */
    protected function typeJson(Fluent $column)
    {
        return 'text';
    }

    /**
     * Create the column definition for a jsonb type.
     *
     * @return string
     */
    protected function typeJsonb(Fluent $column)
    {
        return 'text';
    }

    /**
     * Create the column definition for a date type.
     *
     * @return string
     */
    protected function typeDate(Fluent $column)
    {
        return 'date';
    }

    /**
     * Create the column definition for a date-time type.
     *
     * @return string
     */
    protected function typeDateTime(Fluent $column)
    {
        return 'datetime';
    }

    /**
     * Create the column definition for a date-time type.
     *
     * @return string
     */
    protected function typeDateTimeTz(Fluent $column)
    {
        return 'datetimeoffset(0)';
    }

    /**
     * Create the column definition for a time type.
     *
     * @return string
     */
    protected function typeTime(Fluent $column)
    {
        return 'time';
    }

    /**
     * Create the column definition for a time type.
     *
     * @return string
     */
    protected function typeTimeTz(Fluent $column)
    {
        return 'time';
    }

    /**
     * Create the column definition for a timestamp type.
     *
     * @return string
     */
    protected function typeTimestamp(Fluent $column)
    {
        return 'datetime';
    }

    /**
     * Create the column definition for a timestamp type.
     *
     * @link https://msdn.microsoft.com/en-us/library/bb630289(v=sql.120).aspx
     *
     * @return string
     */
    protected function typeTimestampTz(Fluent $column)
    {
        return 'datetimeoffset(0)';
    }

    /**
     * Create the column definition for a binary type.
     *
     * @return string
     */
    protected function typeBinary(Fluent $column)
    {
        return 'varbinary(255)';
    }

    /**
     * Get the SQL for a nullable column modifier.
     *
     * @return string|null
     */
    protected function modifyNullable(Blueprint $blueprint, Fluent $column)
    {
        return $column->nullable ? ' null' : ' not null';
    }

    /**
     * Get the SQL for a default column modifier.
     *
     * @return string|null
     */
    protected function modifyDefault(Blueprint $blueprint, Fluent $column)
    {
        if (! is_null($column->default)) {
            return ' default '.$this->getDefaultValue($column->default);
        }

        return null;
    }

    /**
     * Get the SQL for an auto-increment column modifier.
     *
     * @return string|null
     */
    protected function modifyIncrement(Blueprint $blueprint, Fluent $column)
    {
        if (in_array($column->type, $this->serials) && $column->autoIncrement) {
            return ' identity primary key';
        }

        return null;
    }
}
