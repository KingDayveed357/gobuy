<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Customer\Mail\OtpCodeMail;
use App\Modules\Customer\Models\OtpCode;
use App\Modules\Customer\Services\OtpService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_registration_issues_an_otp_and_redirects_to_verification(): void
    {
        Mail::fake();

        $this->post(route('register'), [
            'name' => 'New Buyer',
            'email' => 'verify@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('verification.notice'));

        $user = User::firstWhere('email', 'verify@example.com');
        $this->assertNull($user->email_verified_at);
        $this->assertDatabaseHas('otp_codes', ['user_id' => $user->id, 'purpose' => 'email_verification']);
        Mail::assertQueued(OtpCodeMail::class);
    }

    public function test_correct_code_verifies_the_email(): void
    {
        $user = User::factory()->unverified()->create();
        OtpCode::create([
            'user_id' => $user->id,
            'purpose' => 'email_verification',
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->actingAs($user)->post(route('verification.verify'), ['code' => '123456'])
            ->assertRedirect(route('account.dashboard'));

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_wrong_or_expired_code_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();
        OtpCode::create([
            'user_id' => $user->id,
            'purpose' => 'email_verification',
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->subMinute(), // expired
        ]);

        $this->actingAs($user)->post(route('verification.verify'), ['code' => '123456'])
            ->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_resend_issues_a_fresh_code_and_supersedes_the_old(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create();
        app(OtpService::class)->issue($user);
        $first = $user->otpCodes()->latest('id')->first();

        $this->travel(31)->seconds();
        $this->actingAs($user)->post(route('verification.resend'))->assertRedirect();

        $this->assertNotNull($first->fresh()->consumed_at); // old one invalidated
        $this->assertSame(1, $user->otpCodes()->whereNull('consumed_at')->count());
    }

    public function test_unverified_user_is_blocked_from_wholesale_application(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('account.wholesale'))->assertOk(); // viewing is fine

        $this->actingAs($user)->post(route('account.wholesale'), [
            'business_name' => 'X Ltd', 'business_phone' => '0803', 'business_address' => 'Lagos',
            'intent' => 'Bulk buyer',
        ])->assertRedirect(route('verification.notice'));
    }
}
