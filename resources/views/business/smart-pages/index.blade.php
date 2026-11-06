<!doctype html>
<html lang="ka"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Smart Pages — oneQR</title>@include('partials.design-system')<style>
body{background:radial-gradient(ellipse at 82% -18%,#54368b55,transparent 42%),#080711!important}
header.nav{position:sticky;top:0;z-index:20}
.page-heading{padding:26px 28px;border:1px solid #302a49;border-radius:22px;background:linear-gradient(115deg,#171329,#100f1b 68%,#1d1530);box-shadow:0 18px 50px #0004}
.page-heading h1{font-size:clamp(32px,5vw,48px);margin:0 0 9px;letter-spacing:-.055em}
.page-heading .muted{max-width:690px}
.page-heading>.btn{white-space:nowrap}
.surface{box-shadow:0 14px 38px #0003!important}
.surface h2{font-size:19px;margin-top:0}
.surface form{gap:14px!important}
.surface input:not([type=checkbox]),.surface select{min-height:46px}
.cards{align-items:stretch}
.cards>.surface{position:relative;display:flex;flex-direction:column;min-height:210px;transition:transform .2s,border-color .2s}
.cards>.surface:before{content:"";height:3px;position:absolute;inset:0 18px auto;background:linear-gradient(90deg,#7547e8,#b69cff,transparent)}
.cards>.surface:hover{transform:translateY(-3px);border-color:#7053a4!important}
.cards>.surface h2{font-size:22px;margin:18px 0 5px}
.cards>.surface .btn{align-self:flex-start;margin-top:auto}
@media(max-width:600px){.page-heading{padding:20px 17px}.page-heading>.btn{width:100%;text-align:center}.cards{grid-template-columns:1fr!important}}
</style></head>
<body><header class="nav"><a class="logo" href="{{ route('dashboard') }}">one<span>QR</span></a><a href="{{ route('business.profile.edit',$business) }}">← ბიზნესის პროფილი</a></header>
<main><div class="page-heading"><div><p class="eyebrow">SMART PAGE STUDIO</p><h1>ჭკვიანი გვერდები</h1><p class="muted">შექმენი გვერდის ვერსიები და განსაზღვრე, როდის გამოჩნდეს თითოეული QR კოდის დასკანერებისას.</p></div><a class="btn" href="{{ route('business.public',$business->slug) }}" target="_blank">საჯარო გვერდი ↗</a></div>
@if(session('status'))<div class="notice">{{ session('status') }}</div>@endif
<section class="surface"><h2>ახალი გვერდის შექმნა</h2><form method="POST" action="{{ route('business.smart-pages.store',$business) }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:end">@csrf<div style="flex:1;min-width:220px"><label>გვერდის სახელი</label><input name="name" required maxlength="120" placeholder="მაგ. დილის მენიუ" style="width:100%;margin-top:7px"></div><div><label>ტიპი</label><select name="kind" style="display:block;margin-top:7px"><option value="variant">დროითი ვერსია</option><option value="default">ძირითადი გვერდი</option></select></div><button class="btn">შექმნა +</button></form></section>
<div class="cards" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:15px">
@forelse($pages as $page)<article class="surface"><div class="section-heading"><span class="status-pill {{ $page->is_published?'success-pill':'warning-pill' }}">{{ $page->is_published?'გამოქვეყნებულია':'დამალულია' }}</span>@if($page->is_default)<span class="status-pill">ძირითადი</span>@endif</div><h2>{{ $page->name }}</h2><p class="muted">{{ $page->blocks_count }} ბლოკი · {{ $page->schedules->count() }} განრიგი</p><a class="btn" href="{{ route('business.smart-pages.edit',[$business,$page]) }}">რედაქტირება →</a>@if(!$page->is_default)<form method="POST" action="{{ route('business.smart-pages.destroy',[$business,$page]) }}" style="display:inline" onsubmit="return confirm('წავშალოთ გვერდი?')">@csrf @method('DELETE')<button class="btn btn-small" style="margin-left:8px">წაშლა</button></form>@endif</article>@empty<div class="empty-state">ჯერ გვერდები არ გაქვს. შექმენი ძირითადი გვერდი.</div>@endforelse
</div></main></body></html>