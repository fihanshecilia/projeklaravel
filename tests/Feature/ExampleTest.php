<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('employee.login'));
    }

    public function test_login_page_displays_employee_login_form(): void
    {
        $this->get('/masuk')
            ->assertOk()
            ->assertSee('Masuk')
            ->assertSee('Email')
            ->assertSee('Kata Sandi');
    }

    public function test_employee_login_rejects_invalid_credentials(): void
    {
        $this->post('/masuk', ['email' => 'staff@example.com', 'password' => 'wrong-password'])
            ->assertRedirect()
            ->assertSessionHasErrors('email');
    }

    public function test_store_admin_seed_uses_the_configured_email_and_password(): void
    {
        $this->seed(UserSeeder::class);

        $this->post('/masuk', [
            'email' => config('services.store_admin_email'),
            'password' => config('services.store_admin_password'),
        ])->assertRedirect('/admin');

        $this->assertAuthenticatedAs(User::whereEmail(config('services.store_admin_email'))->first());
        $this->assertTrue(Hash::check(
            config('services.store_admin_password'),
            User::whereEmail(config('services.store_admin_email'))->firstOrFail()->password,
        ));
    }
}
