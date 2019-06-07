<?php

namespace Uepg\LaravelSybase\Database\Schema;

use Illuminate\Database\Schema\Blueprint as IlluminateBlueprint;

class Blueprint extends IlluminateBlueprint
{
    /**
     * Function for numeric type.
     *
     * @param  string  $type
     * @param  string  $name
     * @param  array  $parameters
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    public function numeric($column, $total = 8, $autoIncrement = false)
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
