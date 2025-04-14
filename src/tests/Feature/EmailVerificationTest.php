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

        //「認証メールが送られているか？」だけを検証するtest
        //$response = $this->post('/register', [...]); // ①登録
        //$this->assertDatabaseHas(...);              // ②DB確認
        //$response->assertRedirect('/email/verify'); // ③画面遷移確認
        //$user = User::where(...)->first();          // ④ユーザー取得
        //Notification::assertSentTo($user, ...);     // ⑤通知が送られたか（重要）
        //つまり「通知が来たか？」の確認がゴールで、それまでの処理はその準備なんです
    }

    //メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する
    public function testVerifyLinkRedirects()
    {
        Notification::fake(); //メールなどの通知を送らないようにしてモニタリング

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

        // 認証メールが送られたことを確認
        $user = User::where('email', 'test@example.com')->first();
        Notification::assertSentTo($user, \Illuminate\Auth\Notifications\VerifyEmail::class);

        //登録後のリダイレクト先（verifyページ）を確認
        $response->assertRedirect('/email/verify');

        // 認証ページを表示
        $verifyPage = $this->actingAs($user)->get('/email/verify');

        $verifyPage->assertStatus(200); // ページが正常に表示される
        $verifyPage->assertSee('認証はこちらから'); // ←ボタンがあるか確認

        // 「認証はこちらから」リンクが期待通り含まれているかを確認
        $verifyPage->assertSee('/email/verification-notification');
        //「メール認証の誘導ページにボタンがある → 正しいリンクに遷移できる」ことを確認する
        //$response = $this->post('/register', [...]);               // ①登録
        //$this->assertDatabaseHas(...);                             // ②DB確認
        //$user = User::where(...)->first();                         // ③ユーザー取得
        //Notification::assertSentTo($user, ...);                    // ④通知確認
        //$response->assertRedirect('/email/verify');                // ⑤誘導ページに行ってるか
        //$verifyPage = $this->actingAs($user)->get('/email/verify');// ⑥実際にそのページを開く
        //$verifyPage->assertStatus(200);                            // ⑦ページ開けるか
        //$verifyPage->assertSee('認証はこちらから');                // ⑧ボタン確認(重要)
        //$verifyPage->assertSee('/email/verification-notification');// ⑨リンク確認（重要）
        //「画面にボタンがあること」「そのリンクが正しいこと」
        // だから /email/verify ページを実際に開いて 確認する処理が必要になってきます
    }

    public function testVerifiedUserCanAccessAttendancePage()
    {
        Notification::fake(); //メールなどの通知を送らないようにしてモニタリング

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

        //認証メールが送られたことを確認
        $user = User::where('email', 'test@example.com')->first();
        Notification::assertSentTo($user, \Illuminate\Auth\Notifications\VerifyEmail::class);

        //認証済みにする（テスト用）
        $user->email_verified_at = now();
        $user->save();

        //ログイン状態で勤怠画面にアクセス
        $response = $this->actingAs($user)->get('/attendance');

        //勤怠登録画面が表示されるか
        $response->assertStatus(200);
        $response->assertSee('勤務外'); //出勤ボタンなど画面要素を確認
    }
}
