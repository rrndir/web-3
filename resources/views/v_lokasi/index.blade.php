@extends('v_layouts.app')

@section('title', 'Lokasi Kami')

@section('content')
<div class="container my-5">
    <h2 class="text-center mb-4">Lokasi Toko Kami</h2>

    <div class="row">
        <div class="col-md-6">
            <h4>Toko Pusat</h4>
            <p>
                Jl. Gamer Street No. 123, Jakarta<br>
                Telp: (021) 1234-5678<br>
                Email: support@warunggame.com
            </p>
            <p>
                Jam Operasional:<br>
                Senin - Sabtu: 10:00 - 20:00<br>
                Minggu: Tutup
            </p>
        </div>
        <div class="col-md-6">
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!..." 
                width="100%" 
                height="300" 
                style="border:0;" 
                allowfullscreen="" 
                loading="lazy">
            </iframe>
        </div>
    </div>
</div>
@endsection
