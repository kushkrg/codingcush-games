"""Builds the static pages of games.codingcush.com into ../games-site.
Usage: python3 build_site.py     (edit GAMES below to add a game, then run again)"""
import json
from datetime import date
from pathlib import Path

SITE = "https://games.codingcush.com"
OUT = Path(__file__).resolve().parent.parent
TODAY = date.today().isoformat()

CSS = """
.notice{display:block;position:relative;z-index:3;text-align:center;padding:10px 16px;font:600 14px/1.4 system-ui,sans-serif;color:#06131f;text-decoration:none}
.notice-info{background:#6fd8ff}.notice-good{background:#7ef0b0}.notice-warn{background:#ffcf5a}
a.notice:hover{text-decoration:underline}
:root{--bg:#070b1a;--panel:rgba(14,21,48,.82);--line:#26335f;--text:#eef2ff;--muted:#b4bfdf;--accent:#6fd8ff;--gold:#ffd166;--radius:18px}
*{box-sizing:border-box}
#sky{position:fixed;inset:0;width:100%;height:100%;z-index:-1;pointer-events:none;opacity:0;transition:opacity 1.2s}
html{scroll-behavior:smooth}
body{margin:0;background:var(--bg);color:var(--text);font:17px/1.65 system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;-webkit-font-smoothing:antialiased;
 background-image:radial-gradient(900px 500px at 85% -10%,rgba(95,212,255,.15),transparent 60%),radial-gradient(700px 500px at -10% 30%,rgba(140,90,255,.13),transparent 60%);background-repeat:no-repeat}
a{color:var(--accent)}
img{max-width:100%;height:auto;display:block}
.skip{position:absolute;left:-999px}.skip:focus{left:12px;top:12px;background:#fff;color:#000;padding:8px 12px;border-radius:8px;z-index:9}
:focus-visible{outline:3px solid var(--gold);outline-offset:3px;border-radius:6px}
.wrap{max-width:1120px;margin:0 auto;padding:0 22px}
header.top{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:18px 0}
.brand{font-weight:800;font-size:19px;letter-spacing:.06em;text-decoration:none;color:var(--text)}
.brand span{color:var(--accent)}
nav a{color:var(--muted);text-decoration:none;margin-left:20px;font-weight:600;font-size:15px;padding:8px 0;display:inline-block}
nav a:hover{color:var(--text)}
h1,h2,h3{line-height:1.15;margin:0;letter-spacing:.01em;text-shadow:0 2px 18px rgba(4,8,24,.9)}
.lead,.prose p,.crumbs{text-shadow:0 1px 10px rgba(4,8,24,.95)}
h1{font-size:clamp(34px,5.6vw,60px);font-weight:800}
h2{font-size:clamp(24px,3vw,30px);margin-bottom:16px}
h3{font-size:20px}
.hero{display:grid;grid-template-columns:1.05fr 1fr;gap:36px;align-items:center;padding:26px 0 48px}
.tag{display:inline-block;font-size:13px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--accent);border:1px solid var(--line);border-radius:99px;padding:5px 12px;margin-bottom:14px}
.lead{color:var(--muted);font-size:19px;max-width:48ch;margin:14px 0 24px}
.btn{display:inline-flex;align-items:center;gap:10px;background:linear-gradient(180deg,#8fe4ff,#45b4ee);color:#04121f;font-weight:800;text-decoration:none;padding:15px 28px;border-radius:14px;box-shadow:0 10px 30px rgba(63,180,240,.3);transition:transform .15s}
.btn:hover{transform:translateY(-2px)}
.btn.ghost{background:none;color:var(--text);border:1px solid var(--line);box-shadow:none;margin-left:10px}
.shot{position:relative;border-radius:var(--radius);overflow:hidden;border:1px solid var(--line);box-shadow:0 30px 80px rgba(0,0,0,.5);display:block;aspect-ratio:1366/768;background:#050816}
.shot .badge{position:absolute;right:14px;bottom:14px;background:rgba(7,11,26,.85);border:1px solid var(--line);padding:8px 14px;border-radius:10px;font-weight:800;font-size:14px;letter-spacing:.1em;color:var(--text)}
section{padding:22px 0 40px}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:22px}
.card{background:var(--panel);border:1px solid var(--line);border-radius:var(--radius);overflow:hidden;text-decoration:none;color:var(--text);display:flex;flex-direction:column;transition:transform .18s,border-color .18s}
a.card:hover{transform:translateY(-4px);border-color:var(--accent)}
.card .body{padding:16px 18px 20px}
.card p{color:var(--muted);margin:6px 0 12px;font-size:15.5px}
.chips{display:flex;flex-wrap:wrap;gap:6px;padding:0;margin:0;list-style:none}
.chips li{font-size:13px;color:var(--muted);border:1px solid var(--line);border-radius:99px;padding:3px 10px}
.card.soon{align-items:center;justify-content:center;text-align:center;min-height:280px;border-style:dashed;color:var(--muted);padding:24px}
.cols{display:grid;grid-template-columns:1fr 1fr;gap:22px}
.panel{background:var(--panel);border:1px solid var(--line);border-radius:var(--radius);padding:22px}
.panel h3{margin-bottom:12px}
.board{list-style:none;margin:0;padding:0;min-height:200px}
.board li{display:flex;gap:12px;padding:9px 0;border-bottom:1px solid var(--line)}
.board li:last-child{border:0}
.board .place{width:26px;color:var(--muted);font-weight:700}
.board li:first-child .place,.board li:first-child .score{color:var(--gold)}
.board .name{flex:1}
.board .score{font-variant-numeric:tabular-nums;font-weight:700}
.steps{margin:0;padding-left:22px;color:var(--muted)}
.steps li{margin-bottom:8px}
.steps b,.prose b{color:var(--text)}
.prose{max-width:72ch}
.prose p,.prose li{color:var(--muted)}
.prose p{margin:0 0 14px}
table{width:100%;border-collapse:collapse;font-size:15.5px}
caption{text-align:left;color:var(--muted);padding-bottom:8px}
th,td{text-align:left;padding:10px 12px;border-bottom:1px solid var(--line)}
th{color:var(--muted);font-weight:700;font-size:13px;letter-spacing:.06em;text-transform:uppercase}
.scroll{overflow-x:auto}
kbd{background:#182247;border:1px solid var(--line);border-bottom-width:3px;border-radius:7px;padding:1px 8px;font:600 14px ui-monospace,Menlo,Consolas,monospace;color:var(--text)}
.shots{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,250px));gap:16px}
.shots img{border-radius:14px;border:1px solid var(--line);width:100%}
details{background:var(--panel);border:1px solid var(--line);border-radius:14px;padding:4px 18px;margin-bottom:10px}
summary{cursor:pointer;font-weight:700;padding:12px 0;font-size:17px}
details p{color:var(--muted);margin:0 0 14px}
.crumbs{color:var(--muted);font-size:14px;padding:4px 0 0}
.crumbs a{color:var(--muted)}
.note{border-left:3px solid var(--gold);padding:6px 14px;color:var(--muted);margin:0 0 18px}
footer{border-top:1px solid var(--line);color:var(--muted);padding:22px 0 40px;font-size:14.5px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px}
footer a{color:var(--muted)}
@media (max-width:820px){.hero,.cols{grid-template-columns:1fr}.hero{padding-top:8px}nav a{margin-left:14px}.btn.ghost{margin:10px 0 0}}
@media (prefers-reduced-motion:reduce){*{transition:none!important;scroll-behavior:auto}}
"""

