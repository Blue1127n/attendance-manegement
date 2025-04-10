<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class AttendanceDateTest extends TestCase
{
    use RefreshDatabase;

    //現在の日時情報がUIと同じ形式で出力されている
    public function testDateTimeDisplay()
    {
        // Carbon::setTestNow() は日時を固定できる関数　(Carbon::create(2025, 4, 5, 13, 5))は2025年4月5日 (土)13:05で固定
        Carbon::setTestNow(Carbon::create(2025, 4, 5, 13, 5));

        // 日時のフォーマット translatedFormat(...) は日本語の曜日（例: (土)）が表示できる特別なフォーマット関数
        //translatedFormat は Laravel で Carbon（日時ライブラリ）を使うときに、曜日の日本語化ができる2025年4月5日 (土)
        $date = now()->translatedFormat('Y年n月j日 (D)');
        $time = now()->format('H:i'); //これは時間の表示だけを取り出し H = 24時間表記の「時」i = 分（分は m じゃないので注意！）

        // ユーザー作成＆ログイン User::forceCreate([...])強制的にモデルをDBに登録する関数
        //バリデーションやセキュリティ無視して全部保存できるもの（email_verified_at などを無理やり入れたから）
        //通常の User::create([...]) は モデルの $fillable に書いてあるフィールドしか登録できない
        $user = User::forceCreate([
            'last_name' => '田中',
            'first_name' => '一郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user); //actingAs($user) を使うことでログイン状態を再現

        // ページへアクセスして表示確認
        $response = $this->get('/attendance');

        // 日付と時間がページに表示されていることを確認
        //「画面（HTML）にこの文字があるか？」をチェックするテスト関数
        $response->assertSee($date);
        $response->assertSee($time);
    }
}