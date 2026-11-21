<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Vending Machine') · Vending Machine</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script src="{{ asset('js/app.js') }}" defer></script>
</head>
<body>
<header class="site-header">
    <div class="nav-wrap">
        <a class="brand" href="{{ route('products.index') }}"><span class="brand-icon">V</span> vending<span class="brand-light">machine</span><span class="brand-dot">.</span></a>
        @if(session('user_id'))
            <nav aria-label="Main navigation">
                <a @class(['active' => request()->routeIs('products.*') || request()->routeIs('admin.*')]) href="{{ route('products.index') }}">Products</a>
                <a @class(['active' => request()->routeIs('transactions.*')]) href="{{ route('transactions.index') }}">My purchases</a>
            </nav>
            <div class="account"><span>{{ session('user_name') }} <small class="role">{{ session('role') }}</small></span><form method="post" action="{{ route('logout') }}">@csrf<button class="button-quiet" type="submit">Sign out</button></form></div>
        @else
            <span class="header-note">A little refreshment, on demand.</span>
        @endif
    </div>
</header>
<main class="container">
    @if(session('success'))<div class="notice success" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="notice error" role="alert">Please check the highlighted fields and try again.</div>@endif
    @yield('content')
</main>
<footer class="site-footer"><span>Vending Machine</span><span>Prices in USD · Three-decimal precision</span></footer>
</body>
</html>
