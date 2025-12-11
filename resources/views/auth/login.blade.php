@extends('layouts.auth')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <div class="text-center mb-4">
                <a href="{{ url('index') }}">
                    <img src="{{ asset('build/assets/images/brand-logos/desktop-dark.png') }}" alt="logo" height="48">
                </a>
            </div>
            <div class="card custom-card">
                <div class="card-header text-center border-0 pb-0">
                    <div class="card-title mb-1">Sign In</div>
                    <p class="text-muted mb-0">Access your account</p>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.attempt') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" id="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   required>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember">
                            <label class="form-check-label" for="remember">
                                Remember me
                            </label>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-login-box-line me-1"></i> Login
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <span class="text-muted">Don't have an account?</span>
                    <a href="{{ route('register') }}" class="ms-1">Register</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

