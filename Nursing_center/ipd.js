(() => {
  document.getElementById('mobileBtn')?.addEventListener('click', event => {
    const open = document.getElementById('sidebar').classList.toggle('open');
    event.currentTarget.setAttribute('aria-expanded',String(open));
    event.currentTarget.setAttribute('aria-label',open ? 'ปิดเมนู' : 'เปิดเมนู');
  });
  const form = document.getElementById('ipd-form');
  if (!form) return;
  const editable = () => [...form.querySelectorAll('input:not([type="hidden"]),textarea')];
  const saved = JSON.parse(form.dataset.original || '{}'), positions = {};
  const initial = editable().map(input => {
    const path = input.name.match(/\w+/g);
    let value = path.reduce((value,key) => value?.[key], saved);
    if(input.name.endsWith('[]')) { const i = positions[input.name] || 0; positions[input.name] = i+1; value = value?.[i]; }
    return String(value ?? (input.name === 'note' ? '' : input.name === 'support[hours]' ? 8 : 0));
  });
  function update() {
    const sums = {};
    form.querySelectorAll('[data-count],[data-census]').forEach(input => {
      const key = input.dataset.count || input.dataset.census;
      sums[key] = (sums[key] || 0) + (Number(input.value) || 0);
    });
    form.querySelectorAll('[data-sum]').forEach(cell => cell.textContent = sums[cell.dataset.sum]);
    const balance = sums.opening + sums.admit + sums.transfer_in - ['refer','opd','emergency','transfer_out','refuse','death','home'].reduce((n,k) => n+sums[k],0);
    document.getElementById('ipd-balance').textContent = balance.toLocaleString('th-TH');
    const valid = balance >= 0 && sums.general + sums.isolation === balance && sums.newborn <= balance;
    const check = document.getElementById('ipd-check');
    check.textContent = valid ? '✓ ยอดจำแนกผู้ป่วยตรงกับยอดคงพยาบาล' : 'ตรวจสอบยอด: ห้องสามัญ + ห้องแยกต้องเท่ากับคงพยาบาล และทารกต้องไม่เกินยอดรวม';
    check.className = valid ? 'ipd-valid' : 'ipd-invalid';
    const census = form.querySelector('[data-census="general"]');
    census.setCustomValidity(valid ? '' : check.textContent);
    const value = key => Number(form.querySelector(`[data-support="${key}"]`).value) || 0;
    const available = value('nurses') * value('hours');
    for (const key of ['foley','heplock','iv','hf']) {
      const input = form.querySelector(`[data-support="${key}"]`);
      input.setCustomValidity(value(key) <= balance ? '' : 'จำนวนต้องไม่เกินยอดคงพยาบาล');
    }
    form.querySelector('[data-support="foley_child"]').setCustomValidity(value('foley_child') <= value('foley') ? '' : 'ยอดเฉพาะเด็กต้องไม่เกินยอดคาสายสวนทั้งหมด');
    document.getElementById('ipd-productivity').textContent = available > 0 ? (value('care_hours') / available * 100).toFixed(2) + '%' : '—';
  }
  form.addEventListener('input', update);
  let dirty = editable().some((input,i) => input.value !== initial[i]), submitting = false;
  form.addEventListener('input', () => { dirty = editable().some((input,i) => input.value !== initial[i]); });
  form.addEventListener('reset', () => { dirty = false; setTimeout(() => { editable().forEach((input,i) => { input.value = initial[i]; }); update(); }, 0); });
  form.addEventListener('submit', () => { submitting = true; });
  const filter = document.querySelector('.ipd-filter');
  const originalFilter = [...filter.querySelectorAll('input,select')].map(input => input.value);
  const pending = document.createElement('p'); pending.className = 'ipd-invalid'; pending.setAttribute('role','status'); filter.after(pending);
  filter.addEventListener('input', () => {
    const changed = [...filter.querySelectorAll('input,select')].some((input,i) => input.value !== originalFilter[i]);
    pending.textContent = changed ? 'เลือกช่วงข้อมูลใหม่แล้ว กรุณากด “ดูข้อมูล” ก่อนบันทึก' : '';
    const save = document.getElementById('ipd-save'); if(save) save.disabled = changed;
  });
  // Use an in-page dialog: embedded browsers may suppress beforeunload prompts
  // while still cancelling navigation, leaving every account action unresponsive.
  const leaveDialog = document.createElement('dialog');
  leaveDialog.className = 'ipd-leave-dialog';
  leaveDialog.setAttribute('aria-labelledby','ipd-leave-title');
  leaveDialog.innerHTML = '<h2 id="ipd-leave-title">มีข้อมูลที่ยังไม่ได้บันทึก</h2><p>ต้องการออกจากหน้านี้โดยไม่บันทึกการแก้ไขหรือไม่?</p><div class="actions"><button type="button" class="btn" data-stay>อยู่หน้านี้</button><button type="button" class="btn primary" data-leave>ออกโดยไม่บันทึก</button></div>';
  document.body.append(leaveDialog);
  let leaveAction = null;
  const askToLeave = action => { leaveAction = action; leaveDialog.showModal(); };
  leaveDialog.querySelector('[data-stay]').addEventListener('click', () => leaveDialog.close());
  leaveDialog.querySelector('[data-leave]').addEventListener('click', () => {
    const action = leaveAction; leaveDialog.close(); dirty = false; action?.();
  });
  document.addEventListener('click', event => {
    const link = event.target.closest('a[href]');
    if(!dirty || submitting || !link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || link.target === '_blank' || link.hasAttribute('download')) return;
    event.preventDefault(); askToLeave(() => { window.location.assign(link.href); });
  });
  document.addEventListener('submit', event => {
    if(!dirty || submitting || event.target === form || event.defaultPrevented) return;
    event.preventDefault(); const target = event.target;
    askToLeave(() => target.requestSubmit());
  });
  update();
})();