BOARD_JS = """
(async function(){const b=document.getElementById("board");if(!b)return;
const one=(t)=>{b.textContent="";const li=document.createElement("li"),s=document.createElement("span");s.className="name";s.textContent=t;li.append(s);b.append(li)};
try{const r=await fetch("/api/index.php?action=scores&game=%s&level=1&limit=8");const rows=(await r.json()).scores||[];
if(!rows.length){one("No scores yet. Be the first!");return}
b.textContent="";rows.forEach((x,i)=>{const li=document.createElement("li");[["place",i+1],["name",x.name],["score",Number(x.score).toLocaleString("en")]].forEach(([c,t])=>{const s=document.createElement("span");s.className=c;s.textContent=t;li.append(s)});b.append(li)})}
catch(e){one("The leaderboard is unavailable right now.")}})();
"""


SKY_JS = r"""
(function(){
var c=document.getElementById("sky");if(!c||!c.getContext)return;
var still=matchMedia("(prefers-reduced-motion: reduce)").matches,x=c.getContext("2d"),W,H,D,stars=[],gal,gs,left=0,clouds=0,t=0,mx=0,my=0,px=0,py=0,cx=-999,cy=-999,run=true;
function rnd(a,b){return a+Math.random()*(b-a)}
function galaxy(){ // drawn once into its own canvas, then only rotated: cheap on every frame
 gs=Math.round(Math.min(Math.max(W,H)*1.15,1500)*D);gal=document.createElement("canvas");gal.width=gal.height=gs;
 var g=gal.getContext("2d"),m=gs/2;
 var core=g.createRadialGradient(m,m,0,m,m,m*.55);core.addColorStop(0,"rgba(255,240,215,.9)");core.addColorStop(.06,"rgba(255,205,160,.42)");core.addColorStop(.25,"rgba(130,110,255,.16)");core.addColorStop(.6,"rgba(70,100,255,.05)");core.addColorStop(1,"rgba(60,90,255,0)");
 g.fillStyle=core;g.fillRect(0,0,gs,gs);g.globalCompositeOperation="lighter";left=W<700?3600:9000;clouds=70;paint();
}
function paint(){ // a slice per frame, so building the picture never makes the page stutter
 var g=gal.getContext("2d"),m=gs/2,i,r,a,k,n=Math.min(left,500);
 for(i=0;i<n;i++){k=Math.pow(Math.random(),.62);r=k*m*.96;a=(left-i)%2*3.1416+k*5.6+rnd(-.26,.26)*(1.15-k)+(Math.random()<.18?rnd(-1.2,1.2):0);
  g.fillStyle="rgba("+Math.round(150+105*(1-k))+","+Math.round(170+50*(1-k))+",255,"+(0.3+Math.random()*.65).toFixed(2)+")";
  var s=(Math.random()<.05?2.8:rnd(.8,1.9))*D;g.fillRect(m+Math.cos(a)*r+rnd(-9,9)*D*k,m+Math.sin(a)*r+rnd(-9,9)*D*k,s,s);}
 left-=n;
 for(i=0;i<6&&clouds>0;i++,clouds--){k=Math.pow(Math.random(),.7);r=k*m*.85;a=(clouds%2)*3.1416+k*5.6+rnd(-.15,.15);var bx=m+Math.cos(a)*r,by=m+Math.sin(a)*r,br=rnd(30,80)*D,c2=g.createRadialGradient(bx,by,0,bx,by,br);c2.addColorStop(0,clouds%3?"rgba(90,160,255,.16)":"rgba(255,110,190,.14)");c2.addColorStop(1,"rgba(0,0,0,0)");g.fillStyle=c2;g.fillRect(bx-br,by-br,br*2,br*2);}
 if(left>0||clouds>0){if(still)draw();requestAnimationFrame(paint)}else if(still)draw();
}
function size(){D=Math.min(window.devicePixelRatio||1,1.5);W=innerWidth;H=innerHeight;c.width=W*D;c.height=H*D;
 stars=[];var n=Math.min(260,Math.round(W*H/5200)),i;for(i=0;i<n;i++)stars.push({x:Math.random(),y:Math.random(),z:rnd(.2,1),p:rnd(0,6.3),s:rnd(.6,1.7)});galaxy();draw();}
function draw(){
 x.setTransform(1,0,0,1,0,0);x.clearRect(0,0,c.width,c.height);
 px+=(mx-px)*.05;py+=(my-py)*.05;
 var gx=(W*(W<700?.5:.56)+px*-46)*D,gy=(H*.46+py*-32-scrollY*.1)*D,i,s,sx,sy,d,b;
 x.save();x.translate(gx,gy);x.rotate(-.35);x.scale(1,.5);x.rotate(t*.03);x.globalAlpha=W<700?.6:.95;x.drawImage(gal,-gs/2,-gs/2);x.restore();
 for(i=0;i<stars.length;i++){s=stars[i];
  sx=((s.x*W+px*-70*s.z)%W+W)%W;sy=((s.y*H+py*-50*s.z-scrollY*.25*s.z)%H+H)%H;
  d=Math.hypot(sx-cx,sy-cy);b=.35+.3*Math.sin(t*1.6+s.p)+(d<150?(1-d/150)*.9:0);
  if(d<150){sx+=(sx-cx)/d*(150-d)*.12;sy+=(sy-cy)/d*(150-d)*.12}
  x.globalAlpha=Math.min(1,b*s.z+.15);x.fillStyle=d<150?"#bfeaff":"#fff";var r=s.s*s.z*D*(d<150?1.5:1);x.fillRect(sx*D,sy*D,r,r);}
 if(cx>-900){var h=x.createRadialGradient(cx*D,cy*D,0,cx*D,cy*D,190*D);h.addColorStop(0,"rgba(111,216,255,.11)");h.addColorStop(1,"rgba(111,216,255,0)");x.globalAlpha=1;x.fillStyle=h;x.fillRect((cx-190)*D,(cy-190)*D,380*D,380*D);}
}
function loop(){if(!run)return;t+=1/60;draw();requestAnimationFrame(loop)}
function point(e){var p=e.touches?e.touches[0]:e;if(!p)return;cx=p.clientX;cy=p.clientY;mx=cx/W*2-1;my=cy/H*2-1;if(still)draw()}
function start(){size();c.style.opacity=1;if(!still)requestAnimationFrame(loop);
 addEventListener("pointermove",point,{passive:true});addEventListener("touchmove",point,{passive:true});
 addEventListener("pointerleave",function(){cx=cy=-999});
 var rt;addEventListener("resize",function(){clearTimeout(rt);rt=setTimeout(size,160)});
 addEventListener("scroll",function(){if(still)draw()},{passive:true});
 document.addEventListener("visibilitychange",function(){var was=run;run=!document.hidden;if(run&&!was&&!still)requestAnimationFrame(loop)});}
// Wait until the page itself has finished loading, so the animation never delays the content.
function later(){setTimeout(function(){(window.requestIdleCallback||function(f){setTimeout(f,1)})(start)},600)}
if(document.readyState==="complete")later();else addEventListener("load",later);
})();
"""

