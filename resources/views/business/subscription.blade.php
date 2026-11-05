<!doctype html>
<html lang="ka">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>გამოწერა — {{ $business->name }} | oneQR</title>
<style>
:root{color-scheme:dark;--bg:#090812;--panel:#121020;--line:#29243d;--text:#f6f3ff;--muted:#aaa3bd;--purple:#9b72ff;--green:#5ee0b0}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font:15px Inter,system-ui,sans-serif}
header{height:72px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;padding:0 max(22px,calc((100vw - 1120px)/2))}
.logo{font-size:24px;font-weight:900}.logo span{color:var(--purple)}a{color:#c8b5ff;text-decoration:none}
main{max-width:1120px;margin:42px auto;padding:0 22px}h1{font-size:32px;margin:0 0 8px}.muted{color:var(--muted)}
.panel,.plan{background:var(--panel);border:1px solid var(--line);border-radius:18px;padding:22px}
.usage{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin:24px 0}
.value{font-size:27px;font-weight:800;margin-top:8px}.plans{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:16px;margin-top:18px}
.plan.current{border-color:var(--purple);box-shadow:0 0 0 1px #9b72ff33}.price{font-size:26px;font-weight:800;margin:18px 0}.tag{display:inline-block;padding:5px 10px;border-radius:99px;background:#38285e;color:#e0d4ff;font-size:12px}
li{margin:9px 0;color:#d8d2e6}.btn{display:inline-block;margin-top:14px;padding:10px 14px;border:1px solid #684bb0;border-radius:9px;background:#39265f;color:white}
.notice{padding:14px;border:1px solid #514176;border-radius:12px;background:#17122a;color:#d9ccff;margin:18px 0}
</style>@include('partials.design-system')
</head>
<body>
<header><div class="logo">one<span>QR</span></div><a href="{{ route('dashboard') }}">← კაბინეტში დაბრუნება</a></header>
<main>
@if(session('success'))<div class="notice">{{ session('success') }}</div>@endif
@if(session('error'))<div class="notice">{{ session('error') }}</div>@endif
@if($errors->has('payment'))<div class="notice">{{ $errors->first('payment') }}</div>@endif
<h1>გამოწერა და გეგმები</h1><p class="muted">{{ $business->name }} — მართე ლიმიტები და შეარჩიე ბიზნესის მასშტაბზე მორგებული პაკეტი.</p>
<section class="panel">
<div class="tag">მიმდინარე გეგმა</div><h2>{{ $currentPlan->name }}</h2>
<p class="muted">სტატუსი: {{ $subscription?->status === 'active' ? 'აქტიური' : ($subscription?->status ?? 'უფასო გეგმა') }}</p>
<div class="usage">
@foreach(['qr_codes'=>'QR კოდები','branches'=>'ფილიალები','team_members'=>'აქტიური თანამშრომლები'] as $key=>$label)
<div><div class="muted">{{ $label }}</div><div class="value">{{ $usage[$key] }} / {{ $currentPlan->limits[$key] ?? '∞' }}</div></div>
@endforeach
</div>
</section>
<div class="notice">გადახდის პროვაიდერი ჯერ არ არის დაკავშირებული. ფასიანი პაკეტის არჩევა ამ ეტაპზე გამოწერას არ ააქტიურებს; გადახდის ინტეგრაციის შემდეგ დაემატება უსაფრთხო გადახდა და ავტომატური განახლება.</div>
<h2 style="margin-top:34px">აირჩიე გეგმა</h2>
<div class="plans">
@foreach($plans as $plan)
<article class="plan {{ $currentPlan->id === $plan->id ? 'current' : '' }}">
@if($currentPlan->id === $plan->id)<span class="tag">მიმდინარე გეგმა</span>@endif
<h2>{{ $plan->name }}</h2><p class="muted">{{ $plan->description }}</p>
<div class="price">{{ $plan->monthly_price === null ? 'ფასი შეთანხმებით' : ($plan->monthly_price == 0 ? 'უფასო' : number_format((float)$plan->monthly_price, 2).' '.$plan->currency.'/თვე') }}</div>
<ul>
<li>QR კოდები: {{ $plan->limits['qr_codes'] ?? 'შეუზღუდავი' }}</li>
<li>ფილიალები: {{ $plan->limits['branches'] ?? 'შეუზღუდავი' }}</li>
<li>თანამშრომლები: {{ $plan->limits['team_members'] ?? 'შეუზღუდავი' }}</li>
@foreach($plan->features ?? [] as $feature)<li>{{ $feature }}</li>@endforeach
</ul>
@if($currentPlan->id === $plan->id)
<span class="tag">აქტიურია</span>
@elseif($plan->slug === 'free')
<a class="btn" href="{{ route('dashboard') }}">კაბინეტში დაბრუნება</a>
@else
@if(config('tbc.enabled') && $plan->monthly_price !== null && (float)$plan->monthly_price > 0)
<form method="POST" action="{{ route('business.subscription.checkout', [$business, $plan]) }}">
@csrf
<button class="btn" type="submit">TBC-ით გადახდა</button>
</form>
@elseif($plan->monthly_price === null)
<span class="muted">ფასი ჯერ არ არის განსაზღვრული</span>
@else
<span class="muted">TBC გადახდის მონაცემები ჯერ არ არის კონფიგურირებული</span>
@endif
@endif
</article>
@endforeach
</div>
<section class="panel" style="margin-top:28px">
<h2>გადახდების ისტორია</h2>
@if($payments->isEmpty())
<p class="muted">გადახდები ჯერ არ არის.</p>
@else
<div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse;text-align:left">
<thead><tr><th>თარიღი</th><th>გეგმა</th><th>თანხა</th><th>სტატუსი</th><th>პროვაიდერი</th></tr></thead>
<tbody>
@foreach($payments as $payment)
<tr><td>{{ $payment->created_at->format('d.m.Y H:i') }}</td><td>{{ $payment->plan->name }}</td><td>{{ number_format((float)$payment->amount,2) }} {{ $payment->currency }}</td><td>{{ $payment->status }}</td><td>{{ strtoupper($payment->provider) }}</td></tr>
@endforeach
</tbody></table></div>
@endif
</section>
</main></body></html>
