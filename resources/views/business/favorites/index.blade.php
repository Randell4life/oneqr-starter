<!doctype html>
<html lang="ka">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>ჩემი რჩეულები — oneQR</title>
<style>
:root{color-scheme:dark;--bg:#090812;--panel:#121020;--line:#29243d;--text:#f6f3ff;--muted:#aaa3bd;--purple:#9b72ff}
*{box-sizing:border-box}body{margin:0;background:radial-gradient(ellipse at 80% -20%,#3a286455,transparent 42%),var(--bg);color:var(--text);font:15px Inter,system-ui,sans-serif}
header{min-height:76px;padding:18px max(24px,calc((100vw - 1100px)/2));border-bottom:1px solid var(--line);display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}
.logo{font-size:24px;font-weight:900}.logo span{color:var(--purple)}main{max-width:1100px;margin:48px auto;padding:0 24px}
h1{font-size:clamp(30px,5vw,44px);letter-spacing:-.05em;margin:0 0 8px}.muted{color:var(--muted);line-height:1.6}
a{color:#c8b6ff;text-decoration:none}.button{display:inline-flex;align-items:center;justify-content:center;padding:10px 14px;background:#39265f;border:1px solid #684bb0;border-radius:10px;color:#fff}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;margin-top:28px}
.card{padding:22px;border:1px solid var(--line);border-radius:18px;background:linear-gradient(145deg,#171626,#10101a);min-width:0}
.card h2{margin:0 0 6px;font-size:21px}.pill{display:inline-block;color:#d9c9ff;background:#2b2045;border:1px solid #624b91;padding:5px 10px;border-radius:99px;font-size:12px}
.empty{margin-top:26px;padding:28px;border:1px dashed var(--line);border-radius:16px;color:var(--muted)}
form{margin-top:16px}button{cursor:pointer;font:inherit}
@media(max-width:600px){main{margin:28px auto}header{padding:16px 20px}.card{padding:18px}}
</style>
@include('partials.design-system')
</head>
<body>
<header><a class="logo" href="{{ route('dashboard') }}">one<span>QR</span></a><a class="button" href="{{ route('dashboard') }}">კაბინეტში დაბრუნება</a></header>
<main>
<h1>ჩემი რჩეულები</h1>
<p class="muted">შენახული ბიზნესები ერთ სივრცეში — ნებისმიერ დროს დაუბრუნდი მათ Smart Page-ს.</p>
@if(session('favorite_status'))<p class="muted" role="status">{{ session('favorite_status') }}</p>@endif
@if($businesses->isEmpty())
<div class="empty">რჩეულებში ჯერ არაფერი გაქვს შენახული. გახსენი სასურველი ბიზნესის Smart Page და დააჭირე „რჩეულებში დამატებას“.</div>
@else
<div class="grid">
@foreach($businesses as $business)
<article class="card">
<span class="pill">{{ $business->business_type }}</span>
<h2>{{ $business->name }}</h2>
@if($business->tagline)<p class="muted">{{ $business->tagline }}</p>@endif
<p class="muted">{{ trim(($business->city ?? '').' '.($business->address ?? '')) ?: 'მისამართი მითითებული არ არის' }}</p>
<a class="button" href="{{ route('business.public',$business->slug) }}">Smart Page-ის ნახვა →</a>
<form method="POST" action="{{ route('business.public.favorite.destroy',$business->slug) }}">
@csrf @method('DELETE')
<button class="button" type="submit">რჩეულებიდან ამოშლა</button>
</form>
</article>
@endforeach
</div>
<div style="margin-top:24px">{{ $businesses->links() }}</div>
@endif
</main>
</body>
</html>
