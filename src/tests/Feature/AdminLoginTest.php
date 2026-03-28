<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'admin',
            'email_verified_at' => now(),
            'password' => bcrypt('password123'),
        ], $attrs));
    }

    private function createUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'user',
            'email_verified_at' => now(),
            'password' => bcrypt('password123'),
        ], $attrs));
    }

    public function test_admin_login_page_is_displayed()
    {
        $response = $this->get(route('admin.login'));

        $response->assertStatus(200);
    }

    public function test_email_is_required_for_admin_login()
    {
        $response = $this->post(route('admin.login.store'), [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_password_is_required_for_admin_login()
    {
        $response = $this->post(route('admin.login.store'), [
            'email' => 'admin@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_admin_cannot_login_with_invalid_credentials()
    {
        $this->createAdmin([
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->from(route('admin.login'))->post(route('admin.login.store'), [
            'email' => 'admin@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect(route('admin.login'));
        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_general_user_cannot_login_from_admin_login()
    {
        $this->createUser([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->from(route('admin.login'))->post(route('admin.login.store'), [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.login'));
        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_admin_can_login()
    {
        $admin = $this->createAdmin([
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post(route('admin.login.store'), [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($admin);
    }
}