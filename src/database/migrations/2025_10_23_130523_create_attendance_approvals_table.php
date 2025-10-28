<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceApprovalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('attendance_id')->constrained('attendances')->onDelete('cascade');
            $table->timestamp('proposed_clock_in_at')->nullable();
            $table->timestamp('proposed_clock_out_at')->nullable();
            $table->text('proposed_remarks');
            $table->enum('status', ['pending', 'approved'])->default('pending');
            $table->timestamps();

            $table->index(['attendance_id', 'status'], 'attendance_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance_approvals');
    }
}
