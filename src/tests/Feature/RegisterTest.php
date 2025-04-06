<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    //①名前が未入力の場合バリデーションメッセージが表示される
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

    //②メールアドレスが未入力の場合バリデーションメッセージが表示される
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

    //③パスワードが8文字未満の場合バリデーションメッセージが表示される
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

    //④パスワードが一致しない場合バリデーションメッセージが表示される
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

    //⑤パスワードが未入力の場合バリデーションメッセージが表示される
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

    //⑥フォームに内容が入力されていた場合データが正常に保存される
    public function testRegisterOk()
    {
        $response = $this->post('/register', [
            'name' => '田中 一郎',
            'email' => 'tanaka@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'tanaka@example.com',
        ]);

        $response->assertRedirect('/email/verify'); // Fortifyを使っているから
    }
}