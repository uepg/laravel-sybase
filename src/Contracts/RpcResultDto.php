<?php

namespace Uepg\LaravelSybase\Contracts;

interface RpcResultDto
{
    public static function fromArray(array $row): static;
}
