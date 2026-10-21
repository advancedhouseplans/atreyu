(function(){var e=class{constructor(e){this.size=e,this.items=[]}push(e){this.size<=0||(this.items.push(e),this.items.length>this.size&&this.items.splice(0,this.items.length-this.size))}toArray(){return this.items.slice()}};function t({ring:e,skipUrlPrefix:t,activeToken:r=()=>null}){let i=globalThis.fetch;if(globalThis.fetch=async function(a,o){let s=typeof a==`string`?a:a&&a.url||``,c=o&&o.method||a&&a.method||`GET`;if(t&&s.startsWith(t))return i(a,o);let l=o;if(n(s)){let e=r();if(e){let t=new Headers(o?.headers||a?.headers||{});t.set(`X-Atreyu-Active`,e),l={...o,headers:t}}}let u=performance.now();try{let t=await i(a,l);return e.push({method:c,url:s,status:t.status,duration_ms:Math.round(performance.now()-u),ts:Date.now(),debug_id:t.headers.get(`X-Atreyu-Debug-Id`)||t.headers.get(`X-Debug-Id`)||null}),t}catch(t){throw e.push({method:c,url:s,status:0,duration_ms:Math.round(performance.now()-u),ts:Date.now(),debug_id:null}),t}},typeof XMLHttpRequest<`u`){let i=XMLHttpRequest.prototype.open,a=XMLHttpRequest.prototype.send;XMLHttpRequest.prototype.open=function(e,t){return this.__bug={method:e,url:t,start:0},i.apply(this,arguments)},XMLHttpRequest.prototype.send=function(){if(!this.__bug||t&&this.__bug.url.startsWith(t))return a.apply(this,arguments);if(this.__bug.start=performance.now(),n(this.__bug.url)){let e=r();e&&this.setRequestHeader(`X-Atreyu-Active`,e)}return this.addEventListener(`loadend`,()=>{e.push({method:this.__bug.method,url:this.__bug.url,status:this.status,duration_ms:Math.round(performance.now()-this.__bug.start),ts:Date.now(),debug_id:this.getResponseHeader(`X-Atreyu-Debug-Id`)||this.getResponseHeader(`X-Debug-Id`)||null})}),a.apply(this,arguments)}}}function n(e){try{return new URL(e||location.href,location.href).origin===location.origin}catch{return!1}}function r(e){if(!e)return null;let t=e.getAttribute(`data-atreyu-config`)||e.getAttribute(`data-config`);if(!t)return null;let[n]=t.split(`.`);if(!n)return null;try{let e=n.length%4?`=`.repeat(4-n.length%4):``,r=atob(n.replaceAll(`-`,`+`).replaceAll(`_`,`/`)+e);return{...JSON.parse(r),token:t}}catch{return null}}var i=`
    :host {
        all: initial;
        --atreyu-primary: #605dff;
        --atreyu-primary-hover: #4f46e5;
        --atreyu-base-100: #ffffff;
        --atreyu-base-200: #f6f7f9;
        --atreyu-base-300: #e5e7eb;
        --atreyu-content: #18181b;
        --atreyu-muted: #71717a;
        --atreyu-error: #dc2626;
        font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }
    .bug-toggle {
        position: fixed; left: 20px; bottom: 20px;
        z-index: 2147483646;
        display: flex; align-items: center; justify-content: center;
        gap: 8px; min-height: 42px; box-sizing: border-box; padding: 0 14px;
        border: 1px solid rgba(255, 255, 255,.16); border-radius: 999px;
        background: #18181b; color: #fff; cursor: pointer;
        box-shadow: 0 10px 30px rgba(0, 0, 0, .22), 0 1px 2px rgba(0, 0, 0, .2);
        font: 650 13px/1 ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        letter-spacing: -.005em;
        transition: transform .16s ease, background-color .16s ease, box-shadow .16s ease;
    }
    .bug-toggle:hover {
        background: #27272a; transform: translateY(-1px);
        box-shadow: 0 13px 34px rgba(0, 0, 0, .25), 0 1px 2px rgba(0, 0, 0, .2);
    }
    .bug-toggle:focus-visible { outline: 3px solid color-mix(in srgb, var(--atreyu-primary) 35%, transparent); outline-offset: 3px; }
    .bug-toggle-icon { display: grid; place-items: center; line-height: 0; }
    .bug-toggle-icon svg { width: 18px; height: 18px; display: block; }
    .bug-toggle-spin {
        display: none; width: 14px; height: 14px; box-sizing: border-box;
        border: 2px solid rgba(255,255,255,.45); border-top-color: #fff; border-radius: 50%;
        animation: bug-spin .8s linear infinite;
    }
    .bug-toggle.working .bug-toggle-icon { display: none; }
    .bug-toggle.working .bug-toggle-spin { display: block; }
    .bug-toggle.working .bug-toggle-label::after { content: 'Preparing…'; }
    .bug-toggle.working .bug-toggle-label-text { display: none; }
    .bug-toggle[disabled] { cursor: wait; transform: none; }
    .bug-toggle[hidden] { display: none; }
    .bug-modal {
        display: none; position: fixed; inset: 0; z-index: 2147483647;
        box-sizing: border-box; padding: 24px;
        align-items: center; justify-content: center;
        background: rgba(9, 9, 11, .58); backdrop-filter: blur(4px);
    }
    .bug-modal.open { display: flex; animation: bug-fade-in .16s ease-out; }
    .bug-card {
        position: relative; width: min(600px, 100%); max-height: min(860px, 92vh);
        overflow-y: auto; box-sizing: border-box;
        background: var(--atreyu-base-100); color: var(--atreyu-content);
        border: 1px solid rgba(24, 24, 27, .1); border-radius: 16px;
        box-shadow: 0 28px 70px rgba(0, 0, 0, .28);
        font: 14px/1.45 ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        animation: bug-pop-in .18s cubic-bezier(.22, 1, .36, 1);
    }
    .bug-header { display: flex; align-items: flex-start; gap: 12px; padding: 22px 24px 18px; border-bottom: 1px solid var(--atreyu-base-300); }
    .bug-mark { display: grid; place-items: center; flex: 0 0 auto; width: 40px; height: 40px; border-radius: 12px; background: color-mix(in srgb, var(--atreyu-primary) 12%, white); color: var(--atreyu-primary); }
    .bug-mark svg { width: 21px; height: 21px; }
    .bug-heading { min-width: 0; flex: 1; }
    .bug-heading h2 { margin: 0; font-size: 18px; line-height: 1.35; font-weight: 700; letter-spacing: -.015em; }
    .bug-heading p { margin: 4px 0 0; color: var(--atreyu-muted); font-size: 13px; }
    .bug-close { display: grid; place-items: center; width: 32px; height: 32px; margin: -4px -6px 0 0; padding: 0; border: 0; border-radius: 9px; background: transparent; color: var(--atreyu-muted); cursor: pointer; }
    .bug-close:hover { background: var(--atreyu-base-200); color: var(--atreyu-content); }
    .bug-close svg { width: 18px; height: 18px; }
    .bug-form-body { display: grid; gap: 16px; padding: 20px 24px 4px; }
    .bug-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .bug-field { display: grid; gap: 7px; min-width: 0; }
    .bug-field > span { font-size: 13px; font-weight: 650; color: var(--atreyu-content); }
    .bug-optional { color: var(--atreyu-muted); font-weight: 400; }
    .bug-control {
        width: 100%; min-height: 42px; box-sizing: border-box; padding: 9px 11px;
        border: 1px solid #d4d4d8; border-radius: 10px; outline: none;
        background: var(--atreyu-base-100); color: var(--atreyu-content); font: inherit;
        transition: border-color .14s ease, box-shadow .14s ease;
    }
    textarea.bug-control { min-height: 104px; resize: vertical; }
    .bug-control:focus { border-color: var(--atreyu-primary); box-shadow: 0 0 0 3px color-mix(in srgb, var(--atreyu-primary) 18%, transparent); }
    .bug-control::file-selector-button { margin: -5px 10px -5px -6px; padding: 7px 10px; border: 0; border-radius: 7px; background: var(--atreyu-base-200); color: var(--atreyu-content); font: inherit; font-weight: 600; cursor: pointer; }
    .bug-note { color: var(--atreyu-muted); font-size: 12px; }
    .bug-preview { border: 1px solid var(--atreyu-base-300); border-radius: 10px; overflow: hidden;
        background: var(--atreyu-base-200); min-height: 104px; display: flex; align-items: center; justify-content: center;
        color: var(--atreyu-muted); font-size: 12px; }
    .bug-preview img { display: block; width: 100%; height: auto; max-height: 230px; object-fit: cover; object-position: top; }
    .bug-actions { margin-top: 16px; padding: 16px 24px 20px; display: flex; gap: 9px; justify-content: flex-end; align-items: center; border-top: 1px solid var(--atreyu-base-300); }
    .bug-actions button { min-height: 40px; padding: 8px 14px; border-radius: 10px; border: 1px solid var(--atreyu-base-300); background: var(--atreyu-base-100); color: var(--atreyu-content); font: inherit; font-weight: 650; cursor: pointer; }
    .bug-actions button:hover { background: var(--atreyu-base-200); }
    .bug-actions .primary { background: var(--atreyu-primary); color: #fff; border-color: var(--atreyu-primary); }
    .bug-actions .primary:hover { background: var(--atreyu-primary-hover); border-color: var(--atreyu-primary-hover); }
    .bug-actions .primary[disabled] { opacity: .6; cursor: wait; }
    .bug-status { flex: 1; text-align: left; color: var(--atreyu-muted); font-size: 12px; }
    .bug-spinner { width: 12px; height: 12px; border: 2px solid #ccc; border-top-color: #111;
        border-radius: 50%; animation: bug-spin .8s linear infinite; display: inline-block;
        vertical-align: middle; margin-right: 6px; }
    @media (max-width: 560px) {
        .bug-toggle { left: 14px; bottom: 14px; width: 44px; height: 44px; min-height: 44px; padding: 0; }
        .bug-toggle-label { display: none; }
        .bug-modal { align-items: flex-end; padding: 0; }
        .bug-card { width: 100%; max-height: 94vh; border-radius: 16px 16px 0 0; }
        .bug-grid { grid-template-columns: 1fr; }
        .bug-header, .bug-form-body { padding-left: 18px; padding-right: 18px; }
        .bug-actions { padding-left: 18px; padding-right: 18px; }
    }
    @keyframes bug-fade-in { from { opacity: 0; } }
    @keyframes bug-pop-in { from { opacity: 0; transform: translateY(10px) scale(.985); } }
    @keyframes bug-spin { to { transform: rotate(360deg); } }
`;function a({config:e,onSubmit:t,onCapture:n,onOpen:r}){let a=document.createElement(`div`);a.id=`bug-widget-root`,(document.body||document.documentElement).appendChild(a);let s=a.attachShadow({mode:`open`}),c=document.createElement(`style`);c.textContent=i,s.appendChild(c);let l=Math.max(1,Math.round((Number(e.diagnostic_window_ms)||3e5)/6e4));s.innerHTML+=`
        <button class="bug-toggle" title="Report an issue" aria-label="Report an issue" ${e.launcher===!1?`hidden`:``}>
            <span class="bug-toggle-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a3.375 3.375 0 1 1 6.75 0v1.5a3.375 3.375 0 0 1-6.75 0v-1.5Zm0 0H6.75m8.625 0h1.875M8.84 13.5l-1.34 1.34m7.66-1.34 1.34 1.34M9 7.02 7.875 5.625m6.25 0L15 7.02M12 6.375V4.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 12v3A5.25 5.25 0 0 0 10.5 20.25h3A5.25 5.25 0 0 0 18.75 15v-3"/></svg></span>
            <span class="bug-toggle-spin" aria-hidden="true"></span>
            <span class="bug-toggle-label"><span class="bug-toggle-label-text">Report issue</span></span>
        </button>
        <div class="bug-modal" role="dialog" aria-modal="true" aria-labelledby="atreyu-report-title">
            <div class="bug-card" role="document">
                <div class="bug-header">
                    <span class="bug-mark" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
                    </span>
                    <div class="bug-heading">
                        <h2 id="atreyu-report-title">Report an issue</h2>
                        <p>Send the team a bug or feature request with helpful diagnostic context.</p>
                    </div>
                    <button class="bug-close" type="button" aria-label="Close report form">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form class="bug-form">
                    <div class="bug-form-body">
                        <div class="bug-grid">
                            <label class="bug-field"><span>Type</span>
                                <select class="bug-control" name="type">
                                    <option value="bug">Bug</option>
                                    <option value="feature">Feature request</option>
                                    <option value="other">Other</option>
                                </select>
                            </label>
                            <label class="bug-field"><span>Severity</span>
                                <select class="bug-control" name="severity">
                                    <option value="low">Low</option>
                                    <option value="med" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="blocker">Blocker</option>
                                </select>
                            </label>
                        </div>
                        <label class="bug-field"><span>Title</span><input class="bug-control" name="title" required maxlength="200" placeholder="What happened?"></label>
                        <label class="bug-field"><span>Description <span class="bug-optional">(optional)</span></span><textarea class="bug-control" name="description" rows="4" placeholder="What were you doing, and what did you expect instead?"></textarea></label>
                        <label class="bug-field"><span>Additional files <span class="bug-optional">(optional)</span></span><input class="bug-control" type="file" name="attachments" multiple></label>
                        <div class="bug-field">
                            <span>Automatic screenshot</span>
                            <div class="bug-preview">Capturing…</div>
                            <div class="bug-note">A screenshot and relevant technical context from the last ${l} minutes will be included.</div>
                        </div>
                        <div class="bug-note">Reporting as ${o(e.user_email||e.user_name||`Anonymous reporter`)}</div>
                    </div>
                <div class="bug-actions">
                    <span class="bug-status"></span>
                    <button class="cancel" type="button">Cancel</button>
                    <button class="primary submit" type="submit">Send report</button>
                </div>
                </form>
            </div>
        </div>
    `;let u=s.querySelector(`.bug-modal`),d=s.querySelector(`.bug-toggle`),f=s.querySelector(`.bug-form`),p=s.querySelector(`.bug-close`),m=s.querySelector(`.cancel`),h=s.querySelector(`.submit`),g=s.querySelector(`.bug-preview`),_=s.querySelector(`.bug-status`),v=!1,y=null;async function b(e=null){if(!v){v=!0,r?.(),y=e instanceof HTMLElement?e:document.activeElement,e instanceof HTMLElement&&e.setAttribute(`data-atreyu-loading`,`true`),d.disabled=!0,d.classList.add(`working`),g.innerHTML=`Capturing…`,await new Promise(e=>requestAnimationFrame(()=>requestAnimationFrame(e)));try{n&&S(await n())}catch{S(null)}finally{v=!1,e instanceof HTMLElement&&e.removeAttribute(`data-atreyu-loading`),d.disabled=!1,d.classList.remove(`working`),u.classList.add(`open`),s.querySelector(`[name=title]`).focus()}}}function x(){u.classList.remove(`open`),y instanceof HTMLElement&&y.focus()}function S(e){g.innerHTML=e?`<img alt="snapshot" src="${e}">`:`Snapshot unavailable`}function C(e){_.innerHTML=e?`<span class="bug-spinner"></span>${o(e)}`:``}return d.addEventListener(`click`,()=>b(d)),document.addEventListener(`click`,e=>{let t=e.target instanceof Element?e.target.closest(`[data-atreyu-open]`):null;t&&!t.matches(`:disabled`)&&t.getAttribute(`aria-disabled`)!==`true`&&(e.preventDefault(),b(t))}),document.addEventListener(`atreyu:open`,b),p.addEventListener(`click`,x),m.addEventListener(`click`,x),u.addEventListener(`click`,e=>{e.target===u&&x()}),document.addEventListener(`keydown`,e=>{e.key===`Escape`&&x(),e.ctrlKey&&e.shiftKey&&e.key===`B`&&b()}),f.addEventListener(`submit`,e=>{e.preventDefault();let n=new FormData;n.set(`title`,s.querySelector(`[name=title]`).value),n.set(`description`,s.querySelector(`[name=description]`).value),n.set(`type`,s.querySelector(`[name=type]`).value),n.set(`severity`,s.querySelector(`[name=severity]`).value);let r=s.querySelector(`[name=attachments]`).files;for(let e of r)n.append(`attachments[]`,e);t(n,{close:x,disable:()=>{h.disabled=!0,h.textContent=`Sending…`,C(`Sending report…`)},enable:()=>{h.disabled=!1,h.textContent=`Send report`,C(``)}})}),{open:b,close:x,root:a}}function o(e){return String(e).replace(/[&<>"']/g,e=>({"&":`&amp;`,"<":`&lt;`,">":`&gt;`,'"':`&quot;`,"'":`&#39;`})[e])}var s;async function c({endpoint:e}){return await l(e),globalThis.AtreyuSnapshot.capture()}function l(e){return globalThis.AtreyuSnapshot?.capture?Promise.resolve():s||(s=new Promise((t,n)=>{let r=document.createElement(`script`);r.src=`${e.replace(/\/$/,``)}/widget-snapshot.js`,r.async=!0,r.onload=()=>globalThis.AtreyuSnapshot?.capture?t():n(Error(`Atreyu snapshot runtime did not initialize.`)),r.onerror=()=>n(Error(`Atreyu snapshot runtime failed to load.`)),document.head.appendChild(r)}).catch(e=>{throw s=null,e}),s)}async function u({endpoint:e,token:t,formData:n,metadata:r,snapshotBlob:i}){n.set(`metadata`,JSON.stringify(r)),i&&n.set(`snapshot`,i,`snapshot.jpg`);let a=await fetch(`${e}/api/reports`,{method:`POST`,headers:{Authorization:`Bearer ${t}`},body:n});if(!a.ok)return{ok:!1,status:a.status};let o=await a.json().catch(()=>({}));return{ok:!0,status:a.status,report_id:o.report_id}}var d=3e5;function f({network:e,events:t,initialDebugId:n,initialDebugTimestamp:r,windowMs:i=d,maxRequests:a=5,maxEvents:o=0,now:s=Date.now()}){let c=s-i,l=e=>typeof e?.ts!=`number`||e.ts>=c,u=e.filter(l).slice(-Math.max(0,a)),f=t.filter(l).slice(-Math.max(0,o)),p=n&&(typeof r!=`number`||r>=c);return{network:u,events:f,debugIds:Array.from(new Set([p?n:null,...u.map(e=>e.debug_id)].filter(Boolean))).slice(-Math.max(0,a)),windowMs:i}}function p({token:e,refreshMs:t=6e4,fetcher:n=globalThis.fetch.bind(globalThis),onToken:r=()=>{}}){let i=e,a=()=>n(`/_atreyu/activate`,{method:`GET`,headers:{Authorization:`Bearer ${i}`},credentials:`same-origin`,cache:`no-store`}).then(async e=>{if(!e.ok)return;let t=await e.json().catch(()=>null);typeof t?.token==`string`&&(i=t.token,r(i))}).catch(()=>null);a();let o=globalThis.setInterval(a,Math.max(15e3,t)),s=()=>{n(`/_atreyu/deactivate`,{method:`GET`,credentials:`same-origin`,cache:`no-store`,keepalive:!0}).catch(()=>null)};return globalThis.addEventListener?.(`pagehide`,s),globalThis.addEventListener?.(`pageshow`,a),()=>{globalThis.clearInterval(o),globalThis.removeEventListener?.(`pagehide`,s),globalThis.removeEventListener?.(`pageshow`,a),s()}}(function(){if(typeof window>`u`)return;let n=r(document.currentScript||Array.from(document.scripts).find(e=>e.src&&e.src.endsWith(`/widget.js`)));if(!n||!n.endpoint)return;p({token:n.token,refreshMs:Number(n.activation_refresh_ms)||6e4,onToken:e=>{n.token=e}});let i=Number(n.diagnostic_requests)||5,o=new e(i);t({ring:o,skipUrlPrefix:n.endpoint,activeToken:()=>n.token});let s=()=>{let e=null,t=null;a({config:n,onOpen:()=>{t=f({network:o.toArray(),events:[],initialDebugId:n.debug_id,initialDebugTimestamp:n.debug_ts,windowMs:Number(n.diagnostic_window_ms)||3e5,maxRequests:i,maxEvents:0})},onCapture:async()=>{e=null;let t=await c({endpoint:n.endpoint});return e=t,URL.createObjectURL(t)},onSubmit:async(r,{close:i,disable:a,enable:o})=>{a();try{let a=t||{network:[],events:[],debugIds:[]},o={url:location.href,referrer:document.referrer||null,title:document.title,screen_wh:`${screen.width}x${screen.height}`,viewport_wh:`${innerWidth}x${innerHeight}`,dpr:devicePixelRatio,timezone:Intl.DateTimeFormat().resolvedOptions().timeZone,locale:navigator.language,user_agent:navigator.userAgent,app_version:document.querySelector(`meta[name=app-version]`)?.getAttribute(`content`)||null,network_log:a.network,client_events:a.events,debug_ids:a.debugIds},s=await u({endpoint:n.endpoint,token:n.token,formData:r,metadata:o,snapshotBlob:e});s.ok?(i(),alert(`Report sent — thanks!`)):alert(`Failed to send (status ${s.status}). Please try again.`)}catch(e){alert(`Failed to send. Please try again.`),console.error(e)}finally{o()}}})};document.body?s():new MutationObserver((e,t)=>{document.body&&(t.disconnect(),s())}).observe(document.documentElement,{childList:!0,subtree:!0})})()})();