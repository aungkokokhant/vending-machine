@extends('layouts.app')
@section('title', 'Sign in')
@section('content')
<div class="login-layout">
    <section class="login-intro"><span class="eyebrow">YOUR EVERYDAY PIT STOP</span><h1>Take a break.<br>Pick a favorite.</h1><p>Browse what’s in stock, grab your drink, and get back to your day.</p><div class="drink-scene" aria-hidden="true"><div class="decor-circle"></div><div class="can can-red">COKE<span>CLASSIC REFRESHMENT</span></div><div class="can can-blue">PEPSI<span>MAKE IT A MOMENT</span></div><div class="bottle">WATER<span>STAY REFRESHED</span></div></div></section>
    <section class="panel login-panel"><span class="eyebrow">WELCOME BACK</span><h2>Sign in to your account</h2><p class="muted">Your next refreshment is a few clicks away.</p>
        <form method="post" action="{{ route('login.submit') }}" class="form-stack">@csrf
            <div><label for="email">Email address</label><input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="username" maxlength="255" required autofocus @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>@error('email')<p class="field-error" id="email-error">{{ $message }}</p>@enderror</div>
            <div><label for="password">Password</label><input type="password" id="password" name="password" autocomplete="current-password" maxlength="255" required>@error('password')<p class="field-error">{{ $message }}</p>@enderror</div>
            <button class="button" type="submit">Sign in <span aria-hidden="true">→</span></button>
        </form>
        <p class="login-footnote">Use the account provided by your administrator.</p>
    </section>
</div>
@endsection
