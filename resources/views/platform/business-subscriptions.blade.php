<!doctype html>
<html lang="ka">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>ბიზნესების გამოწერები — oneQR</title>
<style>
:root{color-scheme:dark;--bg:#090812;--panel:#121020;--line:#29243d;--text:#f6f3ff;--muted:#aaa3bd;--purple:#9b72ff;--green:#5ee0b0}
*{box-sizing:border-box}body{margin:0;background:radial-gradient(ellipse at 85% -20%,#3b286055,transparent 42%),var(--bg);color:var(--text);font:14px Inter,system-ui,sans-serif}
header{min-height:72px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;padding:12px max(20px,calc((100vw - 1200px)/2));gap:16px;flex-wrap:wrap}
main{max-width:1200px;margin:34px auto;padding:0 20px}.logo{font-size:23px;font-weight:900}.logo span{color:var(--purple)}a{color:#c8b5ff;text-decoration:none}
nav{display:flex;gap:16px;flex-wrap:wrap}h1{font-size:clamp(27px,4vw,36px);margin:0 0 7px;letter-spacing:-.04em}.muted{color:var(--muted)}.intro{margin:0 0 22px}
.notice{padding:13px 16px;border:1px solid #514176;border-radius:12px;background:#17122a;margin:0 0 18px}.notice.errors{border-color:#713d4d;background:#26131d}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin:20px 0}.stat,.business{background:var(--panel);border:1px solid var(--line);border-radius:15px}.stat{padding:17px}.metric{font-size:27px;font-weight:850;margin-top:6px}.label{font-size:12px;color:var(--muted)}
.search{display:flex;gap:10px;flex-wrap:wrap;background:var(--panel);padding:14px;border:1px solid var(--line);border-radius:14px;margin:18px 0}
input,select,textarea{width:100%;background:#0b0a15;color:var(--text);border:1px solid #38314e;border-radius:9px;padding:10px;font:inherit;min-width:0}
.search input{flex:1;min-width:230px}.search button,button{border:0;border-radius:9px;background:linear-gradient(135deg,#8055e8,#a17aff);color:white;font-weight:750;padding:10px 15px;cursor:pointer}
.list{display:grid;gap:13px}.business{padding:18px;display:grid;grid-template-columns:minmax(210px,1fr) minmax(300px,1.2fr);gap:22px}
.business h2{font-size:19px;margin:0 0 5px}.pill{display:inline-block;padding:5px 9px;border-radius:99px;background:#302548;color:#dfd0ff;font-size:11px;margin-bottom:9px}.pill.active{background:#12382f;color:#83edc7}
.details{display:grid;gap:5px}.details p{margin:0;color:var(--muted);font-size:12px}.assign{border-left:1px solid var(--line);padding-left:22px}.assign-title{font-weight:750;margin:0 0 9px}
.fields{display:grid;grid-template-columns:1fr 1fr;gap:10px}.fields .wide{grid-column:1/-1}label{display:block;color:var(--muted);font-size:11px;margin:0 0 5px}
.assign button{margin-top:10px}.hint{font-size:11px;color:var(--muted);margin:7px 0 0}.pagination{padding:18px}.pagination nav{justify-content:space-between}
@media(max-width:760px){.business{grid-template-columns:1fr}.assign{border-left:0;border-top:1px solid var(--line);padding:16px 0 0}.fields{grid-template-columns:1fr}.fields .wide{grid-column:auto}}
@media(max-width:480px){header{padding:12px 16px}main{margin:24px auto}.search input{min-width:100%}.search button{width:100%}}
</style>
</head>
<body>
<header><div class="logo">one<span>QR</span> <span class="label">/ PLATFORM</span></div><nav><a href="{{ route('platform.subscription-plans.index') }}">გეგმები</a><a href="{{ route('platform.subscription-finance.index') }}">ფინანსები</a><a href="{{ route('dashboard') }}">კაბინეტი ←</a></nav></header>
<main>
@if(session('status'))<div class="notice">{{ session('status') }}</div>@endif
@if($errors->any())<div class="notice errors">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<h1>ბიზნესების გამოწერები</h1><p class="muted intro">მოძებნე ბიზნესი, შეამოწმე მიმდინარე გეგმა და საჭიროების შემთხვევაში მიანიჭე ახალი პაკეტი. ადმინისტრატორის ყველა ცვლილება ინახება აუდიტის ჩანაწერად.</p>
<div class="stats"><div class="stat"><div class="label">ბიზნესები</div><div class="metric">{{ number_format($summary['businesses']) }}</div></div><div class="stat"><div class="label">მოქმედი ფასიანი გამოწერები</div><div class="metric">{{ number_format($summary['paid']) }}</div></div></div>
<form class="search" method="GET" action="{{ route('platform.business-subscriptions.index') }}"><input name="q" value="{{ $search }}" placeholder="ბიზნესის სახელი, slug, მფლობელი ან ელფოსტა"><button type="submit">ძიება</button>@if($search !== '')<a style="align-self:center;padding:8px" href="{{ route('platform.business-subscriptions.index') }}">გასუფთავება</a>@endif</form>
<div class="list">
@forelse($businesses as $business)
@php($current = $business->subscriptions->first())
<section class="business">
<div class="details"><div><span class="pill {{ $current ? 'active' : '' }}">{{ $current ? ($current->status === 'trialing' ? 'საცდელი პერიოდი' : 'მოქმედი გეგმა') : 'Free — ნაგულისხმევი' }}</span></div>
<h2>{{ $business->name }}</h2><p>Slug: {{ $business->slug }}</p><p>მფლობელი: {{ $business->owner?->name ?? '—' }} · {{ $business->owner?->email ?? '—' }}</p>
<p>მიმდინარე გეგმა: <strong style="color:#eee">{{ $current?->plan?->name ?? 'Free' }}</strong></p><p>ვადა: {{ $current?->ends_at?->format('d.m.Y H:i') ?? 'უვადო / არ არის მითითებული' }}</p><p>ისტორიული ჩანაწერები: {{ $business->subscription_history_count }}</p></div>
<div class="assign"><p class="assign-title">გეგმის ხელით მინიჭება</p>
<form method="POST" action="{{ route('platform.business-subscriptions.store', $business) }}">@csrf
<div class="fields"><div><label>ახალი გეგმა</label><select name="plan_id" required>@foreach($plans as $plan)<option value="{{ $plan->id }}" @selected(old('plan_id') == $plan->id)>{{ $plan->name }}{{ $plan->slug === 'free' ? ' — უვადო' : '' }}</option>@endforeach</select></div>
<div><label>მოქმედების ვადა (თვე)</label><input type="number" name="duration_months" min="1" max="36" value="{{ old('duration_months', 1) }}" placeholder="1–36"></div>
<div class="wide"><label>მიზეზი / ადმინისტრატორის შენიშვნა</label><textarea name="reason" rows="2" minlength="5" maxlength="500" required placeholder="მაგ.: საცდელი წვდომა პარტნიორისთვის ან კომპენსაცია">{{ old('reason') }}</textarea></div></div>
<p class="hint">Free გეგმა უვადოა. ფასიანი გეგმისთვის ვადა სავალდებულოა. ხელით მინიჭება გადახდას არ ქმნის და ავტომატურ ჩამოჭრას არ ააქტიურებს.</p>
<button type="submit" onclick="return confirm('დარწმუნებული ხარ? მიმდინარე მოქმედი გეგმა დასრულდება და არჩეული გეგმა ჩაირთვება.');">გეგმის მინიჭება</button></form></div>
</section>
@empty<div class="notice">ბიზნესი ვერ მოიძებნა.</div>@endforelse
</div><div class="pagination">{{ $businesses->links() }}</div>
</main></body></html>
