@extends('v_layouts.app')

@section('content')
<div class="section-title">
    <h3 class="title">Pesanan Saya</h3>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@forelse($orders as $order)
    <div class="order-summary clearfix" style="padding: 16px; margin-bottom: 16px;">
        <p>
            <strong>Pesanan #{{ $order->id }}</strong>
            <span class="pull-right">{{ str_replace('_', ' ', ucfirst($order->status)) }}</span>
        </p>
        <p>Total: Rp {{ number_format($order->total_harga, 0, ',', '.') }}</p>
        <a class="primary-btn" href="{{ route('order.show', $order) }}">Lihat pesanan</a>
    </div>
@empty
    <p>Belum ada pesanan.</p>
    <a class="primary-btn" href="{{ route('produk.all') }}">Lihat produk</a>
@endforelse

{{ $orders->links('vendor.pagination.custom') }}
@endsection
