<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Customer\Customer;
use App\Models\Entitas\Entitas;
use App\Models\User;
use Database\Seeders\MasterDataPermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(MasterDataPermissionSeeder::class);
    }

    private function makeEntityUser(?Entitas $entitas = null): User
    {
        $entitas ??= Entitas::create(['entity_type' => 'perusahaan', 'name' => 'PT Test', 'address' => 'Jl. Test']);

        $user = User::create([
            'name' => 'Entity User',
            'email' => uniqid('user', true).'@example.test',
            'username' => uniqid('user', true),
            'password' => bcrypt('password'),
            'entity_id' => $entitas->id,
            'is_active' => true,
            'onboarding_completed' => true,
        ]);

        $user->assignRole('perusahaan');

        return $user;
    }

    private function makeCountry(): Country
    {
        return Country::create(['name' => 'Singapore', 'iso_code' => 'SG', 'iso_code3' => 'SGP', 'phone_code' => '65']);
    }

    public function test_perusahaan_user_can_create_a_customer_for_their_own_entity(): void
    {
        $user = $this->makeEntityUser();
        $country = $this->makeCountry();

        $response = $this->actingAs($user)->post(route('customer.store'), [
            'name' => 'ACME Corp',
            'country_id' => $country->id,
            'address' => 'Somewhere',
        ]);

        $response->assertRedirect(route('customer.index'));
        $this->assertDatabaseHas('customer_companies', [
            'name' => 'ACME Corp',
            'entity_id' => $user->entity_id,
        ]);
    }

    public function test_entity_id_cannot_be_spoofed_via_the_request(): void
    {
        $user = $this->makeEntityUser();
        $otherEntitas = Entitas::create(['entity_type' => 'perusahaan', 'name' => 'PT Other', 'address' => 'Jl. Lain']);
        $country = $this->makeCountry();

        $this->actingAs($user)->post(route('customer.store'), [
            'entity_id' => $otherEntitas->id,
            'name' => 'Spoofed Corp',
            'country_id' => $country->id,
        ]);

        $this->assertDatabaseHas('customer_companies', [
            'name' => 'Spoofed Corp',
            'entity_id' => $user->entity_id,
        ]);
        $this->assertDatabaseMissing('customer_companies', [
            'name' => 'Spoofed Corp',
            'entity_id' => $otherEntitas->id,
        ]);
    }

    public function test_user_without_view_all_only_sees_their_own_entitys_customers(): void
    {
        $ownEntitas = Entitas::create(['entity_type' => 'perusahaan', 'name' => 'PT Own', 'address' => 'Jl. Own']);
        $otherEntitas = Entitas::create(['entity_type' => 'perusahaan', 'name' => 'PT Other', 'address' => 'Jl. Other']);
        $country = $this->makeCountry();

        $ownCustomer = Customer::create(['name' => 'Own Customer', 'country_id' => $country->id, 'entity_id' => $ownEntitas->id]);
        $otherCustomer = Customer::create(['name' => 'Other Customer', 'country_id' => $country->id, 'entity_id' => $otherEntitas->id]);

        $user = $this->makeEntityUser($ownEntitas);

        $response = $this->actingAs($user)->get(route('customer.index'));

        $response->assertOk();
        $ids = $response->viewData('customers')->pluck('id');
        $this->assertTrue($ids->contains($ownCustomer->id));
        $this->assertFalse($ids->contains($otherCustomer->id));
    }

    public function test_user_with_view_all_sees_every_entitys_customers(): void
    {
        $ownEntitas = Entitas::create(['entity_type' => 'perusahaan', 'name' => 'PT Own', 'address' => 'Jl. Own']);
        $otherEntitas = Entitas::create(['entity_type' => 'perusahaan', 'name' => 'PT Other', 'address' => 'Jl. Other']);
        $country = $this->makeCountry();

        $ownCustomer = Customer::create(['name' => 'Own Customer', 'country_id' => $country->id, 'entity_id' => $ownEntitas->id]);
        $otherCustomer = Customer::create(['name' => 'Other Customer', 'country_id' => $country->id, 'entity_id' => $otherEntitas->id]);

        $user = $this->makeEntityUser($ownEntitas);
        $user->givePermissionTo('customer.view-all');

        $response = $this->actingAs($user)->get(route('customer.index'));

        $response->assertOk();
        $ids = $response->viewData('customers')->pluck('id');
        $this->assertTrue($ids->contains($ownCustomer->id));
        $this->assertTrue($ids->contains($otherCustomer->id));
    }

    public function test_view_all_does_not_grant_edit_or_delete_rights_over_another_entitys_customer(): void
    {
        $ownEntitas = Entitas::create(['entity_type' => 'perusahaan', 'name' => 'PT Own', 'address' => 'Jl. Own']);
        $otherEntitas = Entitas::create(['entity_type' => 'perusahaan', 'name' => 'PT Other', 'address' => 'Jl. Other']);
        $country = $this->makeCountry();

        $otherCustomer = Customer::create(['name' => 'Other Customer', 'country_id' => $country->id, 'entity_id' => $otherEntitas->id]);

        $user = $this->makeEntityUser($ownEntitas);
        $user->givePermissionTo('customer.view-all');

        $this->actingAs($user)->put(route('customer.update', $otherCustomer), [
            'name' => 'Hacked Name',
            'country_id' => $country->id,
        ])->assertForbidden();

        $this->actingAs($user)->delete(route('customer.destroy', $otherCustomer))->assertForbidden();

        $this->assertDatabaseHas('customer_companies', ['id' => $otherCustomer->id, 'name' => 'Other Customer']);
    }

    public function test_user_can_update_and_delete_their_own_customer(): void
    {
        $entitas = Entitas::create(['entity_type' => 'perusahaan', 'name' => 'PT Own', 'address' => 'Jl. Own']);
        $country = $this->makeCountry();
        $customer = Customer::create(['name' => 'Old Name', 'country_id' => $country->id, 'entity_id' => $entitas->id]);

        $user = $this->makeEntityUser($entitas);

        $this->actingAs($user)->put(route('customer.update', $customer), [
            'name' => 'New Name',
            'country_id' => $country->id,
            'address' => 'New Address',
        ])->assertRedirect(route('customer.index'));

        $this->assertSame('New Name', $customer->fresh()->name);

        $this->actingAs($user)->delete(route('customer.destroy', $customer))
            ->assertRedirect(route('customer.index'));

        $this->assertDatabaseMissing('customer_companies', ['id' => $customer->id]);
    }

    public function test_user_without_any_permission_cannot_view_index(): void
    {
        $user = User::create([
            'name' => 'No Perm',
            'email' => uniqid('noperm', true).'@example.test',
            'username' => uniqid('noperm', true),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->actingAs($user)->get(route('customer.index'))->assertForbidden();
    }
}
