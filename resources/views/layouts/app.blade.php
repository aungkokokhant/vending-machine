<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#bd252b">
    <title>@yield('title', 'Vending Machine') · Vending Machine</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <script src="{{ asset('js/app.js') }}" defer></script>
</head>
<body class="@yield('body-class')">
<a class="skip-link" href="#main-content">Skip to content</a>
<header class="site-header">
    <div class="nav-wrap">
        <a class="brand" href="{{ route('products.index') }}" aria-label="Vending Machine home"><span class="brand-icon" aria-hidden="true">v<span>.</span></span><span class="brand-name">vending<span class="brand-light">machine</span></span></a>
        @if(session('user_id'))
            <nav aria-label="Main navigation">
                <a @class(['active' => request()->routeIs('products.*') || request()->routeIs('admin.*')]) @if(request()->routeIs('products.*') || request()->routeIs('admin.*')) aria-current="page" @endif href="{{ route('products.index') }}">Products</a>
                <a @class(['active' => request()->routeIs('transactions.*')]) @if(request()->routeIs('transactions.*')) aria-current="page" @endif href="{{ route('transactions.index') }}">My purchases</a>
            </nav>
            <div class="account"><span class="account-avatar" aria-hidden="true">{{ Str::upper(Str::substr(session('user_name', 'Guest'), 0, 1)) }}</span><span class="account-name">{{ session('user_name') }} <small class="role">{{ session('role') }}</small></span><form method="post" action="{{ route('logout') }}">@csrf<button class="button-quiet" type="submit">Sign out <span aria-hidden="true">↗</span></button></form></div>
        @else
            <span class="header-note"><span class="status-dot" aria-hidden="true"></span> A better break starts here.</span>
        @endif
    </div>
</header>
<main class="container" id="main-content">
    @if(session('success'))<div class="notice success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="notice error" role="alert">Please check the highlighted fields and try again.</div>@endif
    @yield('content')
</main>
<footer class="site-footer"><span class="footer-brand">vending<span>machine</span><i aria-hidden="true">.</i></span><span>A little pause. A lot of refreshment.</span><span>Prices in USD <span class="footer-star" aria-hidden="true">✳</span> Enjoy your moment.</span></footer>
</body>
</html>
