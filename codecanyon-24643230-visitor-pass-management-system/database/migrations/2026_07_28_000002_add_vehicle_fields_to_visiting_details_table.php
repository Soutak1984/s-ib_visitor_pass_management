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
            if (!Schema::hasColumn('visiting_details', 'vehicle_number')) {
                $table->string('vehicle_number', 50)->nullable();
            }
            if (!Schema::hasColumn('visiting_details', 'vehicle_compliance')) {
                $table->string('vehicle_compliance', 20)->nullable();
            }
            if (!Schema::hasColumn('visiting_details', 'vehicle_remarks')) {
                $table->text('vehicle_remarks')->nullable();
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
            $columns = [];
            foreach (['vehicle_number', 'vehicle_compliance', 'vehicle_remarks'] as $column) {
                if (Schema::hasColumn('visiting_details', $column)) {
                    $columns[] = $column;
                }
            }
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
