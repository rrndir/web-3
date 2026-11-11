@extends('v_layouts.app')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="billing-details">
            <div class="section-title">
                <h3 class="title">Checkout</h3>
            </div>

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if(!$bankConfigured)
                <div class="alert alert-warning">
                    Rekening transfer belum dikonfigurasi. Admin harus mengisi data rekening Warung Game sebelum customer dapat membayar.
                </div>
            @endif
            @if(!$shippingConfigured)
                <div class="alert alert-warning">
                    Pengiriman fisik belum tersedia. Admin perlu mengatur API key Komerce dan ID lokasi asal pengiriman di konfigurasi server.
                </div>
            @endif

            <form action="{{ route('order.place') }}" method="POST">
                @csrf
                <div class="form-group">
                    <label>Jenis pembelian</label>
                    <div>
                        <label class="radio-inline">
                            <input type="radio" name="fulfillment_type" value="physical" {{ $fulfillmentType === 'physical' ? 'checked' : '' }}>
                            Fisik — dikirim ke alamat
                        </label>
                        <label class="radio-inline">
                            <input type="radio" name="fulfillment_type" value="digital" {{ $fulfillmentType === 'digital' ? 'checked' : '' }}>
                            Digital — kode game dikirim online
                        </label>
                    </div>
                    @error('fulfillment_type') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div id="physical-shipping-fields" @if($fulfillmentType !== 'physical') hidden @endif>
                    <div class="form-group">
                        <label for="alamat">Alamat pengiriman</label>
                        <textarea id="alamat" name="alamat" @if($fulfillmentType === 'physical') required @endif class="form-control" rows="4">{{ old('alamat', $order->alamat ?? '') }}</textarea>
                        @error('alamat') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="pos">Kode pos</label>
                        <input id="pos" name="pos" maxlength="20" @if($fulfillmentType === 'physical') required @endif class="form-control" value="{{ old('pos', $order->pos ?? '') }}">
                        @error('pos') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="telepon_penerima">Nomor telepon penerima</label>
                        <input id="telepon_penerima" name="telepon_penerima" maxlength="30" @if($fulfillmentType === 'physical') required @endif class="form-control"
                               value="{{ old('telepon_penerima', $order->customer->user->hp ?? '') }}">
                        @error('telepon_penerima') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label for="destination_search">Kecamatan/kelurahan tujuan</label>
                        <input id="destination_search" name="destination_search" type="search" autocomplete="off"
                               placeholder="Ketik minimal 3 huruf untuk mencari lokasi"
                               @if($fulfillmentType === 'physical') required @endif class="form-control"
                               value="{{ old('destination_search') }}">
                        <select id="destination-options" size="4" class="form-control mt-2"
                                aria-label="Hasil pencarian lokasi" hidden></select>
                        <input id="destination_city_id" name="destination_city_id" type="hidden"
                               value="{{ old('destination_city_id') }}">
                        <small id="destination-id-info" class="form-text text-info" role="status" hidden></small>
                        @error('destination_city_id') <span class="text-danger">{{ $message }}</span> @enderror
                        @error('destination_search') <span class="text-danger">{{ $message }}</span> @enderror
                        <small class="form-text text-muted">Pilih lokasi dari hasil pencarian RajaOngkir agar ongkir dapat dihitung dengan tepat.</small>
                    </div>
                    <div class="form-group">
                        <label for="checkout-courier">Pilih kurir</label>
                        <select id="checkout-courier" name="courier" class="form-control">
                            <option value="">Pilih ekspedisi</option>
                            @foreach(config('shipping.rajaongkir.couriers') as $code => $name)
                                <option value="{{ $code }}" @selected(old('courier') === $code)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <button id="get-checkout-quotes" type="button" class="btn btn-primary">Cek ongkir</button>
                        @error('shipping') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div id="checkout-quotes" class="form-group" role="radiogroup" aria-label="Pilihan layanan dan ongkir"></div>
                    <input type="hidden" name="shipping_service_code" id="checkout-service-code">
                    <input type="hidden" name="shipping_description" id="checkout-service-description">
                    <input type="hidden" name="shipping_cost" id="checkout-shipping-cost">
                    <input type="hidden" name="shipping_etd" id="checkout-shipping-etd">
                    <p id="shipping-load-error" class="text-danger" role="alert"></p>
                </div>
                <button id="place-order-button" type="submit" class="primary-btn">Lanjut ke pembayaran</button>
                <a href="{{ route('order.cart') }}" class="main-btn">Kembali ke keranjang</a>
            </form>
        </div>
    </div>

    <div class="col-md-4">
        <div class="order-summary">
            <h4>Ringkasan pesanan</h4>
            @foreach($order->orderItems as $item)
                <p>{{ $item->produk->nama_produk }} × {{ $item->quantity }}
                    <span class="pull-right">Rp {{ number_format($item->harga * $item->quantity, 0, ',', '.') }}</span>
                </p>
            @endforeach
            <hr>
            <p id="shipping-estimate" @if($fulfillmentType === 'digital') hidden @endif>Ongkir <span id="shipping-cost-display" class="pull-right">Pilih kurir</span></p>
            <p id="digital-delivery-note" @if($fulfillmentType !== 'digital') hidden @endif>Kode produk akan dikirim di halaman detail pesanan.</p>
            <h4>Subtotal: Rp <span id="checkout-subtotal" data-subtotal="{{ $order->total_harga }}">{{ number_format($order->total_harga, 0, ',', '.') }}</span></h4>
            <h4 id="checkout-total" @if($fulfillmentType === 'digital') hidden @endif>Total: Rp <span id="checkout-total-value">{{ number_format($order->total_harga, 0, ',', '.') }}</span></h4>
        </div>
    </div>
