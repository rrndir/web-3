@extends('v_layouts.app')

@section('content')
<div class="row">
    <div class="col-md-6 col-md-offset-3">
        <div class="billing-details">
            <div class="section-title">
                <h3 class="title">Daftar Customer</h3>
            </div>

            @if($errors->any())
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('customer.register.submit') }}" method="POST">
                @csrf
                <input type="hidden" name="redirect" value="{{ old('redirect', $redirect) }}">

                <div class="form-group">
                    <label for="nama">Nama</label>
                    <input id="nama" type="text" name="nama" value="{{ old('nama') }}" required
                           autocomplete="name" class="form-control">
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required
                           autocomplete="email" class="form-control">
                </div>

                <div class="form-group">
                    <label for="password">Password (minimal 8 karakter)</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password"
                           class="form-control">
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Ulangi Password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required
                           autocomplete="new-password" class="form-control">
                </div>

                <button type="submit" class="primary-btn">Daftar</button>
                <a class="main-btn" href="{{ route('customer.login', ['redirect' => $redirect]) }}">Sudah punya akun? Masuk</a>
            </form>
        </div>
    </div>
</div>
@endsection
