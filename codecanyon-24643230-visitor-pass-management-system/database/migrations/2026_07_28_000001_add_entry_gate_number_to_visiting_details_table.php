<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::table('visiting_details', function (Blueprint $table) {
            if (!Schema::hasColumn('visiting_details', 'entry_gate_number')) {
                $table->string('entry_gate_number', 50)->nullable()->after('purpose');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('visiting_details', function (Blueprint $table) {
            if (Schema::hasColumn('visiting_details', 'entry_gate_number')) {
                $table->dropColumn('entry_gate_number');
            }
        });
    }
};
