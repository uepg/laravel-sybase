<?php

namespace Tests;

use Illuminate\Support\Facades\DB;

class ExampleTest extends TestCase
{

    public function testExample()
    {
        $db = DB::getDefaultConnection();
        $this->assertEquals(config('database.default'), $db);
    }
}
