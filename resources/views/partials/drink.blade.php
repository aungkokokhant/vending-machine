@php($drinkStyle = str_contains(strtolower($drinkName), 'pepsi') ? 'blue' : (str_contains(strtolower($drinkName), 'water') ? 'aqua' : 'red'))
<div class="product-vessel vessel-{{ $drinkStyle }}" aria-hidden="true">
    <span class="vessel-shine"></span>
    <span class="vessel-wordmark">{{ Str::upper(Str::limit($drinkName, 18)) }}</span>
    <span class="vessel-rule"></span>
    <small>{{ $drinkStyle === 'aqua' ? 'PURE & SIMPLE' : 'A CLASSIC FOR A REASON' }}</small>
    <span class="vessel-bottom-label">YOUR EVERYDAY REFRESHMENT</span>
</div>
