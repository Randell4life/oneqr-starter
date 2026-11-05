<style>
:root{color-scheme:dark;--oq-bg:#080811;--oq-surface:#11111d;--oq-surface-2:#171627;--oq-line:#29283e;--oq-text:#f6f4ff;--oq-muted:#a5a2ba;--oq-purple:#9568ff;--oq-purple-2:#7044df;--oq-green:#57d6a3;--oq-amber:#f2bd69;--oq-red:#f17c9b;--oq-radius:18px;--oq-shadow:0 18px 55px #0005}
*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{background:radial-gradient(ellipse at 85% -15%,#30205b55,transparent 38%),var(--oq-bg)!important;color:var(--oq-text)!important;font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif!important;line-height:1.55}
a{color:#cbb9ff;text-decoration:none;transition:color .18s ease}
a:hover{color:#fff}
h1,h2,h3,h4{color:var(--oq-text);letter-spacing:-.035em}
h1{font-size:clamp(1.9rem,4vw,2.8rem);font-weight:850;line-height:1.08}
h2{font-size:1.25rem;font-weight:750}
p{line-height:1.65}
main{max-width:1160px!important;margin:36px auto!important;padding-left:22px!important;padding-right:22px!important}
.panel,.card,.surface,.plan,.stat-card,.empty,.preview{background:linear-gradient(145deg,#171626,#10101a)!important;border:1px solid var(--oq-line)!important;border-radius:var(--oq-radius)!important;box-shadow:0 8px 28px #0002}
.panel,.card,.surface,.plan{padding:22px!important}
.nav,.app-nav,header.nav{height:auto;min-height:70px;border-bottom:1px solid var(--oq-line)!important;background:#090912eF!important;backdrop-filter:blur(18px);padding:14px max(22px,calc((100vw - 1160px)/2))!important}
.logo,.brand{font-weight:900;letter-spacing:-.06em;color:#fff!important;font-size:24px}
.logo span,.brand span{color:var(--oq-purple)!important}
button,.btn,.button{background:linear-gradient(110deg,var(--oq-purple-2),var(--oq-purple))!important;border:1px solid #a78bfa66!important;color:#fff!important;border-radius:11px!important;padding:11px 16px!important;font-weight:750!important;box-shadow:0 6px 18px #7044df22;transition:transform .18s ease,filter .18s ease}
button:hover,.btn:hover,.button:hover{filter:brightness(1.12);transform:translateY(-1px)}
button:focus-visible,a:focus-visible,input:focus-visible,select:focus-visible,textarea:focus-visible{outline:2px solid #b69cff;outline-offset:3px}
input,select,textarea,.field{background:#0b0b15!important;border:1px solid #35334d!important;border-radius:10px!important;color:var(--oq-text)!important;padding:12px 13px!important;font:inherit}
input::placeholder,textarea::placeholder{color:#77748f}
label{color:#d4d0e4!important;font-size:13px;font-weight:650}
.muted,small{color:var(--oq-muted)!important}
hr{border-color:var(--oq-line)}
table{border-collapse:collapse;width:100%}
th{color:#aaa5c1;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.09em;padding:13px}
td{padding:15px 13px;border-top:1px solid var(--oq-line);vertical-align:middle}
tr:hover td{background:#ffffff05}
.notice,.success{border-radius:12px;padding:13px 16px;margin:14px 0;background:#13251f!important;border:1px solid #28654f!important;color:#a9f1d1!important}
.danger{background:#2b1722!important;border:1px solid #71364c!important;color:#ffd1dc!important}
.app-nav{display:flex;align-items:center;justify-content:space-between;gap:18px;flex-wrap:wrap}
.app-nav nav{display:flex;gap:18px;flex-wrap:wrap}
.app-main{max-width:1160px;margin:38px auto;padding:0 22px}
.page-heading{display:flex;justify-content:space-between;align-items:flex-end;gap:20px;flex-wrap:wrap;margin:18px 0 26px}
.eyebrow{color:#a78bfa;font-size:11px;font-weight:800;letter-spacing:.16em;margin:0 0 10px}
.stats-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin:22px 0}
.stat-card{padding:20px!important;display:flex;flex-direction:column;gap:6px}
.stat-card strong{font-size:32px;letter-spacing:-.05em}
.stat-label{font-size:12px;color:#c2bdd5}
.section-heading{display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:16px}
.surface{margin:18px 0}
.table-wrap{width:100%;overflow-x:auto}
.data-table{min-width:780px}
.data-table td small,.data-table td strong{display:block}
.status-pill{display:inline-flex;padding:5px 10px;border-radius:99px;background:#28263a;color:#c4bfd6;font-size:11px;font-weight:750}
.success-pill{background:#16372d;color:#9be8c4}
.warning-pill{background:#3b2d19;color:#ffd58c}
.danger-pill{background:#401e2b;color:#ffb1c6}
.stock-form{display:flex;align-items:center;gap:7px;min-width:370px}
.stock-form input,.stock-form select{min-width:75px;padding:8px!important}
.stock-form input[type=text]{min-width:110px}
.btn-small{padding:8px 11px!important;font-size:12px}
.movement-list{display:grid;gap:0}
.movement-row{display:grid;grid-template-columns:minmax(0,1fr) auto auto;align-items:center;gap:18px;padding:14px 0;border-top:1px solid var(--oq-line)}
.movement-row small{display:block}
.movement-in{color:var(--oq-green);font-weight:800}
.movement-out{color:var(--oq-red);font-weight:800}
.empty-state{text-align:center;padding:28px!important;color:var(--oq-muted)}
.metric,.value{letter-spacing:-.05em}
@media(max-width:850px){.stats-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.page-heading{align-items:flex-start}}
@media(max-width:560px){main,.app-main{padding-left:15px!important;padding-right:15px!important;margin-top:22px!important}.stats-grid{grid-template-columns:1fr 1fr;gap:9px}.stat-card{padding:14px!important}.stat-card strong{font-size:25px}.movement-row{grid-template-columns:1fr auto;gap:7px}.movement-row>span:last-child{grid-column:1/-1}.panel,.card,.surface{padding:16px!important}.app-nav nav{gap:10px;font-size:13px}}
</style>