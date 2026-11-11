@extends('v_layouts.app')

@section('content')
<div class="row">
    <div class="col-md-6 col-md-offset-3">
        <div class="billing-details">
            <div class="section-title">
                <h3 class="title">Masuk sebagai Customer</h3>
            </div>

            <form action="{{ route('customer.login.submit') }}" method="POST">
                @csrf
                <input type="hidden" name="redirect" value="{{ old('redirect', $redirect) }}">

                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required
                           autocomplete="email" class="form-control @error('email') is-invalid @enderror">
                    @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required autocomplete="current-password"
                           class="form-control @error('password') is-invalid @enderror">
                    @error('password') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <button type="submit" class="primary-btn">Masuk</button>
                <a class="main-btn" href="{{ route('customer.register', ['redirect' => $redirect]) }}">Buat akun</a>
                @if(config('services.google.client_id'))
                    <a class="main-btn" href="{{ route('auth.redirect') }}">Masuk dengan Google</a>
                @endif
            </form>
        </div>
    </div>
</div>
@endsection
