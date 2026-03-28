<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBreakColumnsToStampCorrectionRequestsTable extends Migration
{
    public function up()
    {
        Schema::table('stamp_correction_requests', function (Blueprint $table) {
            $table->timestamp('requested_break1_start')->nullable()->after('requested_clock_out_at');
            $table->timestamp('requested_break1_end')->nullable()->after('requested_break1_start');
            $table->timestamp('requested_break2_start')->nullable()->after('requested_break1_end');
            $table->timestamp('requested_break2_end')->nullable()->after('requested_break2_start');
        });
    }

    public function down()
    {
        Schema::table('stamp_correction_requests', function (Blueprint $table) {
            $table->dropColumn([
                'requested_break1_start',
                'requested_break1_end',
                'requested_break2_start',
                'requested_break2_end',
            ]);
        });
    }
}