<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pre_registers', function (Blueprint $table) {
            $table->increments('id');
            $table->date('expected_date');
            $table->time('expected_time');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedInteger('visitor_id')->unsigned();
            $table->string('comment')->nullable();
            $table->auditColumn();
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
        Schema::drop('pre_registers');
    }
};
