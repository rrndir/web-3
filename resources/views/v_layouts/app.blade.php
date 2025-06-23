<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->

    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('image/icon_univ_bsi.png') }}">
    <title>tokoonline</title>

    <!-- Google font -->
    <link href="https://fonts.googleapis.com/css?family=Hind:400,700" rel="stylesheet">

    <!-- Bootstrap -->
    <link type="text/css" rel="stylesheet" href="{{ asset('frontend/css/bootstrap.min.css') }}">

    <!-- Slick -->
    <link type="text/css" rel="stylesheet" href="{{ asset('frontend/css/slick.css') }}">
    <link type="text/css" rel="stylesheet" href="{{ asset('frontend/css/slick-theme.css') }}">

    <!-- nouislider -->
    <link type="text/css" rel="stylesheet" href="{{ asset('frontend/css/nouislider.min.css') }}">

    <!-- Font Awesome Icon -->
    <link rel="stylesheet" href="{{ asset('frontend/css/font-awesome.min.css') }}">

    <!-- Custom stlylesheet -->
    <link type="text/css" rel="stylesheet" href="{{ asset('frontend/css/style.css') }}">

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
		  <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
		  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
		<![endif]-->

</head>

<body>
    <!-- HEADER -->
    <header>
        <!-- top Header -->
        <div id="top-header">
            <div class="container">
                <h2 style="color:white;">Warung <span style="color:#ad0f0f">Game</span></h2>
            </div>
        </div>
        <!-- /top Header -->

       <!-- HEADER -->
<header>
    <div id="header">
        <div class="container" style="display: flex; align-items: center; justify-content: space-between; gap: 20px;">

            <!-- Logo -->
            <div class="header-logo" style="display: flex; align-items: center; gap: 15px;">
                <a class="logo" href="#">
                    <img src="{{ asset('image/wg.png') }}" style="border-radius: 10px; height: 50px;" alt="">
                </a>

                <!-- Menu NAV berpindah ke sini -->
                <div class="menu-nav open" id="menuNav">
                    <ul class="menu-list" style="display: flex; gap: 15px; margin: 0; padding: 0; list-style: none;">
                        <li><a href="{{ route('beranda') }}">Beranda</a></li>
                        <li><a href="{{ route('produk.all') }}">Produk</a></li>
                        <li><a href="{{ route('lokasi') }}">Lokasi</a></li>
                        <li><a href="#">Hubungi Kami</a></li>
                    </ul>
                </div>
            </div>

            <!-- Akun + Keranjang -->
            <div class="pull-right">
                <ul class="header-btns" style="display: flex; align-items: center; gap: 15px;">
                    <!-- Cart -->
                    <li class="header-cart dropdown default-dropdown">
                        <a class="dropdown-toggle" data-toggle="dropdown" aria-expanded="true">
                            <div class="header-btns-icon">
                                <i class="fa fa-shopping-cart"></i>
                            </div>
                            <strong class="text-uppercase">Keranjang</strong>
                        </a>
                    </li>

                    <!-- Account -->
                    @if (Auth::check())
                        <li class="header-account dropdown default-dropdown">
                            <div class="dropdown-toggle" role="button" data-toggle="dropdown" aria-expanded="true">
                                <div class="header-btns-icon">
                                    <i class="fa fa-user-o"></i>
                                </div>
                                <strong class="text-uppercase">{{ Auth::user()->nama }}<i class="fa fa-caret-down"></i></strong>
                            </div>
                            <ul class="custom-menu">
                                <li><a href="{{ route('customer.akun', ['id' => Auth::user()->id]) }}"><i class="fa fa-user-o"></i> Akun Saya</a></li>
                                <li><a href="#"><i class="fa fa-check"></i> History</a></li>
                                <li>
                                    <a href="#" onclick="event.preventDefault(); document.getElementById('keluar-app').submit();">
                                        <i class="fa fa-power-off"></i> Keluar
                                    </a>
                                    <form id="keluar-app" action="{{ route('logout') }}" method="POST" class="display:none">
                                        @csrf
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @else
                        <li class="header-account dropdown default-dropdown">
                            <div class="dropdown-toggle" role="button" data-toggle="dropdown" aria-expanded="true">
                                <div class="header-btns-icon">
                                    <i class="fa fa-user-o"></i>
                                </div>
                                <strong class="text-uppercase">Akun Saya<i class="fa fa-caret-down"></i></strong>
                            </div>
                            <a href="{{ route('auth.redirect') }}" class="text-uppercase">Login</a>
                        </li>
                    @endif

                    <!-- Mobile nav toggle -->
                    <li class="nav-toggle">
                        <button class="nav-toggle-btn main-btn icon-btn"><i class="fa fa-bars"></i></button>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>
