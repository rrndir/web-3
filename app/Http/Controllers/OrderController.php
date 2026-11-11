<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Customer;
use App\Models\Produk;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\RajaOngkirService;

class OrderController extends Controller
{
    public function addToCart(Request $request, $id)
    {
        $result = DB::transaction(function () use ($id) {
            $produk = Produk::whereKey($id)->lockForUpdate()->firstOrFail();
            if (!$produk->status) {
                return ['error' => 'Produk ini sedang tidak tersedia.'];
            }
            if ($produk->stok < 1) {
                return ['error' => 'Produk sedang habis.'];
            }

            $customer = Customer::firstOrCreate(['user_id' => Auth::id()]);
            $order = Order::where('customer_id', $customer->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if (!$order) {
                $order = Order::create([
                    'customer_id' => $customer->id,
                    'status' => 'pending',
                    'total_harga' => 0,
                ]);
            }

            $orderItem = $order->orderItems()
                ->where('produk_id', $produk->id)
                ->lockForUpdate()
                ->first();
            $quantity = ($orderItem?->quantity ?? 0) + 1;

            if ($quantity > $produk->stok) {
                return ['error' => 'Jumlah produk melebihi stok yang tersedia.'];
            }

            if ($orderItem) {
                $orderItem->quantity = $quantity;
                $orderItem->save();
            } else {
                $order->orderItems()->create([
                    'produk_id' => $produk->id,
                    'quantity' => 1,
                    'harga' => $produk->harga,
                ]);
            }

            $order->total_harga = $order->orderItems()->sum(DB::raw('quantity * harga'));
            $order->save();

            return [
                'product_name' => $produk->nama_produk,
                'quantity' => $quantity,
                'cart_count' => $order->orderItems()->sum('quantity'),
            ];
        });

        if (isset($result['error'])) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $result['error']], 422);
            }

            return back()->with('error', $result['error']);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Produk berhasil ditambahkan ke keranjang.',
                'product_name' => $result['product_name'],
                'quantity' => $result['quantity'],
                'cart_count' => $result['cart_count'],
                'cart_url' => route('order.cart'),
                'checkout_url' => route('order.checkout'),
            ]);
        }

        return redirect()->route('order.cart')->with('success', 'Produk berhasil ditambahkan ke keranjang');
    }

    public function viewCart()
    {
        $customer = Customer::where('user_id', Auth::id())->first();
        $order = $customer
            ? Order::where('customer_id', $customer->id)->where('status', 'pending')->first()
            : null;
        if ($order) {
            $order->load('orderItems.produk');
        }
        return view('v_order.cart', compact('order'));
    }

    public function checkout(RajaOngkirService $shipping)
    {
        $customer = Customer::where('user_id', Auth::id())->firstOrFail();
        $order = Order::where('customer_id', $customer->id)
            ->where('status', 'pending')
            ->with('orderItems.produk', 'customer.user')
            ->first();

        if (!$order || $order->orderItems->isEmpty()) {
            return redirect()->route('order.cart')->with('error', 'Keranjang belanja kosong.');
        }

        return view('v_order.checkout', [
            'order' => $order,
            'bankConfigured' => config('shop.bank_name') && config('shop.bank_account_number'),
            'fulfillmentType' => old('fulfillment_type', $order->fulfillment_type ?? 'physical'),
            'shippingConfigured' => $shipping->isConfigured(),
        ]);
    }

    public function checkoutShippingOptions(Request $request, RajaOngkirService $shipping)
    {
        $validated = $request->validate([
            'courier' => ['required', 'string', 'in:' . implode(',', array_keys(config('shipping.rajaongkir.couriers')))],
            'destination_city_id' => ['required', 'string', 'max:30'],
            'destination_search' => ['required', 'string', 'max:255'],
        ]);

        if (!$shipping->isConfigured()) {
            return response()->json([
                'message' => 'Pengiriman belum tersedia. Periksa konfigurasi RajaOngkir dan lokasi asal toko.',
            ], 503);
        }

        try {
            $destination = $shipping->destination(
                $validated['destination_city_id'],
                $validated['destination_search']
            );
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }
        if (!$destination) {
            return response()->json(['message' => 'Pilih lokasi tujuan yang valid dari hasil pencarian.'], 422);
        }

        $customer = Customer::where('user_id', Auth::id())->firstOrFail();
        $order = Order::where('customer_id', $customer->id)
            ->where('status', 'pending')
            ->with('orderItems.produk')
            ->firstOrFail();
        try {
            $weight = $this->cartShippingWeight($order);
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
        try {
            $quotes = $shipping->quotes($destination['id'], $weight, $validated['courier']);
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }

        return response()->json([
            'destination' => $destination,
            'courier' => config("shipping.rajaongkir.couriers.{$validated['courier']}"),
            'quotes' => $quotes,
        ]);
    }

    public function placeOrder(Request $request, RajaOngkirService $shipping)
    {
        $validated = $request->validate([
            'fulfillment_type' => ['required', 'in:physical,digital'],
            'alamat' => ['required_if:fulfillment_type,physical', 'nullable', 'string', 'max:2000'],
            'pos' => ['required_if:fulfillment_type,physical', 'nullable', 'string', 'max:20'],
            'telepon_penerima' => ['required_if:fulfillment_type,physical', 'nullable', 'string', 'max:30'],
            'destination_city_id' => ['required_if:fulfillment_type,physical', 'nullable', 'string', 'max:30'],
            'destination_search' => ['required_if:fulfillment_type,physical', 'nullable', 'string', 'max:255'],
            'courier' => ['required_if:fulfillment_type,physical', 'nullable', 'string', 'in:' . implode(',', array_keys(config('shipping.rajaongkir.couriers')))],
            'shipping_service_code' => ['required_if:fulfillment_type,physical', 'nullable', 'string', 'max:80'],
            'shipping_description' => ['nullable', 'string', 'max:120'],
            'shipping_cost' => ['required_if:fulfillment_type,physical', 'nullable', 'integer', 'min:0'],
            'shipping_etd' => ['required_if:fulfillment_type,physical', 'nullable', 'string', 'max:100'],
        ]);

        $destinationCity = null;
        $shippingQuote = null;
        $quoteWeight = null;
        if ($validated['fulfillment_type'] === 'physical') {
            if (!$shipping->isConfigured()) {
                return back()->withInput()->with('error', 'Pengiriman belum tersedia karena RajaOngkir Komerce atau ID lokasi asal belum dikonfigurasi.');
            }

            try {
                $destinationCity = $shipping->destination(
                    $validated['destination_city_id'],
                    $validated['destination_search']
                );
            } catch (\RuntimeException $exception) {
                return back()->withInput()->with('error', $exception->getMessage());
            }

            if (!$destinationCity) {
                return back()->withInput()->withErrors([
                    'destination_city_id' => 'Pilih kota tujuan yang valid.',
                ]);
            }

            $customer = Customer::where('user_id', Auth::id())->firstOrFail();
            $cart = Order::where('customer_id', $customer->id)
                ->where('status', 'pending')
                ->with('orderItems.produk')
                ->firstOrFail();
            try {
                $quoteWeight = $this->cartShippingWeight($cart);
                $quotes = $shipping->quotes(
                    $destinationCity['id'],
                    $quoteWeight,
                    $validated['courier']
                );
            } catch (\RuntimeException $exception) {
                return back()->withInput()->withErrors(['shipping' => $exception->getMessage()]);
            }

            $shippingQuote = collect($quotes)->first(fn ($quote) =>
                $quote['service'] === $validated['shipping_service_code']
                && $quote['description'] === ($validated['shipping_description'] ?? '')
                && $quote['cost'] === (int) $validated['shipping_cost']
                && $quote['etd'] === $validated['shipping_etd']
            );
            if (!$shippingQuote) {
                return back()->withInput()->withErrors([
                    'shipping' => 'Tarif kurir tidak valid atau sudah berubah. Cek dan pilih ulang layanan pengiriman.',
                ]);
            }
        }

        $customer = Customer::where('user_id', Auth::id())->firstOrFail();
        $order = DB::transaction(function () use ($validated, $customer, $destinationCity, $shippingQuote, $quoteWeight) {
            $order = Order::where('customer_id', $customer->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->firstOrFail();
            $items = $order->orderItems()->with('produk')->get();

            if ($items->isEmpty()) {
                abort(422, 'Keranjang belanja kosong.');
            }

            foreach ($items as $item) {
                if (!$item->produk || !$item->produk->status || $item->quantity > $item->produk->stok) {
                    abort(422, 'Stok salah satu produk tidak lagi mencukupi. Perbarui keranjang Anda.');
                }
            }

            $order->fulfillment_type = $validated['fulfillment_type'];
            $order->total_harga = $items->sum(fn ($item) => $item->harga * $item->quantity);
            if ($validated['fulfillment_type'] === 'physical') {
                $currentWeight = (int) ceil($items->sum(fn ($item) => $item->produk->berat * $item->quantity));
                if ($currentWeight !== $quoteWeight) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'shipping' => 'Berat keranjang berubah. Silakan cek ulang tarif kurir.',
                    ]);
                }

                $order->alamat = $validated['alamat'];
                $order->pos = $validated['pos'];
                $order->telepon_penerima = $validated['telepon_penerima'];
                $order->destination_province_id = null;
                $order->destination_province_name = $destinationCity['province_name'];
                $order->destination_city_id = $destinationCity['id'];
                $order->destination_city_name = $destinationCity['label'];
                $order->total_berat = $quoteWeight;
                $order->kurir = config("shipping.rajaongkir.couriers.{$validated['courier']}");
                $order->shipping_service_code = $shippingQuote['service'];
                $order->layanan_ongkir = $shippingQuote['description'];
                $order->biaya_ongkir = (string) $shippingQuote['cost'];
                $order->estimasi_ongkir = $shippingQuote['etd'];
                $order->total_harga += $shippingQuote['cost'];
                $order->status = 'pending_payment';
            } else {
                $order->alamat = null;
                $order->pos = null;
                $order->telepon_penerima = null;
                $order->total_berat = 0;
                $order->kurir = null;
                $order->biaya_ongkir = null;
                $order->estimasi_ongkir = null;
                $order->destination_province_id = null;
                $order->destination_province_name = null;
                $order->destination_city_id = null;
                $order->destination_city_name = null;
                $order->shipping_service_code = null;
                $order->status = 'pending_payment';
            }
            $order->payment_status = 'unpaid';
            $order->save();

            return $order;
        });

        $message = $order->fulfillment_type === 'physical'
            ? 'Pesanan dibuat. Silakan lanjutkan pembayaran sesuai total termasuk ongkir.'
            : 'Pesanan digital dibuat. Silakan lanjutkan pembayaran transfer.';

        return redirect()->route('order.show', $order)->with('success', $message);
    }

    public function orders()
    {
        $customer = Customer::where('user_id', Auth::id())->firstOrFail();
        $orders = Order::where('customer_id', $customer->id)
            ->where('status', '!=', 'pending')
            ->latest()
            ->paginate(10);

        return view('v_order.index', compact('orders'));
    }

    public function showOrder($id)
    {
        $customer = Customer::where('user_id', Auth::id())->firstOrFail();
        $order = Order::where('customer_id', $customer->id)
            ->where('status', '!=', 'pending')
            ->with('orderItems.produk')
            ->findOrFail($id);

        return view('v_order.show', [
            'order' => $order,
            'bankConfigured' => config('shop.bank_name') && config('shop.bank_account_number'),
        ]);
    }

    public function shippingDestinations(Request $request, RajaOngkirService $shipping)
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:100'],
        ]);

        try {
            return response()->json([
                'destinations' => $shipping->destinations($validated['q']),
            ]);
        } catch (\RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }
    }

    private function cartShippingWeight(Order $order): int
    {
        $weight = 0;
        foreach ($order->orderItems as $item) {
            $productWeight = (float) ($item->produk?->berat ?? 0);
            if ($productWeight <= 0) {
                throw new \RuntimeException(
                    'Berat produk ' . ($item->produk?->nama_produk ?? '') . ' belum diatur. Hubungi admin untuk melengkapi berat produk.'
                );
            }

            $weight += $productWeight * $item->quantity;
        }

        if ($weight <= 0) {
            throw new \RuntimeException('Berat total pesanan harus lebih dari 0 gram.');
        }

        return (int) ceil($weight);
    }

    public function uploadPaymentProof(Request $request, $id)
    {
        if (!config('shop.bank_name') || !config('shop.bank_account_number')) {
            return back()->with('error', 'Pembayaran belum dapat dilakukan karena rekening Warung Game belum dikonfigurasi.');
        }

        $validated = $request->validate([
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);
        $customer = Customer::where('user_id', Auth::id())->firstOrFail();
        $order = Order::where('customer_id', $customer->id)
            ->where('status', 'pending_payment')
            ->findOrFail($id);

        if (!in_array($order->payment_status, ['unpaid', 'rejected'], true)) {
            return back()->with('error', 'Bukti pembayaran pesanan ini sedang diperiksa atau sudah dikonfirmasi.');
        }

        $path = $validated['payment_proof']->store('private/payment-proofs', 'local');
        if (!$path) {
            throw new \RuntimeException('Bukti pembayaran gagal disimpan.');
        }

        $previousProof = $order->payment_proof_path;
        $order->payment_proof_path = $path;
        $order->payment_status = 'under_review';
        $order->payment_submitted_at = now();
        $order->save();
        if ($previousProof) {
            Storage::disk('local')->delete($previousProof);
        }

        return redirect()->route('order.show', $order)->with('success', 'Bukti transfer berhasil dikirim dan menunggu verifikasi admin.');
    }

    public function updateCart(Request $request, $id)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);
        $customer = Customer::where('user_id', Auth::id())->firstOrFail();

        $result = DB::transaction(function () use ($id, $customer, $validated) {
            $orderItem = OrderItem::whereHas('order', function ($query) use ($customer) {
                $query->where('customer_id', $customer->id)->where('status', 'pending');
            })->lockForUpdate()->findOrFail($id);
            $produk = Produk::whereKey($orderItem->produk_id)->lockForUpdate()->firstOrFail();

            if ($validated['quantity'] > $produk->stok) {
                return 'Jumlah produk melebihi stok yang tersedia.';
            }

            $orderItem->quantity = $validated['quantity'];
            $orderItem->save();

            $order = $orderItem->order;
            $order->total_harga = $order->orderItems()->sum(DB::raw('quantity * harga'));
            $order->save();

            return null;
        });

        if ($result) {
            return redirect()->route('order.cart')->with('error', $result);
        }

        return redirect()->route('order.cart')->with('success', 'Jumlah produk berhasil diperbarui');
    }

    public function removeFromCart($id)
    {
        $customer = Customer::where('user_id', Auth::id())->firstOrFail();

        DB::transaction(function () use ($id, $customer) {
            $item = OrderItem::whereHas('order', function ($query) use ($customer) {
                $query->where('customer_id', $customer->id)->where('status', 'pending');
            })->lockForUpdate()->findOrFail($id);
            $order = $item->order;
            $item->delete();

            $order->total_harga = $order->orderItems()->sum(DB::raw('quantity * harga'));
            $order->save();
        });

        return redirect()->route('order.cart')->with('success', 'Produk berhasil dihapus dari keranjang');
    }
}