ORG = {"@type": "Organization", "@id": "https://codingcush.com/#org", "name": "CodingCush", "url": "https://codingcush.com/"}


# Every page: counts the visit (anonymous, once per page per browser tab), counts clicks on
# Play and app installs, and shows the notice or maintenance message set in Admin > Games.
LIVE_JS = r"""
(function () {
  var where = location.pathname.replace(/^\/|\/$/g, '').replace(/[^a-z0-9]+/gi, '_').toLowerCase().slice(0, 16) || 'home';
  function send(name, detail) {
    try {
      var body = JSON.stringify({ game: 'hub', name: name, level: 0, detail: detail });
      if (navigator.sendBeacon) { navigator.sendBeacon('/api/index.php?action=event', new Blob([body], { type: 'text/plain' })); }
    } catch (e) { /* counting must never get in the way */ }
  }
  try {
    if (!sessionStorage.getItem('vy_seen_' + where)) { sessionStorage.setItem('vy_seen_' + where, '1'); send('page_view', where); }
  } catch (e) { send('page_view', where); }
  document.addEventListener('click', function (e) {
    var link = e.target.closest && e.target.closest('a[href*="/play/"]');
    if (link) { send('play_click', where); }
  });
  window.addEventListener('appinstalled', function () { send('install', 'hub'); });
  fetch('/api/index.php?action=site_config').then(function (r) { return r.json(); }).then(function (c) {
    var text = '', link = '', tone = 'info';
    if (c.maintenance && c.maintenance.on) { text = c.maintenance.message || 'The games are being updated. Please check back in a little while.'; tone = 'warn'; }
    else if (c.notice && c.notice.text) { text = c.notice.text; link = c.notice.link || ''; tone = c.notice.tone || 'info'; }
    if (!text) { return; }
    var bar = document.createElement(link ? 'a' : 'div');
    bar.className = 'notice notice-' + (/^(info|good|warn)$/.test(tone) ? tone : 'info');
    bar.textContent = text;
    if (link && /^(https?:)?\//.test(link)) { bar.href = link; }
    document.body.insertBefore(bar, document.body.firstChild.nextSibling);
  }).catch(function () {});
})();
"""


