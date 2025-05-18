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

    $response->assertStatus(302);
    $response->assertRedirect('/email/verify');

    $user = User::where('email', 'test@example.com')->first();
    $this->assertNotNull($user);

    Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function testVerifyLinkRedirects()
    {
    Notification::fake();

    $this->post('/register', [
        'name' => '田中 一郎',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $user = User::where('email', 'test@example.com')->first();
    $this->assertNotNull($user);

    $this->actingAs($user);
    $response = $this->get('/email/verify');

    $response->assertStatus(200);
    $response->assertSeeText('認証はこちらから');
    $response->assertSee('/email/verification-notification');
    }

    public function testVerifiedUserCanAccessAttendancePage()
    {
    $this->post('/register', [
        'name' => '田中 一郎',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $user = User::where('email', 'test@example.com')->first();
    $this->assertNotNull($user);

    $user->email_verified_at = now();
    $user->save();

    $this->actingAs($user);
    $response = $this->get('/attendance');

    $response->assertStatus(200);
    $response->assertSee('勤務外');
    }
}
