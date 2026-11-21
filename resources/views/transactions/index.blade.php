@extends('layouts.app')
@section('title', 'My purchases')
@section('content')
<div class="page-heading"><div><span class="eyebrow">YOUR REFRESHMENT HISTORY</span><h1>My purchases<span class="brand-dot">.</span></h1><p class="muted">A record of every completed purchase.</p></div><a class="button button-outline" href="{{ route('products.index') }}">Browse products →</a></div>
<div class="panel table-panel history-panel">
    <div class="table-scroll">
        <table class="history-table" role="table" aria-label="Purchase history">
            <thead role="rowgroup">
                <tr role="row">
                    <th scope="col" role="columnheader">Order</th>
                    <th scope="col" role="columnheader">Product</th>
                    <th scope="col" role="columnheader">Quantity</th>
                    <th scope="col" role="columnheader">Unit price</th>
                    <th scope="col" role="columnheader">Total</th>
                    <th scope="col" role="columnheader">Date (UTC)</th>
                </tr>
            </thead>
            <tbody role="rowgroup">
                @forelse($transactions as $transaction)
                    <tr class="transaction-row" role="row">
                        <td class="cell-order" data-label="Order" role="cell"><span class="cell-value order-id">#{{ $transaction['id'] }}</span></td>
                        <td class="cell-product" data-label="Product" role="cell"><span class="cell-value">{{ $transaction['product_name'] }}</span></td>
                        <td class="cell-quantity" data-label="Quantity" role="cell"><span class="cell-value">{{ $transaction['quantity'] }}</span></td>
                        <td class="cell-unit-price" data-label="Unit price" role="cell"><span class="cell-value">{{ $transaction['unit_price'] }} USD</span></td>
                        <td class="cell-total" data-label="Total" role="cell"><strong class="cell-value">{{ $transaction['total_price'] }} USD</strong></td>
                        <td class="cell-date" data-label="Date (UTC)" role="cell"><span class="cell-value">{{ $transaction['created_at'] }}</span></td>
                    </tr>
                @empty
                    <tr class="history-empty-row" role="row">
                        <td colspan="6" class="history-empty" role="cell"><span class="empty-icon" aria-hidden="true">↗</span><h2>A fresh page for your favorites.</h2><p>No purchases yet. Your first refreshment is waiting.</p><a class="button button-small" href="{{ route('products.index') }}">Find your next favorite <span aria-hidden="true">↗</span></a></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
{{ $transactions->links() }}
@endsection
