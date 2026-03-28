<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStampCorrectionRequestBreaksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stamp_correction_request_breaks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stamp_correction_request_id');
            
            $table->timestamp('break_start_at');
            $table->timestamp('break_end_at')->nullable();
            
            $table->timestamps();

            $table->foreign('stamp_correction_request_id', 'scrb_scr_id_fk')
                ->references('id')
                ->on('stamp_correction_requests')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stamp_correction_request_breaks');
    }
}
