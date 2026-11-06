<!doctype html>
<html lang="ka">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>გუნდი და ფილიალები — {{ $business->name }} | oneQR</title>
<style>
:root{color-scheme:dark;--bg:#090812;--panel:#121020;--panel2:#19162a;--line:#302a46;--text:#f6f3ff;--muted:#aaa3bd;--purple:#9b72ff;--green:#5ee0b0;--red:#ff829f}
*{box-sizing:border-box}body{margin:0;background:radial-gradient(ellipse at 80% -15%,#55368b44,transparent 42%),var(--bg);color:var(--text);font:15px Inter,system-ui,sans-serif}
a{color:#cbb8ff;text-decoration:none}.nav{height:72px;border-bottom:1px solid var(--line);padding:0 max(20px,calc((100vw - 1160px)/2));display:flex;align-items:center;justify-content:space-between;background:#0b0914cc}
.logo{font-size:23px;font-weight:950;letter-spacing:-1px}.logo span{color:var(--purple)}main{max-width:1160px;margin:38px auto;padding:0 22px}
.eyebrow{font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#b8a2f4;font-weight:800}h1{font-size:clamp(27px,4vw,38px);letter-spacing:-1.3px;margin:8px 0}.muted{color:var(--muted)}.intro{max-width:650px;line-height:1.65}
.stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:13px;margin:25px 0}.stat,.panel{background:linear-gradient(145deg,#191629,#11101b);border:1px solid var(--line);border-radius:17px}.stat{padding:17px 19px}.stat strong{display:block;font-size:27px;margin-top:6px}.stat small{color:var(--muted)}
.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:17px;margin:18px 0}.panel{padding:22px;min-width:0}.panel h2{font-size:19px;margin:0 0 7px}.panel-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:17px}.field{display:block;width:100%;min-height:44px;padding:11px 12px;margin:7px 0 14px;background:#0b0a14;border:1px solid #37314d;border-radius:10px;color:white;font:inherit}.field:focus{outline:2px solid #9b72ff88;border-color:var(--purple)}label{color:#c4bdd3;font-size:12px;font-weight:650}
.btn{display:inline-flex;justify-content:center;align-items:center;gap:7px;min-height:41px;padding:9px 14px;border-radius:10px;border:1px solid #7252bd;background:linear-gradient(110deg,#5535a1,#7854c9);color:white;font:inherit;font-weight:750;cursor:pointer}.btn:hover{filter:brightness(1.13)}.btn-danger{background:#321a2d;border-color:#70405d;color:#ffb3c5}.btn-quiet{background:#1c192a;border-color:#403955}.tag{display:inline-flex;align-items:center;padding:5px 9px;border-radius:99px;background:#38285e;color:#e0d4ff;font-size:11px;font-weight:750;white-space:nowrap}.tag-green{background:#153b32;color:#8ce7c6}.tag-wait{background:#3a3020;color:#f3d69a}
.notice{padding:13px 15px;border:1px solid #514176;border-radius:12px;background:#17122a;color:#e2d8ff;margin:15px 0}.errors{border-color:#714057;background:#281523;color:#ffc0d0}.item{display:flex;justify-content:space-between;align-items:center;gap:14px;padding:15px 0;border-top:1px solid #2c273d}.item:first-of-type{border-top:0}.item-main{min-width:0}.item-main strong{display:block;overflow-wrap:anywhere}.item-sub{font-size:12px;color:var(--muted);margin-top:5px}.item-actions{display:flex;align-items:center;gap:9px;flex-wrap:wrap;justify-content:flex-end}.empty{padding:23px;border:1px dashed #403955;border-radius:12px;color:var(--muted);text-align:center}
@media(max-width:760px){main{margin:25px auto;padding:0 15px}.grid{grid-template-columns:1fr}.stats{gap:8px}.stat{padding:13px}.stat strong{font-size:22px}.panel{padding:17px}.nav{padding:0 15px}}
@media(max-width:520px){.stats{grid-template-columns:1fr}.item{align-items:flex-start;flex-direction:column}.item-actions{width:100%;justify-content:flex-start}.item-actions form{margin-left:auto}.panel-head{align-items:flex-start;flex-direction:column}}
</style>
@include('partials.design-system')
</head>
<body>
<header class="nav"><div class="logo">one<span>QR</span></div><a href="{{ route('dashboard') }}">← კაბინეტში დაბრუნება</a></header>
<main>
<div class="eyebrow">BUSINESS CONTROL CENTER</div>
<h1>{{ $business->name }} — გუნდი და ფილიალები</h1>
<p class="muted intro">მართე ბიზნესის ლოკაციები, გადაანაწილე თანამშრომლები და აკონტროლე მოწვევები ერთი სივრციდან.</p>
@if(session('status'))<div class="notice">{{ session('status') }}</div>@endif
@if($errors->any())<div class="notice errors"><strong>ფორმაში აღმოჩენილია შეცდომა:</strong><ul style="margin:8px 0 0;padding-left:20px">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="stats">
<div class="stat"><small>ფილიალები</small><strong>{{ $branches->count() }}</strong></div>
<div class="stat"><small>გუნდის წევრები</small><strong>{{ $memberships->where('status','active')->count() }}</strong></div>
<div class="stat"><small>მოლოდინში</small><strong>{{ $memberships->where('status','invited')->count() }}</strong></div>
</div>
<div class="grid">
<section class="panel">
<div class="panel-head"><div><div class="eyebrow">NEW LOCATION</div><h2>ახალი ფილიალი</h2></div><span class="tag">ფილიალები</span></div>
<form method="POST" action="{{ route('business.branches.store', $business) }}">@csrf
<label for="branch-name">ფილიალის სახელი</label><input id="branch-name" class="field" name="name" value="{{ old('name') }}" required maxlength="120" placeholder="მაგ. ვაკის ფილიალი">
<label for="branch-address">მისამართი</label><input id="branch-address" class="field" name="address" value="{{ old('address') }}" maxlength="255" placeholder="ქუჩა, ნომერი">
<label for="branch-city">ქალაქი</label><input id="branch-city" class="field" name="city" value="{{ old('city') }}" maxlength="100" placeholder="თბილისი">
<label for="branch-phone">ტელეფონი</label><input id="branch-phone" class="field" name="phone" value="{{ old('phone') }}" maxlength="40" placeholder="+995 ...">
<label for="branch-email">საკონტაქტო ელფოსტა</label><input id="branch-email" class="field" name="email" value="{{ old('email') }}" type="email" maxlength="190" placeholder="branch@example.com">
<label for="branch-timezone">დროის სარტყელი</label><input id="branch-timezone" class="field" name="timezone" value="{{ old('timezone', $business->timezone ?: 'Asia/Tbilisi') }}" required>
<button class="btn" type="submit">＋ ფილიალის დამატება</button>
</form>
</section>
<section class="panel">
<div class="panel-head"><div><div class="eyebrow">TEAM ACCESS</div><h2>თანამშრომლის მოწვევა</h2></div><span class="tag">გუნდი</span></div>
<p class="muted" style="font-size:13px;line-height:1.6">მოწვევა შესაძლებელია მხოლოდ oneQR-ზე უკვე რეგისტრირებული ელფოსტით. წვდომა გააქტიურდება თანამშრომლის თანხმობის შემდეგ.</p>
<form method="POST" action="{{ route('business.members.invite', $business) }}">@csrf
<label for="member-email">თანამშრომლის ელფოსტა</label><input id="member-email" class="field" name="email" type="email" value="{{ old('email') }}" required placeholder="name@example.com">
<label for="member-role">როლი</label><select id="member-role" class="field" name="role" required><option value="staff" @selected(old('role','staff') === 'staff')>თანამშრომელი</option><option value="manager" @selected(old('role') === 'manager')>მენეჯერი</option></select>
<label for="member-branch">ფილიალზე წვდომა</label><select id="member-branch" class="field" name="branch_id"><option value="">ყველა ფილიალი</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((string)old('branch_id') === (string)$branch->id)>{{ $branch->name }}</option>@endforeach</select>
<button class="btn" type="submit">＋ მოწვევის გაგზავნა</button>
</form>
</section>
</div>
<section class="panel">
<div class="panel-head"><div><div class="eyebrow">LOCATIONS</div><h2>ფილიალები</h2></div><span class="tag">{{ $branches->count() }} ლოკაცია</span></div>
@forelse($branches as $branch)
<div class="item"><div class="item-main"><strong>{{ $branch->name }} @if($branch->is_primary)<span class="tag">ძირითადი</span>@endif @if(!$branch->is_active)<span class="tag tag-wait">გამორთული</span>@endif</strong><div class="item-sub">{{ collect([$branch->city,$branch->address])->filter()->implode(' · ') ?: 'მისამართი მითითებული არ არის' }} · {{ $branch->memberships_count }} წევრი</div><div class="item-sub">{{ $branch->phone ?: 'ტელეფონი არ არის მითითებული' }} · {{ $branch->timezone }}</div></div>
<div class="item-actions">@if(!$branch->is_primary)<form method="POST" action="{{ route('business.branches.destroy', [$business,$branch->id]) }}" onsubmit="return confirm('დარწმუნებული ხარ, რომ ფილიალის წაშლა გსურს?')">@csrf @method('DELETE')<button class="btn btn-danger" type="submit">წაშლა</button></form>@endif</div></div>
@empty<div class="empty">ფილიალები ჯერ არ არის დამატებული.</div>@endforelse
</section>
<section class="panel" style="margin-top:17px">
<div class="panel-head"><div><div class="eyebrow">TEAM MEMBERS</div><h2>გუნდი და მოწვევები</h2></div><span class="tag">{{ $memberships->count() }} ჩანაწერი</span></div>
@forelse($memberships as $membership)
<div class="item"><div class="item-main"><strong>{{ $membership->user->name }}</strong><div class="item-sub">{{ $membership->user->email }} · {{ $membership->branch?->name ?? 'ყველა ფილიალი' }}</div></div>
<div class="item-actions"><span class="tag {{ $membership->status === 'active' ? 'tag-green' : 'tag-wait' }}">{{ $membership->role === 'manager' ? 'მენეჯერი' : 'თანამშრომელი' }} · {{ $membership->status === 'active' ? 'აქტიური' : 'მოლოდინში' }}</span><form method="POST" action="{{ route('business.members.destroy', [$business,$membership->id]) }}" onsubmit="return confirm('წავშალოთ გუნდის წევრი ან მოწვევა?')">@csrf @method('DELETE')<button class="btn btn-danger" type="submit">წაშლა</button></form></div></div>
@empty<div class="empty">გუნდის წევრები ჯერ არ დაგიმატებია.</div>@endforelse
</section>
</main>
</body></html>
