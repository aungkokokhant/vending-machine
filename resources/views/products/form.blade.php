@extends('layouts.app')
@section('title', $product ? 'Edit product' : 'Add product')
@section('content')
<a class="back-link" href="{{ route('products.index') }}">← Back to products</a>
<div class="page-heading"><div><span class="eyebrow">INVENTORY MANAGEMENT</span><h1>{{ $product ? 'Edit product' : 'Add a product' }}</h1><p class="muted">Set the price and available inventory.</p></div></div>
<section class="panel narrow-panel"><form class="form-stack" method="post" action="{{ $product ? route('admin.products.update', $product['id']) : route('admin.products.store') }}">@csrf @if($product) @method('PUT') @endif
    <div><label for="name">Product name</label><input id="name" name="name" value="{{ old('name', $product['name'] ?? '') }}" maxlength="255" required @error('name') aria-invalid="true" @enderror>@error('name')<p class="field-error">{{ $message }}</p>@enderror</div>
    <div class="form-row"><div><label for="price">Price (USD)</label><input id="price" name="price" type="number" value="{{ old('price', $product['price'] ?? '') }}" min="0.001" max="9999999.999" step="0.001" required @error('price') aria-invalid="true" @enderror><p class="field-hint">Up to three decimal places.</p>@error('price')<p class="field-error">{{ $message }}</p>@enderror</div><div><label for="quantity_available">Available quantity</label><input id="quantity_available" name="quantity_available" type="number" value="{{ old('quantity_available', $product['quantity_available'] ?? '') }}" min="0" max="4294967295" step="1" required @error('quantity_available') aria-invalid="true" @enderror>@error('quantity_available')<p class="field-error">{{ $message }}</p>@enderror</div></div>
    <div class="form-actions"><button class="button" type="submit">{{ $product ? 'Save changes' : 'Create product' }}</button><a class="button button-outline" href="{{ route('products.index') }}">Cancel</a></div>
</form></section>
@endsection
