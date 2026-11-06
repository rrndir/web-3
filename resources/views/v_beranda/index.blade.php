@extends('v_layouts.app') 
@section('content') 
<!-- template --> 
 
<!-- STORE --> 
<div id="store"> 
    <!-- row -->
    <div class="row">
        @if($produk->isEmpty())
            @php
                $fallbackProduk = [
                    ['nama' => 'Red Dead Redemption 2', 'harga' => 879000, 'foto' => 'red-dead-redemption-2.jpg'],
                    ['nama' => 'Black Myth: Wukong', 'harga' => 699999, 'foto' => 'black-myth-wukong.jpg'],
                    ['nama' => 'ELDEN RING', 'harga' => 599000, 'foto' => 'elden-ring.jpg'],
                    ['nama' => 'Grand Theft Auto V Enhanced', 'harga' => 439000, 'foto' => 'gta-v-enhanced.jpg'],
                    ['nama' => 'Cyberpunk 2077', 'harga' => 699999, 'foto' => 'cyberpunk-2077.jpg'],
                    ['nama' => 'EA SPORTS FC 26', 'harga' => 239000, 'foto' => 'ea-sports-fc-26.jpg'],
                ];
            @endphp
            @foreach ($fallbackProduk as $game)
                <div class="col-md-4 col-sm-6 col-xs-6">
                    <div class="product product-single">
                        <div class="product-thumb">
                            <img src="{{ asset('frontend/img/game/' . $game['foto']) }}"
                                 style="border-radius:10px;width:100%;height:320px;object-fit:cover"
                                 alt="{{ $game['nama'] }}">
                        </div>
                        <div class="product-body">
                            <h3 class="product-price">Rp {{ number_format($game['harga'], 0, ',', '.') }}</h3>
                            <h2 class="product-name"><a href="#">{{ $game['nama'] }}</a></h2>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
        @foreach ($produk as $row) 
        <!-- Product Single --> 
        <div class="col-md-4 col-sm-6 col-xs-6"> 
            <div class="product product-single"> 
                <div class="product-thumb"> 
                    <div class="product-label"> 
                        <span>Kategori</span> 
                        <span class="sale">{{ $row->kategori->nama_kategori }}</span> 
                    </div> 
 
                    <a href="{{ route('produk.detail', $row->id) }}">
    <button class="main-btn quick-view">
        <i class="fa fa-searchplus"></i> Detail Produk
    </button>
</a>

                    <img src="{{ asset('storage/img-produk/thumb_md_' . $row->foto) }}" style="border-radius:10px" alt="{{ $row->nama_produk }}"> 
                </div> 
                <div class="product-body"> 
                    <h3 class="product-price"> Rp. {{ number_format($row->harga, 0, ',', 
'.') }} <span 
                            class="product-old-price">{{ $row->kategori->nama_kategori 
}}</span></h3> 
 
                    <h2 class="product-name"><a href="#">{{ $row->nama_produk }}</a></h2> 
                    <div class="product-btns"> 
                        <a href="#" title="Detail Produk"> 
                            <button class="main-btn icon-btn"><i class="fa 
                            fa-search-plus"></i></button> 
                        </a> 
                        <form action="{{ route('order.addToCart', $row->id) }}" method="post"
                            style="display: inline-block;" title="Pesan Ke Aplikasi">
                            @csrf
                            <button type="submit" class="primary-btn add-to-cart"><i
                                class="fa fa-shopping-cart"></i> Pesan</button>
                        </form>
 
                    </div> 
                </div> 
            </div> 
        </div> 
        <!-- /Product Single --> 
        @endforeach 
        <div class="clearfix visible-md visible-lg visible-sm visible-xs"></div> 
    </div> 
    <!-- /row --> 
</div> 
<!-- /STORE --> 
 
<!-- end template--> 
@endsection 