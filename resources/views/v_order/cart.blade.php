@extends('v_layouts.app')

@section('content')
<div class="col-md-12">
    <div class="order-summary clearfix">
        <div class="section-title">
            <p>KERANJANG</p>
            <h3 class="title">Keranjang Belanja</h3>
        </div>

        {{-- Pesan Sukses --}}
        @if(session()->has('success'))
            <div class="alert alert-success alert-dismissible" role="alert">
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <strong>{{ session('success') }}</strong>
            </div>
        @endif

        @if($order && $order->orderItems->count() > 0)
        <table class="shopping-cart-table table">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th></th>
                    <th class="text-center">Harga</th>
                    <th class="text-center">Quantity</th>
                    <th class="text-center">Total</th>
                    <th class="text-right"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->orderItems as $item)
                <tr>
                    <td class="thumb">
                        <img src="{{ asset('storage/img-produk/thumb_sm_' . $item->produk->foto) }}" alt="">
                    </td>
                    <td class="details">
                        <a>{{ $item->produk->nama_produk }}</a>
                        <ul>
                            <li><span>Berat: {{ $item->produk->berat }} Gram</span></li>
                            <li><span>Stok tersedia: {{ $item->produk->stok }}</span></li>
                        </ul>
                    </td>
                    <td class="price text-center">
                        <strong>Rp. {{ number_format($item->harga, 0, ',', '.') }}</strong>
                    </td>
                    <td class="qty text-center">
                        <form action="{{ route('order.updateCart', $item->id) }}" method="post" style="display: inline-flex;">
                            @csrf
                            @method('PUT')
                            <input type="number" name="quantity" value="{{ $item->quantity }}" min="1" style="width: 60px;" class="form-control">
                            <button type="submit" class="btn btn-sm btn-warning ml-1">Update</button>
                        </form>
                    </td>
                    <td class="total text-center">
                        <strong class="primary-color">
                            Rp. {{ number_format($item->harga * $item->quantity, 0, ',', '.') }}
                        </strong>
                    </td>
                    <td class="text-right">
                        <form action="{{ route('order.removeFromCart', $item->id) }}" method="post">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="main-btn icon-btn">
                                <i class="fa fa-close"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="order-summary">
            <h4>Total: Rp. {{ number_format($order->total_harga, 0, ',', '.') }}</h4>
            <p class="text-muted">Ongkir akan dihitung admin setelah alamat pengiriman dikirim.</p>
        </div>
        <a href="{{ route('order.checkout') }}" class="primary-btn">Lanjut ke Checkout</a>
        <a href="{{ route('produk.all') }}" class="primary-btn">Lanjut Belanja</a>

        @else
            <p class="text-center">Keranjang belanja kosong.</p>
            <div class="text-center">
                <a href="{{ route('produk.all') }}" class="primary-btn">Lihat Produk</a>
            </div>
        @endif
    </div>
</div>
@endsection
