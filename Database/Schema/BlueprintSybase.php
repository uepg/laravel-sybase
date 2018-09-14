<?php

namespace Uepg\LaravelSybase\Database\Schema;

use Illuminate\Database\Schema\Blueprint;

class BlueprintSybase extends Blueprint
{
    public function numeric($column, $total=8, $autoIncrement=false)
    {
        return $this->addColumn('numeric', $column, compact('total', 'autoIncrement'));
    }
}