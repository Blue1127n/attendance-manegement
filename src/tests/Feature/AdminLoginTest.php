<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function testEmailError()
    {
        User::create([
            'last_name' => '田中',
            'first_name' => '次郎',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/admin/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertEquals('メールアドレスを入力してください', session('errors')->first('email'));
    }

    public function testEmptyPassword()
    {
        User::create([
            'last_name' => '田中',
            'first_name' => '次郎',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertEquals('パスワードを入力してください', session('errors')->first('password'));
    }

    public function testMismatchEmail()
    {
        User::create([
            'last_name' => '田中',
            'first_name' => '次郎',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'error@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertEquals('ログイン情報が登録されていません', session('errors')->first('email'));
    }
}