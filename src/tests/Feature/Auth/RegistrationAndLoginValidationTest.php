<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RegistrationAndLoginValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_requires_fields(): void
    {
        $res = $this->post('/register', []);
        $res->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_register_requires_password_confirmation_match(): void
    {
        $payload = [
            'name'                  => 'Taro',
            'email'                 => 'taro@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'different123',
        ];
        $res = $this->post('/register', $payload);
        $res->assertSessionHasErrors(['password']);
    }

    public function test_login_requires_fields(): void
    {
        $res = $this->post('/login', []);
        $res->assertSessionHasErrors(['email', 'password']);
    }

    public function test_login_invalid_credentials(): void
    {
        User::factory()->create([
            'email'    => 'user@example.com',
            'password' => Hash::make('password123'),
        ]);

        $res = $this->post('/login', [
            'email'    => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        $res->assertSessionHasErrors(['email']);
    }
}
