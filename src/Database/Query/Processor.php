<?php

namespace Uepg\LaravelSybase\Database\Query;

use Illuminate\Database\Query\Processors\SqlServerProcessor;

class Processor extends SqlServerProcessor
{
    /**
     * Process the results of an indexes query.
     * @param  array  $results
     * @return array
     */
    public function processIndexes($results)
    {
        $array = [];
        $indexes = collect($results)->unique('name');
        foreach ($indexes AS $index)
        {
            $aux = [];
            $aux['name'] = $index->name;
            $aux['columns'] = $this->concatenaCampos($results,$index->name);
            $aux['unique'] = $index->is_unique;
            $aux['primary'] = $index->is_primary;
            array_push($array, $aux);
        }
        return $array;
    }

    /**
     * @param $results
     * @param $indexName
     * @return array
     * Helper function for building index vector
     */
    public function getColumnsFromIndexResult($results, $indexName)
    {
        $columns = [];
        foreach ($results AS $result)
        {
            if ($result->name == $indexName)
            {
                array_push($columns,$result->column_name);
            }
        }
        return $columns;
    }
}
