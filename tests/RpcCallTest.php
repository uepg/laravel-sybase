<?php

namespace Tests;

use Illuminate\Contracts\Support\Arrayable;
use Uepg\LaravelSybase\Contracts\RpcResultDto;
use Uepg\LaravelSybase\Database\Connection;
use Uepg\LaravelSybase\Database\ProcedureExecutionException;
use Uepg\LaravelSybase\Database\RpcCall;

class RpcCallTest extends TestCase
{
    public function test_to_statement_builds_exec_without_parameters(): void
    {
        $connection = $this->createMock(Connection::class);
        $rpc = new RpcCall($connection, 'dbo.sp_no_args');

        $this->assertSame(
            ['EXEC dbo.sp_no_args', []],
            $rpc->toStatement(),
        );
    }

    public function test_to_statement_builds_exec_with_named_placeholders_and_bindings_order(): void
    {
        $connection = $this->createMock(Connection::class);
        $rpc = (new RpcCall($connection, 'dbo.sp_test'))
            ->with([
                'cd_pessoa_p' => 10,
                '@nm_pessoa_p' => 'Ana',
            ]);

        [$sql, $bindings] = $rpc->toStatement();

        $this->assertSame('EXEC dbo.sp_test @cd_pessoa_p = ?, @nm_pessoa_p = ?', $sql);
        $this->assertSame([10, 'Ana'], $bindings);
    }

    public function test_to_statement_builds_exec_with_positional_placeholders(): void
    {
        $connection = $this->createMock(Connection::class);
        $rpc = (new RpcCall($connection, 'dbo.sp_ordem'))
            ->with([10, 'Ana', null]);

        [$sql, $bindings] = $rpc->toStatement();

        $this->assertSame('EXEC dbo.sp_ordem ?, ?, ?', $sql);
        $this->assertSame([10, 'Ana', null], $bindings);
    }

    public function test_positional_with_replaces_previous_positional_arguments(): void
    {
        $connection = $this->createMock(Connection::class);
        $rpc = (new RpcCall($connection, 'dbo.sp_rep'))
            ->with([1, 2])
            ->with([9]);

        [$sql, $bindings] = $rpc->toStatement();

        $this->assertSame('EXEC dbo.sp_rep ?', $sql);
        $this->assertSame([9], $bindings);
    }

    public function test_named_with_clears_positional_arguments(): void
    {
        $connection = $this->createMock(Connection::class);
        $rpc = (new RpcCall($connection, 'dbo.sp_mix'))
            ->with([1, 2])
            ->with(['p' => 3]);

        [$sql, $bindings] = $rpc->toStatement();

        $this->assertSame('EXEC dbo.sp_mix @p = ?', $sql);
        $this->assertSame([3], $bindings);
    }

    public function test_list_with_clears_named_parameters(): void
    {
        $connection = $this->createMock(Connection::class);
        $rpc = (new RpcCall($connection, 'dbo.sp_switch'))
            ->with(['p' => 1])
            ->with([5, 6]);

        [$sql, $bindings] = $rpc->toStatement();

        $this->assertSame('EXEC dbo.sp_switch ?, ?', $sql);
        $this->assertSame([5, 6], $bindings);
    }

    public function test_get_delegates_to_connection_select_with_options(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('select')
            ->with(
                'EXEC dbo.sp_x @a = ?',
                [1],
                false,
                [\PDO::FETCH_ASSOC],
            )
            ->willReturn([['cd_retorno' => 0]]);

        $rows = (new RpcCall($connection, 'dbo.sp_x'))
            ->with(['a' => 1])
            ->useReadPdo(false)
            ->fetchUsing([\PDO::FETCH_ASSOC])
            ->get();

        $this->assertSame([['cd_retorno' => 0]], $rows);
    }

    public function test_get_is_cached_until_with_changes_parameters(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->exactly(2))
            ->method('select')
            ->willReturnOnConsecutiveCalls(
                [['n' => 1]],
                [['n' => 2]],
            );

        $rpc = (new RpcCall($connection, 'dbo.sp_cache'))->with(['a' => 1]);

        $this->assertSame([['n' => 1]], $rpc->get());
        $this->assertSame([['n' => 1]], $rpc->get(), 'Second get() must not hit the connection again.');

        $rpc->with(['a' => 2]);
        $this->assertSame([['n' => 2]], $rpc->get());
    }

    public function test_throw_on_error_throws_when_cd_retorno_is_non_zero(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('select')->willReturn([
            ['cd_retorno' => 5, 'msg_retorno' => 'Falhou'],
        ]);

        $this->expectException(ProcedureExecutionException::class);
        $this->expectExceptionMessage('Falhou');

        try {
            (new RpcCall($connection, 'dbo.sp_err'))
                ->throwOnError();
        } catch (ProcedureExecutionException $e) {
            $this->assertSame(5, $e->cdRetorno);
            $this->assertSame('Falhou', $e->msgRetorno);

            throw $e;
        }
    }


    public function test_throw_on_error_returns_self_when_cd_retorno_is_zero(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('select')->willReturn([
            ['cd_retorno' => 0, 'msg_retorno' => 'OK'],
        ]);

        $rpc = new RpcCall($connection, 'dbo.sp_ok');
        $result = $rpc->throwOnError();

        $this->assertSame($rpc, $result);
    }

    public function test_throw_on_error_then_first_reuses_same_execution(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())
            ->method('select')
            ->willReturn([
                ['CD_RETORNO' => 0, 'msg_retorno' => 'OK', 'x' => 1],
            ]);

        $rpc = new RpcCall($connection, 'dbo.sp_ok');

        $rpc->throwOnError();
        $first = $rpc->first();

        $this->assertSame(['CD_RETORNO' => 0, 'msg_retorno' => 'OK', 'x' => 1], $first);
    }

    public function test_with_accepts_arrayable_dto(): void
    {
        $dto = new class implements Arrayable
        {
            public function toArray(): array
            {
                return ['@cd_pessoa_p' => 7];
            }
        };

        $connection = $this->createMock(Connection::class);
        $rpc = (new RpcCall($connection, 'dbo.sp_dto'))->with($dto);

        [$sql, $bindings] = $rpc->toStatement();

        $this->assertSame('EXEC dbo.sp_dto @cd_pessoa_p = ?', $sql);
        $this->assertSame([7], $bindings);
    }

    public function test_connection_rpc_returns_rpc_call(): void
    {
        $pdo = $this->createMock(\PDO::class);
        $connection = new Connection($pdo, 'db', '', []);

        $this->assertInstanceOf(RpcCall::class, $connection->rpc('dbo.sp_x'));
    }
}
