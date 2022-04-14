<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlterEnumAddRemovedToTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public $set_schema_table = 'transactions';

    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            DB::statement("ALTER TABLE `transactions` CHANGE `status` `status` ENUM('pending','approved','failed','soled', 'removed') NOT NULL DEFAULT 'pending'");

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            DB::statement("ALTER TABLE ".$this->set_schema_table." CHANGE COLUMN status ENUM('pending', 'approved', 'failed','soled', 'removed') NOT NULL DEFAULT 'pending'");

        });
    }
}
