<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('breaks', function (Blueprint $table) {
            if (!Schema::hasColumn('breaks', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('attendance_id');
            }
            if (Schema::hasColumn('breaks', 'sequence_no')) {
                $table->index('sequence_no');
            }
        });

        $rows = DB::table('breaks')->whereNull('user_id')->get(['id', 'attendance_id']);
        foreach ($rows as $r) {
            $uid = DB::table('attendances')->where('id', $r->attendance_id)->value('user_id');
            if (!is_null($uid)) {
                DB::table('breaks')->where('id', $r->id)->update(['user_id' => $uid]);
            }
        }

        Schema::table('breaks', function (Blueprint $table) {
            try {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            } catch (\Throwable $e) {
            }
        });

    }

    public function down(): void
    {
        Schema::table('breaks', function (Blueprint $table) {
            try {
                $table->dropForeign(['user_id']);
            } catch (\Throwable $e) {
            }
            if (Schema::hasColumn('breaks', 'user_id')) {
                $table->dropColumn('user_id');
            }
        });
    }
};