def page(path, title, description, body, schema, preload=""):
    url = SITE + path
    graph = json.dumps({"@context": "https://schema.org", "@graph": schema}, ensure_ascii=False, separators=(",", ":"))
    return f"""<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{title}</title>
<meta name="description" content="{description}">
<link rel="canonical" href="{url}">
<meta name="robots" content="index, follow, max-image-preview:large">
<meta property="og:type" content="website">
<meta property="og:site_name" content="CodingCush Games">
<meta property="og:title" content="{title}">
<meta property="og:description" content="{description}">
<meta property="og:url" content="{url}">
<meta property="og:image" content="{SITE}/assets/vyomastra-og.jpg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#070b1a">
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.png" type="image/png" sizes="64x64">
<link rel="icon" href="/icon-192.png" type="image/png" sizes="192x192">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="manifest" href="/site.webmanifest">
{preload}<style>{CSS.strip()}</style>
<script type="application/ld+json">{graph}</script>
</head>
<body>
<canvas id="sky" aria-hidden="true"></canvas>
<a class="skip" href="#main">Skip to content</a>
<div class="wrap">
<header class="top">
  <a class="brand" href="/">CODING<span>CUSH</span> GAMES</a>
  <nav aria-label="Main"><a href="/#games">Games</a><a href="/#leaderboard">Leaderboard</a><a href="https://codingcush.com/">Tutorials</a></nav>
</header>
<main id="main">
{body}
</main>
<footer><span>© {date.today().year} CodingCush. Free browser games made with the Godot engine.</span><span><a href="/">All games</a> · <a href="/privacy/">Privacy</a> · <a href="https://codingcush.com/">codingcush.com</a></span></footer>
</div>
<script>{SKY_JS.strip()}</script>
<script>{LIVE_JS.strip()}</script>
</body>
</html>
"""


HERO_IMG = """<img src="/assets/vyomastra-800.webp" srcset="/assets/vyomastra-480.webp 480w, /assets/vyomastra-800.webp 800w, /assets/vyomastra-1280.webp 1280w" sizes="(max-width: 820px) 94vw, 540px" width="1366" height="768" alt="Vyomastra gameplay: a blue fighter above Earth shooting at enemy ships near a spiral galaxy" fetchpriority="high" decoding="async">"""
PRELOAD = '<link rel="preload" as="image" href="/assets/vyomastra-800.webp" imagesrcset="/assets/vyomastra-480.webp 480w, /assets/vyomastra-800.webp 800w, /assets/vyomastra-1280.webp 1280w" imagesizes="(max-width: 820px) 94vw, 540px" fetchpriority="high">\n'

HOME_FAQ = [
    ("Are the games on CodingCush Games free?", "Yes. Every game on CodingCush Games is free to play in the browser. There is nothing to download or install, and an account is optional."),
    ("Do I need an account to play?", "No. You can play as a guest and your progress is saved in that browser. A free account saves your progress online, so it follows you to another device, and puts your best scores on the global leaderboard."),
    ("Which devices can run the games?", "The games run in a current version of Chrome, Edge, Firefox or Safari on a phone, tablet, laptop or desktop computer. You can play with touch, a keyboard or a gamepad."),
    ("What are the games made with?", "The games are made with Godot 4, a free and open-source game engine, and exported to WebAssembly so they run inside the browser."),
]

home_body = f"""
<div class="hero">
  <div>
    <span class="tag">Free · No download</span>
    <h1>Free browser games with saved progress</h1>
    <p class="lead">CodingCush Games is a collection of free games that run in your browser. They are built with the Godot engine, and a free account keeps your coins, unlocks and scores online.</p>
    <a class="btn" href="/vyomastra/play/">▶ Play Vyomastra</a><a class="btn ghost" href="#games">All games</a>
  </div>
  <a class="shot" href="/vyomastra/" aria-label="Vyomastra: game details and how to play">{HERO_IMG}<span class="badge">▶ PLAY</span></a>
</div>

<section id="games" aria-labelledby="games-h">
  <h2 id="games-h">Games</h2>
  <div class="grid">
    <a class="card" href="/vyomastra/">
      <img src="/assets/vyomastra-480.webp" width="480" height="270" alt="" loading="lazy" decoding="async">
      <div class="body">
        <h3>Vyomastra</h3>
        <p>A space shooter with five weapons, boss fights, three ships to unlock and paint jobs to buy.</p>
        <ul class="chips"><li>Shooter</li><li>Touch · Mouse · Keyboard · Gamepad</li><li>Leaderboard</li></ul>
      </div>
    </a>
    <div class="card soon"><div><h3>More games on the way</h3><p>New games appear here as they are finished.</p></div></div>
  </div>
</section>

<section id="leaderboard" aria-labelledby="board-h">
  <h2 id="board-h">Leaderboard</h2>
  <div class="cols">
    <div class="panel">
      <h3>Vyomastra · Level 1 top pilots</h3>
      <ol class="board" id="board" aria-live="polite"><li><span class="name">Loading…</span></li></ol>
    </div>
    <div class="panel">
      <h3>How to get on the leaderboard</h3>
      <ol class="steps">
        <li><b>Open a game</b> and choose <b>Account</b> in its menu.</li>
        <li><b>Create a free account</b> with an email and a password.</li>
        <li><b>Finish a run.</b> Your best score for each level is posted automatically.</li>
      </ol>
    </div>
  </div>
</section>

<section aria-labelledby="faq-h">
  <h2 id="faq-h">Questions and answers</h2>
  {''.join(f'<details><summary>{q}</summary><p>{a}</p></details>' for q, a in HOME_FAQ)}
</section>
<script>{BOARD_JS.strip() % 'vyomastra'}</script>
"""

