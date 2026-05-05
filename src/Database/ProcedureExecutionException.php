<?php

namespace Uepg\LaravelSybase\Database;

use RuntimeException;

class ProcedureExecutionException extends RuntimeException
{
    public function __construct(
        public readonly int $cdRetorno,
        public readonly ?string $msgRetorno = null,
        ?string $message = null,
    ) {
        $text = $message ?? $msgRetorno ?? 'Stored procedure returned a non-zero cd_retorno.';

        parent::__construct($text, $cdRetorno);
    }
}
