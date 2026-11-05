<!doctype html>
<html lang="{{ $business->default_locale ?: 'ka' }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="description" content="{{ $business->tagline ?: Str::limit(strip_tags($business->description ?? ''), 150) }}"><title>{{ $business->name }} — oneQR</title>
<style>
:root{color-scheme:dark;--accent:{{ $business->accent_color ?: '#8B5CF6' }};--bg:#090812;--panel:#141122;--line:#2a2540;--muted:#aaa3bd;--text:#f8f6ff}*{box-sizing:border-box}body{margin:0;background:radial-gradient(ellipse at 50% -20%,color-mix(in srgb,var(--accent) 22%,transparent),transparent 55%),var(--bg);color:var(--text);font:16px Inter,system-ui,sans-serif}.wrap{max-width:820px;margin:0 auto;padding:24px}.cover{height:260px;border-radius:24px;overflow:hidden;background:linear-gradient(135deg,var(--accent),#19142b)}.cover img{width:100%;height:100%;object-fit:cover}.profile{position:relative;margin:-52px 24px 0}.logo{width:104px;height:104px;border-radius:24px;border:5px solid var(--bg);background:#211b35;object-fit:cover}.card{background:var(--panel);border:1px solid var(--line);border-radius:22px;padding:28px;margin-top:18px}.muted{color:var(--muted);line-height:1.7}.type{display:inline-block;background:color-mix(in srgb,var(--accent) 20%,transparent);color:#e7ddff;border-radius:30px;padding:7px 12px;font-size:12px}h1{font-size:clamp(30px,6vw,46px);margin:15px 0 6px;letter-spacing:-1.5px}.tagline{font-size:19px;color:#e2d9f8}.actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:22px}.action{display:inline-block;text-decoration:none;color:white;background:var(--accent);padding:12px 17px;border-radius:12px;font-weight:700}.action.secondary{background:#242035;border:1px solid var(--line)}footer{text-align:center;color:var(--muted);font-size:12px;padding:30px}@media(max-width:600px){.wrap{padding:14px}.cover{height:190px}.card{padding:20px}.profile{margin-left:14px}}
</style>@include('partials.design-system')
</head><body><main class="wrap"><div class="cover">@if($business->cover_path)<img src="{{ Storage::disk('public')->url($business->cover_path) }}" alt="">@endif</div><section class="profile">@if($business->logo_path)<img class="logo" src="{{ Storage::disk('public')->url($business->logo_path) }}" alt="{{ $business->name }} ლოგო">@else<div class="logo" style="display:grid;place-items:center;font-size:34px;font-weight:900;color:var(--accent)">{{ mb_substr($business->name,0,1) }}</div>@endif</section>
<section class="card"><span class="type">{{ ['restaurant'=>'რესტორანი / კაფე','retail'=>'მაღაზია','hotel'=>'სასტუმრო','services'=>'სერვისები','beauty'=>'სილამაზე და მოვლა','events'=>'ღონისძიებები','other'=>'ბიზნესი'][$business->business_type] ?? 'ბიზნესი' }}</span><h1>{{ $business->name }}</h1>@if($business->tagline)<div class="tagline">{{ $business->tagline }}</div>@endif
@if($business->description)<p class="muted" style="white-space:pre-line">{{ $business->description }}</p>@endif
<div class="muted">@if($business->city || $business->address)📍 {{ $business->city }} {{ $business->address }}<br>@endif</div>
<div class="actions">@if($business->phone)<a class="action" href="tel:{{ preg_replace('/[^0-9+]/','',$business->phone) }}">დარეკვა</a>@endif @if($business->email)<a class="action secondary" href="mailto:{{ $business->email }}">ელფოსტის გაგზავნა</a>@endif @if($business->website)<a class="action secondary" href="{{ $business->website }}" target="_blank" rel="noopener noreferrer">ვებგვერდი ↗</a>@endif</div></section>

<section class="card" id="catalog"><div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap"><h2>პროდუქტები და სერვისები</h2><a class="action" href="{{ route('business.cart.show',$business->slug) }}">კალათა</a></div>
@if(session('cart_status'))<p class="muted" style="color:#a8f0d1">{{ session('cart_status') }}</p>@endif
@if(session('order_success'))<div class="muted" style="color:#a8f0d1">შეკვეთა მიღებულია! ნომერი: <strong>{{ session('order_success.number') }}</strong> · ჯამი: {{ session('order_success.total') }}</div>@endif
@if(session('payment_success'))<p class="muted" style="color:#a8f0d1">{{ session('payment_success') }}</p>@endif
@if(session('payment_error'))<p class="muted" style="color:#ffc2d0">{{ session('payment_error') }}</p>@endif
@if(session('payment_pending'))<p class="muted">{{ session('payment_pending') }}</p>@endif
@if(session('order_status'))<p class="muted" style="color:#a8f0d1">{{ session('order_status') }}</p>@endif
@if($errors->any())<div class="muted" style="color:#ffc2d0">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px">
@forelse($business->catalogItems->where('is_available',true) as $item)
<article style="background:#0d0b17;border:1px solid var(--line);border-radius:16px;padding:16px">
@if($item->image_path)<img src="{{ Storage::disk('public')->url($item->image_path) }}" alt="{{ $item->name }}" style="width:100%;height:160px;object-fit:cover;border-radius:10px">@endif
<span class="type">{{ $item->type==='product'?'პროდუქტი':'სერვისი' }}</span><h3>{{ $item->name }}</h3>
@if($item->description)<p class="muted">{{ $item->description }}</p>@endif
<p style="font-weight:800">{{ $item->price !== null ? number_format((float)$item->price,2).' '.$item->currency : 'ფასი შეთანხმებით' }}</p>
<form method="POST" action="{{ route('business.cart.add',[$business->slug,$item->id]) }}" style="display:flex;gap:8px;align-items:center">
@csrf
<input aria-label="რაოდენობა" name="quantity" type="number" min="1" max="100" value="1" required style="width:76px;padding:10px;border-radius:8px;border:1px solid var(--line);background:#090812;color:white">
<button class="action" type="submit" style="border:0;cursor:pointer">კალათაში დამატება</button>
</form></article>
@empty<p class="muted">კატალოგი მალე შეივსება.</p>@endforelse
</div></section>
<footer>ეს გვერდი შექმნილია <strong>oneQR</strong>-ით · ONE SCAN. ENDLESS POSSIBILITIES.</footer></main></body></html>
