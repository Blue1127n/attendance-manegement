<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function testNameError()
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['name']);
        $this->assertEquals('お名前を入力してください', session('errors')->first('name'));
    }

    public function testEmailError()
    {
        $response = $this->post('/register', [
            'name' => '田中 一郎',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertEquals('メールアドレスを入力してください', session('errors')->first('email'));
    }

    public function testShortPassword()
    {
        $response = $this->post('/register', [
            'name' => '田中 一郎',
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertEquals('パスワードは8文字以上で入力してください', session('errors')->first('password'));
    }

    public function testMismatchPassword()
    {
        $response = $this->post('/register', [
            'name' => '田中 一郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertEquals('パスワードと一致しません', session('errors')->first('password'));
    }

    public function testEmptyPassword()
    {
        $response = $this->post('/register', [
            'name' => '田中 一郎',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertEquals('パスワードを入力してください', session('errors')->first('password'));
    }

    public function testRegisterOk()
    {
        $response = $this->post('/register', [
            'name' => '田中 一郎',
            'email' => 'tanaka@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/email/verify');

        $this->assertDatabaseHas('users', [
            'email' => 'tanaka@example.com',
        ]);
    }
}