<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Supplier;
use App\Models\Transaction;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\PurchaseService;
use App\Services\ReportService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_decrements_stock_and_records_stock_log_atomically(): void
    {
        [$product, $cashier] = $this->makeProductAndUser();

        $transaction = app(TransactionService::class)->create([
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'amount_paid' => 24000,
            'payment_method' => 'cash',
        ], $cashier);

        $this->assertSame('completed', $transaction->status);
        $this->assertSame('8.000', $product->fresh()->stock);
        $this->assertDatabaseHas('stock_logs', [
            'transaction_id' => $transaction->id,
            'type' => 'sale',
            'quantity_change' => '-2.000',
        ]);
    }

    public function test_insufficient_payment_rolls_back_the_checkout(): void
    {
        [$product, $cashier] = $this->makeProductAndUser();

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(TransactionService::class)->create([
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'amount_paid' => 10000,
            'payment_method' => 'cash',
        ], $cashier);

        $this->assertSame('10.000', $product->fresh()->stock);
    }

    public function test_insufficient_stock_rolls_back_the_checkout(): void
    {
        [$product, $cashier] = $this->makeProductAndUser();
        $product->update(['stock' => 1]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(TransactionService::class)->create([
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'amount_paid' => 24000,
            'payment_method' => 'cash',
        ], $cashier);

        $this->assertSame('1.000', $product->fresh()->stock);
    }

    public function test_cashier_can_complete_a_pos_sale_and_open_the_receipt(): void
    {
        [$product, $cashier] = $this->makeProductAndUser();
        $order = app(TransactionService::class)->create([
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'amount_paid' => 24000,
            'status' => 'completed',
            'payment_method' => 'cash',
        ], $cashier);

        $this->actingAs($cashier)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertSee('Kasir POS');

        $this->actingAs($cashier)
            ->get(route('pos.receipts.show', $order->transaction_number))
            ->assertOk()
            ->assertSee($order->transaction_number)
            ->assertSee('Beras Test')
            ->assertSee('Tunai');

        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame('24000.00', $order->fresh()->amount_paid);
        $this->assertDatabaseHas('stock_logs', [
            'transaction_id' => $order->id,
            'type' => 'sale',
            'quantity_change' => '-2.000',
        ]);
    }

    public function test_cashier_can_sign_in_with_email_and_password(): void
    {
        $cashier = User::factory()->create([
            'email' => 'kasir@toko.local',
            'role' => 'kasir',
            'password' => 'kasirpassword123',
        ]);

        $response = $this->post('/masuk', [
            'email' => 'kasir@toko.local',
            'password' => 'kasirpassword123',
        ]);

        $response->assertRedirect(route('pos.index'));
        $this->assertAuthenticatedAs($cashier);
    }

    public function test_store_admin_can_sign_in_with_email_and_password(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@toko.local',
            'role' => 'admin_toko',
            'password' => 'adminpassword123',
        ]);

        $response = $this->post('/masuk', [
            'email' => 'admin@toko.local',
            'password' => 'adminpassword123',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_employee_login_is_available_only_at_the_internal_login_route(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/masuk')->assertOk();
        $this->get('/')->assertRedirect(route('employee.login'));
    }

    public function test_sale_with_multi_unit_converts_stock_accurately(): void
    {
        [$product, $cashier] = $this->makeProductAndUser();
        $product->update(['unit' => 'pcs', 'stock' => 100]);

        $unitDus = ProductUnit::create([
            'product_id' => $product->id,
            'unit_name' => 'Dus',
            'conversion_factor' => 24,
            'selling_price' => 250000,
        ]);

        $order = app(TransactionService::class)->create([
            'items' => [
                [
                    'product_id' => $product->id,
                    'product_unit_id' => $unitDus->id,
                    'quantity' => 2, // 2 Dus = 48 pcs
                ],
            ],
            'amount_paid' => 500000,
            'payment_method' => 'cash',
        ], $cashier);

        $this->assertSame('52.000', $product->fresh()->stock); // 100 - 48 = 52
        $this->assertDatabaseHas('transaction_details', [
            'transaction_id' => $order->id,
            'product_unit_id' => $unitDus->id,
            'unit' => 'Dus',
            'quantity' => '2.000',
            'conversion_factor' => '24.000',
            'base_quantity' => '48.000',
            'unit_price' => '250000.00',
            'subtotal' => '500000.00',
        ]);
    }

    public function test_inventory_service_restock_and_adjust_stock(): void
    {
        [$product, $cashier] = $this->makeProductAndUser();

        app(InventoryService::class)->restock($product, 15, 'Kiriman distributor', $cashier);
        $this->assertSame('25.000', $product->fresh()->stock);

        app(InventoryService::class)->adjustStock($product, 20, 'Opname fisik', $cashier);
        $this->assertSame('20.000', $product->fresh()->stock);
    }

    public function test_purchase_service_create_and_receive_restock(): void
    {
        [$product, $admin] = $this->makeProductAndUser();
        $supplier = Supplier::create(['name' => 'PT Sumber Pangan']);

        $purchase = app(PurchaseService::class)->createPurchase([
            'supplier_id' => $supplier->id,
            'purchase_date' => today(),
            'items' => [
                ['product_id' => $product->id, 'quantity' => 50, 'purchase_price' => 9500],
            ],
        ], $admin);

        $this->assertSame('ordered', $purchase->status);
        $this->assertSame('10.000', $product->fresh()->stock);

        app(PurchaseService::class)->receivePurchase($purchase, $admin);
        $this->assertSame('received', $purchase->fresh()->status);
        $this->assertSame('60.000', $product->fresh()->stock);
    }

    public function test_report_service_calculates_daily_summary_accurately(): void
    {
        [$product, $cashier] = $this->makeProductAndUser();
        app(TransactionService::class)->create([
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'amount_paid' => 24000,
            'payment_method' => 'cash',
        ], $cashier);

        $summary = app(ReportService::class)->getTodaySummary();
        $this->assertEquals(24000, $summary['dailyTotal']);
        $this->assertEquals(1, $summary['dailyCount']);
        $this->assertEquals(4000, $summary['dailyProfit']);
    }

    public function test_archiving_a_product_preserves_its_transaction_and_stock_history(): void
    {
        [$product, $customer] = $this->makeProductAndUser();
        $order = app(TransactionService::class)->create([
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'amount_paid' => 12000,
        ], $customer);

        $product->delete();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->assertDatabaseHas('transaction_details', [
            'transaction_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => 'Beras Test',
        ]);
        $this->assertDatabaseHas('stock_logs', [
            'product_id' => $product->id,
            'type' => 'sale',
        ]);

        $product->restore();
        $this->assertNull($product->fresh()->deleted_at);
    }

    private function makeProductAndUser(): array
    {
        $category = Category::create(['name' => 'Beras']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Beras Test',
            'sku' => 'BR-TEST',
            'unit' => 'kg',
            'purchase_price' => 10000,
            'selling_price' => 12000,
            'stock' => 10,
            'min_stock' => 2,
        ]);

        return [$product, User::factory()->create()];
    }
}
