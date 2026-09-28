<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_manage_customers_and_link_them_to_projects(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('customers.store'), ['name' => 'Acme B.V.'])
            ->assertRedirect(route('customers.index'));

        $customer = Customer::query()->sole();
        $project = Project::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($user)
            ->asInertiaRequest()
            ->get(route('projects.show', $project))
            ->assertJsonPath('props.project.customer_name', 'Acme B.V.');

        $this->actingAs($user)->delete(route('customers.destroy', $customer))
            ->assertForbidden();

        $project->delete();

        $this->actingAs($user)->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'));
    }

    public function test_authenticated_users_can_view_and_edit_customers(): void
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create(['name' => 'Acme B.V.']);
        $project = Project::factory()->create(['customer_id' => $customer->id]);

        $this->actingAs($user)
            ->asInertiaRequest()
            ->get(route('customers.show', $customer))
            ->assertOk()
            ->assertJsonPath('component', 'Customers/Show')
            ->assertJsonPath('props.customer.name', 'Acme B.V.')
            ->assertJsonPath('props.projects.0.id', $project->id);

        $this->actingAs($user)
            ->asInertiaRequest()
            ->get(route('customers.edit', $customer))
            ->assertOk()
            ->assertJsonPath('component', 'Customers/Edit')
            ->assertJsonPath('props.customer.name', 'Acme B.V.');

        $this->actingAs($user)
            ->patch(route('customers.update', $customer), ['name' => 'Nieuwe Acme B.V.'])
            ->assertRedirect(route('customers.show', $customer));
    }
}
