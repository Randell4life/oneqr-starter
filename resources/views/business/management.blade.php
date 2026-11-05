<!doctype html>
<html lang="ka"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>ბიზნესის მართვა — oneQR</title>
<style>
:root{color-scheme:dark;--bg:#090812;--panel:#121020;--line:#29243d;--text:#f6f3ff;--muted:#aaa3bd;--purple:#9b72ff}*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font:15px Inter,system-ui,sans-serif}.nav{height:72px;border-bottom:1px solid var(--line);padding:0 max(24px,calc((100vw - 1100px)/2));display:flex;align-items:center;justify-content:space-between}.logo{font-size:23px;font-weight:900}.logo span{color:var(--purple)}main{max-width:1100px;margin:38px auto;padding:0 24px}a{color:#c7b3ff;text-decoration:none}.muted{color:var(--muted)}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:18px;margin:22px 0}.panel{background:var(--panel);border:1px solid var(--line);border-radius:16px;padding:22px}.field{display:block;width:100%;padding:11px;margin:8px 0 14px;background:#0b0a14;border:1px solid var(--line);border-radius:8px;color:white}.btn{background:#39265f;border:1px solid #684bb0;color:white;padding:10px 14px;border-radius:8px;cursor:pointer}.danger{background:#321a2d;border-color:#70405d}.row{display:flex;justify-content:space-between;gap:14px;align-items:center;border-top:1px solid var(--line);padding:14px 0}.tag{font-size:12px;color:#d9c9ff;background:#38285e;border-radius:20px;padding:4px 9px}h1{margin:0 0 8px}h2{font-size:19px;margin:0 0 16px}label{color:var(--muted);font-size:13px}form.inline{display:inline}
</style></head><body><header class="nav"><div class="logo">one<span>QR</span></div><a href="{{ route('dashboard') }}">← კაბინეტში დაბრუნება</a></header>
<main><h1>{{ $business->name }} — მართვა</h1><p class="muted">ფილიალები და თანამშრომლების მოწვევები.</p>
@if(session('status'))<p class="tag">{{ session('status') }}</p>@endif
<div class="grid">
<section class="panel"><h2>ახალი ფილიალი</h2><form method="POST" action="{{ route('business.branches.store', $business) }}">@csrf
<label>ფილიალის სახელი</label><input class="field" name="name" required maxlength="120">
<label>მისამართი</label><input class="field" name="address" maxlength="255">
<label>ქალაქი</label><input class="field" name="city" maxlength="100">
<label>ტელეფონი</label><input class="field" name="phone" maxlength="40">
<label>ელფოსტა</label><input class="field" name="email" type="email">
<label>დროის სარტყელი</label><input class="field" name="timezone" value="{{ $business->timezone }}" required>
<button class="btn">ფილიალის დამატება</button></form></section>
<section class="panel"><h2>თანამშრომლის მოწვევა</h2><p class="muted">მოწვევა შესაძლებელია მხოლოდ oneQR-ზე უკვე რეგისტრირებული ელფოსტით. წვდომა გააქტიურდება თანამშრომლის თანხმობის შემდეგ.</p>
<form method="POST" action="{{ route('business.members.invite', $business) }}">@csrf
<label>თანამშრომლის ელფოსტა</label><input class="field" name="email" type="email" required>
<label>როლი</label><select class="field" name="role" required><option value="staff">თანამშრომელი</option><option value="manager">მენეჯერი</option></select>
<label>ფილიალი</label><select class="field" name="branch_id"><option value="">ყველა ფილიალი</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select>
<button class="btn">მოწვევის გაგზავნა</button></form></section>
</div>
<section class="panel"><h2>ფილიალები ({{ $branches->count() }})</h2>
@forelse($branches as $branch)<div class="row"><div><strong>{{ $branch->name }}</strong> @if($branch->is_primary)<span class="tag">ძირითადი</span>@endif<div class="muted">{{ $branch->city }} {{ $branch->address }} · {{ $branch->memberships_count }} წევრი</div></div>
@if(!$branch->is_primary)<form class="inline" method="POST" action="{{ route('business.branches.destroy', [$business,$branch->id]) }}" onsubmit="return confirm('წავშალოთ ფილიალი?')">@csrf @method('DELETE')<button class="btn danger">წაშლა</button></form>@endif</div>@empty<p class="muted">ფილიალები ჯერ არ არის.</p>@endforelse
</section>
<section class="panel" style="margin-top:18px"><h2>გუნდი და მოწვევები ({{ $memberships->count() }})</h2>
@forelse($memberships as $membership)<div class="row"><div><strong>{{ $membership->user->name }}</strong><div class="muted">{{ $membership->user->email }} · {{ $membership->branch?->name ?? 'ყველა ფილიალი' }}</div></div><div><span class="tag">{{ $membership->role === 'manager' ? 'მენეჯერი' : 'თანამშრომელი' }} · {{ $membership->status === 'active' ? 'აქტიური' : 'მოლოდინში' }}</span><form class="inline" method="POST" action="{{ route('business.members.destroy', [$business,$membership->id]) }}">@csrf @method('DELETE')<button class="btn danger">წაშლა</button></form></div></div>@empty<p class="muted">გუნდი ჯერ არ დაგიმატებია.</p>@endforelse
</section></main></body></html>
