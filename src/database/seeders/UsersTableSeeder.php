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
            'last_name' => '管理者',
            'first_name' => 'ユーザー',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => Carbon::now(),
        ]);

        User::create([
            'last_name' => '一般',
            'first_name' => 'ユーザー1',
            'email' => 'user1@example.com',
            'password' => Hash::make('password234'),
            'email_verified_at' => Carbon::now(),
        ]);

        User::create([
            'last_name' => '一般',
            'first_name' => 'ユーザー2',
            'email' => 'user2@example.com',
            'password' => Hash::make('password345'),
            'email_verified_at' => Carbon::now(),
        ]);

        User::create([
            'last_name' => '一般',
            'first_name' => 'ユーザー3',
            'email' => 'user3@example.com',
            'password' => Hash::make('password456'),
            'email_verified_at' => Carbon::now(),
        ]);

        User::create([
            'last_name' => '一般',
            'first_name' => 'ユーザー4',
            'email' => 'user4@example.com',
            'password' => Hash::make('password567'),
            'email_verified_at' => Carbon::now(),
        ]);

        User::create([
            'last_name' => '一般',
            'first_name' => 'ユーザー5',
            'email' => 'user5@example.com',
            'password' => Hash::make('password678'),
            'email_verified_at' => Carbon::now(),
        ]);
    }
}
