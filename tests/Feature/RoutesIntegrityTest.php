<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoutesIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_and_auth_routes(): void
    {
        $this->get('/')->assertRedirect(route('employee.login'));
        $this->get('/masuk')->assertOk();
        $this->get('/login')->assertOk();
    }

    public function test_cashier_can_access_pos_pages(): void
    {
        $cashier = User::factory()->create(['role' => 'kasir']);

        $this->actingAs($cashier)->get('/kasir')->assertOk();
        $this->actingAs($cashier)->get('/kasir/histori')->assertOk();
    }

    public function test_admin_can_access_admin_pages_and_reports(): void
    {
        $admin = User::factory()->create(['role' => 'admin_toko']);

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/admin/laporan')->assertOk();
        $this->actingAs($admin)->get('/admin/laporan/stok')->assertOk();
    }

    public function test_guest_is_redirected_to_employee_login(): void
    {
        $this->get('/kasir')->assertRedirect(route('employee.login'));
        $this->get('/admin/laporan')->assertRedirect(route('employee.login'));
    }
}
