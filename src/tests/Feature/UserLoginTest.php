<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserLoginTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'user',
            'email_verified_at' => now(),
            'password' => bcrypt('password123'),
        ], $attrs));
    }

    private function createAdmin(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'admin',
            'email_verified_at' => now(),
            'password' => bcrypt('password123'),
        ], $attrs));
    }

    public function test_login_page_is_displayed()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_email_is_required_for_login()
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_password_is_required_for_login()
    {
        $response = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_user_cannot_login_with_invalid_credentials()
    {
        $this->createUser([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'user@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_admin_cannot_login_from_user_login()
    {
        $this->createAdmin([
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_user_can_login()
    {
        $user = $this->createUser([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }
}