home_schema = [
    {"@type": "WebSite", "@id": SITE + "/#site", "url": SITE + "/", "name": "CodingCush Games", "description": "Free browser games made with the Godot engine.", "publisher": {"@id": ORG["@id"]}, "inLanguage": "en"},
    ORG,
    {"@type": "ItemList", "name": "Games", "itemListElement": [{"@type": "ListItem", "position": 1, "url": SITE + "/vyomastra/", "name": "Vyomastra"}]},
    {"@type": "FAQPage", "mainEntity": [{"@type": "Question", "name": q, "acceptedAnswer": {"@type": "Answer", "text": a}} for q, a in HOME_FAQ]},
]

GAME_FAQ = [
    ("What is Vyomastra?", "Vyomastra is a free vertical space shooter that runs in the browser. You fly a fighter up the screen, shoot waves of enemy ships, collect weapon pick-ups, coins and diamonds, and fight a boss at the end of the level."),
    ("Is Vyomastra free?", "Yes. Vyomastra is free to play and has no purchases with real money. Coins and diamonds are earned only by playing."),
    ("How do I play Vyomastra?", "On a phone, drag a finger to move and the ship fires by itself. On a computer, move the mouse and the ship follows it, and hold the left button to fire; or move with the arrow keys or W A S D, hold Space or J to fire, and press Esc or P to pause. A gamepad also works: left stick or D-pad to move and the A button to fire."),
    ("How do I save my progress in Vyomastra?", "Progress saves automatically in your browser. To keep it online and use it on another device, open Account in the game menu and create a free account with an email and password."),
    ("How do I unlock new ships in Vyomastra?", "Open Hangar in the game menu. Bulwark costs 1,200 coins and Wisp costs 20 diamonds. Coins drop from every enemy you destroy; diamonds come from bosses and from clearing a level for the first time."),
    ("Can I play Vyomastra on a phone?", "Yes. On a phone or tablet, drag a finger anywhere on the screen to move the ship; it fires automatically while your finger is down. Tap the pause button at the top to pause."),
    ("How many levels does Vyomastra have?", "All eight levels are playable: Low Orbit, with the Gatekeeper boss; Rock Belt, an asteroid field with the Crusher boss; Ice Moon, where frost shots freeze your ship, with the Frost Manta boss; Storm Giant, with wind that pushes your ship and lightning to dodge, with the Storm Eye boss; The Hive, where you free captured pilots from webs while swarms of mites chase you, with the Hive Queen boss; Scrap Sea, an escort mission where you protect a cargo ship from raiders, with the Junk Titan boss; Armada, where you and two wingmen hold a line that enemies must not cross, with the Dreadnought boss; and The Core, the final mission, whose boss hides behind a shield until you destroy the nodes circling it. Each level unlocks when you beat the one before it."),
]

WEAPONS = [("Blaster", "Blue", "Starting weapon. Balanced speed and fire rate."), ("Rapid", "Green", "Fastest shots and fire rate, low damage per shot."),
           ("Heavy", "Red", "Slow, large shots that do four times the damage."), ("Wide", "Purple", "A fan of three, five or seven shots."),
           ("Pierce", "Cyan", "Shots pass through enemies and hit everything in line.")]
SHIPS = [("Arrow", "Free", "Balanced all-rounder."), ("Bulwark", "1,200 coins", "One extra life, but slower."), ("Wisp", "20 diamonds", "Fastest ship with the smallest hit area.")]

