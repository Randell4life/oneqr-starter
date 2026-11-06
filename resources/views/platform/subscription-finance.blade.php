<!doctype html>
<html lang="ka">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>გამოწერების ფინანსები — oneQR</title>
<style>
:root{color-scheme:dark;--bg:#090812;--panel:#121020;--line:#29243d;--text:#f6f3ff;--muted:#aaa3bd;--purple:#9b72ff;--green:#5ee0b0;--red:#ff8295}
*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font:14px Inter,system-ui,sans-serif}
header{min-height:70px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;padding:12px max(20px,calc((100vw - 1180px)/2));gap:12px}
main{max-width:1180px;margin:36px auto;padding:0 20px}.logo{font-size:23px;font-weight:900}.logo span{color:var(--purple)}a{color:#c8b5ff;text-decoration:none}
h1{font-size:30px;margin:0 0 8px}.muted{color:var(--muted)}.intro{margin:0 0 24px}
.actions{display:flex;gap:14px;flex-wrap:wrap}.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;margin:22px 0}
.card{background:var(--panel);border:1px solid var(--line);border-radius:15px;padding:17px}.metric{font-size:25px;font-weight:850;margin-top:7px}.label{font-size:12px;color:var(--muted)}
.filters{display:flex;align-items:end;gap:12px;flex-wrap:wrap;background:var(--panel);padding:16px;border:1px solid var(--line);border-radius:15px;margin:20px 0}
label{display:block;color:var(--muted);font-size:12px;margin-bottom:6px}input,select{background:#0b0a15;color:var(--text);border:1px solid #38314e;border-radius:9px;padding:10px;font:inherit;min-width:150px}
button,.export{border:0;border-radius:10px;background:linear-gradient(135deg,#8055e8,#a17aff);color:white;font-weight:750;padding:11px 16px;cursor:pointer}
.table-wrap{overflow-x:auto;background:var(--panel);border:1px solid var(--line);border-radius:15px}
table{width:100%;border-collapse:collapse;min-width:850px}th,td{text-align:left;padding:13px 14px;border-bottom:1px solid var(--line);white-space:nowrap}th{font-size:11px;color:var(--muted);font-weight:650}
.status{display:inline-block;border-radius:99px;padding:5px 9px;background:#302548;color:#dfd0ff;font-size:11px}
.status.paid{background:#12382f;color:#83edc7}.status.failed,.status.review{background:#40212c;color:#ff9dae}
.pagination{padding:15px}.pagination nav{display:flex;justify-content:space-between;gap:10px}.pagination a,.pagination span{padding:6px}
@media(max-width:600px){main{margin:24px auto}h1{font-size:25px}.filters>*{width:100%}input,select{width:100%}header{padding:12px 16px}}
</style>
</head>
<body>
<header><div class="logo">one<span>QR</span> <span class="label">/ PLATFORM</span></div><nav class="actions"><a href="{{ route('platform.subscription-plans.index') }}">გეგმები</a><a href="{{ route('dashboard') }}">კაბინეტი ←</a></nav></header>
<main>
<h1>გამოწერების ფინანსები</h1><p class="muted intro">გადახდების ისტორია და შემოსავლის მიმოხილვა. თანხები დათვლილია GEL-ში, გადახდის ჩანაწერების მიხედვით.</p>
<div class="cards">
<div class="card"><div class="label">წარმატებული გადახდები</div><div class="metric">{{ number_format((int) ($summary['paid']->payments_count ?? 0)) }}</div><div class="label">{{ number_format((float) ($summary['paid']->amount_total ?? 0), 2) }} GEL</div></div>
<div class="card"><div class="label">მოლოდინში / ინიციალიზებული</div><div class="metric">{{ number_format((int) (($summary['pending']->payments_count ?? 0) + ($summary['initiated']->payments_count ?? 0))) }}</div><div class="label">{{ number_format((float) (($summary['pending']->amount_total ?? 0) + ($summary['initiated']->amount_total ?? 0)), 2) }} GEL</div></div>
<div class="card"><div class="label">წარუმატებელი / ვადაგასული</div><div class="metric">{{ number_format((int) (($summary['failed']->payments_count ?? 0) + ($summary['expired']->payments_count ?? 0))) }}</div><div class="label">{{ number_format((float) (($summary['failed']->amount_total ?? 0) + ($summary['expired']->amount_total ?? 0)), 2) }} GEL</div></div>
<div class="card"><div class="label">ხელით შესამოწმებელი</div><div class="metric">{{ number_format((int) ($summary['review']->payments_count ?? 0)) }}</div><div class="label">{{ number_format((float) ($summary['review']->amount_total ?? 0), 2) }} GEL</div></div>
</div>
<form class="filters" method="GET" action="{{ route('platform.subscription-finance.index') }}">
<div><label>საწყისი თარიღი</label><input type="date" name="from" value="{{ $from }}"></div>
<div><label>საბოლოო თარიღი</label><input type="date" name="to" value="{{ $to }}"></div>
<div><label>სტატუსი</label><select name="status"><option value="">ყველა სტატუსი</option>@foreach(['pending'=>'მოლოდინში','initiated'=>'ინიციალიზებულია','paid'=>'გადახდილია','failed'=>'შეცდომა','expired'=>'ვადაგასულია','review'=>'შემოწმება'] as $key=>$label)<option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>@endforeach</select></div>
<button type="submit">ფილტრაცია</button><a class="export" href="{{ route('platform.subscription-finance.export', request()->query()) }}">CSV ექსპორტი ↓</a>
</form>
<div class="table-wrap"><table><thead><tr><th>თარიღი</th><th>ბიზნესი</th><th>გეგმა</th><th>თანხა</th><th>პროვაიდერი</th><th>სტატუსი</th><th>Merchant ID</th></tr></thead><tbody>
@forelse($payments as $payment)
<tr><td>{{ $payment->created_at?->format('d.m.Y H:i') }}</td><td>{{ $payment->business?->name ?? '—' }}</td><td>{{ $payment->plan?->name ?? '—' }}</td><td>{{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}</td><td>{{ strtoupper($payment->provider) }}</td><td><span class="status {{ $payment->status }}">{{ ['pending'=>'მოლოდინში','initiated'=>'ინიციალიზებულია','paid'=>'გადახდილია','failed'=>'შეცდომა','expired'=>'ვადაგასულია','review'=>'შემოწმება'][$payment->status] ?? $payment->status }}</span></td><td>{{ $payment->merchant_payment_id }}</td></tr>
@empty<tr><td colspan="7" class="muted">არჩეულ პერიოდში გადახდები არ მოიძებნა.</td></tr>@endforelse
</tbody></table></div><div class="pagination">{{ $payments->links() }}</div>
</main>
</body></html>
