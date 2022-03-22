<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuickSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quick_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_property_id')->constrained('user_properties');
            $table->integer('amount');
            $table->text('description');
            $table->enum('status', ['pending', 'processing', 'closed'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quick_sales');
    }
}