game_body = f"""
<p class="crumbs"><a href="/">Games</a> › Vyomastra</p>
<div class="hero">
  <div>
    <span class="tag">Space shooter · Free</span>
    <h1>Vyomastra</h1>
    <p class="lead">Vyomastra is a free space shooter you play in your browser. Fly up from Earth orbit, switch between five weapons, collect coins and diamonds, and beat the boss to unlock the next level. It plays on phones, tablets and computers.</p>
    <a class="btn" href="/vyomastra/play/">▶ Play now</a><a class="btn ghost" href="#how">How to play</a>
  </div>
  <a class="shot" href="/vyomastra/play/" aria-label="Play Vyomastra">{HERO_IMG}<span class="badge">▶ PLAY</span></a>
</div>

<section id="how" aria-labelledby="how-h">
  <h2 id="how-h">How to play Vyomastra</h2>
  <div class="scroll"><table>
    <caption>Controls</caption>
    <thead><tr><th scope="col">Action</th><th scope="col">Touch</th><th scope="col">Mouse</th><th scope="col">Keyboard</th><th scope="col">Gamepad</th></tr></thead>
    <tbody>
      <tr><th scope="row">Move</th><td>Drag a finger</td><td>Move the mouse</td><td><kbd>←</kbd> <kbd>↑</kbd> <kbd>↓</kbd> <kbd>→</kbd> or <kbd>W</kbd> <kbd>A</kbd> <kbd>S</kbd> <kbd>D</kbd></td><td>Left stick or D-pad</td></tr>
      <tr><th scope="row">Fire</th><td>Automatic while touching</td><td>Hold left button</td><td>Hold <kbd>Space</kbd> or <kbd>J</kbd></td><td>A button</td></tr>
      <tr><th scope="row">Pause</th><td>Pause button</td><td>Pause button</td><td><kbd>Esc</kbd> or <kbd>P</kbd></td><td>Start</td></tr>
      <tr><th scope="row">Fullscreen</th><td>—</td><td>—</td><td><kbd>F11</kbd> in the browser</td><td>—</td></tr>
    </tbody>
  </table></div>
</section>

<section aria-labelledby="weapons-h">
  <h2 id="weapons-h">Weapons and pick-ups</h2>
  <div class="prose"><p>Destroyed enemies drop pick-ups. A weapon pick-up switches your weapon; picking up the weapon you already hold raises its power level, up to level 3. A <b>P</b> pick-up also raises the power level and a <b>+</b> pick-up gives an extra life. Getting hit costs a life and one power level.</p></div>
  <div class="scroll"><table>
    <caption>The five weapons</caption>
    <thead><tr><th scope="col">Weapon</th><th scope="col">Shot colour</th><th scope="col">What it does</th></tr></thead>
    <tbody>{''.join(f'<tr><th scope="row">{n}</th><td>{c}</td><td>{d}</td></tr>' for n, c, d in WEAPONS)}</tbody>
  </table></div>
</section>

<section aria-labelledby="ships-h">
  <h2 id="ships-h">Ships, coins and diamonds</h2>
  <div class="prose"><p>Every enemy drops <b>coins</b>. Bosses drop <b>diamonds</b>, and clearing a level for the first time pays a diamond bonus. Spend them in the Hangar on ships and on eight paint colours. Nothing is sold for real money.</p></div>
  <div class="scroll"><table>
    <caption>Ships in the Hangar</caption>
    <thead><tr><th scope="col">Ship</th><th scope="col">Price</th><th scope="col">Strength</th></tr></thead>
    <tbody>{''.join(f'<tr><th scope="row">{n}</th><td>{p}</td><td>{d}</td></tr>' for n, p, d in SHIPS)}</tbody>
  </table></div>
  <div class="shots" style="margin-top:20px">
    <img src="/assets/vyomastra-boss.webp" width="405" height="720" loading="lazy" decoding="async" alt="Vyomastra boss fight against the Gatekeeper, with its health bar at the top">
    <img src="/assets/vyomastra-hangar.webp" width="405" height="720" loading="lazy" decoding="async" alt="Vyomastra Hangar screen showing a ship, its stats and paint colours">
  </div>
</section>

<section id="leaderboard" aria-labelledby="board-h">
  <h2 id="board-h">Leaderboard and saved progress</h2>
  <div class="cols">
    <div class="panel"><h3>Level 1 top pilots</h3><ol class="board" id="board" aria-live="polite"><li><span class="name">Loading…</span></li></ol></div>
    <div class="panel"><h3>Save progress and post scores</h3>
      <ol class="steps"><li>Open <b>Account</b> in the game menu.</li><li><b>Create a free account</b> with an email and a password.</li><li><b>Finish a run.</b> Your best score per level goes on the global leaderboard, and your coins, diamonds, ships and unlocked levels are saved online.</li></ol>
    </div>
  </div>
</section>

<section aria-labelledby="faq-h">
  <h2 id="faq-h">Vyomastra questions and answers</h2>
  {''.join(f'<details><summary>{q}</summary><p>{a}</p></details>' for q, a in GAME_FAQ)}
  <p style="margin-top:22px"><a class="btn" href="/vyomastra/play/">▶ Play Vyomastra</a></p>
</section>
<script>{BOARD_JS.strip() % 'vyomastra'}</script>
"""

game_schema = [
    {"@type": ["VideoGame", "WebApplication"], "@id": SITE + "/vyomastra/#game", "name": "Vyomastra", "url": SITE + "/vyomastra/",
     "description": "Vyomastra is a free vertical space shooter that runs in the browser, with five weapons, boss fights, unlockable ships and a global leaderboard.",
     "genre": ["Shoot 'em up", "Arcade"], "gamePlatform": "Web browser", "applicationCategory": "GameApplication", "operatingSystem": "Any (modern web browser)",
     "playMode": "SinglePlayer", "inLanguage": "en", "image": SITE + "/assets/vyomastra-og.jpg", "isAccessibleForFree": True,
     "offers": {"@type": "Offer", "price": "0", "priceCurrency": "USD"}, "author": {"@id": ORG["@id"]}, "publisher": {"@id": ORG["@id"]}, "dateModified": TODAY},
    ORG,
    {"@type": "BreadcrumbList", "itemListElement": [{"@type": "ListItem", "position": 1, "name": "Games", "item": SITE + "/"}, {"@type": "ListItem", "position": 2, "name": "Vyomastra", "item": SITE + "/vyomastra/"}]},
    {"@type": "FAQPage", "mainEntity": [{"@type": "Question", "name": q, "acceptedAnswer": {"@type": "Answer", "text": a}} for q, a in GAME_FAQ]},
]

