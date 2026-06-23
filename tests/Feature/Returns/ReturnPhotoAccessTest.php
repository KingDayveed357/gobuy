<?php

namespace Tests\Feature\Returns;

use App\Models\User;
use App\Modules\Order\Models\Order;
use App\Modules\Returns\Models\ReturnRequest;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithAdmin;
use Tests\TestCase;

class ReturnPhotoAccessTest extends TestCase
{
    use InteractsWithAdmin;
    use LazilyRefreshDatabase;

    private function returnWithPhoto(User $owner): array
    {
        Storage::fake('public');
        $order = Order::factory()->create(['user_id' => $owner->id]);
        $return = ReturnRequest::factory()->create(['order_id' => $order->id, 'user_id' => $owner->id]);
        $media = $return->addMedia(UploadedFile::fake()->image('evidence.jpg'))
            ->toMediaCollection(ReturnRequest::MEDIA_PHOTOS);

        return [$return, $media];
    }

    public function test_the_owner_can_view_their_return_photo(): void
    {
        $owner = User::factory()->create();
        [$return, $media] = $this->returnWithPhoto($owner);

        $this->actingAs($owner)->get(route('returns.photo', [$return, $media]))->assertOk();
    }

    public function test_another_customer_cannot_view_the_photo(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        [$return, $media] = $this->returnWithPhoto($owner);

        $this->actingAs($intruder)->get(route('returns.photo', [$return, $media]))->assertForbidden();
    }

    public function test_a_guest_cannot_view_the_photo(): void
    {
        $owner = User::factory()->create();
        [$return, $media] = $this->returnWithPhoto($owner);

        $this->get(route('returns.photo', [$return, $media]))->assertForbidden();
    }

    public function test_a_returns_admin_can_view_the_photo(): void
    {
        $owner = User::factory()->create();
        [$return, $media] = $this->returnWithPhoto($owner);

        $this->actingAsAdmin('Super Admin');
        $this->get(route('returns.photo', [$return, $media]))->assertOk();
    }
}