<!-- /HEADER -->

<!-- NAVIGATION (Kategori saja) -->
<div id="navigation">
    <div class="container">
        <div id="responsive-nav">
            @php 
                $kategori = DB::table('kategori')->orderBy('nama_kategori', 'asc')->get(); 
            @endphp

            <!-- category nav (tetap di bawah) -->
            <div class="category-nav toggleable-nav" id="categoryNav">
                <span class="category-header toggleable-header">Kategori <i class="fa fa-list"></i></span>
                <ul class="category-list">
                    @foreach ($kategori as $row)
                        <li><a href="{{ route('produk.kategori', $row->id) }}">{{ $row->nama_kategori }}</a></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- CSS -->
<style>
    .toggleable-nav ul {
        display: none;
    }

    .toggleable-nav.open ul {
        display: block;
    }

    .toggleable-header {
        cursor: pointer;
    }
</style>

<!-- JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const headers = document.querySelectorAll('.toggleable-header');
        headers.forEach(header => {
            header.addEventListener('click', function () {
                this.parentElement.classList.toggle('open');
            });
        });
    });
</script>



@if (request()->segment(1) == '' || request()->segment(1) == 'beranda')
<!-- HOME -->
<div id="home" class="full-banner">
    <div id="home-slick">
        <!-- banner -->
        <div class="banner banner-1">
            <img src="{{ asset('frontend/banner/banner web.jpeg') }}"alt="Banner Wukong">
            <div class="banner-caption text-center">
                
                
            </div>
        </div>
        <div class="banner banner-1">
            <img src="{{ asset('frontend/banner/banner-wukong.jpg') }}"alt="Banner Wukong">
            <div class="banner-caption text-center">
                <h1>AUTUMN SALE</h1>
                <h3 class="red-color font-weak">DISKON 10%</h3>
                <button class="primary-btn">BELI SEKARANG!!</button>
            </div>
        </div>
    </div>