(OUT / "vyomastra").mkdir(parents=True, exist_ok=True)
(OUT / "index.html").write_text(page("/", "CodingCush Games: free browser games with saved progress",
    "Play free games in your browser with no download. Made with the Godot engine by CodingCush. A free account saves your progress and posts your scores to the global leaderboard.",
    home_body, home_schema, PRELOAD))
(OUT / "vyomastra" / "index.html").write_text(page("/vyomastra/", "Vyomastra: free space shooter to play in your browser",
    "Vyomastra is a free browser space shooter with five weapons, boss fights, ships to unlock and a global leaderboard. No download. See the controls and play now.",
    game_body, game_schema, PRELOAD))

PRIVACY_BODY = """<nav class="crumbs" aria-label="Breadcrumb"><a href="/">Games</a> / Privacy policy</nav>
<section class="prose" aria-labelledby="pp-h">
  <h1 id="pp-h">Privacy policy</h1>
  <p class="lead">What CodingCush Games stores about you, why, and how to delete it. Last updated __TODAY__.</p>
  <p>CodingCush Games (games.codingcush.com) is run by CodingCush. You can play every game as a guest without giving any personal information. This policy covers this games site; the main site codingcush.com has its own policy.</p>

  <h2>If you play as a guest</h2>
  <p>Your progress, coins, settings, local scores and any profile details you add are saved only in your own browser, on your device. They are not sent to us. Clearing your browser's site data removes them.</p>

  <h2>If you create an account</h2>
  <p>An account is optional. It saves your progress online and puts your best scores on the global leaderboard. We store:</p>
  <ul>
    <li><b>Your email address</b>, to identify your account. It is never shown to other players.</li>
    <li><b>Your password</b>, only as a one-way hash. We cannot read it. If you sign in with Google or Facebook, we never receive a password.</li>
    <li><b>Your pilot name</b> (up to 12 characters) and your <b>username</b> (a unique handle such as @sky_rider7, which you can change), which are shown on the public leaderboard next to your scores.</li>
    <li><b>Your game progress</b>: unlocked levels, coins, diamonds, ships, paints and best scores.</li>
    <li><b>An optional profile photo</b>, stored as a small picture. If you add one, it is shown next to your scores on the leaderboard inside the game. You can remove it at any time under Profile.</li>
    <li><b>Optional city, country and age</b>, if you choose to add them. These are shown only to you, never to other players.</li>
    <li><b>A sign-in token</b>, stored as a hash, so you stay signed in. It expires on its own.</li>
  </ul>

  <h2>Sign in with Google or Facebook</h2>
  <p>If you choose "Continue with Google" or "Continue with Facebook", that company asks your permission and then gives us your <b>email address</b> and your <b>name</b>. We use the email to find or create your account and the name only to set your starting pilot name. We do not receive your password, friends list, photos or posts, and we never post anything on your behalf. Signing in with the same email by another method opens the same account.</p>

  <h2>Backups</h2>
  <p>We also note when an account was last used (to the hour), so inactive accounts can be told apart.</p>
  <p>Once a day we keep a copy of the database so that accounts and progress can be restored after a fault. Copies are kept for 14 days and then deleted, so a deleted account can remain in a backup for up to 14 days.</p>

  <h2>Security records</h2>
  <p>When someone tries to sign in or create an account, we keep the IP address of that attempt for 15 minutes to block password guessing. Our hosting company, Hostinger, also keeps standard server logs.</p>

  <h2>Cookies and browser storage</h2>
  <p>We do not use advertising or tracking cookies. During a Google or Facebook sign-in, one short-lived cookie (about 10 minutes) checks that the reply really belongs to your sign-in. Games keep their save data and your sign-in in your browser's storage.</p>

  <h2>Usage counts and error reports</h2>
  <p>To see which missions are too hard and to fix bugs, the games send us two kinds of anonymous information. First, <b>counts</b>: that a page or a game was opened (and whether in a browser or as an installed app, and whether signed in), that Play was pressed, that the game was installed, that someone signed in (and with which method), or that a mission was started, won or lost (and roughly when), or a continue was used. These are added to a daily total and are not linked to your account, device or IP address. Second, <b>error reports</b>: if the game hits an error in your browser, the text of the error, the file and line it came from and the game version are sent. Neither contains your name, email or progress.</p>

  <h2>Advertising and third-party analytics</h2>
  <p>This site currently shows no ads and uses no third-party analytics or tracking scripts. If that changes, this page will be updated first.</p>

  <h2>Sharing</h2>
  <p>We do not sell or rent your information. It is stored on our hosting account with Hostinger. The only information visible to others is your pilot name, username, profile photo (if you added one), ship and scores on the leaderboard.</p>

  <h2 id="delete">How to delete your account and data</h2>
  <ol>
    <li>Open the game and choose <b>Account</b> from the main menu.</li>
    <li>Sign in, then choose <b>Delete my account</b> and confirm.</li>
  </ol>
  <p>This removes your email, password hash, online save, sign-in tokens and all your leaderboard scores from our server straight away. Progress saved in your browser stays until you clear the site data. If you cannot sign in, ask us through the <a href="https://codingcush.com/contact">contact page</a> from the email address of the account, and we will delete it. To also remove this site from your Google or Facebook account, use the "apps with access" page in that account's settings.</p>

  <h2>Children</h2>
  <p>The games are suitable for a general audience, but accounts are not meant for children under 13. Younger players can play as guests.</p>

  <h2>Changes and contact</h2>
  <p>If this policy changes, the date at the top changes with it. Questions: use the <a href="https://codingcush.com/contact">CodingCush contact page</a>.</p>
</section>""".replace("__TODAY__", date.today().strftime("%-d %B %Y"))

