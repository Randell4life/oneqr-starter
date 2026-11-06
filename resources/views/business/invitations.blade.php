<!doctype html>
<html lang="ka"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>ბიზნესის მოწვევები — oneQR</title>
<style>
:root{color-scheme:dark;--bg:#090812;--panel:#151326;--line:#302a46;--text:#f6f3ff;--muted:#aaa3bd;--purple:#9b72ff}
*{box-sizing:border-box}body{margin:0;min-height:100vh;background:radial-gradient(ellipse at 75% -10%,#55368b44,transparent 44%),var(--bg);color:var(--text);font:15px Inter,system-ui,sans-serif}
main{max-width:850px;margin:48px auto;padding:0 22px}a{color:#cbb8ff;text-decoration:none}.eyebrow{font-size:11px;letter-spacing:2px;color:#b8a2f4;font-weight:800}h1{font-size:clamp(28px,5vw,38px);letter-spacing:-1px;margin:9px 0}.muted{color:var(--muted)}.notice{padding:13px 15px;border:1px solid #514176;border-radius:12px;background:#17122a;color:#e2d8ff;margin:17px 0}
.card{background:linear-gradient(145deg,#191629,#11101b);border:1px solid var(--line);border-radius:17px;padding:21px;margin:14px 0}.card-top{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}.card h2{font-size:19px;margin:0}.details{color:var(--muted);font-size:13px;line-height:1.7;margin:12px 0 16px}.tag{display:inline-flex;padding:5px 9px;border-radius:99px;background:#38285e;color:#e0d4ff;font-size:11px;font-weight:750}.btn{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:10px 15px;border-radius:10px;border:1px solid #7252bd;background:linear-gradient(110deg,#5535a1,#7854c9);color:white;font:inherit;font-weight:750;cursor:pointer}.empty{padding:28px;border:1px dashed #403955;border-radius:14px;color:var(--muted);text-align:center;margin-top:20px}
@media(max-width:560px){main{margin:28px auto;padding:0 15px}.card{padding:17px}.card-top{flex-direction:column}}
</style>@include('partials.design-system')
</head><body><main>
<a href="{{ route('dashboard') }}">← კაბინეტში დაბრუნება</a>
<div class="eyebrow" style="margin-top:28px">TEAM ACCESS</div><h1>ბიზნესის მოწვევები</h1><p class="muted">აქ შეგიძლია მიიღო სხვა ბიზნესების მოწვევები და შეუერთდე მათ გუნდს.</p>
@if(session('status'))<div class="notice">{{ session('status') }}</div>@endif
@forelse($invitations as $invite)
<section class="card"><div class="card-top"><h2>{{ $invite->business->name }}</h2><span class="tag">მოლოდინში</span></div>
<div class="details">{{ $invite->role === 'manager' ? 'მენეჯერის' : 'თანამშრომლის' }} როლი · {{ $invite->branch?->name ?? 'ყველა ფილიალი' }}<br>მომწვევი: {{ $invite->inviter?->name ?? 'ბიზნესის მფლობელი' }}<br>მოწვევა მიღებულია: {{ $invite->created_at->format('d.m.Y H:i') }}</div>
<form method="POST" action="{{ route('team.invitations.accept',$invite->id) }}">@csrf<button class="btn" type="submit">მოწვევის მიღება →</button></form></section>
@empty<div class="empty">ახალი მოწვევები არ გაქვს.<br><span style="font-size:13px">როცა ბიზნესის მფლობელი დაგამატებს გუნდში, მოწვევა აქ გამოჩნდება.</span></div>@endforelse
</main></body></html>
