<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        ]);

        $createdUser = User::where('email', 'test@example.com')->first();

        $this->assertNotNull($createdUser);
        $this->assertAuthenticatedAs($createdUser);
        $response->assertRedirect(RouteServiceProvider::HOME);
    }
}