</div>
@endif



    <!-- section -->
    <div class="section">
        <!-- container -->
        <div class="container">
            <!-- row -->
            <div class="row">
                <!-- ASIDE -->
                <div id="aside" class="col-md-3">
                    <!-- aside widget -->
                    <div class="aside">
                        <h3 class="aside-title">Top Rated Product</h3>
                        <!-- widget product -->
                        <div class="product product-widget">
                            <div class="product-thumb">
                                <img src="{{ asset('frontend/img/Headset-Gaming-JETEX-G7-1.jpg') }}" style="border-radius:10px" alt="">
                            </div>
                            <div class="product-body">
                                <h2 class="product-name"><a href="#">Headset Gaming</a></h2>
                                <h3 class="product-price">499000 <del class="product-old-price">599000</del></h3>
                                <div class="product-rating">
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star-o empty"></i>
                                </div>
                            </div>
                        </div>
                        <!-- /widget product -->

                        <!-- widget product -->
                        <div class="product product-widget">
                            <div class="product-thumb">
                                <img src="{{ asset('frontend/img/rdr2icon.jpg') }}" style="border-radius:10px" alt="">
                            </div>
                            <div class="product-body">
                                <h2 class="product-name"><a href="#">Red Dead Redemption</a></h2>
                                <h3 class="product-price">799000 <del class="product-old-price"> 899000</del></h3>
                                <div class="product-rating">
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                    <i class="fa fa-star"></i>
                                </div>
                            </div>
                        </div>
                        <!-- /widget product -->
                    </div>
                    <!-- /aside widget -->
                    <!-- aside widget -->
                    <div class="aside"> 
                        <h3 class="aside-title">Filter Kategori</h3> 
                        <ul class="list-links"> 
                        @foreach ($kategori as $row) 
                        <li><a href="{{ route('produk.kategori', $row->id) }}">{{ $row->nama_kategori }}</a></li> 
                        @endforeach 
                        </ul> 
                    </div> 
                    <!-- /aside widget -->
                </div>
                <!-- /ASIDE -->

                <!-- MAIN -->
                <div id="main" class="col-md-9">
                    <!-- store top filter -->
                    <!-- /store top filter -->

                    <!-- @yieldAwal --> 
                    @yield('content') 
                    <!-- @yieldAkhir--> 

                    <!-- store bottom filter -->

                    <!-- /store bottom filter -->
                </div>
                <!-- /MAIN -->
            </div>
            <!-- /row -->
        </div>
        <!-- /container -->
    </div>
    <!-- /section -->

    <!-- FOOTER -->
    <footer id="footer" class="section section-grey">
        <!-- container -->
        <div class="container">
            <!-- row -->
            <div class="row">
                <!-- footer widget -->
                <div class="col-md-3 col-sm-6 col-xs-6">
                    <div class="footer">
                        <!-- footer logo -->
                        <div class="footer-logo">
                            <a class="logo" href="#">
                                <img src="./img/logo.png" alt="">
                            </a>
                        </div>
                        <!-- /footer logo -->

                        <p>Warung Game adalah toko terpercaya untuk para gamer sejati! Menyediakan berbagai kebutuhan gaming mulai dari game original, aksesoris gaming, hingga perangkat keras seperti keyboard mechanical dan mouse RGB. Nikmati pengalaman belanja mudah, harga terjangkau, dan pelayanan cepat!</p>

                        <!-- footer social -->
                        <ul class="footer-social">
                            <li><a href="#"><i class="fa fa-facebook"></i></a></li>
                            <li><a href="#"><i class="fa fa-twitter"></i></a></li>
                            <li><a href="#"><i class="fa fa-instagram"></i></a></li>
                            <li><a href="#"><i class="fa fa-google-plus"></i></a></li>
                            <li><a href="#"><i class="fa fa-pinterest"></i></a></li>
                        </ul>
                        <!-- /footer social -->
                    </div>
                </div>
                <!-- /footer widget -->

                <!-- footer widget -->
                <div class="col-md-3 col-sm-6 col-xs-6">
                    <div class="footer">
                        <h3 class="footer-header">My Account</h3>
                        <ul class="list-links">
                            <li><a href="#">My Account</a></li>
                            <li><a href="#">My Wishlist</a></li>
                            <li><a href="#">Compare</a></li>
                            <li><a href="#">Checkout</a></li>
                            <li><a href="#">Login</a></li>
                        </ul>
                    </div>
                </div>
                <!-- /footer widget -->

                <div class="clearfix visible-sm visible-xs"></div>

                <!-- footer widget -->
                <div class="col-md-3 col-sm-6 col-xs-6">
                    <div class="footer">
                        <h3 class="footer-header">Customer Service</h3>
                        <ul class="list-links">
                            <li><a href="#">About Us</a></li>
                            <li><a href="#">Shiping & Return</a></li>
                            <li><a href="#">Shiping Guide</a></li>
                            <li><a href="#">FAQ</a></li>
                        </ul>
                    </div>
                </div>
                <!-- /footer widget -->

                <!-- footer subscribe -->
                <div class="col-md-3 col-sm-6 col-xs-6">
                    <div class="footer">
                        <h3 class="footer-header">Yuk Gabung Bersama Warung Game!</h3>
                        <p>Bersama kita ciptakan pengalaman gaming yang lebih seru dan solid!

Ingin update game terbaru, diskon eksklusif, atau sekadar ngobrol bareng sesama gamer?</p>
                        <form>
                            <div class="form-group">
                                <input class="input" placeholder="Enter Email Address">
                            </div>
                            <button class="primary-btn">Join Us</button>
                        </form>
                    </div>
                </div>
                <!-- /footer subscribe -->
            </div>
            <!-- /row -->
            <hr>
            <!-- row -->
            <div class="row">
                <div class="col-md-8 col-md-offset-2 text-center">
                    <!-- footer copyright -->
                    <div class="footer-copyright">
                        <!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. -->
                        Warung Game &copy;<script>
                            document.write(new Date().getFullYear());
                        </script> All rights reserved</i> by <a href="https://colorlib.com" target="_blank">Kelompok 1</a>
                        <!-- Link back to Colorlib can't be removed. Template is licensed under CC BY 3.0. -->
                    </div>
                    <!-- /footer copyright -->
                </div>
            </div>
            <!-- /row -->
        </div>
        <!-- /container -->
    </footer>
    <!-- /FOOTER -->

    <!-- jQuery Plugins -->
    <script src="{{ asset('frontend/js/jquery.min.js') }}"></script>
    <script src="{{ asset('frontend/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('frontend/js/slick.min.js') }}"></script>
    <script src="{{ asset('frontend/js/nouislider.min.js') }}"></script>
    <script src="{{ asset('frontend/js/jquery.zoom.min.js') }}"></script>
    <script src="{{ asset('frontend/js/main.js') }}"></script>
    <a href="#home" onclick="scrollToSection('home')">BERANDA</a>


</script>

</body>

</html>