<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_the_registration_screen(): void
    {
        $response = $this->get('/register');

        $response->assertRedirect('/login');
    }

    public function test_management_users_can_register_new_users(): void
    {
        $manager = User::factory()->create([
            'role' => 10,
        ]);

        $response = $this->actingAs($manager)->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 1,
            'active_flg' => 1,
        ]);

        $createdUser = User::where('email', 'test@example.com')->first();

        $this->assertNotNull($createdUser);
        $this->assertSame(1, $createdUser->active_flg);
        $this->assertAuthenticatedAs($manager);
        $response->assertRedirect(route('register.allshow'));
    }

    public function test_management_users_can_update_users(): void
    {
        $manager = User::factory()->create([
            'role' => 10,
        ]);
        $targetUser = User::factory()->create([
            'role' => 1,
            'active_flg' => 1,
        ]);

        $response = $this->actingAs($manager)->post(route('register.update', ['id' => $targetUser->id]), [
            'name' => 'Updated User',
            'email' => 'updated@example.com',
            'role' => 10,
            'active_flg' => 0,
        ]);

        $response->assertRedirect(route('register.allshow'));
        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'name' => 'Updated User',
            'email' => 'updated@example.com',
            'role' => 10,
            'active_flg' => 0,
        ]);
    }

    public function test_deleting_user_with_related_records_deactivates_instead(): void
    {
        $manager = User::factory()->create([
            'role' => 10,
        ]);
        $targetUser = User::factory()->create([
            'active_flg' => 1,
        ]);

        DB::table('payments')->insert([
            'user_id' => $targetUser->id,
            'payment_year' => 2026,
            'payment_month' => 4,
            'payment_amount' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($manager)->delete(route('register.destroy', ['id' => $targetUser->id]));

        $response->assertRedirect(route('register.allshow'));
        $this->assertDatabaseHas('users', [
            'id' => $targetUser->id,
            'active_flg' => 0,
        ]);
    }
}
