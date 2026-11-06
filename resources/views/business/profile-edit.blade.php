<!doctype html>
<html lang="ka"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>პროფილის კონსტრუქტორი — oneQR</title>
<style>
:root{color-scheme:dark;--bg:#090812;--panel:#121020;--line:#29243d;--text:#f6f3ff;--muted:#aaa3bd;--purple:#9b72ff}*{box-sizing:border-box}body{margin:0;background:var(--bg);color:var(--text);font:15px Inter,system-ui,sans-serif}.nav{height:72px;border-bottom:1px solid var(--line);padding:0 max(22px,calc((100vw - 1050px)/2));display:flex;align-items:center;justify-content:space-between}.logo{font-size:23px;font-weight:900}.logo span{color:var(--purple)}main{max-width:1050px;margin:38px auto;padding:0 22px}a{color:#c7b3ff;text-decoration:none}.muted,label{color:var(--muted)}.layout{display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:20px}.panel{background:var(--panel);border:1px solid var(--line);border-radius:16px;padding:22px;margin:18px 0}.field{display:block;width:100%;padding:12px;margin:7px 0 16px;background:#0b0a14;border:1px solid var(--line);border-radius:9px;color:white}textarea.field{min-height:130px;resize:vertical}.btn{display:inline-block;background:#39265f;border:1px solid #684bb0;color:white;padding:11px 16px;border-radius:9px;cursor:pointer}.tag{display:inline-block;padding:6px 10px;background:#38285e;color:#dfd2ff;border-radius:30px}.preview{position:sticky;top:20px;height:max-content}.preview img{width:100%;height:115px;object-fit:cover;border-radius:10px}.logo-preview{width:64px;height:64px;object-fit:cover;border-radius:16px;margin-top:-32px;border:3px solid var(--panel);background:#201a32}.grid{display:grid;grid-template-columns:1fr 1fr;gap:0 14px}.status{padding:10px;background:#1b2930;border:1px solid #2e6255;border-radius:9px;color:#a8f0d1;margin:15px 0}@media(max-width:760px){.layout{grid-template-columns:1fr}.preview{position:static}.grid{grid-template-columns:1fr}}
</style>@include('partials.design-system')
</head><body><header class="nav"><div class="logo">one<span>QR</span></div><a href="{{ route('dashboard') }}">← კაბინეტში დაბრუნება</a> · <a href="{{ route('business.smart-pages.index', $business) }}">Smart Page Builder →</a></header>
<main><h1>ბიზნესის გვერდის კონსტრუქტორი</h1><p class="muted">შექმენი შენი ბიზნესის საჯარო გვერდი — ის გაიხსნება QR კოდის დასკანერების შემდეგაც.</p>
@if(session('status'))<div class="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="status" style="background:#351d2a;border-color:#71364c;color:#ffd1dc">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div class="layout"><form class="panel" method="POST" enctype="multipart/form-data" action="{{ route('business.profile.update', $business) }}">@csrf @method('PUT')
<h2>ძირითადი ინფორმაცია</h2>
<label>ბიზნესის სახელი</label><input class="field" name="name" required maxlength="150" value="{{ old('name', $business->name) }}">
<label>მოკლე სლოგანი</label><input class="field" name="tagline" maxlength="180" value="{{ old('tagline', $business->tagline) }}" placeholder="მაგ. ხარისხი, რომელსაც ენდობი">
<label>აღწერა</label><textarea class="field" name="description" maxlength="5000" placeholder="მოუყევი მომხმარებელს შენი ბიზნესის შესახებ">{{ old('description', $business->description) }}</textarea>
<label>კატეგორია</label><select class="field" name="business_type"><option value="restaurant" @selected(old('business_type',$business->business_type)==='restaurant')>რესტორანი / კაფე</option><option value="retail" @selected(old('business_type',$business->business_type)==='retail')>მაღაზია / საცალო ვაჭრობა</option><option value="hotel" @selected(old('business_type',$business->business_type)==='hotel')>სასტუმრო</option><option value="services" @selected(old('business_type',$business->business_type)==='services')>სერვისები</option><option value="beauty" @selected(old('business_type',$business->business_type)==='beauty')>სილამაზე და მოვლა</option><option value="events" @selected(old('business_type',$business->business_type)==='events')>ღონისძიებები</option><option value="other" @selected(old('business_type',$business->business_type)==='other')>სხვა</option></select>
<h2>კონტაქტი და მისამართი</h2><div class="grid"><div><label>ელფოსტა</label><input class="field" name="email" type="email" value="{{ old('email',$business->email) }}"></div><div><label>ტელეფონი</label><input class="field" name="phone" value="{{ old('phone',$business->phone) }}"></div><div><label>ვებგვერდი</label><input class="field" name="website" type="url" placeholder="https://" value="{{ old('website',$business->website) }}"></div><div><label>ქალაქი</label><input class="field" name="city" value="{{ old('city',$business->city) }}"></div></div>
<label>მისამართი</label><input class="field" name="address" value="{{ old('address',$business->address) }}">
<h3>რუკაზე მდებარეობა</h3>
<p class="muted">მიუთითე კოორდინატები, რათა მომხმარებლებმა შენი ბიზნესი OneQR-ის რუკაზე იპოვონ.</p>
<div class="grid">
<div><label>განედი (Latitude)</label><input class="field" id="business-latitude" name="latitude" type="number" step="any" min="-90" max="90" value="{{ old('latitude',$business->latitude) }}" placeholder="41.7151"></div>
<div><label>გრძედი (Longitude)</label><input class="field" id="business-longitude" name="longitude" type="number" step="any" min="-180" max="180" value="{{ old('longitude',$business->longitude) }}" placeholder="44.8271"></div>
</div>
<button class="btn" type="button" id="use-business-location">ჩემი მდებარეობის აღება</button>
<span class="muted" id="location-feedback" role="status" aria-live="polite"></span>

<h2>ვიზუალური იდენტობა</h2><div class="grid"><div><label>ლოგო (JPG/PNG/WEBP, მაქს. 2MB)</label><input class="field" type="file" name="logo" accept="image/jpeg,image/png,image/webp"></div><div><label>ყდის ფოტო (მაქს. 5MB)</label><input class="field" type="file" name="cover" accept="image/jpeg,image/png,image/webp"></div></div>
<label>აქცენტის ფერი</label><input class="field" type="color" name="accent_color" value="{{ old('accent_color',$business->accent_color ?: '#8B5CF6') }}">
<h2>გამოქვეყნება</h2><label style="display:flex;gap:10px;align-items:center;margin:12px 0 20px"><input type="checkbox" name="is_published" value="1" @checked(old('is_published',$business->is_published))> საჯარო გვერდის გამოქვეყნება</label>
<button class="btn" type="submit">ცვლილებების შენახვა</button>
</form>
<script>
document.getElementById('use-business-location')?.addEventListener('click',function(){
 const feedback=document.getElementById('location-feedback');
 if(!navigator.geolocation){feedback.textContent='ამ ბრაუზერში მდებარეობის მიღება მიუწვდომელია.';return;}
 feedback.textContent='მიმდინარეობს მდებარეობის მიღება…';
 navigator.geolocation.getCurrentPosition(function(position){
  document.getElementById('business-latitude').value=position.coords.latitude.toFixed(7);
  document.getElementById('business-longitude').value=position.coords.longitude.toFixed(7);
  feedback.textContent='კოორდინატები შევსებულია. შეინახე ცვლილებები.';
 },function(){feedback.textContent='მდებარეობა ვერ მივიღე. შეამოწმე ბრაუზერის ნებართვა ან შეიყვანე კოორდინატები ხელით.'},{enableHighAccuracy:true,timeout:10000,maximumAge:60000});
});
</script>
<aside class="preview"><section class="panel"><h3 style="margin-top:0">გვერდის სტატუსი</h3><span class="tag">{{ $business->is_published ? 'გამოქვეყნებულია' : 'დამალულია' }}</span><p class="muted">საჯარო მისამართი</p><a href="{{ route('business.public', $business->slug) }}" target="_blank">{{ url('/b/'.$business->slug) }}</a><p class="muted">შენახვის შემდეგ საჯარო გვერდზე გამოჩნდება განახლებული ინფორმაცია.</p><a class="btn" style="margin-top:8px" href="{{ route('business.public', $business->slug) }}" target="_blank">საჯარო გვერდის ნახვა ↗</a></section></aside>
</div></main></body></html>
