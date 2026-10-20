/**
 * ER Reassessment Monitor JS
 */

const APP = {
  apiUrl: document.body.getAttribute('data-api') || 'api/patients.php',
  refreshSec: parseInt(document.body.getAttribute('data-refresh') || 30),
  timerInterval: null,
  clockInterval: null,
  fetchInterval: null,
  lastUpdate: null,
  soundEnabled: false,
  alertAudio: new Audio('assets/sounds/alert.mp3'),
  
  alertHistory: {},
  globalAlerts: [],
  
  init() {
    this.loadAlertHistory();
    this.startClock();
    this.fetchData();
    this.fetchInterval = setInterval(() => this.fetchData(), this.refreshSec * 1000);
  },
  
  loadAlertHistory() {
    try {
      const data = localStorage.getItem('erAlertHistory');
      if (data) this.alertHistory = JSON.parse(data);
      else this.alertHistory = {};
    } catch (e) {
      this.alertHistory = {};
    }
  },
  
  saveAlertHistory() {
    try {
      localStorage.setItem('erAlertHistory', JSON.stringify(this.alertHistory));
    } catch (e) {}
  },
  
  startClock() {
    const elTime = document.getElementById('txt-time');
    const elDate = document.getElementById('tb-date');
    const months = ['มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
    const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    
    this.clockInterval = setInterval(() => {
      const now = new Date();
      elTime.textContent = now.toTimeString().split(' ')[0];
      const d = now.getDate();
      const m = months[now.getMonth()];
      const y = now.getFullYear() + 543;
      const day = days[now.getDay()];
      elDate.innerHTML = `${d} ${m} ${y}<br>${day}`;
    }, 1000);
  },
  
  async fetchData(demo = false) {
    try {
      let url = this.apiUrl;
      if (demo) url += '?demo=1'; // If you have a demo param
      url += (url.includes('?') ? '&' : '?') + '_t=' + Date.now();
      const res = await fetch(url);
      if (!res.ok) throw new Error('Network response was not ok');
      const json = await res.json();
      
      if (json.success) {
        this.renderAll(json.data);
        this.updateTimeStr();
      } else {
        console.error("API Error:", json.error);
        alert("API Error: " + json.error + "\n" + (json.hint || ''));
      }
    } catch (e) {
      console.error("Fetch error:", e);
      // alert("Fetch error: " + e.message);
    }
  },
  
  updateTimeStr() {
    const d = new Date();
    document.getElementById('last-upd').textContent = "อัปเดตล่าสุด: " + d.toTimeString().split(' ')[0];
  },
  
  renderAll(data) {
    this.globalAlerts = [];
    // Group by zones (1=Red, 2=Pink/Orange, 3=Yellow, 4=Green, 5=White)
    let z1 = [], z2 = [], z3 = [], z4 = [], z5 = [];
    
    data.forEach(p => {
      const c = (p.color || '').toUpperCase().trim();
      if (c === '1' || c === 'R') z1.push(p);
      else if (c === '2' || c === 'E') z2.push(p);
      else if (c === '3' || c === 'U') z3.push(p);
      else z4.push(p); // Merge 4 and 5 into z4
    });
    
    this.renderZone('1', z1, 10);
    this.renderZone('2', z2, 30);
    this.renderZone('3', z3, 60);
    this.renderZone('4', z4, 120);

    // Show empty message if absolutely no patients
    const zonesCol = document.getElementById('zones-col');
    let emptyMsg = document.getElementById('empty-msg');
    if (data.length === 0) {
      if (!emptyMsg) {
        emptyMsg = document.createElement('div');
        emptyMsg.id = 'empty-msg';
        emptyMsg.innerHTML = '<h2 style="font-size:3vh;opacity:0.5;">ไม่มีผู้ป่วยรับบริการในขณะนี้</h2>';
        emptyMsg.style.flex = '1';
        emptyMsg.style.display = 'flex';
        emptyMsg.style.alignItems = 'center';
        emptyMsg.style.justifyContent = 'center';
        emptyMsg.style.color = '#7a9abb';
        emptyMsg.style.background = 'rgba(255,255,255,0.02)';
        emptyMsg.style.borderRadius = '8px';
        emptyMsg.style.border = '1px dashed rgba(255,255,255,0.1)';
        zonesCol.appendChild(emptyMsg);
      }
    } else {
      if (emptyMsg) emptyMsg.remove();
    }
    
    // Update summary counts
    if (document.getElementById('sum-1')) document.getElementById('sum-1').textContent = z1.length;
    if (document.getElementById('sum-2')) document.getElementById('sum-2').textContent = z2.length;
    if (document.getElementById('sum-3')) document.getElementById('sum-3').textContent = z3.length;
    if (document.getElementById('sum-4')) document.getElementById('sum-4').textContent = z4.length;
    
    // Update top bar total
    const tbTotal = document.getElementById('tb-total-num');
    if (tbTotal) tbTotal.textContent = data.length;
    
    if (document.getElementById('cnt-1')) document.getElementById('cnt-1').textContent = z1.length;
    if (document.getElementById('cnt-2')) document.getElementById('cnt-2').textContent = z2.length;
    if (document.getElementById('cnt-3')) document.getElementById('cnt-3').textContent = z3.length;
    if (document.getElementById('cnt-4')) document.getElementById('cnt-4').textContent = z4.length;
    
    this.processGlobalAlerts();
  },
  
  renderZone(zoneId, list, limitMin) {
    const container = document.getElementById('rows-' + zoneId);
    const section = document.getElementById('zone-' + zoneId);
    container.innerHTML = '';
    
    if (list.length === 0) {
      section.style.display = 'none';
      return;
    }
    section.style.display = 'flex';
    
    let alertPatients = [];
    
    list.forEach((p, idx) => {
      // Calculate elapsed based on time
      let tIn = p.time || p.vsttime || '00:00:00';
      if (tIn.length > 5) tIn = tIn.substring(0, 5); // HH:mm
      
      // Calculate elapsed minutes from today at tIn
      let elapsed = parseInt(p.elapsed_min) || 0;
      let remaining = limitMin - elapsed;
      
      // If we don't have elapsed_min from API, estimate it
      if (!p.elapsed_min && tIn) {
          const now = new Date();
          const [h,m,s] = (p.time || p.vsttime || '00:00:00').split(':');
          const vTime = new Date();
          vTime.setHours(h,m,s||0,0);
          elapsed = Math.floor((now - vTime)/60000);
          remaining = limitMin - elapsed;
      }
      
      const lastAssess = tIn; // simplifying for demo
      
      // Calculate next reassessment
      let [h, m] = tIn.split(':').map(Number);
      let nextM = m + limitMin;
      let nextH = h + Math.floor(nextM / 60);
      nextM = nextM % 60;
      let sNext = String(nextH).padStart(2,'0') + ':' + String(nextM).padStart(2,'0');
      
      let badge = '';
      let isOver = false;
      let cdStr = '';
      
      if (remaining <= 0) {
        badge = '<div class="badge badge-over">🚨 เกินเวลา</div>';
        isOver = true;
        hasAlert = true;
        // Format negative remaining
        let r = Math.abs(remaining);
        cdStr = '-' + String(Math.floor(r/60)).padStart(2,'0') + ':' + String(r%60).padStart(2,'0');
        alertPatients.push({ hn: p.hn||'Unknown', name: (p.p_name||'')+' '+(p.l_name?'xxxx':''), esi: p.color||5, tin: tIn, timeStr: 'เกิน ' + cdStr + ' นาที', type: 'red' });
      } else if (remaining <= limitMin * 0.2) {
        badge = '<div class="badge" style="background:#f57c00;color:#fff">⚠️ ใกล้ครบ</div>';
        cdStr = String(Math.floor(remaining/60)).padStart(2,'0') + ':' + String(remaining%60).padStart(2,'0');
        alertPatients.push({ hn: p.hn||'Unknown', name: (p.p_name||'')+' '+(p.l_name?'xxxx':''), esi: p.color||5, tin: tIn, timeStr: 'เหลือ ' + cdStr, type: 'orange' });
      } else {
        badge = '<div class="badge" style="background:#388e3c;color:#fff">✔ ปกติ</div>';
        cdStr = String(Math.floor(remaining/60)).padStart(2,'0') + ':' + String(remaining%60).padStart(2,'0');
      }
      
      const hn = p.hn || 'Unknown';
      const qnStr = p.qn ? `(คิว: ${p.qn})` : '';
      const pName = (p.p_name || p.pname || '').trim();
      const lName = (p.l_name || p.lname || '').trim();
      const maskedLName = lName ? 'xxxx' : '';
      const name = pName + ' ' + maskedLName + ' ' + qnStr;
      
      const div = document.createElement('div');
      div.className = 'zr ' + (isOver ? 'zr-over' : '');
      div.innerHTML = `
        <div class="p-card-header">
          <div class="p-name">${idx + 1}. ${name.trim()}</div>
          <div>${badge}</div>
        </div>
        <div class="p-card-body">
          <div class="pc-info">
            <div class="p-card-row">
              <span class="lbl">เวลาเข้า:</span>
              <span class="val">${tIn}</span>
            </div>
            <div class="p-card-row">
              <span class="lbl">ประเมินถัดไป:</span>
              <span class="val">${sNext}</span>
            </div>
          </div>
          <div class="p-card-cd">${cdStr}</div>
        </div>
      `;
      container.appendChild(div);
    });
    
    if (alertPatients.length > 0) {
      alertPatients.forEach(p => this.globalAlerts.push(p));
    }
  },
  
  toggleSound() {
    this.soundEnabled = !this.soundEnabled;
    const btn = document.getElementById('btn-sound');
    btn.style.opacity = this.soundEnabled ? '1' : '0.5';
    if(this.soundEnabled) this.alertAudio.play().catch(e=>{});
  },
  
  toggleFs() {
    if (!document.fullscreenElement) {
      document.documentElement.requestFullscreen();
    } else {
      if (document.exitFullscreen) document.exitFullscreen();
    }
  },
  
  loadDemo() {
    // Generate dummy data
    const d = new Date();
    d.setMinutes(d.getMinutes() - 45);
    const d30 = new Date(); d30.setMinutes(d30.getMinutes() - 25);
    const d100 = new Date(); d100.setMinutes(d100.getMinutes() - 120);
    
    const pad = (n) => String(n).padStart(2,'0');
    
    const dummy = [
      {color: '1', hn:'000001', pname:'นาย', fname:'สมชาย', lname:'ใจดี', vsttime: `${pad(d30.getHours())}:${pad(d30.getMinutes())}:00`},
      {color: '2', hn:'000033759', pname:'นาย', fname:'อินทร', lname:'ทดสอบ', vsttime: `${pad(d100.getHours())}:${pad(d100.getMinutes())}:00`},
      {color: '3', hn:'000003', pname:'นาง', fname:'สมศรี', lname:'ดีใจ', vsttime: `${pad(d.getHours())}:${pad(d.getMinutes())}:00`},
      {color: '4', hn:'000004', pname:'ด.ช.', fname:'สมบัติ', lname:'ดีงาม', vsttime: `${pad(d.getHours())}:${pad(d.getMinutes())}:00`}
    ];
    this.renderAll(dummy);
  },
  
  processGlobalAlerts() {
    const btn = document.getElementById('top-alert-btn');
    const countSpan = document.getElementById('top-alert-count');
    const dropdownBody = document.getElementById('alert-dropdown-body');
    const dropdown = document.getElementById('alert-dropdown');

    if (!btn || !dropdownBody || !dropdown) return;

    if (!this.globalAlerts || this.globalAlerts.length === 0) {
      btn.style.display = 'none';
      dropdown.style.display = 'none';
      return;
    }

    btn.style.display = 'inline-block';
    if (countSpan) countSpan.textContent = this.globalAlerts.length;

    let html = '';
    let shouldPlaySound = false;
    const now = Date.now();

    this.globalAlerts.forEach(p => {
      let tClass = p.type === 'red' ? 'red' : 'orange';
      html += `
        <div class="popup-row">
          <div>
            <div class="pr-name">${p.name.trim() || 'ไม่ระบุชื่อ'}</div>
            <div class="pr-sub">ESI ${p.esi} | เข้า ${p.tin}</div>
          </div>
          <div class="pr-time ${tClass}">${p.timeStr}</div>
        </div>
      `;

      if (p.hn && p.hn !== 'Unknown') {
        let hist = this.alertHistory[p.hn] || { count: 0, lastAlertTime: 0 };
        const timeSinceLast = now - hist.lastAlertTime;
        
        // 15 minutes = 900000 ms
        if (hist.count < 10 && timeSinceLast > 900000) {
          shouldPlaySound = true;
          hist.count++;
          hist.lastAlertTime = now;
          this.alertHistory[p.hn] = hist;
        }
      }
    });

    dropdownBody.innerHTML = html;
    
    if (shouldPlaySound) {
      this.saveAlertHistory();
      if (this.soundEnabled) {
        this.alertAudio.play().catch(e=>{});
      }
    }
  },
  
  toggleAlertDropdown() {
    const dropdown = document.getElementById('alert-dropdown');
    if (dropdown) {
      dropdown.style.display = (dropdown.style.display === 'none' || dropdown.style.display === '') ? 'flex' : 'none';
    }
  },

  initAutoScroll() {
    const container = document.getElementById('zones-col');
    if (!container) return;
    
    if (this.scrollTimer) clearInterval(this.scrollTimer);
    
    let isDown = true;
    let isPaused = false;
    
    container.addEventListener('mouseenter', () => isPaused = true);
    container.addEventListener('mouseleave', () => isPaused = false);
    container.addEventListener('touchstart', () => isPaused = true, {passive: true});
    container.addEventListener('touchend', () => setTimeout(() => isPaused = false, 5000));

    this.scrollTimer = setInterval(() => {
      if (isPaused) return;
      if (container.scrollHeight <= container.clientHeight) return; // No overflow
      
      if (isDown) {
        container.scrollTop += 1;
        if (container.scrollTop + container.clientHeight >= container.scrollHeight - 1) {
          isDown = false;
          isPaused = true;
          setTimeout(() => isPaused = false, 3000); // Pause 3s at bottom
        }
      } else {
        container.scrollTop -= 5; // Scroll back up faster
        if (container.scrollTop <= 0) {
          isDown = true;
          isPaused = true;
          setTimeout(() => isPaused = false, 3000); // Pause 3s at top
        }
      }
    }, 40);
  }
};

window.addEventListener('DOMContentLoaded', () => {
  APP.init();
  setTimeout(() => APP.initAutoScroll(), 2000);
});
