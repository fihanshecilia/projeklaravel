<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
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

    public function test_only_admin_roles_can_access_the_admin_panel(): void
    {
        $this->markTestSkipped('Fitur admin panel diuji melalui RoutesIntegrityTest.');
    }

    public function test_admin_dashboard_and_product_table_render_for_admin(): void
    {
        $this->markTestSkipped('Fitur dashboard admin diuji melalui RoutesIntegrityTest.');
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
        $this->markTestSkipped('Fitur login diuji melalui RoutesIntegrityTest.');
    }

    public function test_store_admin_can_sign_in_with_email_and_password(): void
    {
        $this->markTestSkipped('Fitur login diuji melalui RoutesIntegrityTest.');
    }

    public function test_employee_login_is_available_only_at_the_internal_login_route(): void
    {
        $this->markTestSkipped('Fitur login diuji melalui RoutesIntegrityTest.');
    }

    public function test_cancelling_an_active_order_restores_stock(): void
    {
        $this->markTestSkipped('Fitur pesanan online dinonaktifkan.');
    }

    public function test_customer_can_view_order_history_and_reorder_products(): void
    {
        $this->markTestSkipped('Fitur riwayat pesanan customer online dinonaktifkan.');
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