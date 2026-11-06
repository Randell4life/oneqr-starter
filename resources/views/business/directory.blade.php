<!doctype html>
<html lang="ka">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#080811">
<meta name="description" content="აღმოაჩინე ადგილობრივი ბიზნესები საქართველოში — მოძებნე რესტორნები, სასტუმროები, მაღაზიები და სერვისები OneQR-ზე.">
<link rel="canonical" href="{{ route('business.directory') }}">
<title>ბიზნესების აღმოჩენა — OneQR</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<style>
:root{color-scheme:dark;--bg:#080811;--panel:#141222;--line:#302b46;--text:#f8f6ff;--muted:#aaa5bd;--purple:#9568ff;--violet:#7044df}
*{box-sizing:border-box}body{margin:0;background:radial-gradient(ellipse at 80% -15%,#593b9945,transparent 42%),var(--bg);color:var(--text);font:15px Inter,system-ui,sans-serif}a{color:inherit;text-decoration:none}.wrap{width:min(1160px,calc(100% - 36px));margin:auto}
.nav{height:76px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #ffffff16}.brand{font-size:26px;font-weight:950;letter-spacing:-1.5px}.brand span{color:#ad91ff}.navlinks{display:flex;gap:20px;align-items:center;color:#c5bfd5;font-size:14px}.btn{display:inline-flex;justify-content:center;align-items:center;border:1px solid #ffffff22;border-radius:11px;padding:11px 15px;font-weight:750}.primary{background:linear-gradient(110deg,var(--violet),var(--purple));border-color:#b7a0ff55}
.hero{padding:58px 0 28px}.eyebrow{color:#bba7ff;font-size:11px;font-weight:850;letter-spacing:.16em}.hero h1{font-size:clamp(34px,5vw,54px);letter-spacing:-.055em;margin:12px 0}.hero p{color:var(--muted);line-height:1.7;max-width:650px;margin:0}
.filters{margin:24px 0 30px;padding:17px;background:linear-gradient(145deg,#19172a,#11101b);border:1px solid var(--line);border-radius:18px;display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:10px}
input,select{width:100%;min-height:46px;border-radius:10px;border:1px solid #3a3550;background:#0d0c17;color:var(--text);padding:0 13px;font:inherit}
.filters button{border:0;cursor:pointer;color:white;font:inherit;font-weight:800;border-radius:10px;padding:0 20px;background:linear-gradient(110deg,var(--violet),var(--purple))}
.map-panel{margin:22px 0 30px;padding:17px;border:1px solid var(--line);border-radius:18px;background:linear-gradient(145deg,#19172a,#11101b)}.map-heading{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:12px}.map-heading h2{font-size:20px;margin:0}.map-heading p{font-size:12px;color:var(--muted);margin:5px 0 0}#business-map{height:390px;border-radius:13px;overflow:hidden;background:#171426}.map-note{color:var(--muted);font-size:12px;margin-top:10px}.leaflet-popup-content-wrapper,.leaflet-popup-tip{background:#171426;color:#f8f6ff}.leaflet-popup-content a{color:#c7b4ff;font-weight:800}.nearby-button{border:1px solid #8062c9;background:#241b3c;color:#e8deff;border-radius:10px;padding:10px 12px;font-weight:750;cursor:pointer}.meta{display:flex;justify-content:space-between;align-items:center;gap:12px;color:var(--muted);font-size:13px;margin-bottom:15px}
.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
.card{min-width:0;overflow:hidden;border:1px solid var(--line);border-radius:18px;background:linear-gradient(145deg,#191727,#11101b);transition:transform .18s,border-color .18s}.card:hover{transform:translateY(-3px);border-color:#8c6ce0}
.cover{height:155px;background:linear-gradient(135deg,#3a2869,#171426);overflow:hidden}.cover img{width:100%;height:100%;object-fit:cover}.body{padding:17px}.top{display:flex;align-items:center;gap:12px}.logo{width:48px;height:48px;flex:none;border-radius:13px;object-fit:cover;background:#28223d;border:1px solid #ffffff1c}.initial{display:grid;place-items:center;color:#c6b4ff;font-size:21px;font-weight:900}.type{font-size:11px;color:#c7b8ff;background:#8b5cf622;border:1px solid #9b7cff33;padding:5px 9px;border-radius:30px}.name{font-size:19px;font-weight:850;letter-spacing:-.02em;margin:0 0 5px}.tagline{color:#c0bacd;font-size:13px;line-height:1.5;margin:13px 0;min-height:38px}.details{display:flex;flex-wrap:wrap;gap:10px;color:var(--muted);font-size:12px;padding-top:12px;border-top:1px solid #ffffff13}.open{display:flex;justify-content:space-between;align-items:center;margin-top:15px;color:#c7b4ff;font-size:13px;font-weight:800}
.section-head{display:flex;align-items:end;justify-content:space-between;gap:15px;margin:34px 0 16px}.section-head h2{font-size:23px;letter-spacing:-.04em;margin:0}.section-head p{color:var(--muted);font-size:12px;margin:6px 0 0}.chips{display:flex;flex-wrap:wrap;gap:9px;margin:18px 0 28px}.chip{display:inline-flex;gap:9px;align-items:center;padding:10px 13px;border:1px solid var(--line);border-radius:12px;background:#171426;color:#e8e2f7;font-size:13px;font-weight:700}.chip:hover{border-color:#8c6ce0}.chip small{color:#aaa1be;font-weight:500}.empty{padding:45px 20px;text-align:center;border:1px dashed #4b4365;border-radius:18px;color:var(--muted)}.pagination{margin:28px 0 50px}.pagination nav{display:flex;justify-content:center;gap:7px}.pagination a,.pagination span{padding:9px 13px;border:1px solid var(--line);border-radius:9px}
footer{border-top:1px solid #ffffff16;padding:28px 0;color:#88839d;font-size:12px;margin-top:45px}
@media(max-width:900px){.filters{grid-template-columns:repeat(2,minmax(0,1fr))}.filters input{grid-column:span 2}.grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:580px){#business-map{height:320px}.map-heading{align-items:flex-start;flex-direction:column}.wrap{width:calc(100% - 28px)}.nav{height:68px}.navlinks a:not(.btn){display:none}.hero{padding:42px 0 18px}.filters{grid-template-columns:1fr}.filters input{grid-column:auto}.filters button{min-height:45px}.grid{grid-template-columns:1fr}.cover{height:175px}.meta{align-items:flex-start;flex-direction:column}}
</style>
</head>
<body>
<header class="wrap nav"><a class="brand" href="{{ route('home') }}">one<span>QR</span></a><nav class="navlinks"><a href="{{ route('home') }}">მთავარი</a><a href="{{ route('business.directory') }}">აღმოაჩინე</a>@auth<a href="{{ route('dashboard') }}">კაბინეტი</a>@else<a class="btn" href="{{ route('login') }}">შესვლა</a><a class="btn primary" href="{{ route('register') }}">ბიზნესის დამატება</a>@endauth</nav></header>
<main class="wrap">
<section class="hero"><span class="eyebrow">ONEQR DISCOVER</span><h1>აღმოაჩინე შენი შემდეგი<br>საყვარელი ბიზნესი.</h1><p>იპოვე საინტერესო ადგილები, პროდუქტები და მომსახურება საქართველოში. დაათვალიერე ბიზნესები უფასოდ — რეგისტრაციის გარეშე.</p></section>
@if(!$hasFilters)
@if($categoryCounts->isNotEmpty())
<section aria-label="კატეგორიები">
<div class="section-head"><div><h2>დაათვალიერე კატეგორიები</h2><p>აირჩიე სასურველი მიმართულება</p></div></div>
<div class="chips">
@foreach($categoryLabels as $key=>$label)
@if(($categoryCounts[$key] ?? 0)>0)
<a class="chip" href="{{ route('business.directory',['category'=>$key]) }}">{{ $label }} <small>{{ $categoryCounts[$key] }}</small></a>
@endif
@endforeach
</div>
</section>
@endif
@if($popularBusinesses->isNotEmpty())
<section aria-label="პოპულარული ბიზნესები">
<div class="section-head"><div><h2>პოპულარული ბიზნესები</h2><p>რჩეულებში შენახვისა და მომხმარებელთა შეფასებების მიხედვით</p></div></div>
<div class="grid">
@foreach($popularBusinesses as $popular)
<a class="card" href="{{ route('business.public',$popular->slug) }}">
<div class="cover">@if($popular->cover_path)<img src="{{ Storage::disk('public')->url($popular->cover_path) }}" alt="" loading="lazy">@endif</div>
<div class="body"><div class="top">
@if($popular->logo_path)<img class="logo" src="{{ Storage::disk('public')->url($popular->logo_path) }}" alt="" loading="lazy">@else<div class="logo initial">{{ mb_substr($popular->name,0,1) }}</div>@endif
<div style="min-width:0"><div class="name">{{ $popular->name }}</div><span class="type">{{ $categoryLabels[$popular->business_type] ?? 'ბიზნესი' }}</span></div>
</div>
<p class="tagline">{{ $popular->tagline ?: \Illuminate\Support\Str::limit(strip_tags($popular->description ?? 'გაეცანი ბიზნესის შეთავაზებებს OneQR-ზე.'),95) }}</p>
<div class="details">@if($popular->city)<span>📍 {{ $popular->city }}</span>@endif<span>♥ {{ $popular->favorites_count }} რჩეული</span>@if($popular->approved_reviews_count)<span>★ {{ number_format((float)$popular->approved_reviews_avg_rating,1) }} ({{ $popular->approved_reviews_count }})</span>@endif</div>
<div class="open"><span>პროფილის ნახვა</span><span>→</span></div>
</div></a>
@endforeach
</div>
</section>
@endif
@endif
<form class="filters" method="GET" action="{{ route('business.directory') }}">
<input type="hidden" name="lat" id="nearby-lat" value="{{ $filters['lat'] ?? '' }}">
<input type="hidden" name="lng" id="nearby-lng" value="{{ $filters['lng'] ?? '' }}">
<input type="hidden" name="nearby" id="nearby-enabled" value="{{ $filters['nearby'] ?? '' }}">

<input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="მოძებნე ბიზნესი, პროდუქტი ან სერვისი..." aria-label="ძებნა">
<select name="category" aria-label="კატეგორია"><option value="">ყველა კატეგორია</option>@foreach($categoryLabels as $key=>$label)@if(($categoryCounts[$key] ?? 0)>0)<option value="{{ $key }}" @selected(($filters['category'] ?? '')===$key)>{{ $label }}</option>@endif @endforeach</select>
<select name="city" aria-label="ქალაქი"><option value="">ყველა ქალაქი</option>@foreach($cities as $city)<option value="{{ $city }}" @selected(($filters['city'] ?? '')===$city)>{{ $city }}</option>@endforeach</select>
<select name="sort" aria-label="დალაგება"><option value="recent" @selected(($filters['sort'] ?? 'recent')==='recent')>ახლახან დამატებული</option><option value="rated" @selected(($filters['sort'] ?? '')==='rated')>რეიტინგით</option><option value="name" @selected(($filters['sort'] ?? '')==='name')>სახელით</option></select>
<button type="submit">ძიება ↗</button><button class="nearby-button" type="button" id="nearby-search">ჩემთან ახლოს</button>
</form>

<section class="map-panel" aria-label="ბიზნესების რუკა">
<div class="map-heading"><div><h2>ბიზნესები რუკაზე</h2><p>მდებარეობა მითითებული ბიზნესები · მაქსიმუმ 250 წერტილი</p></div><span class="type">{{ $mapBusinesses->count() }} ლოკაცია</span></div>
<div id="business-map" role="application" aria-label="ბიზნესების ინტერაქტიული რუკა"></div>
<div class="map-note" id="map-note" aria-live="polite">@if($mapBusinesses->isEmpty())რუკაზე ჯერ არცერთი ბიზნესის მდებარეობა არ არის მითითებული. ბიზნესის მფლობელს შეუძლია კოორდინატები პროფილის რედაქტირებიდან დაამატოს.@elseმონიშნე რუკაზე წერტილი ბიზნესის მისამართისა და პროფილის სანახავად.@endif</div>
</section>
<div class="meta"><span>ნაპოვნია {{ $businesses->total() }} ბიზნესი</span><span>საჯარო და გამოქვეყნებული პროფილები</span></div>
@if($businesses->count())
<div class="grid">
@foreach($businesses as $business)
<a class="card" href="{{ route('business.public',$business->slug) }}">
<div class="cover">@if($business->cover_path)<img src="{{ Storage::disk('public')->url($business->cover_path) }}" alt="" loading="lazy">@endif</div>
<div class="body"><div class="top">
@if($business->logo_path)<img class="logo" src="{{ Storage::disk('public')->url($business->logo_path) }}" alt="" loading="lazy">@else<div class="logo initial">{{ mb_substr($business->name,0,1) }}</div>@endif
<div style="min-width:0"><div class="name">{{ $business->name }}</div><span class="type">{{ $categoryLabels[$business->business_type] ?? 'ბიზნესი' }}</span></div>
</div>
<p class="tagline">{{ $business->tagline ?: \Illuminate\Support\Str::limit(strip_tags($business->description ?? 'გაეცანი ბიზნესის შეთავაზებებს OneQR-ზე.'),95) }}</p>
<div class="details">@if($business->city)<span>📍 {{ $business->city }}</span>@endif<span>▦ {{ $business->available_items_count }} შეთავაზება</span>@if($business->approved_reviews_count)<span>★ {{ number_format((float)$business->approved_reviews_avg_rating,1) }} ({{ $business->approved_reviews_count }})</span>@endif</div>
<div class="open"><span>პროფილის ნახვა</span><span>→</span></div>
</div></a>
@endforeach
</div>
<div class="pagination">{{ $businesses->links() }}</div>
@else
<div class="empty"><strong style="display:block;color:#f8f6ff;font-size:19px;margin-bottom:8px">შესაბამისი ბიზნესი ვერ მოიძებნა</strong>შეცვალე საძიებო სიტყვა ან ფილტრები და სცადე თავიდან.</div>
@endif
</main>
<footer><div class="wrap" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap"><span><strong style="color:#eeeaff">oneQR</strong> · ONE SCAN. ENDLESS POSSIBILITIES.</span><span>© {{ date('Y') }} oneQR</span></div></footer>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(function(){
 const businesses = @json($mapBusinesses);
 const map = L.map('business-map',{scrollWheelZoom:false}).setView([42.2,43.4],6);
 L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'&copy; OpenStreetMap'}).addTo(map);
 const bounds=[];
 businesses.forEach(function(business){
  const marker=L.marker([business.lat,business.lng]).addTo(map);
  const content=document.createElement('div');
  const title=document.createElement('strong');title.textContent=business.name;content.appendChild(title);
  const address=document.createElement('p');address.textContent=[business.city,business.address].filter(Boolean).join(' · ');content.appendChild(address);
  const link=document.createElement('a');link.href='/p/'+encodeURIComponent(business.slug);link.textContent='პროფილის ნახვა →';content.appendChild(link);
  marker.bindPopup(content);bounds.push([business.lat,business.lng]);
 });
 if(bounds.length===1)map.setView(bounds[0],14);else if(bounds.length>1)map.fitBounds(bounds,{padding:[25,25]});
 const nearby=document.getElementById('nearby-search');
 nearby?.addEventListener('click',function(){
  const note=document.getElementById('map-note');
  if(!navigator.geolocation){note.textContent='ამ ბრაუზერში მდებარეობის მიღება მიუწვდომელია.';return;}
  nearby.disabled=true;nearby.textContent='მდებარეობას ვიღებ…';
  navigator.geolocation.getCurrentPosition(function(position){
   document.getElementById('nearby-lat').value=position.coords.latitude;
   document.getElementById('nearby-lng').value=position.coords.longitude;
   document.getElementById('nearby-enabled').value='1';
   nearby.form.submit();
  },function(){
   note.textContent='მდებარეობა ვერ მივიღე. დაუშვი Location წვდომა ან გამოიყენე ქალაქის ფილტრი.';
   nearby.disabled=false;nearby.textContent='ჩემთან ახლოს';
  },{enableHighAccuracy:true,timeout:10000,maximumAge:60000});
 });
})();
</script>
</body></html>
