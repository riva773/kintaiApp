<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSequenceNoToBreaks extends Migration
{
    public function up()
    {
        Schema::table('breaks', function (Blueprint $table) {
            if (!Schema::hasColumn('breaks', 'sequence_no')) {
                $table->unsignedInteger('sequence_no')->after('attendance_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('breaks', function (Blueprint $table) {
            if (Schema::hasColumn('breaks', 'sequence_no')) {
                $table->dropColumn('sequence_no');
            }
        });
    }
}
