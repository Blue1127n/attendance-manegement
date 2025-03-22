<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Carbon;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => Carbon::now(),
        ]);

        User::create([
            'name' => '一般ユーザー',
            'email' => 'user@example.com',
            'password' => Hash::make('password456'),
            'email_verified_at' => Carbon::now(),
        ]);
    }
}
