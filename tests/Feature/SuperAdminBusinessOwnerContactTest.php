<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminBusinessOwnerContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_an_owner_with_required_phone_and_optional_email(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)->post(route('super-admin.businesses.store'), [
            'name' => 'Phone First Hardware',
            'code' => 'PHONEFIRST',
            'branch_name' => 'Main Branch',
            'branch_code' => 'MAIN',
            'owner_name' => 'Asha Owner',
            'owner_phone' => '712345678',
            'owner_password' => 'secure-password',
            'owner_password_confirmation' => 'secure-password',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['name' => 'Asha Owner', 'phone' => '255712345678', 'email' => null]);
    }

    public function test_owner_phone_is_required_and_email_is_not(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)->post(route('super-admin.businesses.store'), [
            'name' => 'Missing Phone Hardware',
            'code' => 'MISSPHONE',
            'branch_name' => 'Main Branch',
            'branch_code' => 'MAIN',
            'owner_name' => 'No Phone Owner',
            'owner_password' => 'secure-password',
            'owner_password_confirmation' => 'secure-password',
        ])->assertSessionHasErrors('owner_phone');
    }
}
