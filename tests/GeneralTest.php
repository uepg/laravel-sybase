<?php

namespace Tests;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Uepg\LaravelSybase\Database\Schema\Blueprint;

class GeneralTest extends TestCase
{

    public function test_if_table_is_corrrectly_created()
    {
        if(!Schema::hasTable('test123')) {
            Schema::create('test123', function (Blueprint $table) {
                $table->increments('id');
                $table->string('test');
            });
        }

        $this->assertTrue(Schema::hasTable('test123'));
    }

    public function test_if_table_is_correctly_inserted()
    {
        $this->assertDatabaseCount('test123', 0);

        DB::table('test123')->insert([
            'test' => 'test',
        ]);
        $this->assertDatabaseCount('test123', 1);
    }

    public function test_if_table_register_is_correctly_deleted()
    {
        $this->assertDatabaseCount('test123', 1);

        DB::table('test123')->delete(1);

        $this->assertDatabaseCount('test123', 0);
    }

    public function test_if_table_is_correctly_dropped()
    {
        Schema::dropIfExists('test123');
        $this->assertNotTrue(Schema::hasTable('test123'));
    }
}
