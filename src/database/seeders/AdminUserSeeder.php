<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $email = 'admin@example.com';
        $admin = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => '管理者',
                'password' => Hash::make('password'),
                'role' => 'admin'
            ]
        );
    }
}
