@extends('layouts.app')
@section('title', 'Sign in')
@section('body-class', 'auth-page')
@section('content')
<div class="login-layout">
    <section class="login-intro">
        <span class="eyebrow"><span class="eyebrow-line"></span> YOUR EVERYDAY PIT STOP</span>
        <h1>Good things.<br><em>On demand.</em></h1>
        <p>Your favorite drinks. A moment to yourself.<br>A refreshing little break, whenever you need it.</p>
        <div class="login-art" aria-hidden="true">
            <span class="art-orbit orbit-one"></span><span class="art-orbit orbit-two"></span>
            <span class="art-spark spark-one">✳</span><span class="art-spark spark-two">✦</span>
            <div class="login-drink drink-back">@include('partials.drink', ['drinkName' => 'Pepsi'])</div>
            <div class="login-drink drink-front">@include('partials.drink', ['drinkName' => 'Coke'])</div>
            <div class="art-caption"><span class="status-dot"></span> Pick. Sip. Repeat.</div>
            <span class="art-edition">THE EVERYDAY<br>REFRESH COLLECTION</span>
        </div>
    </section>
    <section class="panel login-panel"><span class="login-card-icon" aria-hidden="true">↗</span><span class="eyebrow">A FRESH START</span><h2>Sign in to your account</h2><p class="muted">Welcome back. Your next break is waiting.</p>
        <form method="post" action="{{ route('login.submit') }}" class="form-stack">@csrf
            <div><label for="email">Email address</label><input type="email" id="email" name="email" placeholder="you@example.com" value="{{ old('email') }}" autocomplete="username" maxlength="255" required autofocus @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>@error('email')<p class="field-error" id="email-error">{{ $message }}</p>@enderror</div>
            <div><label for="password">Password</label><input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" maxlength="255" required @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>@error('password')<p class="field-error" id="password-error">{{ $message }}</p>@enderror</div>
            <button class="button" type="submit">Sign in <span aria-hidden="true">→</span></button>
        </form>
        <p class="login-footnote"><span aria-hidden="true">◇</span> Use the account provided by your administrator.</p>
    </section>
</div>
@endsection
