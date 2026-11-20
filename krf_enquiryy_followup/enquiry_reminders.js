(function(){
 'use strict';
 const config=window.krfEnquiryFollowupConfig||{};
 if(config.enabled===false||window.top!==window.self||window.krfEnquiryRemindersStarted)return;
 window.krfEnquiryRemindersStarted=true;
 const minuteSetting=(value,fallback)=>{const n=Number(value??fallback);return Number.isFinite(n)?Math.max(0,Math.min(10080,Math.floor(n))):fallback;};
 const beforeMinutes=minuteSetting(config.reminder_before_minutes,2);
 const afterMinutes=minuteSetting(config.reminder_after_minutes,2);
 const pollSeconds=Math.max(10,Math.min(3600,Number(config.poll_seconds)||30));
 const root=String(document.body.dataset.routurl||'').replace(/\/?$/,'/');
 const key=document.body.dataset.key||'';
 const endpoint=new URL(root+'krf_enquiry_followup/api.php',location.origin);
 endpoint.searchParams.set('k',key);endpoint.searchParams.set('action','reminders');
 const user=[document.body.dataset.companyid,document.body.dataset.subcompanyid,document.body.dataset.projectid,document.body.dataset.loginid].join(':');
 const session=window.krfEnquirySessionTag||'';
 const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
 const parse=v=>Date.parse(String(v).replace(' ','T')+'+05:30');
 const read=k=>{try{return sessionStorage.getItem(k)}catch(e){return null}};
 const write=k=>{try{sessionStorage.setItem(k,'1')}catch(e){}};
 const fmt=v=>{const d=String(v).slice(0,10).split('-').reverse().join('-');return d+' '+String(v).slice(11,16)};
 function stage(at,now){
  if(at<=now)return config.show_overdue!==false&&afterMinutes>0&&now-at>=afterMinutes*60000?'after:'+afterMinutes+':'+Math.floor((now-at)/(afterMinutes*60000)):'later';
  return beforeMinutes>0&&at-now<=beforeMinutes*60000?'before:'+beforeMinutes:'later';
 }
 function duration(minutes){
  const days=Math.floor(minutes/1440),hours=Math.floor((minutes%1440)/60),mins=minutes%60;
  return [days?days+' day'+(days===1?'':'s'):'',hours?hours+' hour'+(hours===1?'':'s'):'',mins?mins+' minute'+(mins===1?'':'s'):''].filter(Boolean).join(' ')||'less than a minute';
 }
 function reminderLabel(at,now){
  if(at===now)return 'Due now';
  const remaining=Math.ceil(Math.abs(at-now)/60000);
  if(at<now)return 'Overdue by '+duration(remaining)+(stage(at,now).startsWith('after:')?' · Reminder repeats every '+duration(afterMinutes)+' after the follow-up time':'');
  const current=stage(at,now);
  const timing=current.startsWith('before:')?Number(current.split(':')[1]):null;
  return 'Due in '+duration(remaining)+(timing===null?'':' · Reminder set for '+duration(timing)+' before follow-up');
 }
 let serverOffset=0;
 function refreshPopup(now){
  if(!popup)return;
  popup.querySelectorAll('article[data-due]').forEach(article=>{
   const at=Number(article.dataset.due);
   article.classList.toggle('late',at<=now);
   article.querySelector('.krf-ef-time-label').textContent=reminderLabel(at,now);
  });
 }
 function caseLink(psono){
  const url=new URL(root+(config.followup_page||'krf_enquiry_followup/enquiry_followup.php'),location.origin);
  if(url.origin!==location.origin)throw new Error('Follow-up link must use the software origin');
  const menu=Array.from(document.querySelectorAll('a.dashboard-link')).find(a=>{try{return new URL(a.href,location.origin).pathname===url.pathname}catch(e){return false}});
  if(menu){const registered=new URL(menu.href,location.origin);const id=registered.searchParams.get('mdid');if(id)url.searchParams.set('mdid',id);}
  url.searchParams.set('iframe','true');
  url.searchParams.set('k',key);if(psono)url.searchParams.set('open_enquiry',psono);
  if(config.followup_module_id)url.searchParams.set('mdid',String(config.followup_module_id));
  return url.toString();
 }
 let busy=false,popup=null,failed=false;
 const style=document.createElement('style');style.textContent='.krf-ef-reminders{position:fixed;inset:0;background:#0006;z-index:100000;display:flex;align-items:center;justify-content:center;padding:16px}.krf-ef-reminders>section{background:white;border-radius:10px;padding:20px;width:min(800px,100%);max-height:85vh;overflow:auto}.krf-ef-reminders article{border-bottom:1px solid #ddd;padding:12px}.krf-ef-reminders .late{background:#fee2e2}.krf-ef-reminders button{padding:8px 14px;margin:10px 0}.krf-ef-reminders h3{margin:0 0 12px}.krf-ef-reminders .krf-ef-open{display:inline-block!important;margin-top:10px;padding:9px 16px;border-radius:6px;background:#175ca4!important;color:white!important;text-decoration:none!important;font-weight:600}.krf-ef-reminders .krf-ef-open:hover{background:#104477!important}.krf-ef-reminders .krf-ef-open:focus-visible{outline:3px solid #83b9ef;outline-offset:2px}';document.head.append(style);
 function show(rows,now){
  popup=document.createElement('div');popup.className='krf-ef-reminders';
  popup.innerHTML='<section role="dialog" aria-modal="true" aria-label="Enquiry follow-up reminders" tabindex="-1"><h3>Enquiry follow-up reminders</h3><button type="button">Close</button> <a class="krf-ef-open" href="'+esc(caseLink(''))+'">Open Enquiry Follow-up</a>'+rows.map(o=>{
   const current=stage(parse(o.next_followup_date),now);
   const at=parse(o.next_followup_date),label=reminderLabel(at,now);
   return '<article data-due="'+at+'" class="'+(at<=now?'late':'')+'"><b>'+esc(o.enquiry_no)+' · '+esc(o.clientname)+' · '+esc(o.product_name)+' · Qty '+esc(o.quantity)+'</b><div>'+esc(fmt(o.next_followup_date))+' · <span class="krf-ef-time-label">'+esc(label)+'</span></div><div>'+esc(o.mobileno)+' · '+esc(o.reason)+'</div><a class="krf-ef-open" href="'+esc(caseLink(o.psono))+'">Open Follow-up</a></article>';
  }).join('')+'</section>';
  const previous=document.activeElement;
  function close(){popup.remove();popup=null;if(previous&&previous.isConnected)previous.focus()}
  popup.querySelector('button').onclick=close;
  popup.addEventListener('click',e=>{
   const button=e.target.closest('a.krf-ef-open');if(!button)return;
   e.preventDefault();const target=new URL(button.href,location.origin);
   const menu=Array.from(document.querySelectorAll('a.dashboard-link')).find(a=>{try{return new URL(a.href,location.origin).pathname===target.pathname}catch(err){return false}});
   close();
   if(menu&&document.getElementById('content_frame')&&window.jQuery){
    // Reuse the dashboard handler, including module password checks and active menu.
    const original=menu.getAttribute('href');menu.setAttribute('href',target.toString());
    try{window.jQuery(menu).trigger('click');}finally{menu.setAttribute('href',original);}
   }else{
    // The dashboard restores this module in its content frame on arrival.
    try{localStorage.setItem('last_active_module_url',target.toString());}catch(err){
     if(typeof window.alertmessage==='function')window.alertmessage('warning','Unable to open the dashboard module. Please open Follow Up from the dashboard menu.');return;
    }
    const dashboard=new URL(root+'admin_dashboard.php',location.origin);dashboard.searchParams.set('k',key);location.assign(dashboard.toString());
   }
  });
  popup.addEventListener('keydown',e=>{
   if(e.key==='Escape')close();
   if(e.key==='Tab'){
    const focusable=[...popup.querySelectorAll('button,a[href]')],first=focusable[0],last=focusable[focusable.length-1];
    if(e.shiftKey&&document.activeElement===first){e.preventDefault();last.focus()}
    else if(!e.shiftKey&&document.activeElement===last){e.preventDefault();first.focus()}
   }
  });
  document.body.append(popup);popup.querySelector('button').focus();
 }
 async function poll(){
  if(document.hidden||busy)return;if(popup){refreshPopup(Date.now()+serverOffset);return;}busy=true;
  try{
   const response=await fetch(endpoint,{method:'POST',credentials:'same-origin',headers:{'Accept':'application/json'}});
   if(!response.ok)throw new Error('Reminder request failed');
   const result=await response.json();if(result.status!=='success')throw new Error('Reminder request failed');
   failed=false;const now=parse(result.server_now),rows=result.data.map(r=>({...r,psono:String(r.id),reason:r.last_reason||'First follow-up'}));serverOffset=now-Date.now();
   const welcome='ef:welcome:'+user+':'+session+':'+result.server_now.slice(0,10);
   const initial=config.show_on_login!==false&&!read(welcome);
   const due=rows.filter(o=>{
    const current=stage(parse(o.next_followup_date),now);
    o.noticeStage=current;
    o.noticeKey='ef:notice:'+user+':'+session+':'+o.psono+':'+o.last_followup_at+':'+o.next_followup_date+':'+current+':'+result.server_now.slice(0,10);
    return current!=='later'&&!read(o.noticeKey);
   });
   if(initial)write(welcome);
   const notices=initial?rows.filter(o=>o.next_followup_date.slice(0,10)<=result.server_now.slice(0,10)):due;
   if(notices.length){show(notices,now);notices.filter(o=>o.noticeStage!=='later').forEach(o=>write(o.noticeKey))}
  }catch(e){if(!failed){console.warn('Enquiry follow-up reminders are unavailable. Check the configuration, SQL migration and module installation.');failed=true}}
  finally{busy=false}
 }
 poll();setInterval(poll,pollSeconds*1000);document.addEventListener('visibilitychange',()=>{if(!document.hidden)poll()});
})();
