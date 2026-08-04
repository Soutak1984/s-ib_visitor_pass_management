<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('visitors', function ($table) {
            $table->string('email')->nullable()->change();
            $table->dropUnique(['email']);
            $table->string('national_identification_no')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('visitors', function ($table) {
            $table->string('email')->change();
            $table->string('national_identification_no')->change();
        });
    }
};
