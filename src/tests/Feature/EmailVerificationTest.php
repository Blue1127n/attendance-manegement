<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use App\Models\User;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function testVerificationEmailSent()
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => '田中 一郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);

        $response->assertRedirect('/email/verify');

        $user = User::where('email', 'test@example.com')->first();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function testVerifyLinkRedirects()
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => '田中 一郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);

        $user = User::where('email', 'test@example.com')->first();
        Notification::assertSentTo($user, \Illuminate\Auth\Notifications\VerifyEmail::class);

        $response->assertRedirect('/email/verify');

        $verifyPage = $this->actingAs($user)->get('/email/verify');
        $verifyPage->assertStatus(200);
        $verifyPage->assertSee('認証はこちらから');
        $verifyPage->assertSee('/email/verification-notification');
    }

    public function testVerifiedUserCanAccessAttendancePage()
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => '田中 一郎',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);

        $user = User::where('email', 'test@example.com')->first();
        Notification::assertSentTo($user, \Illuminate\Auth\Notifications\VerifyEmail::class);

        $user->email_verified_at = now();
        $user->save();

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertStatus(200);
        $response->assertSee('勤務外');
    }
}
