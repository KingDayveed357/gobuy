<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Customer\Models\WholesaleProfile;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class WholesaleIdentityTest extends TestCase
{
    use InteractsWithAdmin, LazilyRefreshDatabase;

    public function test_application_stores_intent_and_uploaded_documents(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('account.wholesale'), [
            'business_name' => 'Obi Stores Ltd',
            'business_phone' => '08030000000',
            'business_address' => 'Lagos',
            'industry' => 'Construction',
            'intent' => 'We buy helmets and boots in bulk every month.',
            'documents' => [UploadedFile::fake()->create('cac.pdf', 100, 'application/pdf')],
        ])->assertRedirect(route('account.dashboard'));

        $profile = $user->wholesaleProfile;
        $this->assertSame('We buy helmets and boots in bulk every month.', $profile->intent);
        $this->assertSame(WholesaleProfile::STATUS_PENDING, $profile->status);
        $this->assertSame(1, $profile->documents()->count());
    }

    public function test_intent_is_required(): void
    {
        $this->actingAs(User::factory()->create())->post(route('account.wholesale'), [
            'business_name' => 'Obi Stores Ltd',
            'business_phone' => '08030000000',
            'business_address' => 'Lagos',
        ])->assertSessionHasErrors('intent');
    }

    public function test_admin_approval_assigns_a_tier_and_marks_profile_approved(): void
    {
        $this->actingAsAdmin('Admin');
        $user = User::factory()->pendingWholesale()->create();
        $user->wholesaleProfile()->create([
            'business_name' => 'Obi Stores Ltd',
            'business_phone' => '08030000000',
            'business_address' => 'Lagos',
            'intent' => 'Bulk buyer',
        ]);

        $this->post(route('admin.wholesale.approve', $user), ['tier' => 'gold'])->assertRedirect();

        $profile = $user->wholesaleProfile->fresh();
        $this->assertSame(WholesaleProfile::STATUS_APPROVED, $profile->status);
        $this->assertSame('gold', $profile->tier);
        $this->assertTrue($user->fresh()->isWholesale());
    }
}
