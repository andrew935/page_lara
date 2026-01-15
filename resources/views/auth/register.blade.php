@extends('layouts.auth')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="text-center mb-4">
                <a href="{{ route('welcome') }}">
                    <img src="{{ asset('img/logo.png') }}" alt="logo" height="48">
                </a>
            </div>
            <div class="card custom-card">
                <div class="card-header text-center border-0 pb-0">
                    <div class="card-title mb-1">Create Account</div>
                    <p class="text-muted mb-0">
                        @if(request('plan'))
                            Register for the <strong class="text-primary">{{ ucfirst(request('plan')) }}</strong> plan
                        @else
                            Register to get started
                        @endif
                    </p>
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

                    <form method="POST" action="{{ route('register.submit') }}">
                        @csrf
                        @if(request('plan'))
                            <input type="hidden" name="plan" value="{{ request('plan') }}">
                        @endif
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" name="name" id="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" required>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" name="password" id="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       required>
                            </div>
                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">Confirm Password</label>
                                <input type="password" name="password_confirmation" id="password_confirmation"
                                       class="form-control" required>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="form-check">
                                <input class="form-check-input @error('terms') is-invalid @enderror" 
                                       type="checkbox" value="1" id="terms" name="terms" 
                                       {{ old('terms') ? 'checked' : '' }} required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="{{ route('terms') }}" target="_blank" class="text-primary">Terms and Conditions</a>
                                </label>
                                @error('terms')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-user-add-line me-1"></i> Register
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <span class="text-muted">Already have an account?</span>
                    <a href="{{ route('login') }}" class="ms-1">Login</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

