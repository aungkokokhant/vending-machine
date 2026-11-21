@extends('layouts.app')
@section('title', 'Delete product')
@section('content')
<a class="back-link" href="{{ route('products.index') }}">← Back to products</a>
<section class="panel narrow-panel delete-panel"><span class="eyebrow">INVENTORY MANAGEMENT</span><h1>Delete {{ $product['name'] }}?</h1><p class="muted">This removes the product from the catalog. Existing purchase records will remain available.</p><form class="form-actions" method="post" action="{{ route('admin.products.destroy', $product['id']) }}">@csrf @method('DELETE')<button class="button button-danger" type="submit">Delete product</button><a class="button button-outline" href="{{ route('admin.products.show', $product['id']) }}">Cancel</a></form></section>
@endsection
