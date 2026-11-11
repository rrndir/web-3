@extends('v_layouts.app')
@section('content')
<div id="store">
    <div class="section-title">
    <h3 class="title">{{ $kategoriAktif->nama_kategori }}</h3>
    </div>
    <div class="row">
    @forelse ($produk as $row)
        <div class="col-md-4 col-sm-6 col-xs-6">
            <div class="product product-single">
                <div class="product-thumb">
                    <div class="product-label">
                        <span>Kategori</span>
                        <span class="sale">{{ $row->kategori->nama_kategori }}</span>
                    </div>
                    <a href="{{ route('produk.detail', $row->id) }}">
                        <button type="button" class="main-btn quick-view">
                            <i class="fa fa-search-plus"></i> Detail Produk
                        </button>
                    </a>
                    <img src="{{ asset('storage/img-produk/thumb_md_' . $row->foto) }}"
                         style="border-radius:10px;width:100%;height:320px;object-fit:cover"
                         alt="{{ $row->nama_produk }}">
                </div>
                <div class="product-body">
                    <h3 class="product-price">Rp. {{ number_format($row->harga, 0, ',', '.') }}</h3>
                    <h2 class="product-name">
                        <a href="{{ route('produk.detail', $row->id) }}">{{ $row->nama_produk }}</a>
                    </h2>
                    <div class="product-btns">
                        @if(Auth::check() && (string) Auth::user()->role === '2')
                            <form action="{{ route('order.addToCart', $row->id) }}" method="post" class="js-add-to-cart">
                                @csrf
                                <button type="submit" class="primary-btn add-to-cart" {{ $row->stok < 1 ? 'disabled' : '' }}>
                                    <i class="fa fa-shopping-cart"></i> {{ $row->stok < 1 ? 'Stok Habis' : 'Tambah ke Keranjang' }}
                                </button>
                            </form>
                        @else
                            <a class="primary-btn add-to-cart" href="{{ route('customer.login', ['redirect' => route('produk.detail', $row->id, false)]) }}">
                                <i class="fa fa-shopping-cart"></i> Masuk untuk Membeli
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-md-12">
            <div class="alert alert-info">
                Belum ada produk yang dipublikasikan pada kategori {{ $kategoriAktif->nama_kategori }}.
            </div>
        </div>
    @endforelse
    <div class="clearfix visible-md visible-lg visible-sm visible-xs"></div>
    </div>
    <div class="store-filter clearfix">
    {{ $produk->links('vendor.pagination.custom') }}
    </div>
</div>
@endsection