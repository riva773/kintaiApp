<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RegisterAndLoginTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTE_REGISTER     = '/register';
    private const ROUTE_LOGIN        = '/login';
    private const ROUTE_ADMIN_LOGIN  = '/admin/login';

    public function test_register_name_required(): void
    {
        $payload = [
            'name' => '',
            'email' => 'user@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];
        $this->post(self::ROUTE_REGISTER, $payload)->assertSessionHasErrors(['name']);
    }

    public function test_register_email_required(): void
    {
        $payload = [
            'name' => 'Taro',
            'email' => '',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];
        $this->post(self::ROUTE_REGISTER, $payload)->assertSessionHasErrors(['email']);
    }

    public function test_register_password_min_rule(): void
    {
        $payload = [
            'name' => 'Taro',
            'email' => 'user@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ];
        $this->post(self::ROUTE_REGISTER, $payload)->assertSessionHasErrors(['password']);
    }

    public function test_register_password_confirmation_mismatch(): void
    {
        $payload = [
            'name' => 'Taro',
            'email' => 'user@example.com',
            'password' => 'password',
            'password_confirmation' => 'DIFFERENT',
        ];
        $this->post(self::ROUTE_REGISTER, $payload)->assertSessionHasErrors(['password']);
    }

    public function test_register_password_required(): void
    {
        $payload = [
            'name' => 'Taro',
            'email' => 'user@example.com',
            'password' => '',
            'password_confirmation' => '',
        ];
        $this->post(self::ROUTE_REGISTER, $payload)->assertSessionHasErrors(['password']);
    }

    public function test_register_success_persist_user(): void
    {
        $payload = [
            'name' => 'Taro',
            'email' => 'taro@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];
        $this->post(self::ROUTE_REGISTER, $payload)->assertStatus(302);
        $this->assertDatabaseHas('users', ['email' => 'taro@example.com']);
    }

    public function test_login_email_required(): void
    {
        $this->post(self::ROUTE_LOGIN, ['email' => '', 'password' => 'password'])
            ->assertSessionHasErrors(['email']);
    }

    public function test_login_password_required(): void
    {
        $this->post(self::ROUTE_LOGIN, ['email' => 'x@example.com', 'password' => ''])
            ->assertSessionHasErrors(['password']);
    }

    public function test_login_with_unregistered_account_fails(): void
    {
        $this->post(self::ROUTE_LOGIN, ['email' => 'none@example.com', 'password' => 'password'])
            ->assertSessionHasErrors();
    }

    public function test_admin_login_email_required(): void
    {
        $this->post(self::ROUTE_ADMIN_LOGIN, ['email' => '', 'password' => 'password'])
            ->assertSessionHasErrors(['email']);
    }

    public function test_admin_login_password_required(): void
    {
        $this->post(self::ROUTE_ADMIN_LOGIN, ['email' => 'admin@example.com', 'password' => ''])
            ->assertSessionHasErrors(['password']);
    }

    public function test_admin_login_with_unregistered_account_fails(): void
    {
        $this->post(self::ROUTE_ADMIN_LOGIN, ['email' => 'none@example.com', 'password' => 'password'])
            ->assertSessionHasErrors();
    }
}
