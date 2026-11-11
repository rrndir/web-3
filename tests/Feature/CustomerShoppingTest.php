<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CustomerShoppingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        Schema::connection('sqlite')->create('user', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('email')->unique();
            $table->string('role');
            $table->boolean('status');
            $table->string('password');
            $table->timestamps();
        });
        Schema::connection('sqlite')->create('customer', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('google_id')->nullable();
            $table->string('google_token')->nullable();
            $table->string('alamat')->nullable();
            $table->string('pos')->nullable();
            $table->timestamps();
        });
        Schema::connection('sqlite')->create('kategori', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kategori');
            $table->timestamps();
        });
        Schema::connection('sqlite')->create('produk', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kategori_id');
            $table->unsignedBigInteger('user_id');
            $table->boolean('status');
            $table->string('nama_produk');
            $table->text('detail');
            $table->double('harga');
            $table->integer('stok');
            $table->float('berat');
            $table->string('foto');
            $table->timestamps();
        });
        Schema::connection('sqlite')->create('order', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status');
            $table->string('noresi')->nullable();
            $table->string('kurir')->nullable();
            $table->string('layanan_ongkir')->nullable();
            $table->string('biaya_ongkir')->nullable();
            $table->string('estimasi_ongkir')->nullable();
            $table->integer('total_berat')->nullable();
            $table->double('total_harga');
            $table->text('alamat')->nullable();
            $table->string('pos')->nullable();
            $table->string('telepon_penerima', 30)->nullable();
            $table->string('payment_status')->default('unpaid');
            $table->string('payment_proof_path')->nullable();
            $table->timestamp('payment_submitted_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('fulfillment_type')->default('physical');
            $table->longText('digital_delivery_codes')->nullable();
            $table->string('destination_province_id', 30)->nullable();
            $table->string('destination_province_name')->nullable();
            $table->string('destination_city_id', 30)->nullable();
            $table->string('destination_city_name')->nullable();
            $table->string('shipping_service_code', 80)->nullable();
            $table->timestamps();
        });
        Schema::connection('sqlite')->create('order_item', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('produk_id');
            $table->integer('quantity');
            $table->double('harga');
            $table->timestamps();
        });
    }

    public function test_customer_can_register_and_get_a_customer_profile(): void
    {
        $response = $this->post(route('customer.register.submit'), [
            'nama' => 'Pembeli',
            'email' => 'buyer@example.test',
            'password' => 'password-123',
            'password_confirmation' => 'password-123',
            'redirect' => '/beranda',
        ]);

        $response->assertRedirect('/beranda');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('user', [
            'email' => 'buyer@example.test',
            'role' => '2',
            'status' => 1,
        ]);
        $this->assertDatabaseHas('customer', [
            'user_id' => Auth::id(),
        ]);
    }

    public function test_guest_is_sent_to_customer_login_when_opening_cart(): void
    {
        $this->get(route('order.cart'))
            ->assertRedirect(route('customer.login', ['redirect' => '/cart']));
    }

    public function test_customer_can_add_published_product_and_view_cart(): void
    {
        [$user, $customer] = $this->createCustomer();
        $product = $this->createProduct($user, ['stok' => 2]);

        $this->actingAs($user)
            ->post(route('order.addToCart', $product->id))
            ->assertRedirect(route('order.cart'));

        $order = Order::where('customer_id', $customer->id)->where('status', 'pending')->firstOrFail();
        $this->assertSame(100000.0, (float) $order->total_harga);
        $this->assertDatabaseHas('order_item', [
            'order_id' => $order->id,
            'produk_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->get(route('order.cart'))
            ->assertOk()
            ->assertSee('Game Test')
            ->assertSee('Rp. 100.000')
            ->assertSee(route('order.checkout'));
    }

    public function test_ajax_add_to_cart_returns_popup_data_without_redirecting(): void
    {
        [$user] = $this->createCustomer();
        $product = $this->createProduct($user);

        $this->actingAs($user)
            ->postJson(route('order.addToCart', $product->id))
            ->assertOk()
            ->assertJsonPath('product_name', 'Game Test')
            ->assertJsonPath('quantity', 1)
            ->assertJsonPath('cart_count', 1)
            ->assertJsonPath('cart_url', route('order.cart'));
    }

    public function test_ajax_add_to_cart_returns_error_for_out_of_stock_product(): void
    {
        [$user] = $this->createCustomer();
        $product = $this->createProduct($user, ['stok' => 0]);

        $this->actingAs($user)
            ->postJson(route('order.addToCart', $product->id))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Produk sedang habis.');

        $this->assertDatabaseMissing('order', ['customer_id' => Customer::where('user_id', $user->id)->value('id')]);
    }

    public function test_customer_cannot_add_more_than_available_stock(): void
    {
        [$user, $customer] = $this->createCustomer();
        $product = $this->createProduct($user, ['stok' => 1]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'status' => 'pending',
            'total_harga' => 100000,
        ]);
        $item = $order->orderItems()->create([
            'produk_id' => $product->id,
            'quantity' => 1,
            'harga' => 100000,
        ]);

        $this->actingAs($user)
            ->from(route('order.cart'))
            ->post(route('order.addToCart', $product->id))
            ->assertRedirect(route('order.cart'))
            ->assertSessionHas('error');

        $this->assertSame(1, $item->fresh()->quantity);
    }

    public function test_customer_can_update_and_remove_items_in_their_cart(): void
    {
        [$user, $customer] = $this->createCustomer();
        $product = $this->createProduct($user, ['stok' => 3]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'status' => 'pending',
            'total_harga' => 100000,
        ]);
        $item = $order->orderItems()->create([
            'produk_id' => $product->id,
            'quantity' => 1,
            'harga' => 100000,
        ]);

        $this->actingAs($user)
            ->put(route('order.updateCart', $item->id), ['quantity' => 3])
            ->assertRedirect(route('order.cart'));

        $this->assertSame(3, $item->fresh()->quantity);
        $this->assertSame(300000.0, (float) $order->fresh()->total_harga);

        $this->delete(route('order.removeFromCart', $item->id))
            ->assertRedirect(route('order.cart'));

        $this->assertDatabaseMissing('order_item', ['id' => $item->id]);
        $this->assertSame(0.0, (float) $order->fresh()->total_harga);
    }

    public function test_customer_cannot_edit_or_remove_another_customers_cart_items(): void
    {
        [$owner, $ownerCustomer] = $this->createCustomer('owner@example.test');
        [$otherUser] = $this->createCustomer('other@example.test');
        $product = $this->createProduct($owner);
        $order = Order::create([
            'customer_id' => $ownerCustomer->id,
            'status' => 'pending',
            'total_harga' => 100000,
        ]);
        $item = $order->orderItems()->create([
            'produk_id' => $product->id,
            'quantity' => 1,
            'harga' => 100000,
        ]);

        $this->actingAs($otherUser)
            ->put(route('order.updateCart', $item->id), ['quantity' => 2])
            ->assertNotFound();

        $this->actingAs($otherUser)
            ->delete(route('order.removeFromCart', $item->id))
            ->assertNotFound();

        $this->assertDatabaseHas('order_item', ['id' => $item->id, 'quantity' => 1]);
    }

    public function test_customer_can_login_with_email_and_password(): void
    {
        [$user] = $this->createCustomer();

        Auth::logout();

        $this->post(route('customer.login.submit'), [
            'email' => $user->email,
            'password' => 'customer-password',
        ])->assertRedirect(route('order.cart'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_customer_checkout_bank_transfer_and_admin_fulfillment_flow(): void
    {
        Storage::fake('local');
        config()->set([
            'shipping.rajaongkir.api_key' => 'test-key',
            'shipping.rajaongkir.base_url' => 'https://api.test/api/v1',
            'shipping.rajaongkir.origin_city_id' => '151',
        ]);
        Http::fake([
            'https://api.test/api/v1/destination/domestic-destination*' => Http::response([
                'meta' => ['message' => 'Success', 'code' => 200, 'status' => 'success'],
                'data' => [[
                    'id' => 152,
                    'label' => 'Kebayoran Baru, Jakarta Selatan, DKI Jakarta',
                    'province_name' => 'DKI Jakarta',
                    'city_name' => 'Jakarta Selatan',
                    'district_name' => 'Kebayoran Baru',
                ]],
            ]),
            'https://api.test/api/v1/calculate/domestic-cost' => Http::response([
                'meta' => ['message' => 'Success', 'code' => 200, 'status' => 'success'],
                'data' => [[
                    'name' => 'JNE',
                    'code' => 'jne',
                    'service' => 'REG',
                    'description' => 'Layanan Reguler',
                    'cost' => 10000,
                    'etd' => '2-3 hari',
                ]],
            ]),
        ]);
        config()->set([
            'shop.bank_name' => 'Bank Test',
            'shop.bank_account_number' => '1234567890',
            'shop.bank_account_name' => 'Warung Game',
        ]);
        [$customerUser, $customer] = $this->createCustomer();
        $product = $this->createProduct($customerUser, ['stok' => 3]);

        $this->actingAs($customerUser)
            ->post(route('order.addToCart', $product->id))
            ->assertRedirect(route('order.cart'));

        $this->get(route('order.checkout'))
            ->assertOk()
            ->assertSee('Checkout')
            ->assertSee('Game Test')
            ->assertSee('Cek ongkir');

        $shippingFields = [
            'fulfillment_type' => 'physical',
            'alamat' => 'Jl. Game No. 10',
            'pos' => '12345',
            'telepon_penerima' => '081234567890',
            'destination_city_id' => '152',
            'destination_search' => 'Kebayoran Baru, Jakarta Selatan, DKI Jakarta',
            'courier' => 'jne',
            'shipping_service_code' => 'REG',
            'shipping_description' => 'Layanan Reguler',
            'shipping_cost' => 10000,
            'shipping_etd' => '2-3 hari',
        ];
        $this->postJson(route('order.checkout-shipping-options'), [
            'courier' => 'jne',
            'destination_city_id' => '999',
            'destination_search' => 'Kebayoran Baru, Jakarta Selatan, DKI Jakarta',
        ])->assertUnprocessable();

        $product->berat = 0;
        $product->save();
        $this->postJson(route('order.checkout-shipping-options'), [
            'courier' => 'jne',
            'destination_city_id' => '152',
            'destination_search' => 'Kebayoran Baru, Jakarta Selatan, DKI Jakarta',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Berat produk Game Test belum diatur. Hubungi admin untuk melengkapi berat produk.');
        $product->berat = 100;
        $product->save();

        $this->postJson(route('order.checkout-shipping-options'), [
            'courier' => 'jne',
            'destination_city_id' => '152',
            'destination_search' => 'Kebayoran Baru, Jakarta Selatan, DKI Jakarta',
        ])
            ->assertOk()
            ->assertJsonPath('quotes.0.service', 'REG')
            ->assertJsonPath('quotes.0.cost', 10000);
        Http::assertSent(fn ($request) =>
            str_ends_with($request->url(), '/calculate/domestic-cost')
            && (string) $request['destination'] === '152'
            && (int) $request['weight'] === 100
            && $request['courier'] === 'jne'
        );

        $this->post(route('order.place'), array_merge($shippingFields, ['shipping_cost' => 1]))
            ->assertSessionHasErrors('shipping');
        $this->assertSame('pending', Order::where('customer_id', $customer->id)->firstOrFail()->status);

        $placeOrderResponse = $this->post(route('order.place'), $shippingFields);

        $order = Order::where('customer_id', $customer->id)->firstOrFail();
        $placeOrderResponse->assertRedirect(route('order.show', $order));
        $this->assertSame('pending_payment', $order->status);
        $this->assertSame('Kebayoran Baru, Jakarta Selatan, DKI Jakarta', $order->destination_city_name);
        $this->assertSame('JNE', $order->kurir);
        $this->assertSame('REG', $order->shipping_service_code);
        $this->assertSame('10000', $order->biaya_ongkir);
        $this->assertSame(110000.0, (float) $order->total_harga);

        $admin = User::create([
            'nama' => 'Admin',
            'email' => 'admin@example.test',
            'role' => '1',
            'status' => 1,
            'password' => Hash::make('admin-password'),
        ]);

        $this->actingAs($admin)
            ->get(route('backend.order.show', $order))
            ->assertOk()
            ->assertSee('Kebayoran Baru, Jakarta Selatan')
            ->assertSee('JNE')
            ->assertDontSee('Tentukan Pengiriman dan Ongkir');

        $order->refresh();
        $this->assertSame('pending_payment', $order->status);
        $this->actingAs($customerUser)
            ->get(route('order.show', $order))
            ->assertOk()
            ->assertSee('1234567890')
            ->assertSee('Warung Game')
            ->assertSee('Layanan Reguler')
            ->assertSee('110.000');

        $this->actingAs($customerUser)
            ->post(route('order.payment-proof.upload', $order), [
                'payment_proof' => UploadedFile::fake()->createWithContent('transfer.pdf', '%PDF-1.4 test proof'),
            ])
            ->assertRedirect(route('order.show', $order));

        $order->refresh();
        $this->assertSame('under_review', $order->payment_status);
        Storage::disk('local')->assertExists($order->payment_proof_path);

        $this->actingAs($admin)
            ->get(route('backend.order.show', $order))
            ->assertOk()
            ->assertSee('Bukti Transfer');
        $this->get(route('backend.order.payment-proof', $order))
            ->assertDownload(basename($order->payment_proof_path));

        $this->put(route('backend.order.payment', $order), ['decision' => 'approve'])
            ->assertRedirect(route('backend.order.show', $order));

        $this->assertSame('processing', $order->fresh()->status);
        $this->assertSame(2, $product->fresh()->stok);

        $this->actingAs($admin)
            ->get(route('backend.order.show', $order))
            ->assertOk()
            ->assertSee('Masukkan nomor resi setelah paket diserahkan kepada kurir');
        $this->put(route('backend.order.ship', $order), ['noresi' => 'RESI123'])
            ->assertRedirect(route('backend.order.show', $order));
        $this->assertSame('shipped', $order->fresh()->status);
        $this->assertSame('RESI123', $order->fresh()->noresi);

        $this->put(route('backend.order.complete', $order))
            ->assertRedirect(route('backend.order.show', $order));
        $this->assertSame('completed', $order->fresh()->status);
    }

    public function test_shipping_api_error_reports_http_status_without_exposing_credentials(): void
    {
        config()->set([
            'shipping.rajaongkir.api_key' => 'sensitive-test-key',
            'shipping.rajaongkir.base_url' => 'https://api.test/api/v1',
        ]);
        Http::fake([
            'https://api.test/api/v1/destination/domestic-destination*' => Http::response([
                'meta' => ['message' => 'Invalid API key', 'code' => 401, 'status' => 'error'],
            ], 401),
        ]);
        [$customerUser] = $this->createCustomer();

        $this->actingAs($customerUser)
            ->getJson(route('order.shipping-destinations', ['q' => 'Jakarta']))
            ->assertStatus(503)
            ->assertJsonPath('message', 'Tidak dapat mengambil data (HTTP 401). Periksa API key dan akses paket API Komerce. Pesan provider: Invalid API key')
            ->assertDontSee('sensitive-test-key');
    }

    public function test_admin_cannot_request_shipping_quote_for_order_without_destination(): void
    {
        [$customerUser, $customer] = $this->createCustomer();
        $admin = User::create([
            'nama' => 'Admin',
            'email' => 'admin@example.test',
            'role' => '1',
            'status' => 1,
            'password' => Hash::make('admin-password'),
        ]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'status' => 'awaiting_shipping',
            'fulfillment_type' => 'physical',
            'total_harga' => 100000,
        ]);

        $this->actingAs($admin)
            ->postJson(route('backend.order.shipping-options', $order), ['courier' => 'jne'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Pesanan ini belum memiliki lokasi tujuan. Customer perlu memilih lokasi tujuan dan membuat ulang pesanan.');
    }

    public function test_customer_cannot_submit_proof_before_shipping_cost_is_set(): void
    {
        Storage::fake('local');
        config()->set([
            'shop.bank_name' => 'Bank Test',
            'shop.bank_account_number' => '1234567890',
        ]);
        [$customerUser, $customer] = $this->createCustomer();
        $order = Order::create([
            'customer_id' => $customer->id,
            'status' => 'awaiting_shipping',
            'total_harga' => 100000,
        ]);

        $this->actingAs($customerUser)
            ->post(route('order.payment-proof.upload', $order), [
                'payment_proof' => UploadedFile::fake()->createWithContent('transfer.pdf', '%PDF-1.4 test proof'),
            ])
            ->assertNotFound();

        $this->assertNull($order->fresh()->payment_proof_path);
    }

    public function test_customer_can_choose_digital_delivery_and_receive_game_codes(): void
    {
        Storage::fake('local');
        config()->set([
            'shop.bank_name' => 'Bank Test',
            'shop.bank_account_number' => '1234567890',
        ]);
        [$customerUser, $customer] = $this->createCustomer();
        $product = $this->createProduct($customerUser, ['stok' => 2]);
        $admin = User::create([
            'nama' => 'Admin',
            'email' => 'admin@example.test',
            'role' => '1',
            'status' => 1,
            'password' => Hash::make('admin-password'),
        ]);

        $this->actingAs($customerUser)
            ->post(route('order.addToCart', $product->id))
            ->assertRedirect(route('order.cart'));

        $this->post(route('order.place'), ['fulfillment_type' => 'digital'])
            ->assertRedirect();

        $order = Order::where('customer_id', $customer->id)->firstOrFail();
        $this->assertSame('digital', $order->fulfillment_type);
        $this->assertSame('pending_payment', $order->status);
        $this->assertNull($order->alamat);
        $this->assertSame(100000.0, (float) $order->total_harga);

        $this->actingAs($customerUser)
            ->post(route('order.payment-proof.upload', $order), [
                'payment_proof' => UploadedFile::fake()->createWithContent('transfer.pdf', '%PDF-1.4 test proof'),
            ])
            ->assertRedirect(route('order.show', $order));

        $this->actingAs($admin)
            ->put(route('backend.order.payment', $order), ['decision' => 'approve'])
            ->assertRedirect(route('backend.order.show', $order));

        $this->assertSame('awaiting_digital_delivery', $order->fresh()->status);
        $this->assertSame(1, $product->fresh()->stok);

        $this->put(route('backend.order.digital-delivery', $order), [
            'codes' => "GAME-CODE-123\n",
        ])->assertRedirect(route('backend.order.show', $order));

        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame('GAME-CODE-123', $order->fresh()->digital_delivery_codes);

        $this->actingAs($customerUser)
            ->get(route('order.show', $order))
            ->assertOk()
            ->assertSee('GAME-CODE-123');
    }

    public function test_admin_requires_one_digital_code_per_purchased_unit(): void
    {
        [$customerUser, $customer] = $this->createCustomer();
        $product = $this->createProduct($customerUser, ['stok' => 3]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'status' => 'awaiting_digital_delivery',
            'fulfillment_type' => 'digital',
            'total_harga' => 200000,
        ]);
        $order->orderItems()->create([
            'produk_id' => $product->id,
            'quantity' => 2,
            'harga' => 100000,
        ]);
        $admin = User::create([
            'nama' => 'Admin',
            'email' => 'admin@example.test',
            'role' => '1',
            'status' => 1,
            'password' => Hash::make('admin-password'),
        ]);

        $this->actingAs($admin)
            ->put(route('backend.order.digital-delivery', $order), ['codes' => 'ONLY-ONE-CODE'])
            ->assertSessionHasErrors('codes');

        $this->assertSame('awaiting_digital_delivery', $order->fresh()->status);
    }

    public function test_admin_cannot_approve_payment_without_a_proof(): void
    {
        [$customerUser, $customer] = $this->createCustomer();
        $admin = User::create([
            'nama' => 'Admin',
            'email' => 'admin@example.test',
            'role' => '1',
            'status' => 1,
            'password' => Hash::make('admin-password'),
        ]);
        $order = Order::create([
            'customer_id' => $customer->id,
            'status' => 'pending_payment',
            'payment_status' => 'unpaid',
            'total_harga' => 100000,
        ]);

        $this->actingAs($admin)
            ->put(route('backend.order.payment', $order), ['decision' => 'approve'])
            ->assertStatus(409);
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        [$owner, $ownerCustomer] = $this->createCustomer('owner@example.test');
        [$otherUser] = $this->createCustomer('other@example.test');
        $order = Order::create([
            'customer_id' => $ownerCustomer->id,
            'status' => 'awaiting_shipping',
            'total_harga' => 100000,
        ]);

        $this->actingAs($otherUser)
            ->get(route('order.show', $order))
            ->assertNotFound();
    }

    public function test_admin_order_management_is_forbidden_to_customers(): void
    {
        [$customer] = $this->createCustomer();

        $this->actingAs($customer)
            ->get(route('backend.order.index'))
            ->assertForbidden();
    }

    private function createCustomer(string $email = 'buyer@example.test'): array
    {
        $user = User::create([
            'nama' => 'Customer Test',
            'email' => $email,
            'role' => '2',
            'status' => 1,
            'password' => Hash::make('customer-password'),
        ]);
        $customer = Customer::create(['user_id' => $user->id]);

        return [$user, $customer];
    }

    private function createProduct(User $user, array $overrides = []): Produk
    {
        return Produk::create(array_merge([
            'kategori_id' => 1,
            'user_id' => $user->id,
            'status' => 1,
            'nama_produk' => 'Game Test',
            'detail' => 'Detail produk test',
            'harga' => 100000,
            'stok' => 5,
            'berat' => 100,
            'foto' => 'game-test.jpg',
        ], $overrides));
    }
}
