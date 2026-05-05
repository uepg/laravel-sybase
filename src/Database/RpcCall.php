<?php

namespace Uepg\LaravelSybase\Database;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Uepg\LaravelSybase\Contracts\RpcResultDto;

class RpcCall
{
    /**
     * @var array<string, mixed>
     */
    protected array $namedParameters = [];

    /**
     * @var array<int, mixed>
     */
    protected array $positionalParameters = [];

    protected bool $useReadPdo = true;

    protected array $fetchUsing = [];

    protected ?array $rows = null;

    public function __construct(
        protected Connection $connection,
        protected string $procedure,
    ) {
        $this->procedure = $this->assertValidProcedureName($procedure);
    }

    /**
     * Named parameters (associative array, object or Arrayable with string keys) become
     * EXEC name @param = ?, ... A zero-based list (only values) becomes
     * EXEC name ?, ?, ... in the same order as the procedure signature.
     *
     * @param  array<int|string, mixed>|object  $parameters
     */
    public function with(array|object $parameters): self
    {
        $this->rows = null;
        $data = $this->parametersToArray($parameters);

        if (is_array($data) && array_is_list($data)) {
            $this->namedParameters = [];
            $this->positionalParameters = $data;
        } else {
            $this->positionalParameters = [];
            $this->namedParameters = array_merge(
                $this->namedParameters,
                $this->normalizeNamedParameters($data),
            );
        }

        return $this;
    }

    public function useReadPdo(bool $useReadPdo = true): self
    {
        $this->rows = null;
        $this->useReadPdo = $useReadPdo;

        return $this;
    }

    /**
     * @param  array<int, mixed>  $fetchUsing
     */
    public function fetchUsing(array $fetchUsing): self
    {
        $this->rows = null;
        $this->fetchUsing = $fetchUsing;

        return $this;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function get(): array
    {
        return $this->execute();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        $rows = $this->execute();

        return $rows[0] ?? null;
    }

    public function throwOnError(): self
    {
        $rows = $this->execute();
        $first = $rows[0] ?? null;

        if ($first === null) {
            throw new InvalidArgumentException(
                'Procedure returned no rows; throwOnError() requires a row with cd_retorno.'
            );
        }

        $cd = $this->findColumnValue($first, 'cd_retorno');

        if ($cd === null) {
            throw new InvalidArgumentException(
                'Procedure result is missing cd_retorno; cannot throwOnError().'
            );
        }

        if ((int) $cd !== 0) {
            $msg = $this->findColumnValue($first, 'msg_retorno');

            throw new ProcedureExecutionException(
                (int) $cd,
                $msg !== null ? (string) $msg : null,
            );
        }

        return $this;
    }


    /**
     * @template T of RpcResultDto
     *
     * @param  class-string<T>  $class
     * @return T|null
     */
    public function firstAs(string $class): mixed
    {
        $row = $this->first();

        return $row !== null ? $class::fromArray($row) : null;
    }

    /**
     * @template T of RpcResultDto
     *
     * @param  class-string<T>  $class
     * @return Collection<int, T>
     */
    public function getAs(string $class): Collection
    {
        return collect($this->get())
            ->map(fn (array $row) => $class::fromArray($row));
    }


    /**
     * @return array{0: string, 1: array<int, mixed>}
     */
    public function toStatement(): array
    {
        return $this->compileExecute();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function execute(): array
    {
        if ($this->rows !== null) {
            return $this->rows;
        }

        [$query, $bindings] = $this->compileExecute();

        $rawRows = $this->connection->select(
            $query,
            $bindings,
            $this->useReadPdo,
            $this->fetchUsing,
        );

        $this->rows = array_map(
            static function (mixed $row): array {
                if (is_array($row)) {
                    return $row;
                }

                if ($row instanceof \stdClass) {
                    return get_object_vars($row);
                }

                return (array) $row;
            },
            $rawRows,
        );
        
        return $this->rows;
    }

    /**
     * @return array{0: string, 1: array<int, mixed>}
     */
    protected function compileExecute(): array
    {
        if ($this->positionalParameters !== []) {
            $n = count($this->positionalParameters);
            $placeholders = implode(', ', array_fill(0, $n, '?'));
            $sql = 'EXEC '.$this->procedure.' '.$placeholders;

            return [$sql, $this->positionalParameters];
        }

        $bindings = [];
        $fragments = [];

        foreach ($this->namedParameters as $name => $value) {
            $fragments[] = '@'.$name.' = ?';
            $bindings[] = $value;
        }

        $sql = $fragments === []
            ? 'EXEC '.$this->procedure
            : 'EXEC '.$this->procedure.' '.implode(', ', $fragments);

        return [$sql, $bindings];
    }

    /**
     * @param  array<int|string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeNamedParameters(array $data): array
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            if (! is_string($key)) {
                throw new InvalidArgumentException('Named procedure parameters require string keys.');
            }

            $canonical = $this->normalizeParameterKey($key);
            $normalized[$canonical] = $value;
        }

        return $normalized;
    }

    /**
     * @param  array<int|string, mixed>|object  $parameters
     * @return array<int|string, mixed>
     */
    protected function parametersToArray(array|object $parameters): array
    {
        if ($parameters instanceof Arrayable) {
            return $parameters->toArray();
        }

        if (is_object($parameters)) {
            return get_object_vars($parameters) ?: (array) $parameters;
        }

        return $parameters;
    }

    protected function normalizeParameterKey(string $key): string
    {
        $key = trim($key);

        if ($key === '') {
            throw new InvalidArgumentException('Parameter name cannot be empty.');
        }

        if (str_starts_with($key, '@')) {
            $key = substr($key, 1);
        }

        if ($key === '' || ! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key)) {
            throw new InvalidArgumentException(sprintf('Invalid parameter name "%s".', $key));
        }

        return strtolower($key);
    }

    protected function assertValidProcedureName(string $procedure): string
    {
        $procedure = trim($procedure);

        if ($procedure === '' || ! preg_match('/^[a-zA-Z0-9_#.\[\]]+$/', $procedure)) {
            throw new InvalidArgumentException('Invalid stored procedure name.');
        }

        return $procedure;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function findColumnValue(array $row, string $name): mixed
    {
        foreach ($row as $column => $value) {
            if (strcasecmp((string) $column, $name) === 0) {
                return $value;
            }
        }

        return null;
    }
}
