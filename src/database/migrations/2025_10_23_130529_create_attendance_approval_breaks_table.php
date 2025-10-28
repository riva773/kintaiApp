<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceApprovalBreaksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_approval_breaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_approval_id')
                ->constrained('attendance_approvals')
                ->onDelete('cascade');
            $table->unsignedInteger('sequence_no');
            $table->timestamp('proposed_break_started_at')->nullable();
            $table->timestamp('proposed_break_ended_at')->nullable();
            $table->timestamps();

            $table->index(['attendance_approval_id', 'sequence_no'], 'approval_break_seq_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance_approval_breaks');
    }
}
