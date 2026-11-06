<!doctype html>
<html lang="ka">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>შეტყობინებები — oneQR</title>
<style>
:root{color-scheme:dark;--bg:#090812;--panel:#121020;--line:#29243d;--text:#f6f3ff;--muted:#aaa3bd;--purple:#9b72ff}
*{box-sizing:border-box}body{margin:0;background:radial-gradient(ellipse at 80% -20%,#3a286455,transparent 42%),var(--bg);color:var(--text);font:15px Inter,system-ui,sans-serif}
header{min-height:76px;padding:18px max(24px,calc((100vw - 1000px)/2));border-bottom:1px solid var(--line);display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap}
.logo{font-size:24px;font-weight:900}.logo span{color:var(--purple)}main{max-width:1000px;margin:46px auto;padding:0 24px}
h1{font-size:clamp(30px,5vw,42px);letter-spacing:-.05em;margin:0 0 8px}.muted{color:var(--muted);line-height:1.6}
a{color:#c8b6ff;text-decoration:none}.button{display:inline-flex;align-items:center;justify-content:center;padding:10px 14px;background:#39265f;border:1px solid #684bb0;border-radius:10px;color:#fff}
.list{display:grid;gap:12px;margin-top:24px}.item{padding:18px 20px;border:1px solid var(--line);border-radius:16px;background:linear-gradient(145deg,#171626,#10101a);display:flex;justify-content:space-between;align-items:flex-start;gap:16px}
.item.unread{border-color:#7155aa;background:linear-gradient(110deg,#241b3c,#121020)}.item h2{font-size:17px;margin:0 0 6px}.item p{margin:0 0 8px}.meta{font-size:12px;color:var(--muted)}
.empty{margin-top:26px;padding:28px;border:1px dashed var(--line);border-radius:16px;color:var(--muted)}
form{margin:0}button{cursor:pointer;font:inherit}
@media(max-width:600px){main{margin:28px auto}header{padding:16px 20px}.item{display:block}.item .button{margin-top:12px}}
</style>
@include('partials.design-system')
</head>
<body>
<header><a class="logo" href="{{ route('dashboard') }}">one<span>QR</span></a><a class="button" href="{{ route('dashboard') }}">კაბინეტში დაბრუნება</a></header>
<main>
<div style="display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap">
<div><h1>შეტყობინებები</h1><p class="muted">შენი ბიზნესების ახალი შეკვეთები, მოთხოვნები და შეფასებები.</p></div>
@if($unreadCount>0)<form method="POST" action="{{ route('notifications.read-all') }}">@csrf @method('PATCH')<button class="button" type="submit">ყველას წაკითხვა ({{ $unreadCount }})</button></form>@endif
</div>
@if(session('status'))<p class="muted" role="status">{{ session('status') }}</p>@endif
@if($notifications->isEmpty())
<div class="empty">შეტყობინებები ჯერ არ გაქვს. ახალი შეკვეთები, მოთხოვნები და შეფასებები აქ გამოჩნდება.</div>
@else
<div class="list">
@foreach($notifications as $notification)
@php($data=$notification->data)
<article class="item {{ $notification->read_at ? '' : 'unread' }}">
<div>
<h2>{{ $data['title'] ?? 'ახალი შეტყობინება' }} @unless($notification->read_at)<span class="meta">● ახალი</span>@endunless</h2>
<p>{{ $data['message'] ?? '' }}</p>
<div class="meta">{{ $data['business_name'] ?? 'ბიზნესი' }} · {{ $notification->created_at->format('d.m.Y H:i') }}</div>
</div>
@if(!empty($data['url']))
@if($notification->read_at)<a class="button" href="{{ $data['url'] }}">ნახვა</a>
@else<form method="POST" action="{{ route('notifications.read', $notification->id) }}">@csrf @method('PATCH')<button class="button" type="submit">ნახვა</button></form>@endif
@endif
</article>
@endforeach
</div>
<div style="margin-top:24px">{{ $notifications->links() }}</div>
@endif
</main>
</body>
</html>
