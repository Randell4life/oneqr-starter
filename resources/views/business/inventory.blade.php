<!doctype html>
<html lang="ka">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>საწყობი — {{ $business->name }} | oneQR</title>
@include('partials.design-system')
</head>
<body>
<header class="app-nav"><a class="brand" href="{{ route('dashboard') }}">one<span>QR</span></a><nav><a href="{{ route('business.catalog.index',$business) }}">კატალოგი</a><a href="{{ route('business.orders.index',$business) }}">შეკვეთები</a><a href="{{ route('business.qr.index',$business) }}">QR კოდები</a></nav></header>
<main class="app-main">
<div class="page-heading"><div><p class="eyebrow">BUSINESS OPERATIONS / INVENTORY</p><h1>{{ $business->name }} — საწყობი</h1><p class="muted">მარაგების კონტროლი, დაბალი ნაშთები და ყველა მოძრაობის ისტორია.</p></div><a class="btn" href="{{ route('business.catalog.index',$business) }}">კატალოგში დაბრუნება</a></div>
@if(session('status'))<div class="notice success">{{ session('status') }}</div>@endif
@if($errors->any())<div class="notice danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div class="stats-grid">
<section class="stat-card"><span class="stat-label">აღრიცხვადი პროდუქტები</span><strong>{{ number_format($summary['tracked']) }}</strong><small>მარაგის კონტროლი ჩართულია</small></section>
<section class="stat-card"><span class="stat-label">მარაგის ერთეულები</span><strong>{{ number_format($summary['units']) }}</strong><small>ყველა პროდუქტში ჯამურად</small></section>
<section class="stat-card"><span class="stat-label">დაბალი მარაგი</span><strong>{{ number_format($summary['low']) }}</strong><small>ზღვარზე ან ზღვარს ქვემოთ</small></section>
<section class="stat-card"><span class="stat-label">ამოწურული</span><strong>{{ number_format($summary['out']) }}</strong><small>ნაშთი ნულია</small></section>
</div>
<section class="surface">
<div class="section-heading"><div><h2>პროდუქტების მარაგი</h2><p class="muted">მარაგი ავტომატურად ჩამოიწერება შეკვეთის გაფორმებისას და დაბრუნდება შეკვეთის გაუქმებისას.</p></div></div>
<div class="table-wrap"><table class="data-table"><thead><tr><th>პროდუქტი</th><th>SKU</th><th>მარაგი</th><th>მინ. ზღვარი</th><th>სტატუსი</th><th>მოძრაობა</th></tr></thead><tbody>
@forelse($items as $item)
<tr><td><strong>{{ $item->name }}</strong><small>{{ $item->is_available ? 'აქტიურია კატალოგში' : 'დამალულია კატალოგში' }}</small></td><td>{{ $item->sku ?: '—' }}</td>
<td>@if($item->track_inventory)<strong>{{ number_format((int)$item->stock_quantity) }}</strong><small>ერთეული</small>@else<span class="muted">არ კონტროლდება</span>@endif</td>
<td>{{ $item->track_inventory ? number_format((int)$item->low_stock_threshold) : '—' }}</td>
<td>@if(!$item->track_inventory)<span class="status-pill">გამორთული</span>@elseif((int)$item->stock_quantity===0)<span class="status-pill danger-pill">ამოწურული</span>@elseif((int)$item->stock_quantity <= (int)$item->low_stock_threshold)<span class="status-pill warning-pill">დაბალი მარაგი</span>@else<span class="status-pill success-pill">ნორმაში</span>@endif</td>
<td>@if($canManage && $item->track_inventory)
<form method="POST" action="{{ route('business.inventory.adjust',[$business,$item->id]) }}" class="stock-form">@csrf
<select name="movement" aria-label="მოძრაობის ტიპი"><option value="restock">მიღება +</option><option value="adjustment">ჩამოწერა −</option></select>
<input type="number" name="quantity" min="1" max="100000" value="1" aria-label="რაოდენობა" required>
<input type="text" name="note" maxlength="255" placeholder="კომენტარი" aria-label="კომენტარი">
<button class="btn btn-small">შენახვა</button></form>
@else<a href="{{ route('business.catalog.index',$business) }}">კატალოგში მართვა</a>@endif</td></tr>
@empty<tr><td colspan="6" class="empty-state">პროდუქტები ჯერ არ არის დამატებული.</td></tr>@endforelse
</tbody></table></div>
{{ $items->links() }}
</section>
<section class="surface"><div class="section-heading"><div><h2>მარაგის ბოლო მოძრაობები</h2><p class="muted">ბოლო 12 მიღება, ჩამოწერა და დაბრუნება.</p></div></div>
<div class="movement-list">@forelse($movements as $movement)
<div class="movement-row"><div><strong>{{ $movement->catalogItem?->name ?? 'წაშლილი პროდუქტი' }}</strong><small>{{ $movement->note ?: ($movement->user?->name ?? 'სისტემური ოპერაცია') }} · {{ $movement->created_at->format('d.m.Y H:i') }}</small></div><span class="{{ $movement->quantity > 0 ? 'movement-in' : 'movement-out' }}">{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}</span><span class="muted">{{ $movement->stock_before }} → {{ $movement->stock_after }}</span></div>
@empty<p class="muted">მოძრაობების ისტორია ჯერ ცარიელია.</p>@endforelse</div>
</section>
</main>
</body></html>
