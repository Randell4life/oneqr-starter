<!doctype html>
<html lang="ka">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>ბიზნესის რეგისტრაცია — oneQR</title>
<style>
:root{color-scheme:dark;--bg:#080711;--panel:#121020;--line:#29243d;--text:#f6f3ff;--muted:#aaa3bd;--purple:#9b72ff;--purple2:#7144e8}
*{box-sizing:border-box}body{margin:0;background:radial-gradient(ellipse at 80% 0,#24154b 0,transparent 45%),var(--bg);color:var(--text);font:15px Inter,system-ui,sans-serif;min-height:100vh;display:grid;place-items:center;padding:24px}
main{width:min(100%,520px);background:#100e1b;border:1px solid var(--line);border-radius:24px;padding:36px;box-shadow:0 24px 90px #0008}
.logo{font-size:24px;font-weight:900;letter-spacing:-1px}.logo span{color:var(--purple)}h1{font-size:30px;margin:28px 0 8px}p{color:var(--muted);line-height:1.6;margin-top:0}
label{display:block;font-size:13px;color:#d8d2e8;margin:17px 0 7px}input,select{width:100%;background:#090813;border:1px solid var(--line);border-radius:11px;padding:13px;color:white;font:inherit}input:focus,select:focus{outline:2px solid #865dff;border-color:transparent}.grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.error{color:#ff9bba;font-size:12px;margin-top:5px}button{width:100%;margin-top:24px;border:0;border-radius:12px;padding:15px;background:linear-gradient(100deg,var(--purple2),var(--purple));color:white;font-weight:800;font-size:15px;cursor:pointer}.foot{text-align:center;margin:20px 0 0;font-size:13px}.foot a{color:#c0aaff}@media(max-width:520px){main{padding:25px}.grid{grid-template-columns:1fr}}
</style></head><body><main>
<div class="logo">one<span>QR</span></div><h1>შექმენი შენი ბიზნესის სივრცე</h1><p>ერთი ანგარიში — შენი ბიზნესის ციფრული პლატფორმა.</p>
<form method="POST" action="{{ route('register.store') }}">@csrf
<label>თქვენი სახელი</label><input name="name" value="{{ old('name') }}" required autocomplete="name">@error('name')<div class="error">{{ $message }}</div>@enderror
<label>ელფოსტა</label><input type="email" name="email" value="{{ old('email') }}" required autocomplete="email">@error('email')<div class="error">{{ $message }}</div>@enderror
<div class="grid"><div><label>პაროლი (მინ. 10 სიმბოლო)</label><input type="password" name="password" required autocomplete="new-password"></div><div><label>გაიმეორე პაროლი</label><input type="password" name="password_confirmation" required autocomplete="new-password"></div></div>@error('password')<div class="error">{{ $message }}</div>@enderror
<label>ბიზნესის დასახელება</label><input name="business_name" value="{{ old('business_name') }}" required>@error('business_name')<div class="error">{{ $message }}</div>@enderror
<label>ბიზნესის ტიპი</label><select name="business_type" required><option value="restaurant">რესტორანი / კაფე</option><option value="retail">მაღაზია / საცალო ვაჭრობა</option><option value="hotel">სასტუმრო / განთავსება</option><option value="services">სერვისები</option><option value="beauty">სილამაზის სფერო</option><option value="events">ღონისძიებები</option><option value="other">სხვა ბიზნესი</option></select>
<button type="submit">ბიზნესის შექმნა</button></form>
<div class="foot">უკვე გაქვს ანგარიში? <a href="/admin/login">ადმინისტრატორის შესვლა</a></div>
</main></body></html>
