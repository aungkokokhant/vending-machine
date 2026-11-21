@extends('layouts.app')
@section('title', 'My purchases')
@section('content')
<div class="page-heading"><div><span class="eyebrow">YOUR REFRESHMENT HISTORY</span><h1>My purchases<span class="brand-dot">.</span></h1><p class="muted">A record of every completed purchase.</p></div><a class="button button-outline" href="{{ route('products.index') }}">Browse products →</a></div>
<div class="panel table-panel"><div class="table-scroll"><table><thead><tr><th scope="col">Order</th><th scope="col">Product</th><th scope="col">Quantity</th><th scope="col">Unit price</th><th scope="col">Total</th><th scope="col">Date (UTC)</th></tr></thead><tbody>@forelse($transactions as $transaction)<tr><td>#{{ $transaction['id'] }}</td><td>{{ $transaction['product_name'] }}</td><td>{{ $transaction['quantity'] }}</td><td>{{ $transaction['unit_price'] }} USD</td><td><strong>{{ $transaction['total_price'] }} USD</strong></td><td>{{ $transaction['created_at'] }}</td></tr>@empty<tr><td colspan="6" class="empty-state">No purchases yet. Your first refreshment is waiting.</td></tr>@endforelse</tbody></table></div></div>
{{ $transactions->links() }}
@endsection