</div>
<script>
    (function () {
        const searchInput = document.getElementById('destination_search');
        const destinationIdInput = document.getElementById('destination_city_id');
        const destinationOptions = document.getElementById('destination-options');
        const destinationIdInfo = document.getElementById('destination-id-info');
        const shippingError = document.getElementById('shipping-load-error');
        const destinationsUrl = @json(route('order.shipping-destinations'));
        const quotesUrl = @json(route('order.checkout-shipping-options'));
        const courierSelect = document.getElementById('checkout-courier');
        const quoteList = document.getElementById('checkout-quotes');
        const placeOrderButton = document.getElementById('place-order-button');
        const isShippingConfigured = {{ $shippingConfigured ? 'true' : 'false' }};
        let searchTimer;
        let requestController;
        let quoteController;

        function showError(message) {
            shippingError.textContent = message;
        }

        function resetQuote() {
            if (quoteController) {
                quoteController.abort();
                quoteController = null;
            }
            quoteList.replaceChildren();
            ['checkout-service-code', 'checkout-service-description', 'checkout-shipping-cost', 'checkout-shipping-etd']
                .forEach(function (id) { document.getElementById(id).value = ''; });
            document.getElementById('shipping-cost-display').textContent = 'Pilih kurir';
            document.getElementById('checkout-total-value').textContent =
                new Intl.NumberFormat('id-ID').format(Number(document.getElementById('checkout-subtotal').dataset.subtotal));
            placeOrderButton.disabled = document.querySelector('input[name="fulfillment_type"]:checked').value === 'physical';
        }

        function requestQuotes() {
            showError('');
            resetQuote();
            if (!isShippingConfigured) {
                showError('Konfigurasi pengiriman belum lengkap. Hubungi admin.');
                return;
            }
            if (!destinationIdInput.value || !searchInput.value.trim() || !courierSelect.value) {
                showError('Pilih lokasi tujuan dan kurir terlebih dahulu.');
                return;
            }

            if (quoteController) quoteController.abort();
            quoteController = new AbortController();
            const button = document.getElementById('get-checkout-quotes');
            button.disabled = true;
            fetch(quotesUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': @json(csrf_token())
                },
                body: JSON.stringify({
                    courier: courierSelect.value,
                    destination_city_id: destinationIdInput.value,
                    destination_search: searchInput.value
                }),
                signal: quoteController.signal
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        if (!response.ok) throw new Error(data.message || 'Tarif kurir gagal dimuat.');
                        return data;
                    });
                })
                .then(function (data) {
                    data.quotes.forEach(function (quote) {
                        const label = document.createElement('label');
                        label.className = 'd-block';
                        const radio = document.createElement('input');
                        radio.type = 'radio';
                        radio.name = 'checkout_quote';
                        radio.addEventListener('change', function () {
                            document.getElementById('checkout-service-code').value = quote.service;
                            document.getElementById('checkout-service-description').value = quote.description;
                            document.getElementById('checkout-shipping-cost').value = String(quote.cost);
                            document.getElementById('checkout-shipping-etd').value = quote.etd;
                            document.getElementById('shipping-cost-display').textContent =
                                'Rp ' + new Intl.NumberFormat('id-ID').format(quote.cost);
                            const total = Number(document.getElementById('checkout-subtotal').dataset.subtotal) + quote.cost;
                            document.getElementById('checkout-total-value').textContent =
                                new Intl.NumberFormat('id-ID').format(total);
                            placeOrderButton.disabled = false;
                        });
                        label.append(radio, document.createTextNode(
                            ' ' + quote.service + ' — ' + quote.description
                            + ', Rp ' + new Intl.NumberFormat('id-ID').format(quote.cost)
                            + ', estimasi ' + quote.etd
                        ));
                        quoteList.appendChild(label);
                    });
                    if (data.quotes.length === 0) showError('Tidak ada layanan kurir untuk lokasi tersebut.');
                })
                .catch(function (error) {
                    if (error.name !== 'AbortError') showError(error.message);
                })
                .finally(function () {
                    button.disabled = false;
                });
        }

        function searchDestinations() {
            const query = searchInput.value.trim();
            showError('');
            destinationIdInput.value = '';
            destinationIdInfo.hidden = true;
            destinationOptions.replaceChildren();
            destinationOptions.hidden = true;
            resetQuote();
            if (query.length < 3) return;

            if (requestController) requestController.abort();
            requestController = new AbortController();
            fetch(destinationsUrl + '?q=' + encodeURIComponent(query), { signal: requestController.signal })
                .then(function (response) {
                    return response.json().then(function (data) {
                        if (!response.ok) throw new Error(data.message || 'Lokasi tujuan gagal dicari.');
                        return data;
                    });
                })
                .then(function (data) {
                    destinationOptions.add(new Option('Pilih lokasi tujuan', ''));
                    data.destinations.forEach(function (destination) {
                        const option = new Option(destination.label + ' (ID: ' + destination.id + ')', destination.id);
                        option.dataset.label = destination.label;
                        destinationOptions.add(option);
                    });
                    destinationOptions.hidden = data.destinations.length === 0;
                    if (data.destinations.length === 0) {
                        showError('Lokasi tidak ditemukan. Coba kata kunci yang lebih spesifik.');
                    }
                })
                .catch(function (error) {
                    if (error.name !== 'AbortError') showError(error.message);
                });
        }

        searchInput.addEventListener('input', function () {
            destinationIdInput.value = '';
            clearTimeout(searchTimer);
            searchTimer = setTimeout(searchDestinations, 300);
        });

        destinationOptions.addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];
            if (!selectedOption || !selectedOption.value) {
                destinationIdInput.value = '';
                destinationIdInfo.hidden = true;
                return;
            }
            destinationIdInput.value = selectedOption.value;
            searchInput.value = selectedOption.dataset.label;
            destinationIdInfo.textContent = 'ID lokasi tujuan terpilih: ' + selectedOption.value;
            destinationIdInfo.hidden = false;
            showError('');
            resetQuote();
        });

        courierSelect.addEventListener('change', resetQuote);
        document.getElementById('get-checkout-quotes').addEventListener('click', requestQuotes);

        document.querySelectorAll('input[name="fulfillment_type"]').forEach(function (input) {
            input.addEventListener('change', function () {
                const physical = this.value === 'physical';
                document.getElementById('physical-shipping-fields').hidden = !physical;
                document.getElementById('shipping-estimate').hidden = !physical;
                document.getElementById('checkout-total').hidden = !physical;
                document.getElementById('digital-delivery-note').hidden = physical;
                ['alamat', 'pos', 'telepon_penerima', 'destination_search'].forEach(function (id) {
                    document.getElementById(id).required = physical;
                });
                placeOrderButton.disabled = physical && !document.getElementById('checkout-service-code').value;
            });
        });
        resetQuote();
    })();
</script>
@endsection
