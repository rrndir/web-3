@extends('backend.v_layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <h4 class="card-title">Kelola Pesanan #{{ $order->id }}</h4>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="row">
            <div class="col-md-6">
                <h5>Informasi Customer</h5>
                <p>{{ $order->customer->user->nama }}<br>
                    {{ $order->customer->user->email }}<br>
                    {{ $order->telepon_penerima }}<br>
                    {{ $order->alamat }}<br>
                    {{ $order->destination_city_name }}{{ $order->destination_province_name ? ', ' . $order->destination_province_name : '' }}<br>
                    {{ $order->pos }}</p>
            </div>
            <div class="col-md-6">
                <h5>Status Pesanan</h5>
                <p>Jenis pembelian: {{ $order->fulfillment_type === 'digital' ? 'Digital' : 'Fisik' }}</p>
                <p>{{ str_replace('_', ' ', ucfirst($order->status)) }}</p>
                <p>Status pembayaran: {{ str_replace('_', ' ', ucfirst($order->payment_status)) }}</p>
                @if($order->kurir)<p>Kurir: {{ $order->kurir }}</p>@endif
                @if($order->noresi)<p>Resi: {{ $order->noresi }}</p>@endif
            </div>
        </div>

        <h5>Produk</h5>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead><tr><th>Nama</th><th>Jumlah</th><th>Berat/unit</th><th>Harga</th><th>Subtotal</th></tr></thead>
                <tbody>
                    @foreach($order->orderItems as $item)
                        <tr>
                            <td>{{ $item->produk->nama_produk }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>
                                {{ $item->produk->berat }} gram
                                @if((float) $item->produk->berat <= 0)
                                    <span class="text-danger">— wajib diisi</span>
                                    <a href="{{ route('backend.produk.edit', $item->produk) }}" class="btn btn-warning btn-sm">Atur berat produk</a>
                                @endif
                            </td>
                            <td>Rp {{ number_format($item->harga, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($item->harga * $item->quantity, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($order->fulfillment_type === 'physical')
            <p>
                Ongkir: {{ $order->biaya_ongkir === null ? 'Belum ditentukan' : 'Rp ' . number_format((float) $order->biaya_ongkir, 0, ',', '.') }}
                @if($order->layanan_ongkir) — {{ $order->kurir }} {{ $order->layanan_ongkir }} (estimasi {{ $order->estimasi_ongkir ?: 'tidak tersedia' }}) @endif
            </p>
        @else
            <p>Pengiriman: Digital (tanpa ongkir)</p>
        @endif
        <h5>Total: Rp {{ number_format($order->total_harga, 0, ',', '.') }}</h5>

        @if($order->fulfillment_type === 'physical' && $order->status === 'awaiting_shipping')
            <hr>
            <h5>Tentukan Pengiriman dan Ongkir</h5>
            @if($order->orderItems->contains(fn ($item) => !$item->produk || (float) $item->produk->berat <= 0))
                <div class="alert alert-warning">
                    Tarif belum dapat dihitung karena berat produk belum diisi. Klik <strong>Atur berat produk</strong>, isi berat aktual dalam gram, simpan produk, lalu kembali dan cek tarif lagi.
                </div>
            @endif
            <form id="shipping-form" action="{{ route('backend.order.shipping', $order) }}" method="POST"
                  data-options-url="{{ route('backend.order.shipping-options', $order) }}" data-csrf="{{ csrf_token() }}">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label for="courier">Ekspedisi</label>
                    <select id="courier" name="courier" required class="form-control">
                        <option value="">Pilih ekspedisi</option>
                        @foreach(config('shipping.rajaongkir.couriers') as $code => $name)
                            <option value="{{ $code }}" @selected(old('courier') === $code)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <button id="get-shipping-options" type="button" class="btn btn-info">Cek tarif dan estimasi</button>
                    <span id="shipping-options-error" class="text-danger" role="alert"></span>
                </div>
                <div id="shipping-options" class="form-group" role="radiogroup" aria-label="Pilihan layanan kurir"></div>
                <input type="hidden" name="shipping_service_code" value="{{ old('shipping_service_code') }}">
                <input type="hidden" name="shipping_description" value="{{ old('shipping_description') }}">
                <div class="form-group">
                    <label for="biaya_ongkir">Biaya ongkir (rupiah)</label>
                    <input id="biaya_ongkir" name="biaya_ongkir" type="text" inputmode="numeric" readonly required class="form-control" value="{{ old('biaya_ongkir') }}">
                </div>
                <div class="form-group">
                    <label for="estimasi_ongkir">Estimasi pengiriman</label>
                    <input id="estimasi_ongkir" name="estimasi_ongkir" type="text" readonly required class="form-control" value="{{ old('estimasi_ongkir') }}">
                </div>
                <button id="save-shipping" class="btn btn-primary" disabled>Simpan ongkir dan minta pembayaran</button>
            </form>
        @endif

        @if($order->payment_proof_path)
            <hr>
            <h5>Bukti Transfer</h5>
            <p>Dikirim: {{ optional($order->payment_submitted_at)->format('d/m/Y H:i') }}</p>
            <a class="btn btn-info" href="{{ route('backend.order.payment-proof', $order) }}">Unduh bukti transfer</a>
        @endif

        @if($order->status === 'pending_payment' && $order->payment_status === 'under_review')
            <form action="{{ route('backend.order.payment', $order) }}" method="POST" class="mt-3">
                @csrf
                @method('PUT')
                <button name="decision" value="approve" class="btn btn-success">Verifikasi dan setujui</button>
                <button name="decision" value="reject" class="btn btn-danger">Tolak bukti transfer</button>
            </form>
        @endif

        @if($order->status === 'processing')
            <hr>
            <h5>Pengiriman</h5>
            <p>Pembayaran telah diverifikasi. Masukkan nomor resi setelah paket diserahkan kepada kurir.</p>
            <form action="{{ route('backend.order.ship', $order) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label for="noresi">Nomor resi</label>
                    <input id="noresi" name="noresi" required maxlength="100" class="form-control" value="{{ old('noresi') }}">
                </div>
                <button class="btn btn-primary">Tandai telah dikirim</button>
            </form>
        @endif

        @if($order->fulfillment_type === 'digital' && $order->status === 'awaiting_digital_delivery')
            <hr>
            <h5>Kirim Kode Digital</h5>
            <p>Masukkan {{ $order->orderItems->sum('quantity') }} kode/serial, satu kode pada setiap baris.</p>
            <form action="{{ route('backend.order.digital-delivery', $order) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label for="codes">Kode / serial game</label>
                    <textarea id="codes" name="codes" rows="5" required class="form-control">{{ old('codes') }}</textarea>
                    @error('codes') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <button class="btn btn-success">Kirim kode ke customer</button>
            </form>
        @elseif($order->fulfillment_type === 'digital' && $order->digital_delivery_codes)
            <hr>
            <h5>Kode Digital Terkirim</h5>
            <pre>{{ $order->digital_delivery_codes }}</pre>
        @endif

        @if($order->status === 'shipped')
            <form action="{{ route('backend.order.complete', $order) }}" method="POST">
                @csrf
                @method('PUT')
                <button class="btn btn-success">Tandai pesanan selesai</button>
            </form>
        @endif

        <a href="{{ route('backend.order.index') }}" class="btn btn-secondary mt-3">Kembali ke daftar pesanan</a>
    </div>
</div>
@if($order->fulfillment_type === 'physical' && $order->status === 'awaiting_shipping')
<script>
    (function () {
        const form = document.getElementById('shipping-form');
        const courier = document.getElementById('courier');
        const optionsContainer = document.getElementById('shipping-options');
        const error = document.getElementById('shipping-options-error');
        const saveButton = document.getElementById('save-shipping');
        const costInput = document.getElementById('biaya_ongkir');
        const etdInput = document.getElementById('estimasi_ongkir');
        const serviceInput = form.querySelector('[name="shipping_service_code"]');
        const descriptionInput = form.querySelector('[name="shipping_description"]');

        function resetSelection() {
            optionsContainer.replaceChildren();
            costInput.value = '';
            etdInput.value = '';
            serviceInput.value = '';
            descriptionInput.value = '';
            saveButton.disabled = true;
        }

        courier.addEventListener('change', resetSelection);
        document.getElementById('get-shipping-options').addEventListener('click', function () {
            error.textContent = '';
            resetSelection();
            if (!courier.value) {
                error.textContent = 'Pilih ekspedisi terlebih dahulu.';
                return;
            }

            this.disabled = true;
            fetch(form.dataset.optionsUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': form.dataset.csrf
                },
                body: JSON.stringify({ courier: courier.value })
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        if (!response.ok) throw new Error(data.message || 'Tarif kurir gagal dimuat.');
                        return data;
                    });
                })
                .then(function (data) {
                    data.quotes.forEach(function (quote, index) {
                        const label = document.createElement('label');
                        label.className = 'd-block';
                        const radio = document.createElement('input');
                        radio.type = 'radio';
                        radio.name = 'shipping_quote';
                        radio.value = String(index);
                        radio.addEventListener('change', function () {
                            serviceInput.value = quote.service;
                            descriptionInput.value = quote.description;
                            costInput.value = String(quote.cost);
                            etdInput.value = quote.etd || 'Tidak tersedia';
                            saveButton.disabled = false;
                        });
                        label.append(radio, document.createTextNode(
                            ' ' + quote.service + ' — ' + quote.description
                            + ', Rp ' + new Intl.NumberFormat('id-ID').format(quote.cost)
                            + ', estimasi ' + (quote.etd || 'tidak tersedia')
                        ));
                        optionsContainer.appendChild(label);
                    });
                    if (data.quotes.length === 0) error.textContent = 'Tidak ada layanan kurir untuk tujuan ini.';
                })
                .catch(function (requestError) {
                    error.textContent = requestError.message;
                })
                .finally(() => {
                    document.getElementById('get-shipping-options').disabled = false;
                });
        });
    })();
</script>
@endif
@endsection
