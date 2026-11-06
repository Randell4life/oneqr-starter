<!doctype html>
<html lang="ka"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{ $page->name }} — Smart Page</title>@include('partials.design-system')
<style>.editor-grid{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:18px}.block-editor{padding:18px;border:1px solid var(--oq-line);border-radius:14px;margin:12px 0;background:#0e0d18}.block-editor label{display:block;margin:9px 0 5px}.block-editor input,.block-editor textarea,.block-editor select{width:100%}.two{display:grid;grid-template-columns:1fr 1fr;gap:10px}.preview-frame{position:sticky;top:18px}.preview-screen{background:#090812;border:1px solid var(--oq-line);border-radius:22px;padding:16px;min-height:280px;overflow:hidden}.preview-block{background:#171626;border:1px solid #29243d;border-radius:13px;padding:13px;margin:10px 0;overflow-wrap:anywhere}.preview-block img{width:100%;max-height:180px;object-fit:cover;border-radius:9px}.preview-block p{white-space:pre-line;color:#aaa3bd}.preview-actions{display:flex;gap:8px;flex-wrap:wrap}.preview-actions span{background:#8057df;padding:8px 11px;border-radius:8px;font-size:12px}.block-editor input[type=file]{width:100%;font-size:12px}.block-upload-preview{display:block;max-width:140px;max-height:90px;object-fit:cover;border-radius:8px;margin-top:8px}@media(max-width:850px){.editor-grid{grid-template-columns:1fr}}</style><style>
body{background:radial-gradient(ellipse at 82% -18%,#54368b44,transparent 42%),#080711!important}
header.nav{position:sticky;top:0;z-index:20}
.page-heading{padding:24px 28px;border:1px solid #302a49;border-radius:22px;background:linear-gradient(115deg,#171329,#100f1b 68%,#1d1530);box-shadow:0 18px 50px #0004}
.page-heading h1{font-size:clamp(30px,4.5vw,44px);margin:0 0 8px}
.editor-grid{grid-template-columns:minmax(0,1fr) minmax(300px,370px);align-items:start;gap:22px}
.editor-grid>form{min-width:0}
.editor-grid>form>.surface{box-shadow:0 14px 38px #0003!important}
.block-editor{position:relative;background:linear-gradient(145deg,#171626,#10101a)!important;border-color:#35304d!important;border-radius:16px!important;padding:20px!important;box-shadow:0 8px 24px #0002}
.block-editor+.block-editor{margin-top:15px}
.block-editor>.section-heading{padding-bottom:12px;border-bottom:1px solid #302c43}
.block-editor>.section-heading strong{font-size:14px;color:#e7ddff}
.block-editor label{font-size:12px}
.block-editor input:not([type=checkbox]),.block-editor textarea,.block-editor select{min-height:42px}
.block-editor textarea{resize:vertical}
.preview-frame{top:92px}
.preview-screen{background:linear-gradient(160deg,#0b0a14,#151225)!important;box-shadow:inset 0 0 0 1px #ffffff08}
.preview-block{background:linear-gradient(145deg,#211c34,#151321)!important;border-color:#40365d!important;box-shadow:0 6px 18px #0003}
.preview-actions span{background:linear-gradient(100deg,#7144e8,#9b72ff)!important}
.editor-grid aside>.surface{box-shadow:0 14px 38px #0003!important}
.editor-grid aside h2{font-size:18px}
.editor-grid aside input:not([type=checkbox]){min-height:42px}
.editor-grid aside .two{gap:9px}
.editor-grid aside form>button{width:100%}
@media(max-width:850px){.editor-grid{grid-template-columns:1fr}.preview-frame{position:static;top:auto}.page-heading{padding:20px}}
@media(max-width:560px){.block-editor{padding:14px!important}.block-editor .section-heading{align-items:flex-start}.block-editor .section-heading .btn{padding:7px 9px!important}.editor-grid aside>.surface{padding:16px!important}}
</style>
</head><body><header class="nav"><a class="logo" href="{{ route('dashboard') }}">one<span>QR</span></a><a href="{{ route('business.smart-pages.index',$business) }}">← გვერდების სია</a></header>
<main><div class="page-heading"><div><p class="eyebrow">PAGE BUILDER</p><h1>{{ $page->name }}</h1><p class="muted">ბლოკების დამატება, დამალვა და თანმიმდევრობის მართვა.</p></div><a class="btn" href="{{ route('business.public',$business->slug) }}" target="_blank">Preview ↗</a></div>
@if(session('status'))<div class="notice">{{ session('status') }}</div>@endif @if($errors->any())<div class="danger" style="padding:14px;border-radius:12px">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div class="editor-grid"><form method="POST" enctype="multipart/form-data" action="{{ route('business.smart-pages.update',[$business,$page]) }}">@csrf @method('PUT')
<section class="surface"><label>გვერდის სახელი</label><input name="name" value="{{ old('name',$page->name) }}" required maxlength="120" style="width:100%;margin:8px 0 16px"><label><input type="checkbox" name="is_published" value="1" @checked(old('is_published',$page->is_published))> გვერდის გამოქვეყნება</label></section>
<div id="blocks">
@foreach($page->blocks as $i=>$block)
<div class="block-editor" data-block><input type="hidden" name="blocks[{{ $i }}][id]" value="{{ $block->id }}"><div class="section-heading"><strong>ბლოკი #{{ $i+1 }}</strong><button type="button" class="btn btn-small" onclick="moveBlock(this,-1)">↑</button> <button type="button" class="btn btn-small" onclick="moveBlock(this,1)">↓</button> <button type="button" class="btn btn-small" onclick="this.closest('[data-block]').remove();renumber();updatePreview()">წაშლა</button></div>
<div class="two"><div><label>ტიპი</label><select name="blocks[{{ $i }}][type]">@foreach(['heading'=>'სათაური','text'=>'ტექსტი','image'=>'სურათის ბმული','button'=>'ღილაკი','contact'=>'კონტაქტი','catalog'=>'კატალოგი','hours'=>'სამუშაო საათები','inquiry'=>'მოთხოვნის ფორმა'] as $value=>$label)<option value="{{ $value }}" @selected($block->type===$value)>{{ $label }}</option>@endforeach</select></div><div><label>ბლოკის სათაური</label><input name="blocks[{{ $i }}][title]" value="{{ $block->title }}"></div></div>
<label>ტექსტი / სურათის URL / სამუშაო საათები</label><textarea name="blocks[{{ $i }}][text]" rows="3">{{ $block->content['text'] ?? '' }}</textarea>
<label>ღილაკის URL ან გარე სურათის URL</label><input name="blocks[{{ $i }}][url]" type="url" value="{{ $block->content['url'] ?? '' }}" placeholder="https://..."><label>სურათის ატვირთვა (JPG, PNG, WEBP · მაქს. 5MB)</label><input name="blocks[{{ $i }}][image]" type="file" accept="image/jpeg,image/png,image/webp" data-image-upload>@if(!empty($block->content['image']))<img class="block-upload-preview" src="{{ Storage::disk('public')->url($block->content['image']) }}" alt="ატვირთული სურათი">@endif
<label><input type="checkbox" name="blocks[{{ $i }}][is_visible]" value="1" @checked($block->is_visible)> ხილული იყოს</label></div>
@endforeach
</div><button type="button" class="btn" onclick="addBlock()">+ ბლოკის დამატება</button> <button class="btn" type="submit">შენახვა და გამოქვეყნება</button>
</form>
<aside><section class="surface preview-frame"><div class="section-heading"><h2>Live Preview</h2><span class="status-pill success-pill">LIVE</span></div><p class="muted">ცვლილებები აქ მყისიერად გამოჩნდება — შენახვამდე.</p><div class="preview-screen"><div style="font-size:11px;color:#aaa3bd;margin-bottom:12px">oneQR · {{ $business->name }}</div><div id="live-preview"><p class="muted">დაამატე ბლოკი გვერდის სანახავად.</p></div></div><div class="muted" style="margin-top:10px;font-size:11px">ეს არის რედაქტორის სწრაფი Preview. საბოლოო გვერდი გამოქვეყნების შემდეგ გადაამოწმე ცალკე.</div></section><section class="surface"><h2>ავტომატური განრიგი</h2><p class="muted">განრიგი ამ ვერსიას ჩართავს მითითებულ დღეებსა და საათებში. დრო ბიზნესის დროის სარტყლით განისაზღვრება: {{ $business->timezone }}.</p>
<form method="POST" action="{{ route('business.smart-pages.schedules.store',[$business,$page]) }}">@csrf
<label>განრიგის სახელი</label><input name="name" required maxlength="120" placeholder="მაგ. დილის შეთავაზება" style="width:100%;margin:7px 0 12px">
<label>დღეები</label><p class="muted">თუ დღეებს არ მონიშნავ, განრიგი ყოველდღე იმოქმედებს.</p><div style="display:grid;grid-template-columns:repeat(2,1fr);gap:5px;margin:7px 0 12px">@foreach([1=>'ორშაბათი',2=>'სამშაბათი',3=>'ოთხშაბათი',4=>'ხუთშაბათი',5=>'პარასკევი',6=>'შაბათი',0=>'კვირა'] as $day=>$label)<label><input type="checkbox" name="weekdays[]" value="{{ $day }}"> {{ $label }}</label>@endforeach</div>
<div class="two"><div><label>დაწყება</label><input type="time" name="starts_at"></div><div><label>დასრულება</label><input type="time" name="ends_at"></div><div><label>თარიღიდან</label><input type="date" name="starts_on"></div><div><label>თარიღამდე</label><input type="date" name="ends_on"></div></div>
<label>პრიორიტეტი (1–100)</label><input type="number" name="priority" min="1" max="100" value="10" required style="width:100%;margin:7px 0 12px">
<label><input type="checkbox" name="is_override" value="1"> სპეციალური კამპანია (უმაღლესი პრიორიტეტი)</label><button class="btn" style="width:100%;margin-top:12px">განრიგის დამატება</button></form></section>
<section class="surface"><h2>აქტიური წესები</h2>@forelse($page->schedules as $schedule)<div style="padding:12px 0;border-top:1px solid var(--oq-line)"><strong>{{ $schedule->name }}</strong><p class="muted">პრიორიტეტი {{ $schedule->priority }} · {{ $schedule->starts_at ?: 'მთელი დღე' }}–{{ $schedule->ends_at ?: 'მთელი დღე' }} · {{ $schedule->is_override?'სპეციალური':'ჩვეულებრივი' }}</p><form method="POST" action="{{ route('business.smart-pages.schedules.destroy',[$business,$schedule->id]) }}">@csrf @method('DELETE')<button class="btn btn-small">წაშლა</button></form></div>@empty<p class="muted">განრიგი ჯერ არ გაქვს.</p>@endforelse</section></aside></div></main>
<script>
let nextBlock={{ $page->blocks->count() }};
function renumber(){document.querySelectorAll('[data-block]').forEach((el,i)=>{el.querySelector('strong').textContent='ბლოკი #'+(i+1);el.querySelectorAll('[name]').forEach(input=>input.name=input.name.replace(/blocks\[\d+\]/,'blocks['+i+']'));});nextBlock=document.querySelectorAll('[data-block]').length;}
function moveBlock(button,direction){const block=button.closest('[data-block]');const target=direction<0?block.previousElementSibling:block.nextElementSibling;if(!target)return;if(direction<0)block.parentNode.insertBefore(block,target);else block.parentNode.insertBefore(target,block);renumber();updatePreview();}
function addBlock(){const i=nextBlock++;const el=document.createElement('div');el.className='block-editor';el.setAttribute('data-block','');el.innerHTML='<input type="hidden" name="blocks['+i+'][id]" value=""><div class="section-heading"><strong>ბლოკი #'+(i+1)+'</strong><span><button type="button" class="btn btn-small" data-up>↑</button> <button type="button" class="btn btn-small" data-down>↓</button> <button type="button" class="btn btn-small" data-remove>წაშლა</button></span></div><div class="two"><div><label>ტიპი</label><select name="blocks['+i+'][type]"><option value="heading">სათაური</option><option value="text">ტექსტი</option><option value="image">სურათის ბმული</option><option value="button">ღილაკი</option><option value="contact">კონტაქტი</option><option value="catalog">კატალოგი</option><option value="hours">სამუშაო საათები</option><option value="inquiry">მოთხოვნის ფორმა</option></select></div><div><label>ბლოკის სათაური</label><input name="blocks['+i+'][title]"></div></div><label>ტექსტი / სურათის URL / საათები</label><textarea name="blocks['+i+'][text]" rows="3"></textarea><label>ღილაკის ან გარე სურათის URL</label><input name="blocks['+i+'][url]" type="url"><label>სურათის ატვირთვა (JPG, PNG, WEBP · მაქს. 5MB)</label><input name="blocks['+i+'][image]" type="file" accept="image/jpeg,image/png,image/webp" data-image-upload><label><input type="checkbox" name="blocks['+i+'][is_visible]" value="1" checked> ხილული იყოს</label>';el.querySelector('[data-up]').onclick=e=>moveBlock(e.currentTarget,-1);el.querySelector('[data-down]').onclick=e=>moveBlock(e.currentTarget,1);el.querySelector('[data-remove]').onclick=()=>{el.remove();renumber()};document.getElementById('blocks').appendChild(el);updatePreview();}

let previewObjectUrls=[];
function updatePreview(){
 previewObjectUrls.forEach(url=>URL.revokeObjectURL(url));previewObjectUrls=[];
 const root=document.getElementById('live-preview'); if(!root)return; root.replaceChildren();
 document.querySelectorAll('[data-block]').forEach((el)=>{
  const type=el.querySelector('[name$="[type]"]')?.value||'text';
  const title=el.querySelector('[name$="[title]"]')?.value||'';
  const body=el.querySelector('[name$="[text]"]')?.value||'';
  const url=el.querySelector('[name$="[url]"]')?.value||'';
  const visible=el.querySelector('[name$="[is_visible]"]')?.checked;
  const file=el.querySelector('[data-image-upload]')?.files?.[0];
  const oldImage=el.querySelector('.block-upload-preview')?.src;
  if(!visible)return;
  const card=document.createElement('div');card.className='preview-block';
  if(type==='heading'){const h=document.createElement('h3');h.textContent=body||title;card.append(h);}
  else if(type==='text'||type==='hours'){if(title){const h=document.createElement('strong');h.textContent=title;card.append(h);}const p=document.createElement('p');p.textContent=body;card.append(p);}
  else if(type==='image'){const img=document.createElement('img');if(file){img.src=URL.createObjectURL(file);previewObjectUrls.push(img.src);}else if(oldImage)img.src=oldImage;else if(url && /^https?:\/\//i.test(url))img.src=url;else img.alt='სურათი არ არის არჩეული';card.append(img);if(title){const p=document.createElement('p');p.textContent=title;card.append(p);}}
  else if(type==='button'){const action=document.createElement('span');action.textContent=title||'ღილაკი';card.append(action);}
  else if(type==='catalog'){const h=document.createElement('strong');h.textContent=title||'კატალოგი';const p=document.createElement('p');p.textContent='პროდუქტებისა და სერვისების კატალოგი';card.append(h,p);}
  else if(type==='contact'){const h=document.createElement('strong');h.textContent=title||'დაგვიკავშირდი';const p=document.createElement('p');p.textContent='საკონტაქტო ინფორმაცია ბიზნესის პროფილიდან';card.append(h,p);}
  else if(type==='inquiry'){const h=document.createElement('strong');h.textContent=title||'მოთხოვნის გაგზავნა';const p=document.createElement('p');p.textContent='სახელი · ტელეფონი · სასურველი თარიღი · შეტყობინება';card.append(h,p);}
  root.append(card);
 });
 if(!root.children.length){const p=document.createElement('p');p.className='muted';p.textContent='ყველა ბლოკი დამალულია.';root.append(p);}
}
document.addEventListener('input',e=>{if(e.target.closest('[data-block]'))updatePreview();});
document.addEventListener('change',e=>{if(e.target.closest('[data-block]'))updatePreview();});
updatePreview();
</script></body></html>