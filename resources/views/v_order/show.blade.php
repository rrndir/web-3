@extends('v_layouts.app')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="order-summary clearfix">
            <div class="section-title">
                <h3 class="title">Pesanan #{{ $order->id }}</h3>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <p><strong>Jenis pembelian:</strong> {{ $order->fulfillment_type === 'digital' ? 'Digital' : 'Fisik' }}</p>
            <p><strong>Status:</strong> {{ str_replace('_', ' ', ucfirst($order->status)) }}</p>
            @if($order->kurir)
                <p><strong>Kurir:</strong> {{ $order->kurir }}</p>
            @endif
            @if($order->layanan_ongkir)
                <p><strong>Layanan:</strong> {{ $order->layanan_ongkir }}
                    @if($order->estimasi_ongkir) (estimasi {{ $order->estimasi_ongkir }}) @endif
                </p>
            @endif
            @if($order->noresi)
                <p><strong>Nomor resi:</strong> {{ $order->noresi }}</p>
            @endif
            @if($order->fulfillment_type === 'physical')
                <p><strong>Alamat pengiriman:</strong><br>{{ $order->alamat }}<br>{{ $order->pos }}</p>
                <p><strong>Telepon penerima:</strong> {{ $order->telepon_penerima }}</p>
            @endif

            <table class="shopping-cart-table table">
                <thead><tr><th>Produk</th><th class="text-center">Jumlah</th><th class="text-right">Total</th></tr></thead>
                <tbody>
                    @foreach($order->orderItems as $item)
                        <tr>
                            <td>{{ $item->produk->nama_produk }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-right">Rp {{ number_format($item->harga * $item->quantity, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if($order->fulfillment_type === 'physical')
                <p>Ongkir: {{ $order->biaya_ongkir === null ? 'Belum ditentukan' : 'Rp ' . number_format((float) $order->biaya_ongkir, 0, ',', '.') }}</p>
            @else
                <p>Pengiriman digital tanpa ongkir.</p>
            @endif
            <h4>Total: Rp {{ number_format($order->total_harga, 0, ',', '.') }}</h4>
        </div>
    </div>

    <div class="col-md-4">
        @if($order->fulfillment_type === 'physical' && $order->status === 'awaiting_shipping')
            <div class="alert alert-info">Pesanan lama ini masih menunggu penetapan ongkir oleh admin.</div>
        @elseif($order->fulfillment_type === 'digital' && $order->status === 'awaiting_digital_delivery')
            <div class="alert alert-info">Pembayaran diterima. Admin sedang menyiapkan kode game digital Anda.</div>
        @elseif($order->status === 'pending_payment')
            <div class="order-summary" style="padding: 16px;">
                <h4>Pembayaran transfer bank</h4>
                @if($order->fulfillment_type === 'physical')
                    <p>Kurir dan ongkir sudah dipilih saat checkout. Pesanan akan diproses untuk dikirim setelah pembayaran diverifikasi.</p>
                @endif
                @if($bankConfigured)
                    <p>Bank: <strong>{{ config('shop.bank_name') }}</strong></p>
                    <p>Nomor rekening: <strong>{{ config('shop.bank_account_number') }}</strong></p>
                    <p>Atas nama: <strong>{{ config('shop.bank_account_name') }}</strong></p>
                    <p>Transfer tepat sejumlah <strong>Rp {{ number_format($order->total_harga, 0, ',', '.') }}</strong>.</p>

                    @if($order->payment_status === 'under_review')
                        <div class="alert alert-info">Bukti transfer sedang diperiksa admin.</div>
                    @else
                        @if($order->payment_status === 'rejected')
                            <div class="alert alert-danger">Bukti sebelumnya ditolak. Silakan kirim ulang bukti yang benar.</div>
                        @endif
                        <form action="{{ route('order.payment-proof.upload', $order) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label for="payment_proof">Bukti transfer (JPG, PNG, atau PDF; maksimal 5 MB)</label>
                                <input id="payment_proof" type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.pdf" required class="form-control">
                                @error('payment_proof') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                            <button type="submit" class="primary-btn">Kirim bukti transfer</button>
                        </form>
                    @endif
                @else
                    <div class="alert alert-warning">Rekening transfer Warung Game belum dikonfigurasi. Silakan hubungi admin; jangan transfer ke nomor yang tidak terverifikasi.</div>
                @endif
            </div>
        @elseif($order->payment_status === 'under_review')
            <div class="alert alert-info">Bukti transfer sudah dikirim dan sedang diverifikasi admin.</div>
        @elseif($order->fulfillment_type === 'physical' && $order->status === 'processing')
            <div class="alert alert-success">Pembayaran terverifikasi. Pesanan sedang diproses untuk pengiriman.</div>
        @elseif($order->fulfillment_type === 'physical' && $order->status === 'shipped')
            <div class="alert alert-success">Pesanan dikirim melalui {{ $order->kurir }}. Resi: {{ $order->noresi }}</div>
        @elseif($order->fulfillment_type === 'digital' && $order->status === 'completed' && $order->digital_delivery_codes)
            <div class="alert alert-success">
                <h4>Kode game digital Anda</h4>
                <pre>{{ $order->digital_delivery_codes }}</pre>
            </div>
        @elseif($order->fulfillment_type === 'physical' && $order->status === 'completed')
            <div class="alert alert-success">Pesanan selesai. Terima kasih telah berbelanja.</div>
        @endif
    </div>
</div>

<a href="{{ route('order.history') }}" class="main-btn">Kembali ke pesanan saya</a>
@endsection
