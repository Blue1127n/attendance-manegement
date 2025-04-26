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

    public function testDateTimeDisplay()
    {
        Carbon::setTestNow(Carbon::create(2025, 4, 5, 13, 5));

        $date = now()->translatedFormat('Y年n月j日 (D)');
        $time = now()->format('H:i');

        $user = User::forceCreate([
            'last_name' => '田中',
            'first_name' => '一郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertSee($date);
        $response->assertSee($time);
    }
}