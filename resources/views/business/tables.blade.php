<!doctype html>
<html lang="ka"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>მაგიდები — {{ $business->name }}</title>
<style>
:root{color-scheme:dark;--bg:#090812;--panel:#141122;--line:#2a2540;--muted:#aaa3bd;--text:#f8f6ff;--accent:#9b72ff}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font:15px Inter,system-ui,sans-serif}main{max-width:1200px;margin:32px auto;padding:0 18px}a{color:#cbbaff;text-decoration:none}
header{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}.muted{color:var(--muted)}.panel,.table-card{background:var(--panel);border:1px solid var(--line);border-radius:14px;padding:18px;margin:14px 0}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:12px}.table-card h2{margin:0 0 8px}.pill{display:inline-block;padding:5px 9px;border-radius:30px;background:#302a43;color:#ddd1ff;font-size:12px}.pill.busy{background:#553e22;color:#ffe0a3}.pill.free{background:#174632;color:#a6f0c9}
.btn{display:inline-block;border:0;border-radius:8px;padding:10px 13px;background:var(--accent);color:white;font-weight:700;margin-top:8px}.empty{padding:24px;border:1px dashed var(--line);border-radius:12px;color:var(--muted)}
@media(max-width:600px){main{margin:18px auto}.table-card{padding:14px}}
</style>@include('partials.design-system')
</head><body><main>
<header><div><a href="{{ route('dashboard') }}">← ბიზნეს-კაბინეტი</a><h1 style="margin:12px 0 4px">მაგიდების მართვა</h1><p class="muted">{{ $business->name }} · მაგიდები წარმოდგენილია QR კოდებით</p></div>
<div><a class="btn" href="{{ route('business.qr.index',$business) }}">QR კოდების მართვა</a> <a class="btn" href="{{ route('business.kitchen',$business) }}">სამზარეულო</a></div></header>
<section class="panel"><strong>{{ $tables->count() }}</strong> მაგიდის QR · <strong>{{ $activeOrders->flatten()->count() }}</strong> აქტიური შეკვეთა
<p class="muted">მაგიდის დასამატებლად შექმენი QR კოდი „მაგიდის შეკვეთა“ კატეგორიით და მიუთითე განთავსების ადგილი, მაგალითად „მაგიდა 04“. QR უცვლელი რჩება და სტუმარი იმავე კოდით განათავსებს შეკვეთას.</p>
</section>
@if($tables->isEmpty())<div class="empty">მაგიდის QR ჯერ არ გაქვს. <a href="{{ route('business.qr.index',$business) }}">შექმენი პირველი მაგიდის QR →</a></div>
@else<div class="grid">
@foreach($tables as $table)
@php($label = $table->placement_label ?: $table->name)
@php($orders = $activeOrders->get($label, collect()))
<article class="table-card">
<div style="display:flex;justify-content:space-between;gap:10px;align-items:center"><h2>{{ $label }}</h2><span class="pill {{ $orders->isNotEmpty() ? 'busy' : 'free' }}">{{ $orders->isNotEmpty() ? 'შეკვეთა აქტიურია' : 'თავისუფალი' }}</span></div>
<p class="muted">{{ $table->branch?->name ?? 'ძირითადი ფილიალი' }} · სკანირება {{ number_format($table->scan_count) }}</p>
@if($orders->isNotEmpty())
<p><strong>{{ $orders->count() }} აქტიური შეკვეთა</strong></p>
@foreach($orders as $order)<p class="muted">#{{ $order->order_number }} · {{ $order->status }} · {{ number_format((float)$order->total,2) }} {{ $order->currency }}</p>@endforeach
@else<p class="muted">ამ მაგიდაზე აქტიური შეკვეთა არ ფიქსირდება.</p>@endif
<div><a href="{{ route('business.qr.print',[$business,$table->id]) }}">QR ბარათის ბეჭდვა ↗</a> · <a href="{{ route('qr.redirect',$table->public_id) }}" target="_blank" rel="noopener">QR-ის შემოწმება ↗</a></div>
@if($canManageQr)<p><a href="{{ route('business.qr.index',[$business,'placement'=>$label]) }}">მაგიდის QR-ის რედაქტირება →</a></p>@endif
</article>
@endforeach
</div>@endif
</main></body></html>