(OUT / "privacy").mkdir(parents=True, exist_ok=True)
(OUT / "privacy" / "index.html").write_text(page("/privacy/", "Privacy policy | CodingCush Games",
    "What CodingCush Games stores when you play as a guest or with an account, how Google and Facebook sign-in is used, and how to delete your account and data.",
    PRIVACY_BODY, [ORG, {"@type": "WebPage", "@id": SITE + "/privacy/", "url": SITE + "/privacy/", "name": "Privacy policy", "isPartOf": {"@id": SITE + "/#website"}, "dateModified": TODAY}]))

(OUT / "admin").mkdir(parents=True, exist_ok=True)
# The admin tools now live in the CodingCush admin panel (Admin > Games); this address points there.
ADMIN_HOME = "https://codingcush.com/admin/games"
(OUT / "admin" / "index.html").write_text(f"""<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="robots" content="noindex, nofollow">
<meta http-equiv="refresh" content="0; url={ADMIN_HOME}"><title>Admin moved | CodingCush Games</title></head>
<body><p>The games admin is now in the CodingCush admin panel: <a href="{ADMIN_HOME}">{ADMIN_HOME}</a></p></body></html>
""")

(OUT / "404.html").write_text(page("/404.html", "Page not found | CodingCush Games", "This page does not exist.",
    '<section><h1>Page not found</h1><p class="lead">That page does not exist. The games are on the home page.</p><a class="btn" href="/">All games</a></section>', [ORG]).replace("index, follow, max-image-preview:large", "noindex"))

(OUT / "site.webmanifest").write_text(json.dumps({"name": "CodingCush Games", "short_name": "CC Games", "start_url": "/", "display": "browser",
    "background_color": "#070b1a", "theme_color": "#070b1a",
    "icons": [{"src": "/icon-192.png", "sizes": "192x192", "type": "image/png"}, {"src": "/icon-512.png", "sizes": "512x512", "type": "image/png"}]}, indent=1))
(OUT / "robots.txt").write_text(f"User-agent: *\nAllow: /\nDisallow: /api/\nDisallow: /admin/\n\nSitemap: {SITE}/sitemap.xml\n")
urls = ["/", "/vyomastra/", "/privacy/"]
(OUT / "sitemap.xml").write_text('<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n'
    + "".join(f"  <url><loc>{SITE}{u}</loc><lastmod>{TODAY}</lastmod></url>\n" for u in urls) + "</urlset>\n")
(OUT / "llms.txt").write_text(f"""# CodingCush Games

> Free games that run in the web browser, made with the Godot 4 engine by CodingCush (codingcush.com). No download and no real-money purchases. A free account saves progress online and posts scores to a global leaderboard.

## Games

- [Vyomastra]({SITE}/vyomastra/): free vertical space shooter. Five weapons (Blaster, Rapid, Heavy, Wide, Pierce), boss fights, three ships (Arrow, Bulwark, Wisp), paint colours, coins and diamonds, global leaderboard. Plays with touch, mouse, keyboard or gamepad. Eight levels are playable (Low Orbit, Rock Belt, Ice Moon, Storm Giant, The Hive, Scrap Sea, Armada and The Core). In Rock Belt, large asteroids cannot be destroyed and must be dodged. In Ice Moon, frost shots freeze the ship (slower movement and firing) instead of costing a life; a second freeze while frozen costs a life. In Storm Giant, gusts of wind push the ship sideways and lightning strikes a column of the screen about a second after a flickering warning line appears. In The Hive, captured pilots drift down wrapped in green webs: shooting or touching the web frees them for score and coins, and every third rescue drops an extra life. In Scrap Sea, a cargo ship flies ahead of the player: raiders line up above it and dive, scrap dents it and snipers fire yellow shots at it; if its hull reaches zero the mission is lost, and the hull slowly repairs itself when left alone. In Armada, every enemy that leaves the bottom of the screen alive weakens a defence line of 12 points (a frigate costs 3); at zero the mission is lost. Two computer-flown wingmen fly beside the player and fire with them. In The Core, the final boss cannot be damaged while shield nodes circle it; destroying every node opens it for about eight seconds, after which the nodes return, one more each time (up to five). It has three stages that add frost, lightning, spiral fire, wind and mites.

## Facts

- Price: free. Coins and diamonds are earned only by playing.
- Platform: current Chrome, Edge, Firefox or Safari on a phone, tablet, laptop or desktop computer.
- Controls (Vyomastra): on touch screens drag to move (fires automatically); with a mouse the ship follows the cursor and the left button fires; arrow keys or WASD to move, Space or J to fire, Esc or P to pause; gamepad supported.
- Accounts: optional; email and password, or Google or Facebook sign-in, inside each game under Account. Accounts can be deleted there too.
- Privacy policy: {SITE}/privacy/
- Publisher: CodingCush, https://codingcush.com/
""")
print("built", sorted(p.name for p in OUT.iterdir()))
