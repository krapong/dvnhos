(() => {
  window.nccPrint = () => setTimeout(() => window.print(), 0);
  window.nccUrl=path=>{
    const [route,...query]=path.split('?');
    const base=window.NCC_BASE||'';
    const clean=route.startsWith(base+'/')?route.slice(base.length):'/'+route.replace(/^\//,'');
    return base+'/router.php?route='+encodeURIComponent(clean)+(query.length?'&'+query.join('?'):'');
  };
  window.rewriteAppLinks=()=>{
    document.querySelectorAll('a[href],form[action]').forEach(element=>{
      const attr=element.tagName==='FORM'?'action':'href',raw=element.getAttribute(attr);
      if(!raw||raw.includes('router.php?')||/^(https?:|#|mailto:)/.test(raw))return;
      if(/(?:\.html(?:\?|$)|\/(?:users|password|logout)(?:\?|$))/.test(raw))element.setAttribute(attr,window.nccUrl(raw));
    });
  };
  const user = window.NCC_USER;
  if (!user) return;
  if (user.unit !== 'all') {
    window.NURSING_UNITS = window.NURSING_UNITS.filter(unit => unit.id === user.unit);
  }
  document.addEventListener('DOMContentLoaded', () => {
    if(pageUnit){state.date=todayThaiISO();state.shift=pageUnit.id==='lr'?'evening':'day';}
    if (user.unit !== 'all') {
      Object.keys(SOURCE_REPORTS).forEach(unit => { if (unit !== user.unit) delete SOURCE_REPORTS[unit]; });
    }
  });
  window.mountAccountBar=()=>{
    document.getElementById('accountBar')?.remove();
    const names = {admin:'ผู้ดูแลระบบ',executive:'ผู้บริหาร',director:'หัวหน้ากลุ่มงาน',head:'หัวหน้าหน่วย',staff:'ผู้บันทึก'};
    const bar = document.createElement('div');
    bar.className = 'account-bar';bar.id='accountBar';
    const label = document.createElement('span');
    label.textContent = `${user.name} · ${names[user.role]} · ${user.unit === 'all' ? 'ทุกหน่วยงาน' : user.unit.toUpperCase()}`;
    bar.append(label);
    if (user.role === 'admin') {
      const link = document.createElement('a'); link.href = window.nccUrl('/users'); link.className = 'btn primary'; link.textContent = 'จัดการผู้ใช้'; bar.append(link);
    }
    const password = document.createElement('a');password.href=window.nccUrl('/password');password.className='btn';password.textContent='เปลี่ยนรหัสผ่าน';bar.append(password);
    const form=document.createElement('form');form.method='post';form.action=window.nccUrl('/logout');
    const token=document.createElement('input');token.type='hidden';token.name='csrf';token.value=window.NCC_CSRF;form.append(token);
    const button=document.createElement('button');button.className='btn logout-button';button.textContent='ออกจากระบบ';form.append(button);bar.append(form);
      document.querySelector('.app')?.prepend(bar);
      const canWrite = ['admin','head','staff'].includes(user.role);
      document.querySelectorAll('a[href]').forEach(link => {
        const path = link.getAttribute('href').split('?')[0];
        if ((path==='settings.html' && user.role!=='admin') || (path==='approvals.html' && !['admin','director','head'].includes(user.role)) || (path==='entry.html' && !canWrite)) link.remove();
      });
  };
})();
