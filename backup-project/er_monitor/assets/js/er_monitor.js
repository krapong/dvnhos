/**
 * er_monitor.js
 * LOS สี: เขียว 0-30 นาที | เหลือง 30-60 | ส้ม 60-100 | แดง 100-120 | OVER
 */
const ER = (() => {

    const cfg = {
        apiUrl: document.body.dataset.apiUrl || 'api/patients.php',
        refreshSec: parseInt(document.body.dataset.refresh || '30'),
        losLimit: parseInt(document.body.dataset.los || '120'),
        // threshold สีตามเวลาที่ผ่านไป (elapsed minutes)
        T_GREEN: 30,   // 0-30 นาที = เขียว
        T_YELLOW: 60,   // 30-60 นาที = เหลือง
        T_ORANGE: 100,  // 60-100 นาที = ส้ม
        // > 100 นาที = แดง / OVER
        WARN_POPUP: 100, // popup เมื่อผ่าน 100 นาที
    };

    let patients = [];
    let statusMap = {};
    let timerSec = 0;
    let soundOn = true;
    let audioCtx = null;
    let alreadyAlerted = new Set();
    let popupOpen = false;
    let popupTm = null;
    let errorState = false;

    const STATUSES = [
        { cls: 'st0', label: 'Investigation' },
        { cls: 'st1', label: 'Waiting' },
        { cls: 'st2', label: 'Notify staff' },
        { cls: 'st3', label: 'Admit' },
        { cls: 'st4', label: 'Plan refer' },
    ];

    const DEMO = [
        { hn: '2341', queue: 1, time: '13:30:10', p_name: 'นาย สมชาย', color: '1', dch_list: '', lab: 'LB001', dx: 'I21', xr: '', observe: '1', esi_class: 'esi1', esi_label: 'Level 1 Resuscitation' },
        { hn: '1982', queue: 2, time: '13:10:05', p_name: 'นาง พิมพิกา', color: '2', dch_list: '', lab: 'LB002', dx: 'J18', xr: 'XR01', observe: '', esi_class: 'esi2', esi_label: 'Level 2 Emergent' },
        { hn: '3105', queue: 3, time: '12:55:30', p_name: 'นาย ธนพล', color: '2', dch_list: '', lab: '', dx: 'S00', xr: 'XR02', observe: '', esi_class: 'esi2', esi_label: 'Level 2 Emergent' },
        { hn: '0976', queue: 4, time: '12:20:12', p_name: 'นาง จุฑามาศ', color: '3', dch_list: '', lab: 'LB005', dx: 'R51', xr: '', observe: '1', esi_class: 'esi3', esi_label: 'Level 3 Urgent' },
        { hn: '4521', queue: 5, time: '13:40:45', p_name: 'เด็กชาย กิตติพงษ์', color: '3', dch_list: '', lab: 'LB006', dx: 'A09', xr: '', observe: '', esi_class: 'esi3', esi_label: 'Level 3 Urgent' },
        { hn: '2011', queue: 6, time: '13:00:55', p_name: 'นาง นภา', color: '3', dch_list: '', lab: '', dx: 'N39', xr: '', observe: '', esi_class: 'esi3', esi_label: 'Level 3 Urgent' },
        { hn: '3349', queue: 7, time: '14:00:22', p_name: 'นาง บริศรา', color: '4', dch_list: '', lab: '', dx: 'M54', xr: 'XR08', observe: '', esi_class: 'esi4', esi_label: 'Level 4 Less Urgent' },
        { hn: '5123', queue: 8, time: '14:10:10', p_name: 'นาย สุรชัย', color: '4', dch_list: '', lab: '', dx: 'K29', xr: '', observe: '', esi_class: 'esi4', esi_label: 'Level 4 Less Urgent' },
        { hn: '1208', queue: 9, time: '13:50:01', p_name: 'นาย วิรุฬห์', color: '5', dch_list: '', lab: '', dx: 'J00', xr: '', observe: '', esi_class: 'esi5', esi_label: 'Level 5 Non-Urgent' },
    ];

    // ── helpers ───────────────────────────────────────────────────
    function elapsedMin(t) {
        if (!t) return 0;
        const [h, m, s] = t.split(':').map(Number);
        const base = new Date(); base.setHours(h, m, s || 0, 0);
        return Math.max(0, (Date.now() - base) / 60000);
    }

    function fmtMmSs(minutes) {
        const totalSec = Math.round(minutes * 60);
        const m = Math.floor(totalSec / 60);
        const s = totalSec % 60;
        return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
    }

    /** คืน CSS class สีตาม elapsed minutes */
    function losColorClass(elapsed) {
        if (elapsed >= cfg.losLimit) return 'los-over';
        if (elapsed >= cfg.T_ORANGE) return 'los-red';
        if (elapsed >= cfg.T_YELLOW) return 'los-orange';
        if (elapsed >= cfg.T_GREEN) return 'los-yellow';
        return 'los-green';
    }

    /** คืนสีของ progress bar */
    function barColor(elapsed) {
        if (elapsed >= cfg.losLimit) return '#ff0000';
        if (elapsed >= cfg.T_ORANGE) return '#ff3333';
        if (elapsed >= cfg.T_YELLOW) return '#ff7700';
        if (elapsed >= cfg.T_GREEN) return '#ffcc00';
        return '#33dd88';
    }

    /** row highlight class */
    function rowClass(elapsed) {
        if (elapsed >= cfg.losLimit) return 'row-over';
        if (elapsed >= cfg.T_ORANGE) return 'row-danger';
        if (elapsed >= cfg.T_YELLOW) return 'row-warn';
        return '';
    }

    function esc(s) {
        return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    function today() { return new Date().toISOString().slice(0, 10); }

    // ── Audio ─────────────────────────────────────────────────────
    function getCtx() {
        if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        return audioCtx;
    }
    function playTone(freqs, type = 'sine', vol = 0.45, dur = 0.22, gap = 0.08) {
        if (!soundOn) return;
        try {
            const ctx = getCtx();
            let t = ctx.currentTime;
            freqs.forEach(f => {
                if (!f) { t += gap; return; }
                const o = ctx.createOscillator(), g = ctx.createGain();
                o.connect(g); g.connect(ctx.destination);
                o.type = type;
                o.frequency.setValueAtTime(f, t);
                g.gain.setValueAtTime(vol, t);
                g.gain.exponentialRampToValueAtTime(0.001, t + dur);
                o.start(t); o.stop(t + dur);
                t += dur + gap;
            });
        } catch (_) { }
    }
    function beepWarn() { playTone([880, 0, 880, 0, 1100], 'sine'); }
    function beepCrit() { playTone([1047, 0, 1047, 0, 1047, 0, 1319], 'square', 0.4, 0.18); }

    function toggleSound() {
        try { getCtx().resume(); } catch (_) { }
        soundOn = !soundOn;
        const b = document.getElementById('btn-sound');
        if (b) { b.textContent = soundOn ? '🔔' : '🔕'; b.classList.toggle('muted', !soundOn); }
    }

    // ── Popup ─────────────────────────────────────────────────────
    function showPopup(list) {
        if (popupOpen) return;
        popupOpen = true;
        const body = document.getElementById('alert-popup-body');
        body.innerHTML = list.map(p => {
            const el = elapsedMin(p.time);
            const rem = cfg.losLimit - el;
            const cls = rem <= 0 ? 'rem-over' : 'rem-crit';
            const txt = rem <= 0
                ? `⛔ OVER (${fmtMmSs(Math.abs(rem))} เกิน)`
                : `⚠ เหลือ ${fmtMmSs(rem)}`;
            return `<div class="al-row">
                <div>
                    <div class="al-name">${esc(p.p_name || '-')} xxx</div>
                    <div class="al-hn">${esc(p.esi_label || '')}</div>
                </div>
                <div class="al-rem ${cls}">${txt}</div>
            </div>`;
        }).join('');

        document.getElementById('alert-popup-title').textContent =
            `🚨 แจ้งเตือน ${list.length} ราย — ใกล้ครบ ${cfg.losLimit} นาที`;
        document.getElementById('alert-popup').style.display = 'flex';

        const hasOver = list.some(p => elapsedMin(p.time) >= cfg.losLimit);
        hasOver ? beepCrit() : beepWarn();
        flashHeader(true);

        // Auto close after 15 seconds
        clearTimeout(popupTm);
        popupTm = setTimeout(closePopup, 15000);
    }

    function closePopup() {
        clearTimeout(popupTm);
        document.getElementById('alert-popup').style.display = 'none';
        popupOpen = false;
        flashHeader(false);
    }

    let flashTm = null;
    function flashHeader(on) {
        const hdr = document.getElementById('er-header');
        clearInterval(flashTm);
        if (!on) { hdr.style.background = ''; return; }
        let state = false;
        flashTm = setInterval(() => {
            hdr.style.background = state ? '#1a0505' : '#111d30';
            state = !state;
        }, 500);
    }

    // ── Check alerts ──────────────────────────────────────────────
    function checkAlerts() {
        const alertList = patients.filter(p => elapsedMin(p.time) >= cfg.WARN_POPUP);
        const newOnes = alertList.filter(p => !alreadyAlerted.has(p.hn));
        if (newOnes.length && !popupOpen) {
            newOnes.forEach(p => alreadyAlerted.add(p.hn));
            showPopup(newOnes);
        }
        // ลบ HN ที่ออกไปแล้ว
        alreadyAlerted.forEach(hn => {
            if (!patients.find(p => p.hn === hn)) alreadyAlerted.delete(hn);
        });
    }

    // ── Render summary ────────────────────────────────────────────
    function renderSummary() {
        const counts = { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 };
        patients.forEach(p => { const c = parseInt(p.color) || 3; if (counts[c] !== undefined) counts[c]++; });
        const labels = { 1: 'Resuscitation', 2: 'Emergent', 3: 'Urgent', 4: 'Less Urgent', 5: 'Non-Urgent' };
        const bar = document.getElementById('summary-bar'); if (!bar) return;
        
        let h = '<span style="font-size:0.8rem; color:var(--text-muted); margin-right:0.5rem; text-transform:uppercase; font-weight:700;">ESI Distribution:</span>';
        [1, 2, 3, 4, 5].forEach(n => {
            h += `<div class="sum-item">
                    <span class="sum-dot e${n}"></span>
                    <span class="sum-count">${counts[n]}</span>
                    <span style="font-size:0.85rem; color:var(--text-secondary)">${labels[n]}</span>
                  </div><span style="color:var(--glass-border)">|</span>`;
        });
        const alerts = patients.filter(p => elapsedMin(p.time) >= cfg.WARN_POPUP).length;
        h += `<div class="sum-item" style="margin-left:auto">
                <span style="font-size:0.85rem; color:var(--text-muted)">Total Patients:</span>
                <span class="sum-count" style="color:var(--accent-blue)">${patients.length}</span>
              </div>`;
        if (alerts) h += `<div class="sum-item" style="color:#ef4444; font-weight:800; font-size:0.9rem; animation: pulse-danger 2s infinite;">⚠ ALERTS: ${alerts}</div>`;
        bar.innerHTML = h;
        document.getElementById('alert-count').textContent = alerts;
        
        const indicator = document.getElementById('alert-indicator');
        if (indicator) indicator.style.display = alerts ? 'block' : 'none';
    }

    // ── Render table ──────────────────────────────────────────────
    function renderTable() {
        const tb = document.getElementById('er-tbody'); if (!tb) return;
        if (errorState) return; // ถ้ามี Error จาก API ค้างอยู่ ไม่ต้อง Render ทับ

        if (!patients.length) {
            tb.innerHTML = '<tr><td colspan="7" class="no-data">ไม่มีข้อมูลผู้ป่วยห้องฉุกเฉินในขณะนี้</td></tr>';
            return;
        }

        let html = '';
        patients.forEach((p, i) => {
            const el = elapsedMin(p.time);
            const pct = Math.min(100, (el / cfg.losLimit) * 100);

            const rc = rowClass(el);
            const lCls = losColorClass(el);

            const losHtml = `<div class="los-cell">
                                <span class="los-val ${lCls}">${el >= cfg.losLimit ? 'OVER' : fmtMmSs(el)}</span>
                                <div class="los-bar-wrap">
                                    <div class="los-bar-fill" style="width:${pct}%; background:var(--los-${lCls.replace('los-', '')})"></div>
                                </div>
                             </div>`;

            const si = statusMap[p.hn] ?? 0, st = STATUSES[si];
            const labT = p.lab ? '<span class="tag tag-lab">LAB</span>' : '';
            const xrT = p.xr ? '<span class="tag tag-xr">XR</span>' : '';
            const obsT = p.observe ? '<span class="tag tag-obs">OBS</span>' : '';
            const dxT = p.dx ? `<span class="tag tag-dx">${esc(p.dx)}</span>` : '';

            // ใช้ p.queue ถ้ามี ถ้าไม่มีใช้ลำดับ i+1
            const displayQueue = p.queue || (i + 1);

            html += `<tr class="${rc}">
                <td class="qnum">${displayQueue}</td>
                <td style="text-align:center">
                    <span class="esi-badge ${p.esi_class}">${p.esi_label.replace('Level ', 'L')}</span>
                </td>
                <td>
                    <div class="p-name">${esc(p.p_name || '-')} xxx</div>
                    <div class="p-hn">HN: ${esc(p.hn)}</div>
                </td>
                <td style="text-align:center"><span class="entry-time">${p.time || '-'}</span></td>
                <td style="text-align:center">${losHtml}</td>
                <td>
                    <div style="display:flex; flex-wrap:wrap; gap:4px; justify-content:center;">
                        ${labT}${xrT}${obsT}${dxT}
                    </div>
                </td>
                <td style="text-align:center">
                    <button class="st-btn ${st.cls}" onclick="ER.cycleStatus('${esc(p.hn)}')">${st.label}</button>
                </td>
            </tr>`;
        });

        tb.innerHTML = html;
        renderSummary();
        checkAlerts();
    }

    // ── Fetch ─────────────────────────────────────────────────────
    async function fetchPatients() {
        const tb = document.getElementById('er-tbody');
        try {
            const res = await fetch(cfg.apiUrl + '?date=' + today(), { signal: AbortSignal.timeout(8000) });
            const text = await res.text();
            let json;
            try {
                json = JSON.parse(text);
            } catch (e) {
                if (patients.length === 0 && tb) {
                    errorState = true;
                    tb.innerHTML = `<tr><td colspan="7" class="no-data" style="color:#ef4444">
                        <div style="font-size:2rem; margin-bottom:1rem;">❌</div>
                        <b>Server Error:</b> ไม่สามารถอ่านข้อมูลจาก Server ได้ (Invalid JSON)<br>
                        <div style="margin-top:1rem; padding:1rem; background:rgba(0,0,0,0.2); border-radius:8px; font-family:monospace; font-size:0.8rem; text-align:left; max-width:500px; margin-left:auto; margin-right:auto; overflow:auto;">
                            ${esc(text.slice(0, 500))}
                        </div>
                    </td></tr>`;
                }
                return;
            }

            if (!json.success) {
                if (patients.length === 0 && tb) {
                    errorState = true;
                    tb.innerHTML = `<tr><td colspan="7" class="no-data" style="color:#ef4444">
                        <div style="font-size:2rem; margin-bottom:1rem;">⚠️</div>
                        <b>API Error:</b> ${esc(json.error || 'Unknown error')}<br>
                        <div style="margin-top:0.5rem; color:var(--text-secondary);">${esc(json.hint || '')}</div>
                    </td></tr>`;
                }
                return;
            }

            errorState = false; // เคลียร์ error state เมื่อสำเร็จ
            const newHns = new Set((json.data || []).map(p => p.hn));
            alreadyAlerted.forEach(hn => { if (!newHns.has(hn)) alreadyAlerted.delete(hn); });
            patients = json.data || [];
            document.getElementById('last-update').textContent =
                'อัปเดต: ' + new Date().toLocaleTimeString('th-TH');
            
            renderTable();
        } catch (err) {
            if (patients.length === 0 && tb) {
                errorState = true;
                tb.innerHTML = `<tr><td colspan="7" class="no-data" style="color:#ef4444">
                    <div style="font-size:2rem; margin-bottom:1rem;">📡</div>
                    <b>Network Error:</b> ไม่สามารถเชื่อมต่อกับ API ได้<br>
                    <small style="opacity:0.6">${esc(err.message)}</small>
                </td></tr>`;
            }
        }
    }

    // ── Status ────────────────────────────────────────────────────
    function cycleStatus(hn) {
        statusMap[hn] = ((statusMap[hn] ?? 0) + 1) % STATUSES.length;
        renderTable();
    }

    // ── Clock ─────────────────────────────────────────────────────
    function tick() {
        const now = new Date();
        const cl = document.getElementById('clock-time');
        if (cl) cl.textContent = now.toLocaleTimeString('th-TH', { hour12: false });
        timerSec++;
        const tm = document.getElementById('live-timer');
        if (tm) {
            const m = Math.floor(timerSec / 60) % 100, s = timerSec % 60;
            tm.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
        }
        renderTable();
    }

    function loadDemo() {
        patients = DEMO.map(p => ({ ...p }));
        statusMap = {}; alreadyAlerted.clear();
        document.getElementById('last-update').textContent = 'Demo: ' + new Date().toLocaleTimeString('th-TH');
    }
    function toggleFullscreen() {
        !document.fullscreenElement
            ? document.documentElement.requestFullscreen?.()
            : document.exitFullscreen?.();
    }

    // ── Init ──────────────────────────────────────────────────────
    function init() {
        setInterval(tick, 1000); tick();
        fetchPatients();
        setInterval(fetchPatients, cfg.refreshSec * 1000);
        document.addEventListener('click', () => { try { getCtx().resume(); } catch (_) { } }, { once: true });
    }

    document.addEventListener('DOMContentLoaded', init);
    return { cycleStatus, toggleSound, closePopup, loadDemo, toggleFullscreen, fetchPatients };
})();
