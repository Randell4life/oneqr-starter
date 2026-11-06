<!doctype html>
<html lang="ka">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta http-equiv="refresh" content="20">
<title>სამზარეულო — {{ $business->name }}</title>
<style>
:root{color-scheme:dark;--bg:#090812;--panel:#141122;--line:#2a2540;--muted:#aaa3bd;--text:#f8f6ff;--accent:#9b72ff;--green:#39c98a;--amber:#f3b84b}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font:15px Inter,system-ui,sans-serif}
main{max-width:1500px;margin:24px auto;padding:0 18px}a{color:#cbbaff;text-decoration:none}
header{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}
.muted{color:var(--muted)}.stats{display:flex;gap:10px;flex-wrap:wrap;margin:18px 0}
.stat,.ticket{background:var(--panel);border:1px solid var(--line);border-radius:14px;padding:16px}
.stat strong{font-size:22px;display:block}.lanes{display:grid;grid-template-columns:repeat(3,minmax(260px,1fr));gap:14px;align-items:start}
.lane h2{font-size:17px}.ticket{margin:10px 0}.ticket-top{display:flex;justify-content:space-between;gap:10px;align-items:start}
.table{color:#cbbaff;font-weight:800;font-size:18px}.items{border-top:1px solid var(--line);margin-top:12px;padding-top:8px}
.item{display:flex;justify-content:space-between;gap:10px;padding:6px 0}.item strong{font-size:15px}
.note{background:#0d0b17;border-radius:8px;padding:9px;margin-top:8px;color:var(--muted)}
button{border:0;border-radius:9px;padding:11px 13px;font-weight:700;cursor:pointer;background:var(--accent);color:white}
button.secondary{background:#302a43}button.cancel{background:#633347}.actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}
.empty{border:1px dashed var(--line);border-radius:12px;padding:20px;color:var(--muted)}
@media(max-width:900px){.lanes{grid-template-columns:1fr}}@media(min-width:901px) and (max-width:1150px){.lanes{grid-template-columns:repeat(2,minmax(250px,1fr))}}
</style>
</head>
<body><main>
<header><div><a href="{{ route('business.orders.index',$business) }}">← შეკვეთები</a><h1 style="margin:12px 0 4px">სამზარეულოს ეკრანი</h1><p class="muted" style="margin:0">{{ $business->name }} · განახლება ყოველ 20 წამში</p></div>
<form method="GET"><button class="secondary" type="submit">ახლავე განახლება</button></form></header>
<section class="stats"><div class="stat"><span class="muted">ახალი შეკვეთები</span><strong>{{ $newCount }}</strong></div><div class="stat"><span class="muted">მზადდება</span><strong>{{ $preparingCount }}</strong></div><div class="stat"><span class="muted">აქტიური ჯამი</span><strong>{{ $orders->count() }}</strong></div></section>
<div class="lanes">
<section class="lane"><h2>ახალი შეკვეთები</h2>
@php($newOrders = $orders->where('status','new'))
@forelse($newOrders as $order)
<article class="ticket"><div class="ticket-top"><div><strong>#{{ $order->order_number }}</strong><p class="muted">{{ $order->created_at->format('H:i') }} · {{ $order->customer_name }}</p></div>@if($order->table_label)<span class="table">{{ $order->table_label }}</span>@endif</div>
<div class="items">@foreach($order->items as $item)<div class="item"><strong>{{ $item->item_name }}</strong><span>× {{ $item->quantity }}</span></div>@endforeach</div>
@if($order->notes)<div class="note">{{ $order->notes }}</div>@endif
<div class="actions"><form method="POST" action="{{ route('business.orders.update-status',[$business,$order->id]) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="confirmed"><button>მიღება</button></form><form method="POST" action="{{ route('business.orders.update-status',[$business,$order->id]) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="cancelled"><button class="cancel">გაუქმება</button></form></div></article>
@empty<div class="empty">ახალი შეკვეთები არ არის.</div>@endforelse
</section>
<section class="lane"><h2>მზადდება</h2>
@php($preparingOrders = $orders->whereIn('status',['confirmed','processing']))
@forelse($preparingOrders as $order)
<article class="ticket"><div class="ticket-top"><div><strong>#{{ $order->order_number }}</strong><p class="muted">{{ $order->created_at->format('H:i') }} · {{ $order->customer_name }}</p></div>@if($order->table_label)<span class="table">{{ $order->table_label }}</span>@endif</div>
<div class="items">@foreach($order->items as $item)<div class="item"><strong>{{ $item->item_name }}</strong><span>× {{ $item->quantity }}</span></div>@endforeach</div>
@if($order->notes)<div class="note">{{ $order->notes }}</div>@endif
<div class="actions">@if($order->status === 'confirmed')<form method="POST" action="{{ route('business.orders.update-status',[$business,$order->id]) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="processing"><button>მომზადების დაწყება</button></form>@endif<form method="POST" action="{{ route('business.orders.update-status',[$business,$order->id]) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="completed"><button>მზადაა</button></form></div></article>
@empty<div class="empty">მომზადების პროცესში შეკვეთები არ არის.</div>@endforelse
</section>
<section class="lane"><h2>დასრულებული</h2><div class="empty">დასრულებული შეკვეთები ჩანს შეკვეთების სრულ სიაში.</div><p><a href="{{ route('business.orders.index',$business) }}">ყველა შეკვეთის ნახვა →</a></p></section>
</div></main></body></html>