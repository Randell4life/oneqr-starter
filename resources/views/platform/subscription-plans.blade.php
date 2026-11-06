<!doctype html>
<html lang="ka">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>გამოწერის გეგმები — oneQR</title>
<style>
:root{color-scheme:dark;--bg:#090812;--panel:#121020;--line:#29243d;--text:#f6f3ff;--muted:#aaa3bd;--purple:#9b72ff;--green:#5ee0b0;--danger:#ff8295}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font:14px Inter,system-ui,sans-serif}
header{height:70px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;padding:0 max(20px,calc((100vw - 1180px)/2))}
main{max-width:1180px;margin:36px auto;padding:0 20px}.logo{font-size:23px;font-weight:900}.logo span{color:var(--purple)}
a{color:#c8b5ff;text-decoration:none}h1{font-size:30px;margin:0 0 8px}.muted{color:var(--muted)}.intro{margin:0 0 24px}
.notice{padding:13px 16px;border:1px solid #514176;border-radius:12px;background:#17122a;margin:0 0 20px}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px}
.card{background:var(--panel);border:1px solid var(--line);border-radius:18px;padding:20px}
.card h2{margin:0;font-size:21px}.meta{display:flex;justify-content:space-between;align-items:center;gap:12px;margin:12px 0 18px}
.pill{font-size:11px;padding:5px 9px;border-radius:99px;background:#38285e;color:#e0d4ff}
label{display:block;color:var(--muted);font-size:12px;margin:13px 0 6px}
input,textarea,select{width:100%;background:#0b0a15;color:var(--text);border:1px solid #38314e;border-radius:9px;padding:10px;font:inherit}
textarea{min-height:90px;resize:vertical}.limits{display:grid;grid-template-columns:1fr 1fr;gap:0 12px}
.row{display:grid;grid-template-columns:1fr 1fr;gap:12px}.check{display:flex;align-items:center;gap:9px;margin-top:15px}.check input{width:auto}
button{border:0;border-radius:10px;background:linear-gradient(135deg,#8055e8,#a17aff);color:white;font-weight:750;padding:11px 16px;margin-top:17px;cursor:pointer}
small{color:var(--muted)}@media(max-width:480px){.grid{grid-template-columns:1fr}.row,.limits{grid-template-columns:1fr}header{padding:0 16px}}
</style>
</head>
<body>
<header><div class="logo">one<span>QR</span> <small> / PLATFORM</small></div><a href="{{ route('dashboard') }}">კაბინეტში დაბრუნება ←</a></header>
<main>
@if(session('status'))<div class="notice">{{ session('status') }}</div>@endif
@if($errors->any())<div class="notice">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<h1>გამოწერის გეგმების მართვა</h1>
<p class="muted intro">ფასები და ლიმიტები მოქმედებს ახალი გადახდებისას. უკვე აქტიური გამოწერის ვადა/გეგმა ავტომატურად არ იცვლება.</p>
<div class="grid">
@foreach($plans as $plan)
<section class="card">
<div class="meta"><h2>{{ $plan->name }}</h2><span class="pill">{{ $plan->active_subscriptions_count }} აქტიური გამოწერა</span></div>
<form method="POST" action="{{ route('platform.subscription-plans.update', $plan) }}">
@csrf @method('PUT')
<label>გეგმის სახელი</label><input name="name" value="{{ old('name', $plan->name) }}" required maxlength="80">
<label>აღწერა</label><textarea name="description" maxlength="1000">{{ old('description', $plan->description) }}</textarea>
<div class="row">
<div><label>თვიური ფასი (GEL)</label><input type="number" name="monthly_price" min="0" max="99999999.99" step="0.01" value="{{ old('monthly_price', $plan->monthly_price) }}" placeholder="ფასი შეთანხმებით"></div>
<div><label>ვალუტა</label><input value="GEL" disabled><input type="hidden" name="currency" value="GEL"></div>
</div>
<label>ლიმიტები (ცარიელი = შეუზღუდავი)</label>
<div class="limits">
@foreach(['qr_codes'=>'QR კოდები','branches'=>'ფილიალები','team_members'=>'თანამშრომლები','catalog_items'=>'კატალოგი'] as $key=>$label)
<div><label>{{ $label }}</label><input type="number" min="0" max="1000000" name="limits[{{ $key }}]" value="{{ old('limits.'.$key, $plan->limits[$key] ?? '') }}" placeholder="∞"></div>
@endforeach
</div>
<label>ფუნქციები — თითო ხაზზე ერთი</label>
<textarea name="features_text">{{ old('features_text', implode("\n", $plan->features ?? [])) }}</textarea>
<div class="row">
<div><label>სორტირების რიგი</label><input type="number" min="0" max="65535" name="sort_order" value="{{ old('sort_order', $plan->sort_order) }}" required></div>
<div class="check"><input type="checkbox" name="is_active" value="1" {{ $plan->is_active || $plan->slug === 'free' ? 'checked' : '' }} {{ $plan->slug === 'free' ? 'disabled' : '' }}><span>აქტიური გეგმა</span>@if($plan->slug === 'free')<input type="hidden" name="is_active" value="1">@endif</div>
</div>
<button type="submit">ცვლილებების შენახვა</button>
</form>
</section>
@endforeach
</div>
</main>
</body></html>
