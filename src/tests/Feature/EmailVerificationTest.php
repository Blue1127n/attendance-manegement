<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification; //Notification::fake()を使用するため
use Tests\TestCase;
use App\Models\User;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    //会員登録後、認証メールが送信される
    public function testVerificationEmailSent()
    {
        //実際にメールを送らず、あとで「送られたか？」を確認できる
        Notification::fake(); //メールなどの通知を送らないようにして監視する

        $response = $this->post('/register', [
            'name' => '田中 一郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        //ユーザーがデータベースに存在しているか確認
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);

        //登録後のリダイレクト先（verifyページ）を確認
        $response->assertRedirect('/email/verify');

        //ユーザーを取得
        $user = User::where('email', 'test@example.com')->first();

        //メール通知（VerifyEmail）が送信されたことを確認
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function testVerifyLinkRedirects()
    {
        
    }
}
