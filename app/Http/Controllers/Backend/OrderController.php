<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\RajaOngkirService;
use RuntimeException;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('customer.user')
            ->where('status', '!=', 'pending')
            ->latest()
            ->paginate(15);

        return view('backend.v_order.index', compact('orders'));
    }

    public function show($id)
    {
        $order = Order::with(['customer.user', 'orderItems.produk'])->findOrFail($id);
        abort_if($order->status === 'pending', 404);

        return view('backend.v_order.show', compact('order'));
    }

    public function shippingOptions(Request $request, $id, RajaOngkirService $shipping)
    {
        $validated = $request->validate([
            'courier' => ['required', 'string', 'in:' . implode(',', array_keys(config('shipping.rajaongkir.couriers')))],
        ]);
        $order = Order::whereKey($id)
            ->where('fulfillment_type', 'physical')
            ->where('status', 'awaiting_shipping')
            ->firstOrFail();

        if (!$order->destination_city_id) {
            return response()->json([
                'message' => 'Pesanan ini belum memiliki lokasi tujuan. Customer perlu memilih lokasi tujuan dan membuat ulang pesanan.',
            ], 422);
        }

        try {
            $weight = $this->shippingWeight($order);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
        $order->total_berat = $weight;
        $order->save();

        try {
            $quotes = $shipping->quotes(
                (string) $order->destination_city_id,
                $weight,
                $validated['courier']
            );
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }

        return response()->json([
            'courier' => config("shipping.rajaongkir.couriers.{$validated['courier']}"),
            'quotes' => $quotes,
        ]);
    }

    public function updateShipping(Request $request, $id, RajaOngkirService $shipping)
    {
        $validated = $request->validate([
            'courier' => ['required', 'string', 'in:' . implode(',', array_keys(config('shipping.rajaongkir.couriers')))],
            'shipping_service_code' => ['required', 'string', 'max:80'],
            'shipping_description' => ['nullable', 'string', 'max:120'],
            'biaya_ongkir' => ['required', 'regex:/^\d{1,9}$/'],
            'estimasi_ongkir' => ['required', 'string', 'max:100'],
        ]);

        $orderBeforeSave = Order::whereKey($id)
            ->where('fulfillment_type', 'physical')
            ->where('status', 'awaiting_shipping')
            ->firstOrFail();

        if (!$orderBeforeSave->destination_city_id) {
            return back()->withErrors([
                'shipping' => 'Pesanan ini belum memiliki lokasi tujuan. Customer perlu memilih lokasi tujuan dan membuat ulang pesanan.',
            ]);
        }

        try {
            $weight = $this->shippingWeight($orderBeforeSave);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['shipping' => $exception->getMessage()]);
        }
        $orderBeforeSave->total_berat = $weight;
        $orderBeforeSave->save();

        try {
            $quotes = $shipping->quotes(
                (string) $orderBeforeSave->destination_city_id,
                $weight,
                $validated['courier']
            );
        } catch (RuntimeException $exception) {
            return back()->withInput()->withErrors(['shipping' => $exception->getMessage()]);
        }

        $quote = collect($quotes)->first(fn ($quote) =>
            $quote['service'] === $validated['shipping_service_code']
            && $quote['description'] === ($validated['shipping_description'] ?? '')
            && $quote['cost'] === (int) $validated['biaya_ongkir']
            && $quote['etd'] === $validated['estimasi_ongkir']
        );

        if (!$quote) {
            return back()->withInput()->withErrors([
                'shipping' => 'Tarif berubah atau pilihan ongkir tidak valid. Hitung ulang tarif kurir.',
            ]);
        }

        $order = DB::transaction(function () use ($validated, $id, $quote) {
            $order = Order::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($order->fulfillment_type !== 'physical' || $order->status !== 'awaiting_shipping') {
                abort(409, 'Ongkir hanya dapat diatur untuk pesanan yang menunggu pengiriman.');
            }

            $subtotal = $order->orderItems()->sum(DB::raw('quantity * harga'));
            $order->kurir = config("shipping.rajaongkir.couriers.{$validated['courier']}");
            $order->shipping_service_code = $quote['service'];
            $order->layanan_ongkir = $quote['description'];
            $shippingCost = $quote['cost'];
            $order->biaya_ongkir = (string) $shippingCost;
            $order->estimasi_ongkir = $quote['etd'];
            $order->total_harga = $subtotal + $shippingCost;
            $order->status = 'pending_payment';
            $order->payment_status = 'unpaid';
            $order->save();

            return $order;
        });

        return redirect()->route('backend.order.show', $order)->with('success', 'Tarif dan estimasi kurir tersimpan. Pesanan siap dibayar.');
    }

    public function reviewPayment(Request $request, $id)
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
        ]);

        $result = DB::transaction(function () use ($validated, $id) {
            $order = Order::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($order->status !== 'pending_payment' || $order->payment_status !== 'under_review' || !$order->payment_proof_path) {
                abort(409, 'Pesanan ini tidak memiliki bukti transfer yang menunggu pemeriksaan.');
            }

            if ($validated['decision'] === 'reject') {
                $order->payment_status = 'rejected';
                $order->save();

                return 'Bukti transfer ditolak. Customer dapat mengirim ulang bukti.';
            }

            $items = $order->orderItems()->with('produk')->get();
            foreach ($items as $item) {
                $product = Produk::whereKey($item->produk_id)->lockForUpdate()->firstOrFail();
                if ($item->quantity > $product->stok) {
                    abort(409, 'Stok produk tidak cukup untuk menyetujui pesanan.');
                }
            }

            foreach ($items as $item) {
                $product = Produk::whereKey($item->produk_id)->lockForUpdate()->firstOrFail();
                $product->decrement('stok', $item->quantity);
            }

            $order->payment_status = 'paid';
            $order->paid_at = now();
            $order->status = $order->fulfillment_type === 'digital'
                ? 'awaiting_digital_delivery'
                : 'processing';
            $order->save();

            return $order->fulfillment_type === 'digital'
                ? 'Pembayaran disetujui. Kode digital siap diserahkan.'
                : 'Pembayaran disetujui. Pesanan siap dikirim.';
        });

        return redirect()->route('backend.order.show', $id)->with('success', $result);
    }

    public function ship(Request $request, $id)
    {
        $validated = $request->validate([
            'noresi' => ['required', 'string', 'max:100'],
        ]);
        $order = Order::whereKey($id)
            ->where('fulfillment_type', 'physical')
            ->where('status', 'processing')
            ->firstOrFail();
        $order->noresi = $validated['noresi'];
        $order->status = 'shipped';
        $order->shipped_at = now();
        $order->save();

        return redirect()->route('backend.order.show', $order)->with('success', 'Pesanan ditandai telah dikirim.');
    }

    public function deliverDigital(Request $request, $id)
    {
        $validated = $request->validate([
            'codes' => ['required', 'string', 'max:20000'],
        ]);

        $order = DB::transaction(function () use ($validated, $id) {
            $order = Order::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($order->fulfillment_type !== 'digital' || $order->status !== 'awaiting_digital_delivery') {
                abort(409, 'Pesanan ini tidak menunggu penyerahan produk digital.');
            }

            $codes = preg_split('/\r\n|\r|\n/', $validated['codes']);
            $codes = array_values(array_filter(array_map('trim', $codes), fn ($code) => $code !== ''));
            $requiredCodes = (int) $order->orderItems()->sum('quantity');
            if (count($codes) !== $requiredCodes) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'codes' => "Masukkan tepat {$requiredCodes} kode, satu kode pada setiap baris.",
                ]);
            }

            $order->digital_delivery_codes = implode("\n", $codes);
            $order->status = 'completed';
            $order->completed_at = now();
            $order->save();

            return $order;
        });

        return redirect()->route('backend.order.show', $order)->with('success', 'Produk digital diserahkan kepada customer.');
    }

    public function complete($id)
    {
        $order = Order::whereKey($id)
            ->where('fulfillment_type', 'physical')
            ->where('status', 'shipped')
            ->firstOrFail();
        $order->status = 'completed';
        $order->completed_at = now();
        $order->save();

        return redirect()->route('backend.order.show', $order)->with('success', 'Pesanan ditandai selesai.');
    }

    private function shippingWeight(Order $order): int
    {
        $order->loadMissing('orderItems.produk');
        $weight = 0;

        foreach ($order->orderItems as $item) {
            $productWeight = (float) ($item->produk?->berat ?? 0);
            if ($productWeight <= 0) {
                throw new RuntimeException(
                    'Berat produk belum diatur. Admin buka Backend → Produk, isi berat produk minimal 1 gram, lalu cek tarif kembali.'
                );
            }

            $weight += $productWeight * $item->quantity;
        }

        if ($weight <= 0) {
            throw new RuntimeException('Berat total pesanan harus lebih dari 0 gram.');
        }

        return (int) ceil($weight);
    }

    public function downloadPaymentProof($id)
    {
        $order = Order::findOrFail($id);
        abort_if(!$order->payment_proof_path || !Storage::disk('local')->exists($order->payment_proof_path), 404);

        return Storage::disk('local')->download($order->payment_proof_path);
    }
}
