 
@extends('v_layouts.app') 
@section('content') 
<!-- template --> 
 
<!-- row --> 
<div class="row"> 
    <div class="col-md-12"> 
        <div class="billing-details"> 
            <div class="section-title"> 
                <h3 class="title"> WUTWUTWUTWUTUWTUWUTT </h3> 
            </div> 
        </div> 
    </div> 
    <!--  Product Details --> 
    <div class="product product-details clearfix"> 
        <div class="col-md-6"> 
            <div id="product-main-view"> 
                <div class="product-view"> 
                    <img src="{{ asset('storage/img-produk/thumb_lg_' . $row->foto) }}" 
alt=""> 
                </div> 
                @foreach ($fotoProdukTambahan as $item) 
                <div class="product-view"> 
                    @if ($item->produk_id == $row->id) 
                    <img src="{{ asset('storage/img-produk/' . $item->foto) }}" alt=""> 
                    @else 
                    @endif 
                </div> 
                @endforeach 
            </div> 
            <div id="product-view"> 
 
 
 
                <div class="product-view"> 
                    <img src="{{ asset('storage/img-produk/thumb_sm_' . $row->foto) }}" 
alt=""> 
                </div> 
                @foreach ($fotoProdukTambahan as $item) 
                <div class="product-view"> 
                    @if ($item->produk_id == $row->id) 
                    <img src="{{ asset('storage/img-produk/' . $item->foto) }}" alt=""> 
                    @else 
                    @endif 
                </div> 
                @endforeach 
            </div> 
        </div> 
        <div class="col-md-6"> 
            <div class="product-body"> 
                <div class="product-label"> 
                    <span>MACAM MACAM</span> 
                    <span class="sale">CUKURUKUUUKKK</span> 
                </div> 
                <h2 class="product-name"> KUCING BAWA HOKI </h2> 
                <h3 class="product-price">Rp. 999999999
</h3> 
                <p> 
                    SEMOGA BANYAK ONG NYAAAA
                </p> 
                <div class="product-options"> 
                    <ul class="size-option"> 
                        <li><span class="text-uppercase">Berat:</span></li> 
                        10000 Gram 
                    </ul> 
                    <ul class="size-option"> 
                        <li><span class="text-uppercase">Stok:</span></li> 
                        unlimited
                    </ul> 
                </div> 
 
                <div class="product-btns"> 
                    <form action="#" method="post" 
                        style="display: inline-block;"> 
                        @csrf 
                        <button type="submit" class="primary-btn add-to-cart"><i class="fa 
fa-shopping-cart"></i> 
                            BELI lah</button> 
                    </form> 
                </div> 
            </div> 
        </div> 
 
    </div> 
</div> 
<!-- /Product Details --> 
 
<!-- end template--> 
@endsection 