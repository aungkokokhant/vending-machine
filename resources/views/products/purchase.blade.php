@extends('layouts.app')
@section('title', 'Buy '.$product['name'])
@section('content')
<a class="back-link" href="{{ route('products.index') }}">← Back to products</a>
<div class="page-heading"><div><span class="eyebrow">A MOMENT OF REFRESHMENT</span><h1>{{ $product['name'] }}</h1><p class="muted">Choose your quantity and confirm your purchase.</p></div></div>
<div class="purchase-layout"><section class="panel purchase-product-panel"><div class="purchase-art">@include('partials.drink', ['drinkName' => $product['name']])</div><div class="purchase-product-details"><dl class="detail-list"><div><dt>Price per unit</dt><dd>{{ $product['price'] }} USD</dd></div><div><dt>Available quantity</dt><dd>{{ $product['quantity_available'] }}</dd></div></dl><p class="muted">The final price and stock are checked when you confirm.</p></div></section><section class="panel order-panel"><span class="eyebrow">A LITTLE SOMETHING FOR YOU</span><h2>Your order</h2><p class="muted order-intro">One step closer to your refreshing moment.</p>
    @if($product['quantity_available'] > 0)
        <form class="form-stack" method="post" action="{{ route('products.purchase', $product['slug']) }}" data-purchase-form data-unit-price="{{ $product['price'] }}">@csrf
            <div><label for="quantity">Quantity</label><input type="number" id="quantity" name="quantity" value="{{ old('quantity', 1) }}" min="1" max="{{ $product['quantity_available'] }}" step="1" required @error('quantity') aria-invalid="true" @enderror>@error('quantity')<p class="field-error">{{ $message }}</p>@enderror</div>
            <div class="order-total"><span>Estimated total</span><strong><output data-order-total aria-live="polite">{{ $product['price'] }}</output> <small>USD</small></strong></div><button class="button button-full" type="submit">Confirm purchase <span class="button-arrow" aria-hidden="true">↗</span></button>
        </form>
        <p class="order-note"><span aria-hidden="true">◇</span> A good break is just a click away.</p>
    @else
        <div class="notice">This product is currently out of stock.</div><a class="button button-outline" href="{{ route('products.index') }}">Browse other products</a>
    @endif
</section></div>
@endsection
