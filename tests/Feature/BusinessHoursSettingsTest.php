<?php

namespace Tests\Feature;

use App\Models\BusinessHours;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessHoursSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_view_and_update_the_business_hours(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->asInertiaRequest()
            ->get(route('business-hours.edit'))
            ->assertOk()
            ->assertJsonPath('props.businessHours.working_days', [1, 2, 3, 4, 5])
            ->assertJsonPath('props.businessHours.starts_at', '09:00')
            ->assertJsonPath('props.businessHours.ends_at', '17:00');

        $this->actingAs($user)
            ->patch(route('business-hours.update'), [
                'working_days' => [1, 2, 3, 4],
                'starts_at' => '08:30',
                'ends_at' => '16:30',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('business_hours', [
            'id' => 1,
            'starts_at' => '08:30',
            'ends_at' => '16:30',
        ]);
        $this->assertSame([1, 2, 3, 4], BusinessHours::query()->findOrFail(1)->working_days);
    }
}
