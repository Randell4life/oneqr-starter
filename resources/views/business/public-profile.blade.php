<!doctype html>
<html lang="{{ $business->default_locale ?: 'ka' }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><meta name="oneqr-interaction-url" content="{{ route('business.public.interactions', $business->slug) }}"><meta name="description" content="{{ $business->tagline ?: Str::limit(strip_tags($business->description ?? ''), 150) }}"><title>{{ $business->name }} — oneQR</title>
<style>
:root{color-scheme:dark;--accent:{{ $business->accent_color ?: '#8B5CF6' }};--bg:#090812;--panel:#141122;--line:#2a2540;--muted:#aaa3bd;--text:#f8f6ff}*{box-sizing:border-box}body{margin:0;background:radial-gradient(ellipse at 50% -20%,color-mix(in srgb,var(--accent) 22%,transparent),transparent 55%),var(--bg);color:var(--text);font:16px Inter,system-ui,sans-serif}.wrap{max-width:820px;margin:0 auto;padding:24px}.cover{height:260px;border-radius:24px;overflow:hidden;background:linear-gradient(135deg,var(--accent),#19142b)}.cover img{width:100%;height:100%;object-fit:cover}.profile{position:relative;margin:-52px 24px 0}.logo{width:104px;height:104px;border-radius:24px;border:5px solid var(--bg);background:#211b35;object-fit:cover}.card{background:var(--panel);border:1px solid var(--line);border-radius:22px;padding:28px;margin-top:18px}.muted{color:var(--muted);line-height:1.7}.type{display:inline-block;background:color-mix(in srgb,var(--accent) 20%,transparent);color:#e7ddff;border-radius:30px;padding:7px 12px;font-size:12px}h1{font-size:clamp(30px,6vw,46px);margin:15px 0 6px;letter-spacing:-1.5px}.tagline{font-size:19px;color:#e2d9f8}.actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:22px}.action{display:inline-block;text-decoration:none;color:white;background:var(--accent);padding:12px 17px;border-radius:12px;font-weight:700}.action.secondary{background:#242035;border:1px solid var(--line)}footer{text-align:center;color:var(--muted);font-size:12px;padding:30px}@media(max-width:600px){.wrap{padding:14px}.cover{height:190px}.card{padding:20px}.profile{margin-left:14px}}
</style>@include('partials.design-system')<style>
body{background:radial-gradient(ellipse at 50% -18%,color-mix(in srgb,var(--accent) 27%,transparent),transparent 58%),#080711!important}
.wrap{max-width:900px!important;padding:28px 22px!important}
.cover{height:clamp(190px,30vw,330px);border:1px solid color-mix(in srgb,var(--accent) 32%,#302a49);box-shadow:0 22px 60px #0005}
.profile{margin-top:-58px}
.logo{box-shadow:0 10px 28px #0006}
.card{background:linear-gradient(145deg,#171626f5,#10101af5)!important;border-color:#302c45!important;box-shadow:0 14px 38px #0003!important}
.card h2{font-size:clamp(20px,3vw,25px);margin-top:0}
.card h3{font-size:18px;margin:13px 0 7px}
.type{border:1px solid color-mix(in srgb,var(--accent) 48%,transparent);background:color-mix(in srgb,var(--accent) 20%,#141122)}
.action{background:linear-gradient(110deg,color-mix(in srgb,var(--accent) 75%,#39206f),var(--accent));box-shadow:0 7px 20px color-mix(in srgb,var(--accent) 22%,transparent);transition:transform .18s,filter .18s}
.action:hover{color:#fff;transform:translateY(-2px);filter:brightness(1.08)}
.action.secondary{background:#211e31;border-color:#39344f}
#catalog>div[style*="grid-template-columns"]{align-items:stretch}
#catalog article{display:flex;flex-direction:column;gap:7px;background:linear-gradient(145deg,#171626,#10101a)!important;border-color:#302c45!important;box-shadow:0 10px 28px #0003;transition:transform .2s,border-color .2s}
#catalog article:hover{transform:translateY(-3px);border-color:color-mix(in srgb,var(--accent) 55%,#302c45)!important}
#catalog article>img{height:190px!important;border:1px solid #ffffff12}
#catalog article h3{margin:6px 0 0}
#catalog article form{margin-top:auto;flex-wrap:wrap}
#catalog article input{min-height:42px}
#catalog article button{flex:1}
#catalog>div:first-child{margin-top:18px}
footer{border-top:1px solid #29283e;margin-top:30px;letter-spacing:.04em}
@media(max-width:600px){.wrap{padding:14px!important}.cover{height:190px;border-radius:18px}.profile{margin-left:12px;margin-top:-48px}.logo{width:88px;height:88px;border-radius:20px}.card{padding:18px!important;border-radius:18px!important}.actions .action{flex:1;text-align:center}.card#catalog>div:first-child{align-items:flex-start!important}}
</style>
</head><body><main class="wrap"><div class="cover">@if($business->cover_path)<img src="{{ Storage::disk('public')->url($business->cover_path) }}" alt="">@endif</div><section class="profile">@if($business->logo_path)<img class="logo" src="{{ Storage::disk('public')->url($business->logo_path) }}" alt="{{ $business->name }} ლოგო">@else<div class="logo" style="display:grid;place-items:center;font-size:34px;font-weight:900;color:var(--accent)">{{ mb_substr($business->name,0,1) }}</div>@endif</section>
<section class="card">@if(session('inquiry_status'))<p class="muted" style="color:#a8f0d1">{{ session('inquiry_status') }}</p>@endif @if($errors->any())<div class="muted" style="color:#ffc2d0">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif <span class="type">{{ ['restaurant'=>'რესტორანი / კაფე','retail'=>'მაღაზია','hotel'=>'სასტუმრო','services'=>'სერვისები','beauty'=>'სილამაზე და მოვლა','events'=>'ღონისძიებები','other'=>'ბიზნესი'][$business->business_type] ?? 'ბიზნესი' }}</span><h1>{{ $business->name }}</h1>@if($business->tagline)<div class="tagline">{{ $business->tagline }}</div>@endif
@if($business->description)<p class="muted" style="white-space:pre-line">{{ $business->description }}</p>@endif
<div class="muted">@if($business->city || $business->address)📍 {{ $business->city }} {{ $business->address }}<br><a class="action secondary" data-track="directions" href="https://www.google.com/maps/search/?api=1&query={{ urlencode(trim(($business->city ?? '') . ' ' . ($business->address ?? ''))) }}" target="_blank" rel="noopener noreferrer">მარშრუტის ნახვა ↗</a>@endif</div>
<div class="actions">@if($business->phone)<a class="action" data-track="call" href="tel:{{ preg_replace('/[^0-9+]/','',$business->phone) }}">დარეკვა</a>@endif @if($business->email)<a class="action secondary" data-track="email" href="mailto:{{ $business->email }}">ელფოსტის გაგზავნა</a>@endif @if($business->website)<a class="action secondary" data-track="website" href="{{ $business->website }}" target="_blank" rel="noopener noreferrer">ვებგვერდი ↗</a>@endif</div></section>

@if($smartPage)
@foreach($smartPage->blocks->where('is_visible',true) as $block)
<section class="card" style="border-color:color-mix(in srgb,var(--accent) 35%,var(--line))">
@if($block->type==='heading')<h2 style="font-size:28px">{{ $block->content['text'] ?? $block->title }}</h2>
@elseif($block->type==='text')@if($block->title)<h2>{{ $block->title }}</h2>@endif<p class="muted" style="white-space:pre-line">{{ $block->content['text'] ?? '' }}</p>
@elseif($block->type==='image')@if(!empty($block->content['image']) || !empty($block->content['url']))<img src="{{ !empty($block->content['image']) ? Storage::disk('public')->url($block->content['image']) : $block->content['url'] }}" alt="{{ $block->title ?? $business->name }}" loading="lazy" style="width:100%;max-height:420px;object-fit:cover;border-radius:16px">@endif
@elseif($block->type==='button')@if($block->title && !empty($block->content['url']))<a class="action" data-track="cta" href="{{ $block->content['url'] }}" target="_blank" rel="noopener noreferrer">{{ $block->title }} ↗</a>@endif
@elseif($block->type==='contact')<h2>{{ $block->title ?: 'დაგვიკავშირდი' }}</h2><div class="actions">@if($business->phone)<a class="action" data-track="call" href="tel:{{ preg_replace('/[^0-9+]/','',$business->phone) }}">დარეკვა</a>@endif @if($business->email)<a class="action secondary" data-track="email" href="mailto:{{ $business->email }}">ელფოსტა</a>@endif</div>
@elseif($block->type==='catalog')<h2>{{ $block->title ?: 'კატალოგი' }}</h2><a class="action" data-track="catalog" href="#catalog">პროდუქტებისა და სერვისების ნახვა ↓</a>
@elseif($block->type==='hours')<h2>{{ $block->title ?: 'სამუშაო საათები' }}</h2><p class="muted" style="white-space:pre-line">{{ $block->content['text'] ?? '' }}</p>
@elseif($block->type==='inquiry')<h2>{{ $block->title ?: 'მოთხოვნის გაგზავნა' }}</h2><p class="muted">შეავსე ფორმა და ბიზნესი დაგიკავშირდება.</p><form method="POST" data-track-form="inquiry" action="{{ route('business.public.inquiries.store',$business->slug) }}" style="display:grid;gap:10px">@csrf<input name="customer_name" required maxlength="120" placeholder="სახელი და გვარი" autocomplete="name"><input name="customer_phone" required maxlength="40" placeholder="ტელეფონის ნომერი" autocomplete="tel"><input name="customer_email" type="email" maxlength="190" placeholder="ელფოსტა (არასავალდებულო)" autocomplete="email"><select name="inquiry_type" required><option value="general">ზოგადი კითხვა</option><option value="booking">ჯავშნის მოთხოვნა</option><option value="consultation">კონსულტაცია</option></select><label class="muted">სასურველი თარიღი (არასავალდებულო)</label><input name="preferred_date" type="date" min="{{ now()->toDateString() }}"><textarea name="message" rows="3" maxlength="2000" placeholder="დამატებითი ინფორმაცია"></textarea><button class="action" type="submit" style="border:0;cursor:pointer">მოთხოვნის გაგზავნა</button></form>
@endif
</section>
@endforeach
@endif
<section class="card" id="catalog"><div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap"><h2>პროდუქტები და სერვისები</h2><a class="action" data-track="cart" href="{{ route('business.cart.show',$business->slug) }}">კალათა</a></div>
@if(session('cart_status'))<p class="muted" style="color:#a8f0d1">{{ session('cart_status') }}</p>@endif
@if(session('order_success'))<div class="muted" style="color:#a8f0d1">შეკვეთა მიღებულია! ნომერი: <strong>{{ session('order_success.number') }}</strong> · ჯამი: {{ session('order_success.total') }}</div>@endif
@if(session('payment_success'))<p class="muted" style="color:#a8f0d1">{{ session('payment_success') }}</p>@endif
@if(session('payment_error'))<p class="muted" style="color:#ffc2d0">{{ session('payment_error') }}</p>@endif
@if(session('payment_pending'))<p class="muted">{{ session('payment_pending') }}</p>@endif
@if(session('order_status'))<p class="muted" style="color:#a8f0d1">{{ session('order_status') }}</p>@endif
@if($errors->any())<div class="muted" style="color:#ffc2d0">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px">
@forelse($business->catalogItems->where('is_available',true) as $item)
<article style="background:#0d0b17;border:1px solid var(--line);border-radius:16px;padding:16px">
@if($item->image_path)<img src="{{ Storage::disk('public')->url($item->image_path) }}" alt="{{ $item->name }}" style="width:100%;height:160px;object-fit:cover;border-radius:10px">@endif
<span class="type">{{ $item->type==='product'?'პროდუქტი':'სერვისი' }}</span><h3>{{ $item->name }}</h3>
@if($item->description)<p class="muted">{{ $item->description }}</p>@endif
<p style="font-weight:800">{{ $item->price !== null ? number_format((float)$item->price,2).' '.$item->currency : 'ფასი შეთანხმებით' }}</p>
@if($item->track_inventory)<p class="muted">{{ (int)$item->stock_quantity > 0 ? 'მარაგშია: '.(int)$item->stock_quantity.' ერთეული' : 'ამოწურულია' }}</p>@endif
@if(!$item->track_inventory || (int)$item->stock_quantity > 0)
<form method="POST" data-track-form="order" action="{{ route('business.cart.add',[$business->slug,$item->id]) }}" style="display:flex;gap:8px;align-items:center">
@csrf
<input aria-label="რაოდენობა" name="quantity" type="number" min="1" max="100" value="1" required style="width:76px;padding:10px;border-radius:8px;border:1px solid var(--line);background:#090812;color:white">
<button class="action" type="submit" style="border:0;cursor:pointer">კალათაში დამატება</button>
</form>@else<p class="muted">პროდუქტი დროებით არ არის მარაგში.</p>@endif</article>
@empty<p class="muted">კატალოგი მალე შეივსება.</p>@endforelse
</div></section>
<footer>ეს გვერდი შექმნილია <strong>oneQR</strong>-ით · ONE SCAN. ENDLESS POSSIBILITIES.</footer></main>
<script>
(() => {
    const endpoint = document.querySelector('meta[name="oneqr-interaction-url"]')?.content;
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!endpoint || !token) return;

    const record = (type) => {
        if (!type) return;
        fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            keepalive: true,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify({ type }),
        }).catch(() => {});
    };

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[data-track]');
        if (link) record(link.dataset.track);
    }, { passive: true });

    document.addEventListener('submit', (event) => {
        const form = event.target.closest('form[data-track-form]');
        if (form) record(form.dataset.trackForm);
    }, true);
})();
</script>
</body></html>
