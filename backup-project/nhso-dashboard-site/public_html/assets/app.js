(() => {
  const root = document.querySelector('.lx-page');
  dash.colors.slice(0, 8).forEach((c, i) => root.style.setProperty('--lx-col' + i, c));
  let gradSeq = 0;

  const TH_M = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
  const f0 = d3.format(',.0f');
  const f2 = d3.format(',.2f');
  const f1 = d3.format(',.1f');
  const fyMonths = (fy) => {
    const y = fy - 543;
    return d3.range(0, 12).map((i) => {
      const m = ((9 + i) % 12) + 1;
      const yy = m >= 10 ? y - 1 : y;
      return yy + '-' + String(m).padStart(2, '0');
    });
  };
  const mLabel = (ym) => {
    const [y, m] = ym.split('-').map(Number);
    return TH_M[m] + ' ' + String((y + 543) % 100);
  };

  // ---------- calculations ----------
  dash.calc('sm_by_fy', {
    inputs: ['smartmoney_transfers'],
    title: 'Smart Money รายปีงบ',
    description: 'ยอดจัดสรร เงินโอนสุทธิ และยอดหัก/ชะลอ ต่อปีงบประมาณ (ล้านบาท) พร้อม % เปลี่ยนแปลงของเงินโอนสุทธิจากปีก่อน',
    fn: (smartmoney_transfers) => {
      const g = {};
      for (const d of smartmoney_transfers) {
        const s = (g[d.fy] = g[d.fy] || { fy: d.fy, amount: 0, net: 0, lines: 0 });
        s.amount += d.amount; s.net += d.net; s.lines += 1;
      }
      const ys = Object.values(g).sort((a, b) => a.fy - b.fy);
      return ys.map((s, i) => ({
        fy: s.fy, lines: s.lines,
        amount_mb: Math.round(s.amount / 1e4) / 100,
        net_mb: Math.round(s.net / 1e4) / 100,
        deducted_mb: Math.round((s.amount - s.net) / 1e4) / 100,
        chg_pct: i > 0 && ys[i - 1].net ? Math.round(((s.net - ys[i - 1].net) / ys[i - 1].net) * 1000) / 10 : null,
      }));
    },
  });
  dash.calc('sm_by_month', {
    inputs: ['smartmoney_transfers'],
    title: 'Smart Money รายเดือน',
    description: 'เงินโอนสุทธิและยอดจัดสรรต่อเดือนที่โอน (บาท)',
    fn: (smartmoney_transfers) => {
      const g = {};
      for (const d of smartmoney_transfers) {
        const k = d.fy + '|' + d.month;
        const s = (g[k] = g[k] || { fy: d.fy, month: d.month, net: 0, amount: 0, lines: 0 });
        s.net += d.net; s.amount += d.amount; s.lines += 1;
      }
      return Object.values(g).map((s) => ({ ...s, net: Math.round(s.net * 100) / 100, amount: Math.round(s.amount * 100) / 100 }));
    },
  });
  dash.calc('sm_by_fund', {
    inputs: ['smartmoney_transfers'],
    title: 'Smart Money แยกกองทุน',
    description: 'เงินโอนสุทธิและยอดจัดสรรต่อกองทุนในแต่ละปีงบประมาณ (บาท)',
    fn: (smartmoney_transfers) => {
      const g = {};
      for (const d of smartmoney_transfers) {
        const k = d.fy + '|' + d.fund;
        const s = (g[k] = g[k] || { fy: d.fy, fund: d.fund, net: 0, amount: 0 });
        s.net += d.net; s.amount += d.amount;
      }
      return Object.values(g).map((s) => ({ ...s, net: Math.round(s.net * 100) / 100, amount: Math.round(s.amount * 100) / 100 }));
    },
  });
  dash.calc('ec_by_fy', {
    inputs: ['eclaim_summary'],
    title: 'e-Claim รายปีงบ',
    description: 'จำนวนรายการ ยอดเรียกเก็บและยอดชดเชย (ล้านบาท) อัตราได้รับชดเชย % ติด Error และ % เปลี่ยนแปลงของยอดชดเชยจากปีก่อน ต่อประเภท (OP/IP/PPFS) ต่อปีงบ',
    fn: (eclaim_summary) => {
      const g = {};
      for (const d of eclaim_summary) {
        const k = d.cat + '|' + d.fy;
        const s = (g[k] = g[k] || { cat: d.cat, fy: d.fy, claims: 0, err_claims: 0, billed: 0, paid: 0 });
        s.claims += d.claims; s.err_claims += d.err_claims; s.billed += d.billed; s.paid += d.paid;
      }
      const prev = {};
      for (const s of Object.values(g)) prev[s.cat + '|' + s.fy] = s.paid;
      return Object.values(g).map((s) => ({
        cat: s.cat, fy: s.fy, claims: s.claims, err_claims: s.err_claims,
        paid_chg_pct: prev[s.cat + '|' + (s.fy - 1)] ? Math.round(((s.paid - prev[s.cat + '|' + (s.fy - 1)]) / prev[s.cat + '|' + (s.fy - 1)]) * 1000) / 10 : null,
        billed_mb: Math.round(s.billed / 1e4) / 100,
        paid_mb: Math.round(s.paid / 1e4) / 100,
        pay_pct: s.billed ? Math.round((s.paid / s.billed) * 1000) / 10 : 0,
        err_pct: s.claims ? Math.round((s.err_claims / s.claims) * 1000) / 10 : 0,
      }));
    },
  });
  dash.calc('ec_by_month', {
    inputs: ['eclaim_summary'],
    title: 'e-Claim รายเดือน',
    description: 'จำนวนรายการ ยอดเรียกเก็บ และยอดชดเชย (บาท) ต่อเดือนที่รับบริการ',
    fn: (eclaim_summary) => {
      const g = {};
      for (const d of eclaim_summary) {
        const k = d.cat + '|' + d.month;
        const s = (g[k] = g[k] || { cat: d.cat, fy: d.fy, month: d.month, claims: 0, err_claims: 0, billed: 0, paid: 0 });
        s.claims += d.claims; s.err_claims += d.err_claims; s.billed += d.billed; s.paid += d.paid;
      }
      return Object.values(g).map((s) => ({ ...s, billed: Math.round(s.billed * 100) / 100, paid: Math.round(s.paid * 100) / 100 }));
    },
  });
  dash.calc('ec_by_right', {
    inputs: ['eclaim_summary'],
    title: 'e-Claim แยกสิทธิ',
    description: 'จำนวนรายการและยอดชดเชย (บาท) ต่อสิทธิหลัก',
    fn: (eclaim_summary) => {
      const g = {};
      for (const d of eclaim_summary) {
        const k = d.cat + '|' + d.fy + '|' + d.right;
        const s = (g[k] = g[k] || { cat: d.cat, fy: d.fy, right: d.right, claims: 0, paid: 0 });
        s.claims += d.claims; s.paid += d.paid;
      }
      return Object.values(g).map((s) => ({ ...s, paid: Math.round(s.paid * 100) / 100 }));
    },
  });
  dash.calc('ec_by_item', {
    inputs: ['eclaim_summary'],
    title: 'e-Claim แยกกองทุน/รายการ',
    description: 'จำนวนรายการและยอดชดเชย (บาท) ต่อกองทุนหรือรายการบริการ',
    fn: (eclaim_summary) => {
      const g = {};
      for (const d of eclaim_summary) {
        const k = d.cat + '|' + d.fy + '|' + d.item;
        const s = (g[k] = g[k] || { cat: d.cat, fy: d.fy, item: d.item, claims: 0, paid: 0 });
        s.claims += d.claims; s.paid += d.paid;
      }
      return Object.values(g).map((s) => ({ ...s, paid: Math.round(s.paid * 100) / 100 }));
    },
  });

  dash.calc('seamless_by_group', {
    inputs: ['smartmoney_transfers'],
    title: 'Seamless แยกกลุ่ม',
    description: 'เงินโอน Seamless (งวดเบิกจ่ายรหัส D…) จัดกลุ่มตามรหัส: DCKD=CKD, DKTP=KTP, DNAP=NAPP, DMTB=TB, DANC=ANC, DMOR=MOPH, DATK=ATK, DCNB=น้ำมันกัญชา, DDOW=คัดกรองดาวน์, DTLM=คัดกรองธาลัสซีเมีย, DCCS=คัดกรองมะเร็งปากมดลูก, DCMH=จิตเวช, DDSA=ฟื้นฟู, DTTM=แพทย์แผนไทย, DTDT=โรคโลหิตจาง ธาลัสซีเมีย; ต่อปีงบ (ล้านบาท) รวมแถว “ทั้งหมด”',
    fn: (smartmoney_transfers) => {
      const MAP = { DCKD: 'CKD', DKTP: 'KTP', DNAP: 'NAPP', DMTB: 'TB', DANC: 'ANC', DMOR: 'MOPH', DATK: 'ATK', DCNB: 'น้ำมันกัญชา', DDOW: 'คัดกรองดาวน์', DTLM: 'คัดกรองธาลัสซีเมีย', DCCS: 'คัดกรองมะเร็งปากมดลูก', DCMH: 'จิตเวช', DDSA: 'ฟื้นฟู', DTTM: 'แพทย์แผนไทย', DTDT: 'โรคโลหิตจาง ธาลัสซีเมีย' };
      const groups = ['ทั้งหมด', ...Object.values(MAP)];
      const years = [...new Set(smartmoney_transfers.map((d) => d.fy))];
      const g = {};
      for (const fy of years) for (const grp of groups) g[fy + '|' + grp] = { fy, group: grp, amount: 0, net: 0, lines: 0 };
      for (const d of smartmoney_transfers) {
        const m = /^(D[A-Z]{3})\d/.exec(d.period || '');
        const grp = m && MAP[m[1]];
        if (!grp) continue;
        for (const k of [d.fy + '|' + grp, d.fy + '|ทั้งหมด']) { g[k].amount += d.amount; g[k].net += d.net; g[k].lines += 1; }
      }
      return Object.values(g).map((s) => {
        const all = g[s.fy + '|ทั้งหมด'].net;
        return { fy: s.fy, group: s.group, lines: s.lines, net: Math.round(s.net * 100) / 100, amount_mb: Math.round(s.amount / 1e4) / 100, net_mb: Math.round(s.net / 1e4) / 100, share_pct: all ? Math.round((s.net / all) * 1000) / 10 : 0 };
      });
    },
  });
  dash.calc('seamless_by_month', {
    inputs: ['smartmoney_transfers'],
    title: 'Seamless รายเดือน',
    description: 'เงินโอน Seamless สุทธิ (บาท) ต่อกลุ่มต่อเดือนที่โอน ใช้การจัดกลุ่มรหัสเดียวกับ “Seamless แยกกลุ่ม” รวมแถว “ทั้งหมด”',
    fn: (smartmoney_transfers) => {
      const MAP = { DCKD: 'CKD', DKTP: 'KTP', DNAP: 'NAPP', DMTB: 'TB', DANC: 'ANC', DMOR: 'MOPH', DATK: 'ATK', DCNB: 'น้ำมันกัญชา', DDOW: 'คัดกรองดาวน์', DTLM: 'คัดกรองธาลัสซีเมีย', DCCS: 'คัดกรองมะเร็งปากมดลูก', DCMH: 'จิตเวช', DDSA: 'ฟื้นฟู', DTTM: 'แพทย์แผนไทย', DTDT: 'โรคโลหิตจาง ธาลัสซีเมีย' };
      const g = {};
      for (const d of smartmoney_transfers) {
        const m = /^(D[A-Z]{3})\d/.exec(d.period || '');
        const grp = m && MAP[m[1]];
        if (!grp) continue;
        for (const k of [grp, 'ทั้งหมด']) {
          const key = k + '|' + d.month;
          const s = (g[key] = g[key] || { fy: d.fy, group: k, month: d.month, net: 0, amount: 0, lines: 0 });
          s.net += d.net; s.amount += d.amount; s.lines += 1;
        }
      }
      return Object.values(g).map((s) => ({ ...s, net: Math.round(s.net * 100) / 100, amount: Math.round(s.amount * 100) / 100 }));
    },
  });

  // ---------- state ----------
  const DEFAULT_FY = 2569;
  const SG_MAP = { DCKD: 'CKD', DKTP: 'KTP', DNAP: 'NAPP', DMTB: 'TB', DANC: 'ANC', DMOR: 'MOPH', DATK: 'ATK', DCNB: 'น้ำมันกัญชา', DDOW: 'คัดกรองดาวน์', DTLM: 'คัดกรองธาลัสซีเมีย', DCCS: 'คัดกรองมะเร็งปากมดลูก', DCMH: 'จิตเวช', DDSA: 'ฟื้นฟู', DTTM: 'แพทย์แผนไทย', DTDT: 'โรคโลหิตจาง ธาลัสซีเมีย' };
  const sgOf = (period) => { const m = /^(D[A-Z]{3})\d/.exec(period || ''); return (m && SG_MAP[m[1]]) || null; };
  const curGroup = () => dash.params().sgroup || 'ทั้งหมด';
  document.querySelectorAll('.lx-sg').forEach((b) => b.addEventListener('click', () => dash.setParams({ sgroup: b.dataset.group })));
  const fySel = document.querySelector('.lx-fy');
  const curFy = () => Number(dash.params().fy ?? DEFAULT_FY);
  fySel.addEventListener('change', () => dash.setParams({ fy: Number(fySel.value) }));

  const tabs = [...document.querySelectorAll('.lx-tab')];
  const btns = [...document.querySelectorAll('.lx-tabbtn')];
  let current = 'smartmoney';
  const show = (id, push) => {
    if (!tabs.some((t) => t.id === id)) return;
    current = id;
    if (typeof pStop === 'function') { pStop(); if (id === 'present') { pAnimate = true; } }
    tabs.forEach((t) => { t.hidden = t.id !== id; });
    btns.forEach((b) => b.setAttribute('aria-selected', String(b.dataset.go === id)));
    if (push) dash.setLink(id);
    draw();
  };
  btns.forEach((b) => b.addEventListener('click', () => show(b.dataset.go, true)));

  // ---------- helpers ----------
  const status = (...ids) => {
    const s = ids.map((id) => dash.data(id).status);
    if (s.some((x) => x === 'error' || x === 'connect' || x === 'declined' || x === 'missing')) return 'error';
    if (s.every((x) => x === 'ok')) return 'ok';
    return 'loading';
  };
  const fail = (el) => { el.classList.remove('dash-skeleton'); el.replaceChildren(); const d = document.createElement('div'); d.className = 'lx-fail'; d.textContent = 'Failed to load'; el.append(d); };
  const empty = (el, msg) => { el.classList.remove('dash-skeleton'); el.replaceChildren(); const d = document.createElement('p'); d.className = 'lx-empty'; d.textContent = msg; el.append(d); };
  const tipFor = (box) => {
    let t = box.querySelector(':scope > .lx-tip');
    if (!t) { t = document.createElement('div'); t.className = 'lx-tip'; t.hidden = true; box.append(t); }
    return t;
  };
  const showTip = (box, tip, ev, lines) => {
    tip.replaceChildren();
    lines.forEach((l, i) => { const d = document.createElement('div'); if (i === 0) d.className = 'h'; d.textContent = l; tip.append(d); });
    tip.hidden = false;
    const r = box.getBoundingClientRect();
    let x = ev.clientX - r.left + 12, y = ev.clientY - r.top + 12;
    const w = tip.offsetWidth, h = tip.offsetHeight;
    if (x + w > r.width) x = Math.max(0, ev.clientX - r.left - w - 12);
    if (y + h > r.height) y = Math.max(0, ev.clientY - r.top - h - 12);
    tip.style.left = x + 'px'; tip.style.top = y + 'px';
  };

  // vertical bar chart by month
  const barChart = (el, rows, opts) => {
    el.classList.remove('dash-skeleton');
    el.querySelectorAll('svg').forEach((s) => s.remove());
    const W = Math.max(280, el.clientWidth), H = 260;
    const svg = d3.select(el).append('svg').attr('width', '100%').attr('viewBox', `0 0 ${W} ${H}`).attr('role', 'img');
    const max = d3.max(rows, (d) => d.v) || 1;
    const y = d3.scaleLinear([0, max], [H - 28, 12]).nice();
    const fmtTick = opts.tickFmt || ((v) => (v >= 1e6 ? f1(v / 1e6) + 'M' : v >= 1e3 ? f0(v / 1e3) + 'K' : f0(v)));
    const probe = svg.append('text').attr('class', 'lx-axis').style('font-size', '11px').text(fmtTick(y.domain()[1]));
    const ml = (probe.node().getComputedTextLength ? probe.node().getComputedTextLength() : 40) * 1.25 + 16;
    probe.remove();
    const x = d3.scaleBand(rows.map((d) => d.k), [ml, W - 6]).padding(0.28);
    svg.append('g').attr('class', 'lx-grid-l').attr('transform', `translate(${ml},0)`)
      .call(d3.axisLeft(y).ticks(4).tickSize(-(W - ml - 6)).tickFormat(''));
    svg.append('g').attr('class', 'lx-axis').attr('transform', `translate(${ml},0)`)
      .call(d3.axisLeft(y).ticks(4).tickFormat(fmtTick)).attr('font-size', null).attr('font-family', null)
      .call((g) => g.select('.domain').remove());
    const step = x.bandwidth() < 26 ? 2 : 1;
    svg.append('g').attr('class', 'lx-axis').attr('transform', `translate(0,${H - 28})`)
      .call(d3.axisBottom(x).tickFormat((k, i) => (i % step === 0 ? opts.label(k) : '')).tickSizeOuter(0))
      .attr('font-size', null).attr('font-family', null);
    const tip = tipFor(el);
    const bw = Math.min(32, x.bandwidth());
    const gid = 'lxg' + (++gradSeq);
    const lg = svg.append('defs').append('linearGradient').attr('id', gid).attr('x1', 0).attr('y1', 0).attr('x2', 0).attr('y2', 1);
    lg.append('stop').attr('offset', 0).style('stop-color', 'color-mix(in srgb, var(--lx-accent) 75%, white)');
    lg.append('stop').attr('offset', 1).style('stop-color', 'var(--lx-accent)');
    svg.append('g').selectAll('rect').data(rows).join('rect')
      .attr('x', (d) => x(d.k) + (x.bandwidth() - bw) / 2).attr('width', bw)
      .attr('y', (d) => y(Math.max(0, d.v))).attr('height', (d) => Math.max(0, y(0) - y(Math.max(0, d.v))))
      .attr('rx', 4).style('fill', (d) => (opts.muted && !d.hl ? 'var(--cds-chart-muted)' : `url(#${gid})`))
      .on('mousemove', (ev, d) => { d3.select(ev.currentTarget).style('opacity', 0.75); showTip(el, tip, ev, opts.tip(d)); })
      .on('mouseleave', (ev) => { d3.select(ev.currentTarget).style('opacity', null); tip.hidden = true; });
    if (opts.valueLabels) {
      svg.append('g').selectAll('text').data(rows).join('text').attr('class', 'lx-axis')
        .attr('x', (d) => x(d.k) + x.bandwidth() / 2).attr('y', (d) => y(Math.max(0, d.v)) - 4)
        .attr('text-anchor', 'middle').style('fill', 'var(--color-fg)').style('font-size', '11px')
        .text((d) => opts.valueLabels(d));
    }
  };

  const hbars = (el, rows, opts) => {
    el.classList.remove('dash-skeleton');
    el.replaceChildren();
    const wrap = document.createElement('div');
    wrap.className = 'lx-hb' + (opts.err ? ' err' : '');
    const max = d3.max(rows, (d) => d.v) || 1;
    const total = opts.total || d3.sum(rows, (d) => d.v);
    rows.forEach((d) => {
      const r = document.createElement('div'); r.className = 'lx-hb-row';
      const l = document.createElement('span'); l.className = 'l'; l.textContent = d.k; l.title = d.k;
      const v = document.createElement('span'); v.className = 'v';
      v.textContent = opts.fmt(d.v) + (total ? '  ·  ' + f1((d.v / total) * 100) + '%' : '');
      const b = document.createElement('div'); b.className = 'lx-hb-bar';
      const i = document.createElement('i'); i.style.width = Math.max(1, (d.v / max) * 100) + '%';
      b.append(i); r.append(l, v, b); wrap.append(r);
    });
    el.append(wrap);
  };

  const donut = (el, rows) => {
    el.classList.remove('dash-skeleton');
    el.replaceChildren();
    const wrap = document.createElement('div'); wrap.className = 'lx-donutwrap';
    const S = 170, R = S / 2;
    const svg = d3.create('svg').attr('width', S).attr('height', S).attr('viewBox', `0 0 ${S} ${S}`);
    const total = d3.sum(rows, (d) => d.v);
    const pie = d3.pie().sort(null).value((d) => d.v)(rows);
    const arc = d3.arc().innerRadius(R * 0.6).outerRadius(R - 2).padAngle(0.012).cornerRadius(3);
    const col = (i) => dash.colors[i % dash.colors.length];
    const tip = tipFor(el);
    svg.append('g').attr('transform', `translate(${R},${R})`).selectAll('path').data(pie).join('path')
      .attr('d', arc).style('fill', (d, i) => col(i))
      .on('mousemove', (ev, d) => showTip(el, tip, ev, [d.data.k, f0(d.data.v) + ' รายการ · ' + f1((d.data.v / total) * 100) + '%']))
      .on('mouseleave', () => { tip.hidden = true; });
    const c = svg.append('g').attr('transform', `translate(${R},${R})`).attr('text-anchor', 'middle');
    c.append('text').attr('y', -2).style('font-family', 'var(--font-anthropic-serif)').style('font-size', '22px').style('fill', 'var(--color-fg)').text(f0(total));
    c.append('text').attr('y', 18).style('font-size', '11px').style('fill', 'var(--color-fg-muted)').text('รายการ');
    const lg = document.createElement('div'); lg.className = 'lx-legend';
    rows.forEach((d, i) => {
      const r = document.createElement('div');
      const sw = document.createElement('i'); sw.style.background = col(i);
      const n = document.createElement('span'); n.textContent = d.k;
      const v = document.createElement('span'); v.textContent = f1((d.v / total) * 100) + '%';
      r.append(sw, n, v); lg.append(r);
    });
    wrap.append(svg.node(), lg);
    el.append(wrap);
  };

  const gauge = (el, value) => {
    el.classList.remove('dash-skeleton');
    el.replaceChildren();
    const S = 200, R = 84;
    const svg = d3.create('svg').attr('width', S).attr('height', S * 0.7).attr('viewBox', `0 0 ${S} ${S * 0.7}`);
    const g = svg.append('g').attr('transform', `translate(${S / 2},${S * 0.6})`);
    const a = d3.arc().innerRadius(R - 18).outerRadius(R).cornerRadius(9).startAngle(-Math.PI / 2);
    g.append('path').attr('d', a.endAngle(Math.PI / 2)()).style('fill', 'color-mix(in srgb, var(--color-fg) 8%, transparent)');
    const v = Math.max(0, Math.min(100, value));
    const gid = 'lxg' + (++gradSeq);
    const lg = svg.append('defs').append('linearGradient').attr('id', gid).attr('x1', 0).attr('x2', 1).attr('y1', 0).attr('y2', 0);
    lg.append('stop').attr('offset', 0).style('stop-color', 'var(--lx-col4)');
    lg.append('stop').attr('offset', 1).style('stop-color', 'var(--lx-accent)');
    g.append('path').attr('d', a.endAngle(-Math.PI / 2 + (Math.PI * v) / 100)()).style('fill', `url(#${gid})`);
    g.append('text').attr('text-anchor', 'middle').attr('y', -8).style('font-family', 'var(--font-anthropic-serif)').style('font-size', '34px').style('fill', 'var(--color-fg)').text(f1(value) + '%');
    g.append('text').attr('text-anchor', 'middle').attr('y', 14).style('font-size', '11px').style('fill', 'var(--color-fg-muted)').text('ของยอดเรียกเก็บ');
    el.append(svg.node());
  };

  // tables with search + sort
  const tableState = new Map();
  const drawTable = (card, rows, cols, opts) => {
    const st = tableState.get(card) || { q: '', key: opts.defSort, dir: -1 };
    tableState.set(card, st);
    const input = card.querySelector('input[type=search]');
    if (!input.dataset.bound) {
      input.dataset.bound = '1';
      input.addEventListener('input', () => { st.q = input.value; draw(); });
      card.querySelectorAll('th button').forEach((b) => b.addEventListener('click', () => {
        if (st.key === b.dataset.k) st.dir = -st.dir; else { st.key = b.dataset.k; st.dir = typeof rows[0]?.[b.dataset.k] === 'number' ? -1 : 1; }
        draw();
      }));
    }
    const q = st.q.trim().toLowerCase().split(/\s+/).filter(Boolean);
    let list = rows;
    if (q.length) list = rows.filter((r) => { const s = cols.map((c) => r[c.f]).join(' ').toLowerCase() + ' ' + (r.month || ''); return q.every((t) => s.includes(t)); });
    const k = st.key;
    list = list.slice().sort((a, b) => (a[k] < b[k] ? -1 : a[k] > b[k] ? 1 : 0) * st.dir);
    card.querySelectorAll('th').forEach((th) => {
      const b = th.querySelector('button');
      th.setAttribute('aria-sort', b && b.dataset.k === k ? (st.dir > 0 ? 'ascending' : 'descending') : 'none');
    });
    const tb = card.querySelector('tbody');
    tb.replaceChildren(...list.map((r) => {
      const tr = document.createElement('tr'); tr.dataset.key = String(r.id);
      cols.forEach((c) => {
        const td = document.createElement('td');
        td.textContent = c.fmt ? c.fmt(r[c.f]) : r[c.f];
        if (c.n) td.className = 'lx-n'; else if (c.wrap) td.className = 'lx-wrap';
        if (c.bad && r[c.f] > 0) td.className += ' lx-badtxt';
        tr.append(td);
      });
      return tr;
    }));
    st.list = list; st.cols = cols; st.opts = opts;
    const xb = card.querySelector('.lx-tbl-export');
    if (xb && !xb.dataset.bound) { xb.dataset.bound = '1'; xb.addEventListener('click', () => exportTable(card)); }
    if (xb) xb.hidden = !downloadsApi;
    const tot = opts.totals(list);
    card.querySelector('.lx-count').textContent = `แสดง ${f0(list.length)} จาก ${f0(rows.length)} แถว · ${tot}`;
  };

  // ---------- 5-year comparison ----------
  const YEAR_COLORS = ['#2563eb', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#0ea5e9'];
  const yearBars = (el, rows, sel) => {
    el.classList.remove('dash-skeleton');
    el.replaceChildren();
    const W = Math.max(280, el.clientWidth), H = 300, mt = 24, mb = 46, ml = 58, mr = 8;
    const svg = d3.select(el).append('svg').attr('width', '100%').attr('viewBox', `0 0 ${W} ${H}`).attr('role', 'img');
    const max = d3.max(rows, (r) => r.v) || 1;
    const y = d3.scaleLinear([0, max * 1.06], [H - mb, mt]).nice();
    const x = d3.scaleBand(rows.map((r) => r.k), [ml, W - mr]).padding(0.28);
    svg.append('g').attr('class', 'lx-grid-l').attr('transform', `translate(${ml},0)`).call(d3.axisLeft(y).ticks(10).tickSize(-(W - ml - mr)).tickFormat(''));
    svg.append('g').attr('class', 'lx-grid-l').attr('transform', `translate(0,${mt})`).call(d3.axisTop(x).tickSize(-(H - mt - mb)).tickFormat('')).call((g) => g.selectAll('line').attr('transform', `translate(${x.step() / 2},0)`));
    svg.append('g').attr('class', 'lx-axis').attr('transform', `translate(${ml},0)`).call(d3.axisLeft(y).ticks(10).tickFormat(f0))
      .attr('font-size', null).attr('font-family', null).call((g) => g.select('.domain').remove());
    svg.append('g').attr('class', 'lx-axis').attr('transform', `translate(0,${H - mb})`).call(d3.axisBottom(x).tickSizeOuter(0))
      .attr('font-size', null).attr('font-family', null)
      .call((g) => g.selectAll('text').style('font-size', '12px').style('font-weight', (k) => (k === sel ? 700 : 400)).style('fill', 'var(--color-fg)'));
    svg.append('text').attr('transform', `translate(14,${(mt + H - mb) / 2}) rotate(-90)`).attr('text-anchor', 'middle').style('font-size', '12px').style('fill', 'var(--color-fg-muted)').text('ล้านบาท');
    svg.append('text').attr('x', (ml + W - mr) / 2).attr('y', H - 8).attr('text-anchor', 'middle').style('font-size', '12px').style('fill', 'var(--color-fg-muted)').text('ปีงบประมาณ');
    const tip = tipFor(el);
    const bw = Math.min(96, x.bandwidth());
    const g = svg.append('g');
    g.selectAll('rect').data(rows).join('rect')
      .attr('x', (d) => x(d.k) + (x.bandwidth() - bw) / 2).attr('width', bw)
      .attr('y', (d) => y(Math.max(0, d.v))).attr('height', (d) => Math.max(0, y(0) - y(Math.max(0, d.v))))
      .attr('rx', 3).style('fill', (d, i) => YEAR_COLORS[i % YEAR_COLORS.length])
      .style('stroke', (d) => (d.k === sel ? 'var(--color-fg)' : 'none')).style('stroke-width', 2)
      .on('mousemove', (ev, d) => { d3.select(ev.currentTarget).style('opacity', 0.85); showTip(el, tip, ev, ['ปีงบประมาณ ' + d.k, f2(d.v) + ' ล้านบาท'].concat(d.extra ? [d.extra] : [])); })
      .on('mouseleave', (ev) => { d3.select(ev.currentTarget).style('opacity', null); tip.hidden = true; });
    g.selectAll('text').data(rows).join('text')
      .attr('x', (d) => x(d.k) + x.bandwidth() / 2).attr('y', (d) => y(Math.max(0, d.v)) - 7)
      .attr('text-anchor', 'middle').style('font-size', '11px').style('fill', 'var(--color-fg-muted)')
      .text((d) => f2(d.v));
    const yrEls = el.closest('.lx-compare')?.querySelectorAll('.lx-yr-range') || [];
    yrEls.forEach((s) => { s.textContent = rows.length ? 'ปีงบประมาณ ' + rows[0].k + ' - ' + rows[rows.length - 1].k : ''; });
  };
  const groupedBars = (el, rows, names, colors) => {
    el.classList.remove('dash-skeleton');
    el.replaceChildren();
    const leg = document.createElement('div'); leg.className = 'lx-gleg';
    names.forEach((n, i) => { const sp = document.createElement('span'); const sw = document.createElement('i'); sw.style.background = colors[i]; const t = document.createElement('span'); t.textContent = n; sp.append(sw, t); leg.append(sp); });
    el.append(leg);
    const W = Math.max(280, el.clientWidth), H = 300, mt = 22, mb = 30;
    const svg = d3.select(el).append('svg').attr('width', '100%').attr('viewBox', `0 0 ${W} ${H}`).attr('role', 'img');
    const max = d3.max(rows, (r) => d3.max(r.vals)) || 1;
    const y = d3.scaleLinear([0, max * 1.08], [H - mb, mt]).nice();
    const probe = svg.append('text').style('font-size', '11px').text(f0(y.domain()[1]));
    const ml = probe.node().getComputedTextLength() + 10;
    probe.remove();
    const x0 = d3.scaleBand(rows.map((r) => r.k), [ml, W - 4]).padding(0.22);
    const x1 = d3.scaleBand(d3.range(names.length), [0, x0.bandwidth()]).padding(0.12);
    svg.append('g').selectAll('rect').data(rows.filter((r) => r.hl)).join('rect')
      .attr('x', (r) => x0(r.k) - (x0.step() * x0.paddingInner()) / 2).attr('width', x0.step()).attr('y', mt - 18).attr('height', H - mb - mt + 18)
      .attr('rx', 8).style('fill', 'color-mix(in srgb, var(--lx-accent) 12%, transparent)');
    svg.append('g').attr('class', 'lx-grid-l').attr('transform', `translate(${ml},0)`).call(d3.axisLeft(y).ticks(5).tickSize(-(W - ml - 4)).tickFormat(''));
    svg.append('g').attr('class', 'lx-axis').attr('transform', `translate(${ml},0)`).call(d3.axisLeft(y).ticks(5).tickFormat(f0))
      .attr('font-size', null).attr('font-family', null).call((g) => g.select('.domain').remove());
    svg.append('g').attr('class', 'lx-axis').attr('transform', `translate(0,${H - mb})`)
      .call(d3.axisBottom(x0).tickFormat((k) => 'ปีงบ ' + k).tickSizeOuter(0)).attr('font-size', null).attr('font-family', null)
      .call((g) => g.selectAll('text').style('font-weight', (k) => (rows.find((r) => r.k === k)?.hl ? 700 : 400)).style('font-size', '12px'));
    const tip = tipFor(el);
    const bw = Math.min(40, x1.bandwidth());
    const items = rows.flatMap((r) => r.vals.map((v, i) => ({ r, v, i })));
    const g = svg.append('g');
    g.selectAll('rect').data(items).join('rect')
      .attr('x', (d) => x0(d.r.k) + x1(d.i) + (x1.bandwidth() - bw) / 2).attr('width', bw)
      .attr('y', (d) => y(Math.max(0, d.v))).attr('height', (d) => Math.max(0, y(0) - y(Math.max(0, d.v))))
      .attr('rx', 5).style('fill', (d) => colors[d.i])
      .on('mousemove', (ev, d) => { d3.select(ev.currentTarget).style('opacity', 0.8); showTip(el, tip, ev, ['ปีงบ ' + d.r.k].concat(names.map((n, i) => n + ': ' + f2(d.r.vals[i]) + ' ล้านบาท'), d.r.extra ? [d.r.extra] : [])); })
      .on('mouseleave', (ev) => { d3.select(ev.currentTarget).style('opacity', null); tip.hidden = true; });
    const small = bw < 30;
    g.selectAll('text').data(items).join('text')
      .attr('x', (d) => x0(d.r.k) + x1(d.i) + x1.bandwidth() / 2).attr('y', (d) => y(Math.max(0, d.v)) - 5)
      .attr('text-anchor', 'middle').style('font-size', small ? '9px' : '11px').style('font-weight', 600).style('fill', 'var(--color-fg)')
      .text((d) => (d.v >= 100 ? f0(d.v) : f1(d.v)));
  };
  const drawCompare = (sec, fy, cat) => {
    const card = sec.querySelector('.lx-compare');
    if (!card) return;
    const yId = cat ? 'ec_by_fy' : 'sm_by_fy';
    const el = card.querySelector('[data-chart="years"]');
    const st = status(yId);
    if (st === 'error') { fail(el); return; }
    if (st !== 'ok') return;
    const years = dash.data(yId).data.filter((r) => !cat || r.cat === cat).map((r) => r.fy).sort((a, b) => a - b).slice(-5);
    const yrows = dash.data(yId).data.filter((r) => (!cat || r.cat === cat) && years.includes(r.fy)).sort((a, b) => a.fy - b.fy);
    const chgOf = (r) => (cat ? r.paid_chg_pct : r.chg_pct);
    yearBars(el, yrows.map((r) => ({
      k: String(r.fy), v: cat ? r.paid_mb : r.net_mb,
      extra: chgOf(r) == null ? '' : 'เปลี่ยนจากปีก่อน ' + (chgOf(r) > 0 ? '+' : '') + f1(chgOf(r)) + '%',
    })), String(fy));
    const tb = card.querySelector('tbody');
    const fields = [...card.querySelectorAll('th')].map((th) => th.dataset.field);
    const rows = dash.data(yId).data.filter((r) => (!cat || r.cat === cat) && years.includes(r.fy)).sort((a, b) => b.fy - a.fy);
    tb.replaceChildren(...rows.map((r) => {
      const tr = document.createElement('tr');
      tr.dataset.key = cat ? cat + ',' + r.fy : String(r.fy);
      if (r.fy === fy) tr.className = 'sel';
      fields.forEach((f) => {
        const td = document.createElement('td');
        const v = r[f];
        if (f === 'fy') td.textContent = String(v);
        else if (/chg_pct$/.test(f)) { td.textContent = v == null ? '—' : (v > 0 ? '+' : '') + f1(v) + '%'; td.className = 'lx-n lx-chg-cell ' + (v > 0 ? 'up' : v < 0 ? 'down' : ''); }
        else if (/_pct$/.test(f)) { td.textContent = f1(v) + '%'; td.className = 'lx-n'; }
        else if (/_mb$/.test(f)) { td.textContent = f2(v); td.className = 'lx-n'; }
        else { td.textContent = f0(v); td.className = 'lx-n'; }
        tr.append(td);
      });
      return tr;
    }));
  };

  // ---------- Excel export ----------
  let downloadsApi = null, assetsApi = null, dbApi = null;
  const exportBtn = document.querySelector('.lx-export');
  const importBtn = document.querySelector('.lx-import');
  const fileIn = document.querySelector('.lx-file');
  const impBox = document.querySelector('.lx-imp');
  const impMsg = impBox.querySelector('.lx-imp-msg');
  const impOk = impBox.querySelector('.lx-imp-ok');
  const impCancel = impBox.querySelector('.lx-imp-cancel');
  const say = (msg, ok) => { impBox.hidden = false; impMsg.textContent = msg; impOk.hidden = !ok; };
  impCancel.addEventListener('click', () => { impBox.hidden = true; pending = null; });
  const saveXlsx = async (filename, sheets) => {
    if (!downloadsApi) return;
    try {
      const bytes = window.lxXlsx.write(sheets);
      await downloadsApi.save({ filename, data: new Blob([bytes]) });
    } catch (e) {
      if (e && e.code === 'declined') return;
      say('ส่งออกไม่สำเร็จ: ' + ((e && (e.message || e.code)) || 'ไม่ทราบสาเหตุ'), false);
    }
  };
  const C = {
    sm: [{ h: 'วันที่โอน', k: 'date_th', w: 12 }, { h: 'ปีงบ', k: 'fy', type: 'int', w: 8 }, { h: 'Batch No.', k: 'batch', w: 10 }, { h: 'งวด/เลขที่เบิกจ่าย', k: 'period', w: 24 },
      { h: 'กองทุน', k: 'fund', w: 34 }, { h: 'กองทุนย่อยเฉพาะด้าน', k: 'subfund', w: 44 }, { h: 'จำนวนเงิน', k: 'amount', type: 'money', w: 16 },
      { h: 'ชะลอการโอน', k: 'held', type: 'money', w: 14 }, { h: 'รายการหักจากยอดโอนเงิน', k: 'deducted', type: 'money', w: 16 }, { h: 'หลักประกันสัญญา', k: 'guarantee', type: 'money', w: 14 },
      { h: 'ภาษี', k: 'tax', type: 'money', w: 12 }, { h: 'คงเหลือ (สุทธิ)', k: 'net', type: 'money', w: 16 }, { h: 'จำนวนเงินรอหักกลบ', k: 'pending_offset', type: 'money', w: 16 }],
    ec: [{ h: 'ประเภท', k: 'cat', w: 8 }, { h: 'ปีงบ', k: 'fy', type: 'int', w: 8 }, { h: 'เดือนบริการ', k: 'month_th', w: 11 }, { h: 'สิทธิ', k: 'right', w: 18 },
      { h: 'กองทุน / รายการ', k: 'item', w: 40 }, { h: 'รายการ', k: 'claims', type: 'int', w: 10 }, { h: 'ติด Error', k: 'err_claims', type: 'int', w: 10 },
      { h: 'เรียกเก็บ (บาท)', k: 'billed', type: 'money', w: 16 }, { h: 'ได้รับชดเชย (บาท)', k: 'paid', type: 'money', w: 16 }, { h: 'เรียกเก็บที่ติด Error (บาท)', k: 'err_billed', type: 'money', w: 18 }],
    smY: [{ h: 'ปีงบ', k: 'fy', type: 'int', w: 8 }, { h: 'ยอดจัดสรร (ล้านบาท)', k: 'amount_mb', type: 'money', w: 18 }, { h: 'หัก/ชะลอ (ล้านบาท)', k: 'deducted_mb', type: 'money', w: 18 },
      { h: 'โอนสุทธิ (ล้านบาท)', k: 'net_mb', type: 'money', w: 18 }, { h: 'เปลี่ยนแปลงจากปีก่อน (%)', k: 'chg_pct', type: 'pct', w: 20 }, { h: 'รายการโอน', k: 'lines', type: 'int', w: 12 }],
    ecY: [{ h: 'ประเภท', k: 'cat', w: 8 }, { h: 'ปีงบ', k: 'fy', type: 'int', w: 8 }, { h: 'รายการส่งเบิก', k: 'claims', type: 'int', w: 14 }, { h: 'ติด Error', k: 'err_claims', type: 'int', w: 12 },
      { h: '% ติด Error', k: 'err_pct', type: 'pct', w: 12 }, { h: 'เรียกเก็บ (ล้านบาท)', k: 'billed_mb', type: 'money', w: 18 }, { h: 'ได้รับชดเชย (ล้านบาท)', k: 'paid_mb', type: 'money', w: 18 },
      { h: '% ได้รับชดเชย', k: 'pay_pct', type: 'pct', w: 14 }, { h: 'ยอดชดเชยเปลี่ยนจากปีก่อน (%)', k: 'paid_chg_pct', type: 'pct', w: 22 }],
    sg: [{ h: 'ปีงบ', k: 'fy', type: 'int', w: 8 }, { h: 'กลุ่ม Seamless', k: 'group', w: 26 }, { h: 'ยอดจัดสรร (ล้านบาท)', k: 'amount_mb', type: 'money', w: 18 },
      { h: 'โอนสุทธิ (ล้านบาท)', k: 'net_mb', type: 'money', w: 18 }, { h: 'รายการโอน', k: 'lines', type: 'int', w: 12 }, { h: '% ของ Seamless', k: 'share_pct', type: 'pct', w: 14 }],
    err: [{ h: 'ประเภท', k: 'cat', w: 8 }, { h: 'ปีงบ', k: 'fy', type: 'int', w: 8 }, { h: 'Error Code', k: 'code', w: 14 }, { h: 'จำนวนรายการ', k: 'claims', type: 'int', w: 14 }, { h: 'ยอดเรียกเก็บ (บาท)', k: 'billed', type: 'money', w: 18 }],
  };
  const exportAll = async () => {
    const P = period();
    const ok = (id) => dash.data(id).status === 'ok';
    if (!['sm_by_fy', 'ec_by_fy', 'smartmoney_transfers', 'eclaim_summary', 'eclaim_errors', 'seamless_by_group'].every(ok)) { say('ข้อมูลยังโหลดไม่ครบ ลองอีกครั้งในไม่กี่วินาที', false); return; }
    const by = (a, b) => (a < b ? -1 : a > b ? 1 : 0);
    const ec = dash.data('eclaim_summary').data;
    const tr = dash.data('smartmoney_transfers').data.filter(P.inDate);
    const sgRows = tr.filter((r) => sgOf(r.period));
    const sgAll = d3.sum(sgRows, (r) => r.net);
    const sgSum = [['ทั้งหมด', sgRows], ...d3.groups(sgRows, (r) => sgOf(r.period))].map(([group, v]) => {
      const x = sumT(v); return { fy: P.range ? P.label : P.fy, group, amount_mb: x.amount_mb, net_mb: x.net_mb, lines: x.lines, share_pct: sgAll ? Math.round((x.net / sgAll) * 1000) / 10 : 0 };
    });
    const errs = d3.rollups(dash.data('eclaim_errors').data.filter((r) => (r.month ? P.inMonth(r) : r.fy === P.fy)), (v) => ({ claims: d3.sum(v, (r) => r.claims), billed: Math.round(d3.sum(v, (r) => r.billed) * 100) / 100 }), (r) => r.cat, (r) => r.code)
      .flatMap(([cat, cs]) => cs.map(([code, x]) => ({ cat, fy: P.range ? P.label : P.fy, code, ...x })));
    const tag = P.range ? 'ช่วง' : String(P.fy);
    const sheets = [
      { name: 'เทียบ 5 ปี Smart Money', title: 'Smart Money เทียบรายปีงบประมาณ', columns: C.smY, rows: dash.data('sm_by_fy').data.slice().sort((a, b) => a.fy - b.fy) },
      { name: 'เทียบ 5 ปี e-Claim', title: 'e-Claim (OP / IP / PPFS) เทียบรายปีงบประมาณ', columns: C.ecY, rows: dash.data('ec_by_fy').data.slice().sort((a, b) => by(a.cat, b.cat) || a.fy - b.fy) },
      { name: 'Smart Money ' + tag, title: 'รายการโอนเงิน Smart Money ' + P.label, columns: C.sm, rows: tr.slice().sort((a, b) => by(a.date, b.date)) },
      ...['OP', 'IP', 'PPFS'].map((cat) => ({ name: cat + ' ' + tag, title: 'สรุป ' + cat + ' ' + P.label + ' (ตามเดือนที่รับบริการ)', columns: C.ec, rows: ec.filter((r) => r.cat === cat && P.inMonth(r)).sort((a, b) => by(a.month, b.month) || b.paid - a.paid) })),
      { name: 'Seamless ' + tag, title: 'Seamless แยกกลุ่ม ' + P.label, columns: C.sg, rows: sgSum.sort((a, b) => b.net_mb - a.net_mb) },
      { name: 'Seamless รายการ ' + tag, title: 'รายการโอน Seamless ' + P.label, columns: [{ h: 'กลุ่ม', k: 'sg', w: 22 }, ...C.sm], rows: sgRows.map((r) => ({ ...r, sg: sgOf(r.period) })).sort((a, b) => by(a.sg, b.sg) || by(a.date, b.date)) },
      { name: 'Error Code ' + tag, title: 'Error Code ' + P.label, columns: C.err, rows: errs.sort((a, b) => by(a.cat, b.cat) || b.claims - a.claims) },
    ];
    await saveXlsx('แดชบอร์ด สปสช ' + (P.range ? P.range.start + ' ถึง ' + P.range.end : 'ปีงบ ' + P.fy) + '.xlsx', sheets);
  };
  const exportTable = async (card) => {
    const st = tableState.get(card);
    if (!st || !st.list) return;
    const isSm = card.id === 'sm-table' || card.id === 'seamless-table';
    const cat = card.id === 'seamless-table' ? 'Seamless ' + curGroup() : isSm ? 'SmartMoney' : card.closest('.lx-tab').dataset.cat;
    const q = st.q.trim();
    const P = period();
    const ptxt = P.range ? P.range.start + ' ถึง ' + P.range.end : 'ปีงบ ' + P.fy;
    await saveXlsx(cat + ' ' + ptxt + (q ? ' ค้นหา ' + q.replace(/[\\/:*?"<>|]/g, ' ').slice(0, 40) : '') + '.xlsx', [{
      name: cat.slice(0, 22) + ' ' + (P.range ? 'ช่วง' : P.fy), title: (card.id === 'seamless-table' ? 'รายการโอน ' + cat : isSm ? 'รายการโอนเงิน Smart Money' : 'สรุป ' + cat) + ' ' + P.label + (q ? ' · ค้นหา: ' + q : ''),
      columns: isSm ? C.sm : C.ec, rows: st.list,
    }]);
  };
  exportBtn.addEventListener('click', async () => { exportBtn.disabled = true; try { await exportAll(); } finally { exportBtn.disabled = false; } });

  // ---------- Excel import (Smart Money report) ----------
  let pending = null;
  const TH_IDX = { 'ม.ค.': 1, 'ก.พ.': 2, 'มี.ค.': 3, 'เม.ย.': 4, 'พ.ค.': 5, 'มิ.ย.': 6, 'ก.ค.': 7, 'ส.ค.': 8, 'ก.ย.': 9, 'ต.ค.': 10, 'พ.ย.': 11, 'ธ.ค.': 12 };
  const parseDate = (v) => {
    if (typeof v === 'number' && v > 20000) { const d = new Date(Date.UTC(1899, 11, 30) + v * 864e5); return [d.getUTCFullYear(), d.getUTCMonth() + 1, d.getUTCDate()]; }
    const s = String(v ?? '').trim();
    let m = /^(\d{1,2})\s+(\S+)\s+(\d{4})$/.exec(s);
    if (m && TH_IDX[m[2]]) { let y = Number(m[3]); if (y > 2400) y -= 543; return [y, TH_IDX[m[2]], Number(m[1])]; }
    m = /^(\d{1,2})\/(\d{1,2})\/(\d{4})/.exec(s);
    if (m) { let y = Number(m[3]); if (y > 2400) y -= 543; return [y, Number(m[2]), Number(m[1])]; }
    return null;
  };
  const toNum = (v) => { if (typeof v === 'number') return v; const n = Number(String(v ?? '').replace(/,/g, '').trim()); return isFinite(n) ? n : 0; };
  const parseSmart = (sheet) => {
    const hi = sheet.rows.findIndex((r) => r && r.some((c) => String(c ?? '').trim() === 'วันที่โอน') && r.some((c) => String(c ?? '').trim() === 'คงเหลือ'));
    if (hi < 0) return null;
    const H = sheet.rows[hi].map((c) => String(c ?? '').trim());
    const ix = (n) => H.indexOf(n);
    const I = { date: ix('วันที่โอน'), batch: ix('Batch No.'), period: ix('งวด/เลขที่เบิกจ่าย'), fund: ix('กองทุน'), subfund: ix('กองทุนย่อยเฉพาะด้าน'),
      amount: ix('จำนวนเงิน'), held: ix('ชะลอการโอน'), deducted: ix('รายการหักจากยอดโอนเงิน'), guarantee: ix('หลักประกันสัญญา'), tax: ix('ภาษี'), net: ix('คงเหลือ'), pending_offset: ix('จำนวนเงินรอหักกลบ') };
    if (I.date < 0 || I.net < 0 || I.amount < 0 || I.fund < 0) return null;
    const out = [];
    for (const r of sheet.rows.slice(hi + 1)) {
      if (!r) continue;
      const d = parseDate(r[I.date]);
      if (!d || typeof toNum(r[0]) !== 'number' || String(r[0] ?? '').trim() === 'รวม') continue;
      const [y, m, day] = d;
      const g = (k) => (I[k] >= 0 ? r[I[k]] : null);
      const n = (k) => Math.round(toNum(g(k)) * 100) / 100;
      const row = { fy: y + 543 + (m >= 10 ? 1 : 0), date: `${y}-${String(m).padStart(2, '0')}-${String(day).padStart(2, '0')}`, month: `${y}-${String(m).padStart(2, '0')}`,
        batch: String(g('batch') ?? '').trim(), period: String(g('period') ?? '').trim(), fund: String(g('fund') ?? '').trim() || '-', subfund: String(g('subfund') ?? '').trim() || '-',
        amount: n('amount'), held: n('held'), deducted: n('deducted'), guarantee: n('guarantee'), tax: n('tax'), net: n('net'), pending_offset: n('pending_offset') };
      row.date_th = `${day} ${TH_M[m]} ${(y + 543) % 100}`;
      row.month_th = `${TH_M[m]} ${(y + 543) % 100}`;
      row.deducted_total = Math.round((row.amount - row.net) * 100) / 100;
      out.push(row);
    }
    return out;
  };
  importBtn.addEventListener('click', () => { fileIn.value = ''; fileIn.click(); });
  fileIn.addEventListener('change', async () => {
    const files = [...fileIn.files];
    if (!files.length) return;
    say('กำลังอ่านไฟล์ ' + files.length + ' ไฟล์…', false);
    const found = [], skipped = [];
    for (const f of files) {
      try {
        if (!/\.xlsx$/i.test(f.name)) { skipped.push(f.name + ' (รองรับเฉพาะ .xlsx)'); continue; }
        const sheets = await window.lxXlsx.read(await f.arrayBuffer());
        let rows = null;
        for (const sh of sheets) { rows = parseSmart(sh); if (rows) break; }
        if (rows && rows.length) found.push({ name: f.name, rows }); else skipped.push(f.name + ' (ไม่ใช่รายงานโอนเงิน Smart Money)');
      } catch (e) { skipped.push(f.name + ' (อ่านไฟล์ไม่ได้)'); }
    }
    const t = dash.data('smartmoney_transfers');
    if (!found.length) { say('ไม่พบข้อมูลที่นำเข้าได้\n' + skipped.join('\n') + '\n\nหน้านี้นำเข้าได้เฉพาะไฟล์รายงานโอนเงิน Smart Money (nhso_ปีงบ.xlsx) ส่วนไฟล์ REP e-Claim ให้ส่งให้ Claude ในแชตเพื่ออัปเดต', false); return; }
    if (t.status !== 'ok') { say('ข้อมูลเดิมยังโหลดไม่เสร็จ ลองอีกครั้ง', false); return; }
    const incoming = found.flatMap((f) => f.rows);
    const years = [...new Set(incoming.map((r) => r.fy))].sort();
    const kept = t.data.filter((r) => !years.includes(r.fy));
    const replaced = t.data.length - kept.length;
    const merged = kept.concat(incoming).sort((a, b) => (a.date < b.date ? 1 : a.date > b.date ? -1 : 0)).map((r, i) => ({ ...r, id: i + 1 }));
    pending = { merged, files: found.map((f) => f.name) };
    const net = d3.sum(incoming, (r) => r.net);
    say(`พบ ${f0(incoming.length)} รายการโอน จาก ${found.length} ไฟล์ (${found.map((f) => f.name).join(', ')})\n` +
      `ปีงบประมาณ ${years.join(', ')} · เงินโอนสุทธิรวม ${f2(net)} บาท\n` +
      `ข้อมูลเดิมของปีงบเหล่านี้ ${f0(replaced)} รายการจะถูกแทนที่ทั้งหมด ปีอื่นคงเดิม` + (skipped.length ? '\nข้ามไฟล์: ' + skipped.join(', ') : ''), true);
  });
  impOk.addEventListener('click', async () => {
    if (!pending || !assetsApi || !dbApi) return;
    impOk.disabled = true;
    impMsg.textContent = 'กำลังบันทึกข้อมูล…';
    try {
      const blob = new Blob([JSON.stringify(pending.merged)], { type: 'application/json' });
      const up = await assetsApi.upload(blob, { type: 'application/json' });
      await dbApi.doc('datasets/smartmoney_transfers').update({
        source: { kind: 'file', url: up.url, name: 'smartmoney_transfers.json' },
        updated: { at: new Date().toISOString(), by: 'นำเข้าจากหน้าแดชบอร์ด: ' + pending.files.join(', ') },
      });
      try { dash.refresh('smartmoney_transfers'); } catch (e) { /* the source reloads on its own */ }
      say('นำเข้าเรียบร้อย ข้อมูล Smart Money อัปเดตแล้ว (' + f0(pending.merged.length) + ' รายการ)', false);
      pending = null;
    } catch (e) {
      say('บันทึกไม่สำเร็จ: ' + ((e && (e.message || e.code)) || 'ไม่ทราบสาเหตุ') + '\nเฉพาะผู้มีสิทธิ์แก้ไขแดชบอร์ดเท่านั้นที่นำเข้าได้', false);
    } finally { impOk.disabled = false; }
  });
  (async () => {
    try { downloadsApi = await claude.use('downloads'); } catch (e) { downloadsApi = null; }
    exportBtn.hidden = !downloadsApi;
    document.querySelectorAll('.lx-tbl-export').forEach((b) => { b.hidden = !downloadsApi; });
    try { assetsApi = await claude.use('assets'); dbApi = await claude.use('db'); } catch (e) { assetsApi = null; }
    importBtn.hidden = !(assetsApi && dbApi);
  })();

  // ---------- period selection: fiscal year or date range ----------
  const isoOk = (s) => typeof s === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(s);
  const curRange = () => {
    const p = dash.params();
    if (!isoOk(p.start) || !isoOk(p.end)) return null;
    return p.start <= p.end ? { start: p.start, end: p.end } : { start: p.end, end: p.start };
  };
  const thDate = (iso) => { const [y, m, d] = iso.split('-').map(Number); return d + ' ' + TH_M[m] + ' ' + (y + 543); };
  const fyOfIso = (iso) => { const [y, m] = iso.split('-').map(Number); return y + 543 + (m >= 10 ? 1 : 0); };
  const monthsBetween = (a, b) => {
    const out = [];
    let [y, m] = a.split('-').map(Number);
    const [y2, m2] = b.split('-').map(Number);
    while (y < y2 || (y === y2 && m <= m2)) { out.push(y + '-' + String(m).padStart(2, '0')); m += 1; if (m > 12) { m = 1; y += 1; } }
    return out;
  };
  const daysBetween = (a, b) => {
    const out = [];
    for (let t = Date.parse(a + 'T00:00:00Z'); t <= Date.parse(b + 'T00:00:00Z'); t += 864e5) out.push(new Date(t).toISOString().slice(0, 10));
    return out;
  };
  const period = () => {
    const fy = curFy(), r = curRange();
    if (!r) return { fy, range: null, label: 'ปีงบ ' + fy, short: 'ปีงบ ' + fy, months: fyMonths(fy), inDate: (row) => row.fy === fy, inMonth: (row) => row.fy === fy };
    const m1 = r.start.slice(0, 7), m2 = r.end.slice(0, 7);
    return {
      fy: fyOfIso(r.end), range: r,
      label: (m1 === m2 ? 'เดือน ' + TH_M[+m1.slice(5)] + ' ' + (+m1.slice(0, 4) + 543) : 'ช่วง ' + TH_M[+m1.slice(5)] + ' ' + (+m1.slice(0, 4) + 543) + ' – ' + TH_M[+m2.slice(5)] + ' ' + (+m2.slice(0, 4) + 543)),
      short: (r.start + '_' + r.end),
      months: monthsBetween(m1, m2),
      days: daysBetween(r.start, r.end),
      inDate: (row) => row.date >= r.start && row.date <= r.end,
      inMonth: (row) => row.month >= m1 && row.month <= m2,
    };
  };
  // marks point at fiscal-year figures; while a date range is on, the figures are worked out here instead
  const setMarked = (scope, on) => {
    scope.querySelectorAll('[data-fy-where]').forEach((el) => {
      if (on && el.dataset.mfield) {
        el.dataset.field = el.dataset.mfield; el.dataset.where = el.dataset.mwhere;
        delete el.dataset.mfield; delete el.dataset.mwhere;
      } else if (!on && el.dataset.field) {
        el.dataset.mfield = el.dataset.field; el.dataset.mwhere = el.dataset.where;
        delete el.dataset.field; delete el.dataset.where;
      }
    });
  };
  const fmtField = (f, v) => {
    if (v == null || Number.isNaN(v)) return '—';
    if (f === 'chg_pct' || f === 'paid_chg_pct') return (v > 0 ? '+' : '') + f1(v);
    if (/_mb$/.test(f)) return f2(v);
    if (/_pct$/.test(f)) return f1(v);
    return f0(v);
  };
  // fill unmarked (range-mode) figures from a values object, matching by the stored field name
  const fillComputed = (scope, values, match) => {
    scope.querySelectorAll('[data-fy-where][data-mfield]').forEach((el) => {
      if (match && !match(el)) return;
      el.classList.remove('dash-skeleton');
      el.textContent = fmtField(el.dataset.mfield, values[el.dataset.mfield]);
    });
  };
  const sumT = (rows) => {
    const amount = d3.sum(rows, (r) => r.amount), net = d3.sum(rows, (r) => r.net);
    return { amount, net, lines: rows.length, amount_mb: Math.round(amount / 1e4) / 100, net_mb: Math.round(net / 1e4) / 100, deducted_mb: Math.round((amount - net) / 1e4) / 100 };
  };
  const sumC = (rows) => {
    const claims = d3.sum(rows, (r) => r.claims), err = d3.sum(rows, (r) => r.err_claims), billed = d3.sum(rows, (r) => r.billed), paid = d3.sum(rows, (r) => r.paid);
    return { claims, err_claims: err, billed, paid, billed_mb: Math.round(billed / 1e4) / 100, paid_mb: Math.round(paid / 1e4) / 100,
      pay_pct: billed ? Math.round((paid / billed) * 1000) / 10 : 0, err_pct: claims ? Math.round((err / claims) * 1000) / 10 : 0 };
  };
  // bars over time: whole months, or days when the range is short
  const timeBars = (el, P, monthRows, dayRows, valKey, tipFn) => {
    if (P.range && P.days.length <= 62) {
      const byD = d3.rollup(dayRows, (v) => ({ v: d3.sum(v, (r) => r[valKey]), amount: d3.sum(v, (r) => r.amount ?? r.billed ?? 0), lines: v.length }), (r) => r.date);
      const rows = P.days.map((d) => ({ k: d, v: byD.get(d)?.v || 0, r: byD.get(d) }));
      barChart(el, rows, { label: (k) => String(Number(k.slice(8))), tip: (d) => tipFn(thDate(d.k), d) });
    } else {
      const byM = new Map(monthRows.map((r) => [r.month, r]));
      const rows = P.months.map((m) => ({ k: m, v: byM.get(m)?.[valKey] || 0, r: byM.get(m) }));
      barChart(el, rows, { label: mLabel, tip: (d) => tipFn(mLabel(d.k), d) });
    }
  };

  const drawSeamless = (P) => {
    const sec = document.getElementById('seamless');
    const grp = curGroup();
    sec.querySelectorAll('.lx-sg').forEach((b) => b.setAttribute('aria-pressed', String(b.dataset.group === grp)));
    sec.querySelector('.lx-sel-name').textContent = grp;
    const t = dash.data('smartmoney_transfers');
    const gd = dash.data('seamless_by_group');
    // rows of the chosen period, per group
    let per = null;
    if (t.status === 'ok') {
      const rows = t.data.filter((r) => P.inDate(r) && sgOf(r.period));
      per = { 'ทั้งหมด': sumT(rows) };
      d3.group(rows, (r) => sgOf(r.period)).forEach((v, k) => { per[k] = sumT(v); });
      per.rows = rows;
    }
    const valOf = (g) => (per && per[g]) || sumT([]);
    sec.querySelectorAll('.lx-sg').forEach((b) => b.classList.toggle('zero', !(valOf(b.dataset.group).lines)));
    if (P.range && per) {
      const all = valOf('ทั้งหมด').net;
      sec.querySelectorAll('[data-fy-where][data-mfield]').forEach((el) => {
        const tile = el.closest('.lx-sg');
        const g = tile ? tile.dataset.group : el.hasAttribute('data-sg-where') ? grp : 'ทั้งหมด';
        const v = valOf(g);
        const vals = { ...v, net: Math.round(v.net * 100) / 100, share_pct: all ? Math.round((v.net / all) * 1000) / 10 : 0 };
        el.classList.remove('dash-skeleton');
        el.textContent = fmtField(el.dataset.mfield, vals[el.dataset.mfield]);
      });
    }
    const mEl = sec.querySelector('[data-chart="sg-monthly"]');
    const sm = status('seamless_by_month');
    if (sm === 'error') fail(mEl); else if (sm === 'ok' && per) {
      const mrows = dash.data('seamless_by_month').data.filter((r) => r.group === grp && P.inMonth(r));
      const drows = per.rows.filter((r) => grp === 'ทั้งหมด' || sgOf(r.period) === grp);
      if (!drows.length) empty(mEl, 'ไม่มีเงินโอนของกลุ่มนี้ในช่วงที่เลือก');
      else timeBars(mEl, P, mrows, drows, 'net', (h, d) => [h, 'โอนสุทธิ ' + f2(d.v) + ' บาท', 'ยอดจัดสรร ' + f2(d.r?.amount || 0) + ' บาท', (d.r?.lines || 0) + ' รายการ']);
    }
    const gEl = sec.querySelector('[data-chart="sg-groups"]');
    if (gd.status === 'error') fail(gEl); else if (per) {
      const list = Object.keys(per).filter((k) => k !== 'ทั้งหมด' && k !== 'rows');
      const rows = list.map((k) => ({ k, v: Math.round(per[k].net / 1e4) / 100 })).filter((r) => r.v !== 0).sort((a, b) => b.v - a.v);
      if (!rows.length) empty(gEl, 'ไม่มีเงินโอน Seamless ในช่วงที่เลือก'); else hbars(gEl, rows, { fmt: (v) => f2(v) + ' ล้านบาท' });
    }
    const yEl = sec.querySelector('[data-chart="sg-years"]');
    if (gd.status === 'error') fail(yEl); else if (gd.status === 'ok') {
      const fy = P.fy;
      const yrows = gd.data.filter((r) => r.group === grp).sort((a, b) => a.fy - b.fy).slice(-5);
      yearBars(yEl, yrows.map((r) => ({ k: String(r.fy), v: r.net_mb, extra: f0(r.lines) + ' รายการ' })), String(fy));
      const tb = sec.querySelector('.lx-sgyt tbody');
      tb.replaceChildren(...yrows.slice().reverse().map((r) => {
        const tr = document.createElement('tr'); tr.dataset.key = r.fy + ',' + encodeURIComponent(r.group);
        if (r.fy === fy) tr.className = 'sel';
        [[String(r.fy), ''], [f2(r.amount_mb), 'lx-n'], [f2(r.net_mb), 'lx-n'], [f0(r.lines), 'lx-n'], [f1(r.share_pct) + '%', 'lx-n']].forEach(([x, c]) => { const td = document.createElement('td'); td.textContent = x; td.className = c; tr.append(td); });
        return tr;
      }));
    }
    const tcard = document.getElementById('seamless-table');
    if (per) {
      drawTable(tcard, per.rows.filter((r) => grp === 'ทั้งหมด' || sgOf(r.period) === grp), [
        { f: 'date_th' }, { f: 'period', wrap: true }, { f: 'fund', wrap: true }, { f: 'subfund', wrap: true },
        { f: 'amount', n: true, fmt: f2 }, { f: 'deducted_total', n: true, fmt: f2, bad: true }, { f: 'net', n: true, fmt: f2 },
      ], { defSort: 'date', totals: (l) => 'คงเหลือรวม ' + f2(d3.sum(l, (r) => r.net)) + ' บาท' });
    }
  };

  // ---------- draw ----------
  const setWhere = (scope, P) => {
    scope.querySelectorAll('[data-fy-where][data-where]').forEach((el) => {
      el.dataset.where = el.dataset.where.replace(/fy=\d+/, 'fy=' + P.fy);
    });
    scope.querySelectorAll('.lx-fy-label').forEach((el) => { el.textContent = P.label; });
  };
  const fillMarks = (scope, srcId) => {
    const d = dash.data(srcId);
    scope.querySelectorAll(`[data-source="${srcId}"][data-field][data-where]`).forEach((el) => {
      if (d.status !== 'ok') { if (d.status !== 'loading') { el.classList.remove('dash-skeleton'); el.textContent = '—'; } return; }
      el.classList.remove('dash-skeleton');
      const cond = Object.fromEntries(el.dataset.where.split('&').map((p) => p.split('=').map(decodeURIComponent)));
      const row = d.data.find((r) => Object.entries(cond).every(([k, v]) => String(r[k]) === v));
      const v = row ? row[el.dataset.field] : null;
      const f = el.dataset.field;
      el.textContent = fmtField(f, v);
      if (f === 'chg_pct' || f === 'paid_chg_pct') { el.classList.toggle('up', v > 0); el.classList.toggle('down', v < 0); }
    });
  };

  const drawFySelect = () => {
    const a = dash.data('sm_by_fy'), b = dash.data('ec_by_fy');
    const ys = new Set();
    if (a.status === 'ok') a.data.forEach((r) => ys.add(r.fy));
    if (b.status === 'ok') b.data.forEach((r) => ys.add(r.fy));
    const list = [...ys].sort((x, y) => y - x);
    const fy = curFy();
    if (!list.length) list.push(fy);
    if (fySel.options.length !== list.length) fySel.replaceChildren(...list.map((y) => new Option('พ.ศ. ' + y, String(y))));
    fySel.value = String(fy);
  };

  const drawSmart = (P) => {
    const sec = document.getElementById('smartmoney');
    const smY = dash.data('sm_by_fy');
    const chg = sec.querySelector('.lx-chgwrap');
    if (P.range) chg.hidden = true;
    else if (smY.status === 'ok') { const r = smY.data.find((x) => x.fy === P.fy); chg.hidden = !(r && r.chg_pct != null); }
    const t = dash.data('smartmoney_transfers');
    const rows = t.status === 'ok' ? t.data.filter(P.inDate) : null;
    if (P.range && rows) fillComputed(sec, sumT(rows));

    const mEl = sec.querySelector('[data-chart="sm-monthly"]');
    const st = status('sm_by_month');
    if (st === 'error') fail(mEl); else if (st === 'ok' && rows) {
      if (P.range && !rows.length) empty(mEl, 'ไม่มีเงินโอนในช่วงที่เลือก');
      else timeBars(mEl, P, dash.data('sm_by_month').data.filter(P.inMonth), rows, 'net', (h, d) => [h, 'โอนสุทธิ ' + f2(d.v) + ' บาท', 'ยอดจัดสรร ' + f2(d.r?.amount || 0) + ' บาท', (d.r?.lines || 0) + ' รายการ']);
    }
    const fEl = sec.querySelector('[data-chart="sm-funds"]');
    const sf = status('sm_by_fund');
    if (sf === 'error') fail(fEl); else if (sf === 'ok' && rows) {
      const all = d3.rollups(rows, (v) => d3.sum(v, (r) => r.net), (r) => r.fund).map(([fund, net]) => ({ fund, net })).filter((r) => r.net > 0);
      const top = all.sort((a, b) => b.net - a.net).slice(0, 10).map((r) => ({ k: r.fund, v: r.net }));
      if (!top.length) empty(fEl, 'ไม่มีเงินโอนในช่วงที่เลือก');
      else hbars(fEl, top, { fmt: f0, total: d3.sum(all, (r) => r.net) });
    }
    drawCompare(sec, P.fy, null);
    if (rows) {
      drawTable(document.getElementById('sm-table'), rows, [
        { f: 'date_th' }, { f: 'period', wrap: true }, { f: 'fund', wrap: true }, { f: 'subfund', wrap: true },
        { f: 'amount', n: true, fmt: f2 }, { f: 'deducted_total', n: true, fmt: f2, bad: true }, { f: 'net', n: true, fmt: f2 },
      ], { defSort: 'date', totals: (l) => 'คงเหลือรวม ' + f2(d3.sum(l, (r) => r.net)) + ' บาท' });
    }
  };

  const PP_ITEMS = [['ANC', 'บริการฝากครรภ์ ANC 1-8 ครั้ง'], ['ANC_LAB', 'บริการตรวจทางห้องปฏิบัติการ ครั้งที่ 1-2'], ['ANC_ULTRA', 'บริการตรวจอัลตราซาวด์'], ['ANC_DENT', 'บริการขัดฟันทำความสะอาด ANC'],
    ['DM_GDM', 'บริการสำหรับกลุ่มหญิงที่มีภาวะเบาหวานในขณะตั้งครรภ์ (GDM)'], ['PP_ANC_PRT', 'การทดสอบการตั้งครรภ์ (PRT)'], ['PP_ANC_PNC', 'การตรวจหลังคลอด (PNC) / จ่ายค่าเดินทาง'],
    ['PP_HEALTH_CHECKUP', 'บริการเจาะเลือดส่งตรวจ FPG / Cholesterol / HDL'], ['PP_CBC', 'บริการเจาะเลือดเพื่อตรวจนับเม็ดเลือดอย่างสมบูรณ์ (CBC)'], ['PP_CA_COLORECTAL', 'คัดกรองมะเร็งลำไส้ใหญ่และลำไส้ตรง FIT TEST อายุ 50-70 ปี'],
    ['PP_DENT_FLUORIDE', 'บริการเคลือบฟลูออไรด์ชนิดเข้มข้นสูงเฉพาะกลุ่มเสี่ยง 25-59 ปี'], ['HERB_FS', 'จ่ายยาสมุนไพร (HERBFS+OPPP INDIVIDUAL)'], ['WALKIN_ONE-ID', 'บริการนอกเขตในกรณีมีเหตุผลสมควร OP Anywhere'],
    ['WALKIN_DRUG_OTHER_ONE-ID', 'บริการนอกเขตในกรณีมีเหตุผลสมควร OP Anywhere (ยา)'], ['PP_HEME_IRON', 'บริการยาเสริมธาตุเหล็ก / ป้องกันโลหิตจาง'], ['FPNHSO', 'บริการยาคุมกำเนิด (ยาคุมใต้ผิวหนัง, ยาฉีด, ยาฝัง, ถอด)'],
    ['PP_IRON_SCREENING', 'บริการคัดกรองภาวะโลหิตจาง การขาดธาตุเหล็ก'], ['HERB', 'รายการแผนไทย นวด อบ ประคบ']];
  const ppOpen = new Set();
  const drawPpItems = (sec, P) => {
    const card = sec.querySelector('#ppfs-services');
    if (!card) return;
    const d = dash.data('ppfs_items');
    const tb = card.querySelector('tbody'), tf = card.querySelector('tfoot');
    if (d.status !== 'ok') return;
    const rows = d.data.filter(P.inMonth);
    const byCode = d3.group(rows, (r) => r.code);
    const cell = (txt, cls) => { const td = document.createElement('td'); td.textContent = txt; if (cls) td.className = cls; return td; };
    const out = [];
    let totN = 0, totP = 0, active = 0;
    PP_ITEMS.forEach(([code, name]) => {
      const list = byCode.get(code) || [];
      const isLab = code === 'ANC_LAB';
      const n = isLab ? d3.sum(list, (r) => (r.lab1 || 0) + (r.lab2 || 0)) : d3.sum(list, (r) => r.claims), pay = d3.sum(list, (r) => r.paid);
      totN += n; totP += pay; if (n) active += 1;
      const tr = document.createElement('tr'); tr.className = 'lx-pp-main' + (ppOpen.has(code) ? ' open' : ''); tr.dataset.code = code;
      const first = document.createElement('td'); const tog = document.createElement('span'); tog.className = 'lx-tog'; tog.textContent = '▶'; first.append(tog, document.createTextNode(' ' + name));
      const unpaid = code === 'DM_GDM' ? d3.sum(list, (r) => r.unpaid || 0) : 0;
      if (unpaid) { const sm = document.createElement('small'); sm.className = 'lx-pp-hint lx-pp-warn'; sm.textContent = 'ได้รับชดเชย ' + f0(n) + ' ครั้ง × 2,620 บ. · ไม่พบยอดชดเชย 2,620 อีก ' + f0(unpaid) + ' รายการ (ควรตรวจสอบ)'; first.append(document.createElement('br'), sm); }
      if (isLab) { const sm = document.createElement('small'); sm.className = 'lx-pp-hint'; sm.textContent = 'LAB1 ' + f0(d3.sum(list, (r) => r.lab1 || 0)) + ' ครั้ง (600 บ.) · LAB2 ' + f0(d3.sum(list, (r) => r.lab2 || 0)) + ' ครั้ง (190 บ.) · คำนวณจากยอด PP ตามราคา PP Fee 68'; first.append(document.createElement('br'), sm); }
      const st = document.createElement('td'); st.className = 'lx-c'; const b = document.createElement('span'); b.className = 'lx-badge' + (n ? '' : ' none'); b.textContent = n ? 'มีผลงาน' : '-'; st.append(b);
      tr.append(first, cell(code, 'lx-code'), cell(f0(n), 'lx-n'), cell('฿' + f2(pay), 'lx-n lx-money'), st);
      out.push(tr);
      if (ppOpen.has(code)) {
        const months = d3.rollups(list, (v) => ({ n: isLab ? d3.sum(v, (r) => (r.lab1 || 0) + (r.lab2 || 0)) : d3.sum(v, (r) => r.claims), p: d3.sum(v, (r) => r.paid), l1: d3.sum(v, (r) => r.lab1 || 0), l2: d3.sum(v, (r) => r.lab2 || 0) }), (r) => r.month).sort((a, b) => (a[0] < b[0] ? -1 : 1));
        if (code === 'ANC') {
          const pd = dash.data('anc_patterns');
          if (pd.status === 'ok') {
            const pats = d3.rollups(pd.data.filter(P.inMonth), (v) => ({ n: d3.sum(v, (r) => r.claims), p: d3.sum(v, (r) => r.paid) }), (r) => r.pattern).sort((a, b) => b[1].n - a[1].n);
            if (pats.length) {
              const h = document.createElement('tr'); h.className = 'lx-pp-sub lx-pp-subh'; const td = cell('องค์ประกอบที่เคลมในแต่ละรายการ (แยกตามราคา PP Fee 68)'); td.colSpan = 5; h.append(td); out.push(h);
              pats.forEach(([pat, v]) => { const s2 = document.createElement('tr'); s2.className = 'lx-pp-sub'; s2.append(cell(pat), cell('รวม ' + f2(v.n ? v.p / v.n : 0) + ' บ./รายการ'), cell(f0(v.n) + ' รายการ', 'lx-n'), cell('฿' + f2(v.p), 'lx-n'), cell('')); out.push(s2); });
              const h2 = document.createElement('tr'); h2.className = 'lx-pp-sub lx-pp-subh'; const td2 = cell('จำนวนครั้งฝากครรภ์ (360 บ.) รายเดือน'); td2.colSpan = 5; h2.append(td2); out.push(h2);
            }
          }
        }
        if (!months.length) { const s = document.createElement('tr'); s.className = 'lx-pp-sub'; const td = cell('ไม่มีรายการในช่วงที่เลือก'); td.colSpan = 5; s.append(td); out.push(s); }
        months.forEach(([m, v]) => { const s = document.createElement('tr'); s.className = 'lx-pp-sub'; s.append(cell(mLabel(m)), cell(isLab ? 'LAB1 ' + f0(v.l1) + ' · LAB2 ' + f0(v.l2) : ''), cell(f0(v.n), 'lx-n'), cell('฿' + f2(v.p), 'lx-n'), cell('')); out.push(s); });
      }
    });
    tb.replaceChildren(...out);
    const ft = document.createElement('tr');
    const st = document.createElement('td'); st.className = 'lx-c'; const b = document.createElement('span'); b.className = 'lx-badge sum'; b.textContent = 'รวม'; st.append(b);
    ft.append(cell('รวมกองทุนย่อย ' + PP_ITEMS.length + ' กลุ่ม, มีผลงาน ' + active + ' กลุ่ม'), cell('-'), cell(f0(totN), 'lx-n'), cell('฿' + f2(totP), 'lx-n lx-money'), st);
    tf.replaceChildren(ft);
    if (!tb.dataset.bound) {
      tb.dataset.bound = '1';
      tb.addEventListener('click', (ev) => {
        const tr = ev.target.closest('tr.lx-pp-main');
        if (!tr) return;
        const c = tr.dataset.code;
        if (ppOpen.has(c)) ppOpen.delete(c); else ppOpen.add(c);
        draw();
      });
    }
  };

  const drawClaims = (sec, P) => {
    const cat = sec.dataset.cat;
    const t = dash.data('eclaim_summary');
    const rows = t.status === 'ok' ? t.data.filter((r) => r.cat === cat && P.inMonth(r)) : null;
    const tot = rows ? sumC(rows) : null;
    if (P.range && tot) fillComputed(sec, tot);
    const g = sec.querySelector('[data-chart="gauge"]');
    const s1 = dash.data('ec_by_fy');
    if (s1.status === 'error') fail(g); else if (tot) {
      if (tot.claims) gauge(g, tot.pay_pct); else empty(g, 'ไม่มีข้อมูลในช่วงที่เลือก');
    }
    const mEl = sec.querySelector('[data-chart="monthly"]');
    const sm = status('ec_by_month');
    if (sm === 'error') fail(mEl); else if (sm === 'ok' && rows) {
      const byM = new Map(dash.data('ec_by_month').data.filter((r) => r.cat === cat && P.inMonth(r)).map((r) => [r.month, r]));
      const mrows = P.months.map((m) => ({ k: m, v: byM.get(m)?.paid || 0, r: byM.get(m) }));
      if (!rows.length) empty(mEl, 'ไม่มีข้อมูลในช่วงที่เลือก');
      else barChart(mEl, mrows, { label: mLabel, tip: (d) => [mLabel(d.k), 'ได้รับชดเชย ' + f2(d.v) + ' บาท', 'เรียกเก็บ ' + f2(d.r?.billed || 0) + ' บาท', f0(d.r?.claims || 0) + ' รายการ · ติด Error ' + f0(d.r?.err_claims || 0)] });
    }
    const dEl = sec.querySelector('[data-chart="donut"]');
    const sr = status('ec_by_right');
    if (sr === 'error') fail(dEl); else if (sr === 'ok' && rows) {
      const all = d3.rollups(rows, (v) => d3.sum(v, (r) => r.claims), (r) => r.right).map(([right, claims]) => ({ right, claims })).sort((a, b) => b.claims - a.claims);
      const top = all.slice(0, 5).map((r) => ({ k: r.right, v: r.claims }));
      const rest = d3.sum(all.slice(5), (r) => r.claims);
      if (rest > 0) top.push({ k: 'อื่น ๆ', v: rest });
      if (!top.length) empty(dEl, 'ไม่มีข้อมูลในช่วงที่เลือก'); else donut(dEl, top);
    }
    const iEl = sec.querySelector('[data-chart="items"]');
    const si = status('ec_by_item');
    if (si === 'error') fail(iEl); else if (si === 'ok' && rows) {
      const all = d3.rollups(rows, (v) => d3.sum(v, (r) => r.paid), (r) => r.item).map(([item, paid]) => ({ item, paid }));
      const top = all.filter((r) => r.paid > 0).sort((a, b) => b.paid - a.paid).slice(0, 10).map((r) => ({ k: r.item === '-' ? '(ไม่ระบุกองทุน)' : r.item, v: r.paid }));
      if (!top.length) empty(iEl, 'ไม่มีข้อมูลในช่วงที่เลือก'); else hbars(iEl, top, { fmt: f0, total: d3.sum(all, (r) => r.paid) });
    }
    const eEl = sec.querySelector('[data-chart="errors"]');
    const se = dash.data('eclaim_errors');
    if (se.status === 'error') fail(eEl); else if (se.status === 'ok') {
      const all = d3.rollups(se.data.filter((r) => r.cat === cat && (r.month ? P.inMonth(r) : r.fy === P.fy)), (v) => d3.sum(v, (r) => r.claims), (r) => r.code);
      const top = all.sort((a, b) => b[1] - a[1]).slice(0, 10).map(([code, n]) => ({ k: 'รหัส ' + code, v: n }));
      if (!top.length) empty(eEl, 'ไม่พบรายการติด Error ในช่วงที่เลือก');
      else hbars(eEl, top, { fmt: (v) => f0(v) + ' รายการ', err: true, total: 0 });
    }
    if (cat === 'PPFS') drawPpItems(sec, P);
    drawCompare(sec, P.fy, cat);
    if (rows) {
      drawTable(sec.querySelector('.lx-tablecard'), rows, [
        { f: 'month_th' }, { f: 'right' }, { f: 'item', wrap: true },
        { f: 'claims', n: true, fmt: f0 }, { f: 'err_claims', n: true, fmt: f0, bad: true },
        { f: 'billed', n: true, fmt: f2 }, { f: 'paid', n: true, fmt: f2 },
      ], { defSort: 'paid', totals: (l) => f0(d3.sum(l, (r) => r.claims)) + ' รายการ · ชดเชยรวม ' + f2(d3.sum(l, (r) => r.paid)) + ' บาท' });
    }
  };

  // ---------- range controls (month to month) ----------
  const fromSel = document.querySelector('.lx-mfrom');
  const toSel = document.querySelector('.lx-mto');
  const clearBtn = document.querySelector('.lx-range-clear');
  const rangeNote = document.querySelector('.lx-range-note');
  const lastDay = (m) => { const [y, mo] = m.split('-').map(Number); return m + '-' + String(new Date(Date.UTC(y, mo, 0)).getUTCDate()).padStart(2, '0'); };
  const applyMonths = () => {
    let a = fromSel.value, b = toSel.value;
    if (!a && !b) return;
    if (!a) a = b; if (!b) b = a;
    if (a > b) [a, b] = [b, a];
    dash.setParams({ start: a + '-01', end: lastDay(b) });
  };
  fromSel.addEventListener('change', applyMonths);
  toSel.addEventListener('change', applyMonths);
  clearBtn.addEventListener('click', () => dash.setParams({ start: null, end: null }));
  fySel.addEventListener('change', () => dash.setParams({ start: null, end: null }));
  const drawRangeControls = () => {
    const r = curRange();
    clearBtn.hidden = !r;
    root.classList.toggle('lx-ranged', !!r);
    const months = new Set();
    const a = dash.data('smartmoney_transfers'), b = dash.data('ec_by_month');
    if (a.status === 'ok') a.data.forEach((x) => months.add(x.month));
    if (b.status === 'ok') b.data.forEach((x) => months.add(x.month));
    const list = [...months].filter((m) => m >= '2021-10').sort().reverse();
    const opt = (m) => { const [y, mo] = m.split('-').map(Number); return new Option(TH_M[mo] + ' ' + (y + 543), m); };
    if (fromSel.options.length !== list.length + 1) {
      fromSel.replaceChildren(new Option('ตั้งแต่เดือน…', ''), ...list.map(opt));
      toSel.replaceChildren(new Option('ถึงเดือน…', ''), ...list.map(opt));
    }
    fromSel.value = r ? r.start.slice(0, 7) : '';
    toSel.value = r ? r.end.slice(0, 7) : '';
    const mName = (iso) => { const [y, mo] = iso.split('-').map(Number); return TH_M[mo] + ' ' + (y + 543); };
    rangeNote.textContent = r ? 'กำลังแสดง ' + (r.start.slice(0, 7) === r.end.slice(0, 7) ? 'เดือน ' + mName(r.start) : mName(r.start) + ' – ' + mName(r.end)) + ' (' + thDate(r.start) + ' – ' + thDate(r.end) + ')' : '';
    rangeNote.hidden = !r;
  };

  // ---------- presentation (animated allocation story) ----------
  let pScene = 0, pPlaying = true, pTimer = null, pAnimate = true;
  const pStage = document.getElementById('present-stage');
  const pScenes = pStage ? [...pStage.querySelectorAll('.lx-scene')] : [];
  const pDots = document.querySelector('.lx-dots');
  const pPlayBtn = document.querySelector('.lx-playbtn');
  const reduceMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
  const pStop = () => { clearTimeout(pTimer); pTimer = null; };
  const pSchedule = () => { pStop(); if (pPlaying && current === 'present') pTimer = setTimeout(() => pGo(pScene + 1), 7000); };
  const pGo = (i) => { pScene = (i + pScenes.length) % pScenes.length; pAnimate = true; draw(); pSchedule(); };
  document.querySelector('.lx-prev')?.addEventListener('click', () => pGo(pScene - 1));
  document.querySelector('.lx-next')?.addEventListener('click', () => pGo(pScene + 1));
  pPlayBtn?.addEventListener('click', () => { pPlaying = !pPlaying; pPlayBtn.textContent = pPlaying ? '⏸ หยุด' : '▶ เล่น'; if (pPlaying) pSchedule(); else pStop(); });
  const countUp = (el) => {
    const txt = el.textContent; const v = Number(txt.replace(/,/g, ''));
    if (!isFinite(v) || reduceMotion) return;
    const t0 = performance.now(), dur = 1500;
    const step = (t) => { const k = Math.min(1, (t - t0) / dur); el.textContent = f2(v * (1 - Math.pow(1 - k, 3))); if (k < 1) requestAnimationFrame(step); else el.textContent = txt; };
    requestAnimationFrame(step);
  };
  const stageSvg = (el, H) => {
    el.replaceChildren();
    const W = Math.max(300, el.clientWidth);
    const svg = d3.select(el).append('svg').attr('width', '100%').attr('viewBox', `0 0 ${W} ${H}`).attr('role', 'img');
    return { svg, W, H };
  };
  const drawWaterfall = (el, r, anim) => {
    const { svg, W, H } = stageSvg(el, 340);
    const mt = 40, mb = 44;
    const items = [
      { k: 'ยอดจัดสรร', lo: 0, hi: r.amount_mb, v: r.amount_mb, c: '#ffd166' },
      { k: 'ถูกหัก / ชะลอ', lo: r.net_mb, hi: r.amount_mb, v: r.deducted_mb, c: '#ff8a80' },
      { k: 'โอนเข้าบัญชีจริง', lo: 0, hi: r.net_mb, v: r.net_mb, c: '#69f0ae' },
    ];
    const y = d3.scaleLinear([0, r.amount_mb * 1.05 || 1], [H - mb, mt]);
    const x = d3.scaleBand(items.map((d) => d.k), [20, W - 20]).padding(0.35);
    const g = svg.append('g');
    // connectors
    g.append('line').attr('x1', x(items[0].k) + x.bandwidth()).attr('x2', x(items[1].k)).attr('y1', y(r.amount_mb)).attr('y2', y(r.amount_mb)).style('stroke', 'rgba(255,255,255,.4)').style('stroke-dasharray', '4 4');
    g.append('line').attr('x1', x(items[1].k) + x.bandwidth()).attr('x2', x(items[2].k)).attr('y1', y(r.net_mb)).attr('y2', y(r.net_mb)).style('stroke', 'rgba(255,255,255,.4)').style('stroke-dasharray', '4 4');
    const bars = g.selectAll('rect.b').data(items).join('rect').attr('class', 'b').attr('x', (d) => x(d.k)).attr('width', x.bandwidth()).attr('rx', 8).style('fill', (d) => d.c);
    const lbl = g.selectAll('text.v').data(items).join('text').attr('class', 'v').attr('x', (d) => x(d.k) + x.bandwidth() / 2).attr('y', (d) => y(d.hi) - 12).attr('text-anchor', 'middle').style('font-size', '22px').style('font-weight', 700).text((d) => (d.k.startsWith('ถูก') ? '−' : '') + f2(d.v) + ' ล้าน');
    g.selectAll('text.k').data(items).join('text').attr('class', 'k').attr('x', (d) => x(d.k) + x.bandwidth() / 2).attr('y', H - 14).attr('text-anchor', 'middle').style('font-size', '16px').text((d) => d.k);
    const pct = r.amount_mb ? (r.deducted_mb / r.amount_mb) * 100 : 0;
    g.append('text').attr('x', x(items[1].k) + x.bandwidth() / 2).attr('y', y((r.amount_mb + r.net_mb) / 2) + 6).attr('text-anchor', 'middle').style('font-size', '15px').style('font-weight', 700).style('fill', '#3b0d0d').text(f1(pct) + '%');
    if (anim) {
      bars.attr('y', (d) => y(d.lo)).attr('height', 0).transition().delay((d, i) => 300 + i * 900).duration(900).ease(d3.easeCubicOut)
        .attr('y', (d) => y(d.hi)).attr('height', (d) => Math.max(2, y(d.lo) - y(d.hi)));
      lbl.style('opacity', 0).transition().delay((d, i) => 900 + i * 900).duration(500).style('opacity', 1);
    } else {
      bars.attr('y', (d) => y(d.hi)).attr('height', (d) => Math.max(2, y(d.lo) - y(d.hi)));
    }
  };
  const drawAllocYears = (el, rows, fy, anim, titleEl) => {
    const { svg, W, H } = stageSvg(el, 340);
    const mt = 40, mb = 40;
    const y = d3.scaleLinear([0, (d3.max(rows, (r) => r.amount_mb) || 1) * 1.12], [H - mb, mt]);
    const x = d3.scaleBand(rows.map((r) => String(r.fy)), [20, W - 20]).padding(0.3);
    const g = svg.append('g');
    const bars = g.selectAll('rect').data(rows).join('rect').attr('x', (d) => x(String(d.fy))).attr('width', x.bandwidth()).attr('rx', 8)
      .style('fill', (d) => (d.fy === fy ? '#ffd166' : 'rgba(159,192,255,.55)'));
    const lbl = g.selectAll('text.v').data(rows).join('text').attr('class', 'v').attr('x', (d) => x(String(d.fy)) + x.bandwidth() / 2).attr('y', (d) => y(d.amount_mb) - 10).attr('text-anchor', 'middle')
      .style('font-size', (d) => (d.fy === fy ? '22px' : '16px')).style('font-weight', 700).text((d) => f2(d.amount_mb));
    g.selectAll('text.k').data(rows).join('text').attr('x', (d) => x(String(d.fy)) + x.bandwidth() / 2).attr('y', H - 12).attr('text-anchor', 'middle').style('font-size', '16px').style('font-weight', (d) => (d.fy === fy ? 700 : 400)).text((d) => 'ปีงบ ' + d.fy);
    if (anim) {
      bars.attr('y', y(0)).attr('height', 0).transition().delay((d, i) => 200 + i * 350).duration(900).ease(d3.easeCubicOut).attr('y', (d) => y(d.amount_mb)).attr('height', (d) => y(0) - y(d.amount_mb));
      lbl.style('opacity', 0).transition().delay((d, i) => 800 + i * 350).duration(400).style('opacity', 1);
    } else bars.attr('y', (d) => y(d.amount_mb)).attr('height', (d) => y(0) - y(d.amount_mb));
    const i = rows.findIndex((r) => r.fy === fy);
    if (titleEl) {
      if (i > 0 && rows[i - 1].amount_mb) {
        const ch = ((rows[i].amount_mb - rows[i - 1].amount_mb) / rows[i - 1].amount_mb) * 100;
        titleEl.textContent = 'ปีงบ ' + fy + ' ยอดจัดสรร' + (ch >= 0 ? 'เพิ่มขึ้น ' : 'ลดลง ') + f1(Math.abs(ch)) + '% จากปีก่อน';
      } else titleEl.textContent = 'ยอดจัดสรรรายปี';
    }
  };
  const drawAllocFunds = (el, fy, anim) => {
    const fd = dash.data('sm_by_fund');
    if (fd.status !== 'ok') return;
    const all = fd.data.filter((r) => r.fy === fy && (r.amount || 0) > 0).sort((a, b) => b.amount - a.amount);
    const total = d3.sum(all, (r) => r.amount);
    const top = all.slice(0, 5).map((r) => ({ k: r.fund, v: r.amount }));
    const rest = d3.sum(all.slice(5), (r) => r.amount);
    if (rest > 0) top.push({ k: 'กองทุนอื่น ๆ', v: rest });
    const { svg, W, H } = stageSvg(el, Math.max(300, top.length * 56 + 20));
    const lw = Math.min(360, W * 0.42);
    const x = d3.scaleLinear([0, d3.max(top, (d) => d.v) || 1], [0, W - lw - 150]);
    const g = svg.append('g');
    const rowsG = g.selectAll('g').data(top).join('g').attr('transform', (d, i) => `translate(0,${10 + i * 56})`);
    rowsG.append('text').attr('x', lw - 12).attr('y', 26).attr('text-anchor', 'end').style('font-size', '15px').text((d) => (d.k.length > 34 ? d.k.slice(0, 33) + '…' : d.k));
    const bars = rowsG.append('rect').attr('x', lw).attr('y', 8).attr('height', 26).attr('rx', 6).style('fill', (d, i) => (i === 0 ? '#ffd166' : i < 5 ? 'rgba(255,209,102,' + (0.85 - i * 0.12) + ')' : 'rgba(159,192,255,.5)'));
    const lbl = rowsG.append('text').attr('y', 27).style('font-size', '15px').style('font-weight', 700).text((d) => f2(d.v / 1e6) + ' ล้าน · ' + f1(total ? (d.v / total) * 100 : 0) + '%');
    if (anim) {
      bars.attr('width', 0).transition().delay((d, i) => 200 + i * 250).duration(900).ease(d3.easeCubicOut).attr('width', (d) => Math.max(3, x(d.v)));
      lbl.attr('x', lw + 8).style('opacity', 0).transition().delay((d, i) => 200 + i * 250).duration(900).ease(d3.easeCubicOut).attr('x', (d) => lw + Math.max(3, x(d.v)) + 10).style('opacity', 1);
    } else { bars.attr('width', (d) => Math.max(3, x(d.v))); lbl.attr('x', (d) => lw + Math.max(3, x(d.v)) + 10); }
  };
  const drawPresent = (P) => {
    if (!pStage) return;
    const fy = curFy();
    const sec = document.getElementById('present');
    sec.querySelectorAll('.lx-fy-label').forEach((e) => { e.textContent = 'ปีงบประมาณ ' + fy; });
    pScenes.forEach((s, i) => s.classList.toggle('on', i === pScene));
    if (pDots.children.length !== pScenes.length) {
      pDots.replaceChildren(...pScenes.map((_, i) => { const b = document.createElement('button'); b.setAttribute('aria-label', 'ฉาก ' + (i + 1)); b.addEventListener('click', () => pGo(i)); return b; }));
    }
    [...pDots.children].forEach((b, i) => b.setAttribute('aria-current', String(i === pScene)));
    const anim = pAnimate && !reduceMotion;
    const y = dash.data('sm_by_fy');
    if (y.status !== 'ok') return;
    pAnimate = false;
    const rows = y.data.slice().sort((a, b) => a.fy - b.fy).slice(-5);
    const r = y.data.find((x) => x.fy === fy);
    const sc = pScenes[pScene];
    if (anim) sc.querySelectorAll('.lx-count').forEach(countUp);
    if (!r) return;
    if (pScene === 1) drawWaterfall(sc.querySelector('[data-chart="waterfall"]'), r, anim);
    if (pScene === 2) drawAllocYears(sc.querySelector('[data-chart="alloc-years"]'), rows, fy, anim, sc.querySelector('.lx-trend-title'));
    if (pScene === 3) drawAllocFunds(sc.querySelector('[data-chart="alloc-funds"]'), fy, anim);
    if (pScene === 4) {
      const per100 = r.amount_mb ? (r.net_mb / r.amount_mb) * 100 : 0;
      sc.querySelector('.lx-sum-cap').textContent = 'จากทุก 100 บาทที่ได้รับจัดสรร เข้าบัญชีโรงพยาบาลจริง ' + f2(per100) + ' บาท';
    }
    if (pPlaying && !pTimer) pSchedule();
  };

  let animated = false;
  function draw() {
    drawFySelect();
    drawRangeControls();
    const P = period();
    tabs.forEach((t) => {
      const TP = t.id === 'present' ? { ...P, fy: curFy(), range: null, label: 'ปีงบประมาณ ' + curFy() } : P;
      setMarked(t, !TP.range);
      setWhere(t, TP);
      if (TP.range) return;
      if (t.id === 'seamless') {
        const grp = curGroup();
        t.querySelectorAll('[data-sg-where][data-where]').forEach((el) => { el.dataset.where = 'fy=' + P.fy + '&group=' + grp; });
        fillMarks(t, 'seamless_by_group');
      } else fillMarks(t, (t.id === 'smartmoney' || t.id === 'present') ? 'sm_by_fy' : 'ec_by_fy');
    });
    const sec = document.getElementById(current);
    if (!sec || sec.hidden) return;
    if (current === 'smartmoney') drawSmart(P); else if (current === 'seamless') drawSeamless(P); else if (current === 'present') drawPresent(P); else drawClaims(sec, P);
    if (!animated && status('sm_by_fy', 'ec_by_fy') === 'ok') { animated = true; root.classList.add('lx-anim'); }
  }

  // skeletons for chart boxes until data arrives
  document.querySelectorAll('.lx-chart, .lx-gauge').forEach((el) => el.classList.add('dash-skeleton'));

  if (dash.params().fy == null) dash.setParams({ fy: DEFAULT_FY });
  const named = location.hash.slice(1);
  show(tabs.some((t) => t.id === named) ? named : 'smartmoney', false);
  dash.onData(draw);
})();
