@extends('backend.v_layouts.app')

@section('content')
<div class="card">
    <div class="card-body">
        <h4 class="card-title">Pesanan Customer</h4>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Pesanan</th>
                        <th>Customer</th>
                        <th>Jenis</th>
                        <th>Status</th>
                        <th>Pembayaran</th>
                        <th>Total</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td>#{{ $order->id }}</td>
                            <td>{{ $order->customer->user->nama }}<br>{{ $order->customer->user->email }}</td>
                            <td>{{ $order->fulfillment_type === 'digital' ? 'Digital' : 'Fisik' }}</td>
                            <td>{{ str_replace('_', ' ', ucfirst($order->status)) }}</td>
                            <td>{{ str_replace('_', ' ', ucfirst($order->payment_status)) }}</td>
                            <td>Rp {{ number_format($order->total_harga, 0, ',', '.') }}</td>
                            <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td><a href="{{ route('backend.order.show', $order) }}" class="btn btn-primary btn-sm">Kelola</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center">Belum ada pesanan customer.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $orders->links() }}
    </div>
</div>
@endsection
