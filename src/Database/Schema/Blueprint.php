<?php

namespace Uepg\LaravelSybase\Database\Schema;

use Illuminate\Database\Schema\Blueprint as IlluminateBlueprint;
use Illuminate\Database\Schema\ColumnDefinition;

class Blueprint extends IlluminateBlueprint
{
    /**
     * Function for numeric type.
     *
     * @return ColumnDefinition
     */
    public function numeric($column, int $total = 8, bool $autoIncrement = false)
    {
        return $this->addColumn(
            'numeric',
            $column,
            compact(
                'total',
                'autoIncrement'
            )
        );
    }
}
