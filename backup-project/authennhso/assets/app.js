/**
 * Application Orchestration Engine
 * NHSO Claim & Authen Code Detection System
 * Connected to HOSxP (Read-Only)
 */

document.addEventListener('DOMContentLoaded', function() {
    // Intercept fetch calls to handle session expiration (401 Unauthorized)
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        return originalFetch(...args).then(response => {
            if (response.status === 401) {
                window.location.href = 'login.php?expired=1';
                return new Promise(() => {}); // Halt execution chain
            }
            return response;
        });
    };

    // Application State
    const state = {
        filters: {
            vstdate_start: '',
            vstdate_end: '',
            pttype: 'all',
            department: 'all',
            issue_type: 'all_issues',
            search: ''
        },
        sortBy: 'vstdate',
        sortOrder: 'DESC',
        page: 1,
        limit: 20,
        charts: {
            dept: null,
            pttype: null
        }
    };

    // DOM Elements
    const elements = {
        statTotalVisits: document.getElementById('stat-total-visits'),
        statTotalPatients: document.getElementById('stat-total-patients'),
        statNoAuthen: document.getElementById('stat-no-authen'),
        statNotClosedHosxp: document.getElementById('stat-not-closed-hosxp'),
        statNotClosedNhso: document.getElementById('stat-not-closed-nhso'),
        statClosedNhso: document.getElementById('stat-closed-nhso'),
        statLossValue: document.getElementById('stat-loss-value'),
        
        filterStart: document.getElementById('filter-start'),
        filterEnd: document.getElementById('filter-end'),
        filterPttype: document.getElementById('filter-pttype'),
        filterDept: document.getElementById('filter-dept'),
        filterIssue: document.getElementById('filter-issue'),
        filterSearch: document.getElementById('filter-search'),
        btnResetFilters: document.getElementById('btn-reset-filters'),
        btnExportCsv: document.getElementById('btn-export-csv'),
        
        patientTableBody: document.getElementById('patient-table-body'),
        recordCount: document.getElementById('record-count'),
        
        detailModal: document.getElementById('detail-modal'),
        modalClose: document.querySelector('.modal-close'),
        btnCloseModal: document.getElementById('btn-close-modal'),
        
        toastContainer: document.getElementById('toast-container')
    };

    // Load departments dynamically from HOSxP
    function loadDepartments() {
        fetch('api.php?action=get_departments')
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    const select = elements.filterDept;
                    res.data.forEach(dept => {
                        const opt = document.createElement('option');
                        opt.value = dept.depcode;
                        opt.textContent = dept.department;
                        select.appendChild(opt);
                    });
                }
            })
            .catch(err => console.error('Load departments error:', err));
    }

    // Init dates (Today only as default)
    function initDateFilters() {
        const today = new Date();
        elements.filterStart.value = formatDateYMD(today);
        elements.filterEnd.value = formatDateYMD(today);
        updateStateFilters();
    }

    function formatDateYMD(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function updateStateFilters() {
        state.filters.vstdate_start = elements.filterStart.value;
        state.filters.vstdate_end = elements.filterEnd.value;
        state.filters.pttype = elements.filterPttype.value;
        state.filters.department = elements.filterDept.value;
        state.filters.issue_type = elements.filterIssue.value;
        state.filters.search = elements.filterSearch.value.trim();
    }

    function getQueryString() {
        return new URLSearchParams({
            vstdate_start: state.filters.vstdate_start,
            vstdate_end: state.filters.vstdate_end,
            pttype: state.filters.pttype,
            department: state.filters.department,
            issue_type: state.filters.issue_type,
            search: state.filters.search,
            sort_by: state.sortBy,
            sort_order: state.sortOrder,
            page: state.page,
            limit: state.limit
        }).toString();
    }

    // Fetch Stats
    function fetchStats() {
        fetch(`api.php?action=get_stats&${getQueryString()}`)
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') renderStats(res.data);
                else showToast('error', 'ดึงสถิติไม่สำเร็จ: ' + res.message);
            })
            .catch(err => showToast('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ'));
    }

    function formatBaht(value) {
        return new Intl.NumberFormat('th-TH', { style: 'currency', currency: 'THB' }).format(value);
    }

    function renderStats(data) {
        const kpis = data.kpis;
        elements.statTotalVisits.textContent = kpis.total_visits.toLocaleString();
        elements.statTotalPatients.textContent = kpis.total_patients.toLocaleString();
        elements.statNoAuthen.textContent = kpis.no_authen.toLocaleString();
        elements.statNotClosedHosxp.textContent = kpis.not_closed_hosxp.toLocaleString();
        elements.statNotClosedNhso.textContent = kpis.not_closed_nhso.toLocaleString();
        if (elements.statClosedNhso) elements.statClosedNhso.textContent = kpis.closed_nhso.toLocaleString();
        elements.statLossValue.textContent = formatBaht(kpis.loss_prevention_value);
        
        if (typeof Chart !== 'undefined') {
            renderDepartmentChart(data.by_department);
            renderPttypeChart(data.by_pttype);
        }
    }

    function renderDepartmentChart(deptData) {
        const ctx = document.getElementById('dept-chart');
        if (!ctx) return;
        
        const labels = deptData.map(d => {
            const name = d.department;
            return name.length > 18 ? name.substring(0, 18) + '…' : name;
        });
        const dataIssues = deptData.map(d => parseInt(d.issues));
        const dataTotal = deptData.map(d => parseInt(d.total));
        
        if (state.charts.dept) state.charts.dept.destroy();
        
        state.charts.dept = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'ยังไม่ Confirm',
                        data: dataIssues,
                        backgroundColor: 'rgba(239, 68, 68, 0.7)',
                        borderColor: '#ef4444',
                        borderWidth: 1,
                        borderRadius: 6
                    },
                    {
                        label: 'ทั้งหมด',
                        data: dataTotal,
                        backgroundColor: 'rgba(99, 102, 241, 0.3)',
                        borderColor: 'rgba(99, 102, 241, 0.6)',
                        borderWidth: 1,
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { position: 'top', labels: { color: '#94a3b8', font: { family: 'Sarabun', size: 10 } } }
                },
                scales: {
                    x: { ticks: { color: '#94a3b8', font: { family: 'Sarabun', size: 10 } }, grid: { color: 'rgba(255,255,255,0.05)' } },
                    y: { ticks: { color: '#94a3b8', font: { family: 'Sarabun', size: 10 } }, grid: { display: false } }
                }
            }
        });
    }

    function renderPttypeChart(pttypeData) {
        const ctx = document.getElementById('pttype-chart');
        if (!ctx) return;
        
        const filtered = pttypeData.filter(d => parseInt(d.issues) > 0);
        const labels = filtered.map(d => {
            const name = d.pttype_name;
            return name.length > 20 ? name.substring(0, 20) + '…' : name;
        });
        const issuesCount = filtered.map(d => parseInt(d.issues));
        
        if (state.charts.pttype) state.charts.pttype.destroy();
        
        if (issuesCount.length === 0) {
            state.charts.pttype = new Chart(ctx, {
                type: 'doughnut',
                data: { labels: ['ไม่มีสิทธิ์ที่มีปัญหา'], datasets: [{ data: [1], backgroundColor: ['rgba(255,255,255,0.05)'], borderColor: ['rgba(255,255,255,0.08)'] }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
            });
            return;
        }
        
        const colors = ['rgba(99,102,241,0.7)','rgba(245,158,11,0.7)','rgba(16,185,129,0.7)','rgba(239,68,68,0.7)','rgba(168,85,247,0.7)','rgba(236,72,153,0.7)','rgba(20,184,166,0.7)','rgba(251,146,60,0.7)'];
        const borders = ['#6366f1','#f59e0b','#10b981','#ef4444','#a855f7','#ec4899','#14b8a6','#fb923c'];
        
        state.charts.pttype = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: issuesCount,
                    backgroundColor: colors.slice(0, issuesCount.length),
                    borderColor: borders.slice(0, issuesCount.length),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'right', labels: { color: '#94a3b8', font: { family: 'Sarabun', size: 10 } } } }
            }
        });
    }

    // Fetch List
    function fetchList() {
        elements.patientTableBody.innerHTML = `
            <tr><td colspan="8" style="text-align:center; padding:40px;">
                <div style="font-size:24px; display:inline-block; margin-bottom:8px;">⏳</div>
                <div>กำลังดึงข้อมูลจากฐานข้อมูล HOSxP...</div>
            </td></tr>`;
        
        fetch(`api.php?action=get_list&${getQueryString()}`)
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') renderList(res.data, res.pagination);
                else showToast('error', 'ไม่สามารถดึงรายการ: ' + res.message);
            })
            .catch(err => showToast('error', 'ข้อผิดพลาดเครือข่าย'));
    }

    function renderList(list, pagination) {
        elements.recordCount.textContent = pagination ? pagination.total_records.toLocaleString() : list.length.toLocaleString();
        
        if (list.length === 0) {
            elements.patientTableBody.innerHTML = `
                <tr><td colspan="8">
                    <div class="empty-state">
                        <div class="empty-state-icon">🛡️</div>
                        <div class="empty-state-title">ไม่พบผู้รับบริการที่ตรงตามเงื่อนไข</div>
                        <div class="empty-state-desc">ลองเปลี่ยนตัวกรอง หรือขยายช่วงวันที่เพื่อดูข้อมูลเพิ่มเติม</div>
                    </div>
                </td></tr>`;
            
            // Hide pagination when empty
            const wrapper = document.getElementById('pagination-wrapper');
            if (wrapper) wrapper.style.display = 'none';
            return;
        }
        
        let html = '';
        list.forEach(row => {
            // 1. Authen Badge
            let authenBadge = '';
            const isExempt = (row.pttype_name && (row.pttype_name.indexOf('ออกหน่วย') !== -1 || row.pttype_name.indexOf('ชำระเงิน') !== -1));
            
            if (row.nhso_authen_code && row.nhso_authen_code.trim() !== '') {
                authenBadge = `<span class="badge badge-success">🔑 Authen: ${row.nhso_authen_code}</span>`;
            } else if (isExempt) {
                authenBadge = `<span class="badge badge-exempt">⚪ ยกเว้น Authen (ไม่ต้องขอ)</span>`;
            } else {
                authenBadge = `<span class="badge badge-danger">🚫 ไม่มีเลข Authen</span>`;
            }
            
            // 2. Invoice/Visit Badge (ไม่ปิด visit)
            let visitBadge = '';
            if (row.finance_number && row.finance_number.trim() !== '') {
                visitBadge = `<span class="badge badge-success">🧾 ปิด Visit (Inv: ${row.finance_number})</span>`;
            } else {
                visitBadge = `<span class="badge badge-danger">⚠️ ไม่ปิด visit (ไม่มี Invoice)</span>`;
            }
            
            // 3. Privilege Badge (ไม่ปิดสิทธิ์ EP / ปิดสิทธิ์ PP)
            let privilegeBadge = '';
            if (row.nhso_authen_code && row.nhso_authen_code.startsWith('PP')) {
                privilegeBadge = `<span class="badge badge-success">🔒 ปิดสิทธิ์ (PP)</span>`;
            } else if (row.nhso_authen_code && row.nhso_authen_code.startsWith('EP')) {
                privilegeBadge = `<span class="badge badge-danger">🔓 ไม่ปิดสิทธิ์ (EP)</span>`;
            } else {
                privilegeBadge = `<span class="badge badge-muted">➖ ไม่มีข้อมูลสิทธิ์</span>`;
            }
            
            // Date formatting
            const vstDateObj = new Date(row.vstdate);
            const formattedDate = vstDateObj.toLocaleDateString('th-TH', { year: '2-digit', month: 'short', day: 'numeric' });
            const formattedTime = row.vsttime ? row.vsttime.substring(0, 5) : '-';
            
            const income = parseFloat(row.total_income) || 0;
            
            html += `
                <tr data-vn="${row.vn}">
                    <td>
                        <div style="display:flex; flex-direction:column;">
                            <span class="patient-hn">${row.hn}</span>
                            <span style="font-size:11px; font-weight:600; color:var(--text-muted); font-family:'Inter', sans-serif; margin-top:2px;">VN: ${row.vn}</span>
                        </div>
                    </td>
                    <td>
                        <div class="patient-info-cell">
                            <span class="patient-name">${row.fullname}</span>
                            <span class="patient-cid">${row.cid ? row.cid.replace(/(\d{1})(\d{4})(\d{5})(\d{2})(\d{1})/, '$1-$2-$3-$4-$5') : '-'}</span>
                        </div>
                    </td>
                    <td>
                        <div style="font-weight:500;">${formattedDate}</div>
                        <div style="font-size:12px; color:var(--text-muted);">${formattedTime} น.</div>
                    </td>
                    <td class="visit-dept">${row.dept_name || '-'}</td>
                    <td style="font-size:13px; font-weight:500;">${row.pttype_name || row.pttype}</td>
                    <td class="income-cell" style="text-align:right; font-weight:600;">${income.toLocaleString('th-TH', {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
                    <td>
                        <div class="checklist-cell" style="display:flex; flex-direction:column; gap:4px;">
                            <div>${authenBadge}</div>
                            <div>${visitBadge}</div>
                            <div>${privilegeBadge}</div>
                        </div>
                    </td>
                    <td>
                        <button class="btn btn-primary btn-sm btn-view-details" data-vn="${row.vn}">🔎 ดู</button>
                    </td>
                </tr>`;
        });
        
        elements.patientTableBody.innerHTML = html;
        
        document.querySelectorAll('.btn-view-details').forEach(btn => {
            btn.addEventListener('click', function() {
                openDetailsModal(this.getAttribute('data-vn'));
            });
        });
        
        // Render pagination controls
        renderPagination(pagination);
    }

    function renderPagination(pag) {
        const wrapper = document.getElementById('pagination-wrapper');
        const buttonsContainer = document.getElementById('pagination-buttons');
        
        if (!wrapper || !buttonsContainer) return;
        
        if (!pag || pag.total_records === 0 || pag.total_pages <= 1) {
            wrapper.style.display = 'none';
            return;
        }
        
        wrapper.style.display = 'flex';
        
        // Update pagination information text
        const start = (pag.current_page - 1) * pag.limit + 1;
        const end = Math.min(pag.current_page * pag.limit, pag.total_records);
        
        const pagStartSpan = document.getElementById('pag-start');
        const pagEndSpan = document.getElementById('pag-end');
        const pagTotalSpan = document.getElementById('pag-total');
        
        if (pagStartSpan) pagStartSpan.textContent = start.toLocaleString();
        if (pagEndSpan) pagEndSpan.textContent = end.toLocaleString();
        if (pagTotalSpan) pagTotalSpan.textContent = pag.total_records.toLocaleString();
        
        // Generate buttons
        let html = '';
        
        // Prev button
        const isPrevDisabled = pag.current_page === 1 ? 'disabled' : '';
        html += `<button class="pagination-btn ${isPrevDisabled}" data-page="${pag.current_page - 1}" ${isPrevDisabled ? 'disabled' : ''}>&laquo; ก่อนหน้า</button>`;
        
        // Smart page numbers: show first page, dots, pages around current page, dots, last page
        const maxPagesToShow = 5;
        let startPage = Math.max(1, pag.current_page - Math.floor(maxPagesToShow / 2));
        let endPage = startPage + maxPagesToShow - 1;
        
        if (endPage > pag.total_pages) {
            endPage = pag.total_pages;
            startPage = Math.max(1, endPage - maxPagesToShow + 1);
        }
        
        if (startPage > 1) {
            html += `<button class="pagination-btn" data-page="1">1</button>`;
            if (startPage > 2) {
                html += `<span style="padding: 6px; color: var(--text-muted); font-size: 13px;">...</span>`;
            }
        }
        
        for (let p = startPage; p <= endPage; p++) {
            const isActive = p === pag.current_page ? 'active' : '';
            html += `<button class="pagination-btn ${isActive}" data-page="${p}">${p}</button>`;
        }
        
        if (endPage < pag.total_pages) {
            if (endPage < pag.total_pages - 1) {
                html += `<span style="padding: 6px; color: var(--text-muted); font-size: 13px;">...</span>`;
            }
            html += `<button class="pagination-btn" data-page="${pag.total_pages}">${pag.total_pages}</button>`;
        }
        
        // Next button
        const isNextDisabled = pag.current_page === pag.total_pages ? 'disabled' : '';
        html += `<button class="pagination-btn ${isNextDisabled}" data-page="${pag.current_page + 1}" ${isNextDisabled ? 'disabled' : ''}>ถัดไป &raquo;</button>`;
        
        buttonsContainer.innerHTML = html;
        
        // Add event listeners to pagination buttons
        buttonsContainer.querySelectorAll('.pagination-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const newPage = parseInt(this.getAttribute('data-page'));
                if (newPage && newPage !== pag.current_page && newPage >= 1 && newPage <= pag.total_pages) {
                    state.page = newPage;
                    fetchList();
                    // Smooth scroll to table top
                    const tableContainer = document.querySelector('.table-panel');
                    if (tableContainer) {
                        tableContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }
            });
        });
    }

    // Modal
    function openDetailsModal(vn) {
        fetch(`api.php?action=get_details&vn=${vn}`)
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    populateModal(res.data);
                    elements.detailModal.classList.add('active');
                } else {
                    showToast('error', res.message);
                }
            })
            .catch(err => showToast('error', 'เกิดข้อผิดพลาดในการดึงข้อมูล'));
    }

    function populateModal(d) {
        document.getElementById('m-vn-text').textContent = d.vn;
        document.getElementById('m-hn').textContent = d.hn;
        document.getElementById('m-cid').textContent = d.cid ? d.cid.replace(/(\d{1})(\d{4})(\d{5})(\d{2})(\d{1})/, '$1-$2-$3-$4-$5') : '-';
        document.getElementById('m-name').textContent = d.fullname;
        
        let ageText = d.age;
        if (d.birthday && d.birthday !== '0000-00-00') {
            ageText += ' ปี (เกิด ' + new Date(d.birthday).toLocaleDateString('th-TH') + ')';
        }
        document.getElementById('m-age').textContent = ageText;
        document.getElementById('m-phone').textContent = d.hometel || 'ไม่มีเบอร์';
        document.getElementById('m-pttype').textContent = `[${d.pttype}] ${d.pttype_name}`;
        
        document.getElementById('m-vstdate').textContent = `${new Date(d.vstdate).toLocaleDateString('th-TH')} เวลา ${d.vsttime ? d.vsttime.substring(0,5) : '-'} น.`;
        document.getElementById('m-dept').textContent = d.dept_name || '-';
        document.getElementById('m-doctor').textContent = d.doctor_name || '-';
        document.getElementById('m-spclty').textContent = d.spclty_name || '-';
        document.getElementById('m-dx').textContent = d.diagnoses || '-';
        document.getElementById('m-income').textContent = formatBaht(parseFloat(d.total_income) || 0);
        
        // NHSO claim info
        const authenEl = document.getElementById('m-authen-code');
        const confirmEl = document.getElementById('m-confirm-status');
        const seqEl = document.getElementById('m-nhso-seq');
        const nhsoAmountEl = document.getElementById('m-nhso-amount');
        const confirmDateEl = document.getElementById('m-confirm-date');
        const staffEl = document.getElementById('m-confirm-staff');
        
        if (d.nhso_authen_code) {
            authenEl.innerHTML = `<span class="badge badge-success">✓ ${d.nhso_authen_code}</span>`;
        } else {
            authenEl.innerHTML = `<span class="badge badge-danger">✗ ยังไม่ได้ขอเลข Authen Code</span>`;
        }
        
        if (d.nhso_status === 'Y') {
            confirmEl.innerHTML = `<span class="badge badge-success">✓ Confirmed — ปิดสิทธิ์เรียบร้อยแล้ว</span>`;
        } else if (d.nhso_authen_code) {
            confirmEl.innerHTML = `<span class="badge badge-warning">⏳ มี Authen แต่ยังไม่ได้ Confirm ปิดสิทธิ์</span>`;
        } else {
            confirmEl.innerHTML = `<span class="badge badge-danger">✗ ยังไม่มีข้อมูลการปิดสิทธิ์</span>`;
        }
        
        seqEl.textContent = d.nhso_seq || '-';
        nhsoAmountEl.textContent = d.nhso_total_amount ? formatBaht(parseFloat(d.nhso_total_amount)) : '-';
        confirmDateEl.textContent = d.nhso_requst_datetime || '-';
        staffEl.textContent = d.confirm_staff || '-';
    }

    function closeModal() {
        elements.detailModal.classList.remove('active');
    }

    elements.modalClose.addEventListener('click', closeModal);
    elements.btnCloseModal.addEventListener('click', closeModal);
    elements.detailModal.addEventListener('click', function(e) {
        if (e.target === elements.detailModal) closeModal();
    });

    // Filter handlers
    function updateActiveKpiCard() {
        const currentFilter = elements.filterIssue.value;
        const cardFilters = {
            'all': 'card-total-visits',
            'missing_authen': 'card-no-authen',
            'not_closed_visit': 'card-not-closed-visit',
            'not_closed_nhso': 'card-not-closed-nhso',
            'closed_nhso': 'card-closed-nhso'
        };
        
        Object.keys(cardFilters).forEach(filterVal => {
            const cardId = cardFilters[filterVal];
            const cardEl = document.getElementById(cardId);
            if (cardEl) {
                if (filterVal === currentFilter) {
                    cardEl.classList.add('active-kpi-card');
                } else {
                    cardEl.classList.remove('active-kpi-card');
                }
            }
        });
    }

    function handleFilterChange() {
        state.page = 1; // Reset to page 1 on filter change
        updateStateFilters();
        updateActiveKpiCard();
        fetchStats();
        fetchList();
    }

    // KPI Cards interactive navigation
    const cardFilters = {
        'card-total-visits': 'all',
        'card-no-authen': 'missing_authen',
        'card-not-closed-visit': 'not_closed_visit',
        'card-not-closed-nhso': 'not_closed_nhso',
        'card-closed-nhso': 'closed_nhso'
    };

    Object.keys(cardFilters).forEach(cardId => {
        const el = document.getElementById(cardId);
        if (el) {
            el.addEventListener('click', function() {
                const targetFilter = cardFilters[cardId];
                elements.filterIssue.value = targetFilter;
                handleFilterChange();
                
                // Visual feedback: brief pop animation
                el.classList.add('kpi-clicked');
                setTimeout(() => el.classList.remove('kpi-clicked'), 400);
                
                // Show a nice toast message
                const filterText = elements.filterIssue.options[elements.filterIssue.selectedIndex].text;
                showToast('success', `กรองข้อมูลตาม: ${filterText}`);
                
                // Smooth scroll to table panel
                const tableContainer = document.querySelector('.table-panel');
                if (tableContainer) {
                    tableContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        }
    });

    elements.filterStart.addEventListener('change', handleFilterChange);
    elements.filterEnd.addEventListener('change', handleFilterChange);
    elements.filterPttype.addEventListener('change', handleFilterChange);
    elements.filterDept.addEventListener('change', handleFilterChange);
    elements.filterIssue.addEventListener('change', handleFilterChange);
    
    let searchDebounceTimer;
    elements.filterSearch.addEventListener('input', function() {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(handleFilterChange, 400);
    });

    elements.btnResetFilters.addEventListener('click', function() {
        elements.filterSearch.value = '';
        elements.filterPttype.value = 'all';
        elements.filterDept.value = 'all';
        elements.filterIssue.value = 'all_issues';
        state.page = 1; // Reset to page 1
        initDateFilters();
        handleFilterChange(); // Fetch with reset values
        showToast('success', 'ล้างตัวกรองเรียบร้อยแล้ว');
    });

    elements.btnExportCsv.addEventListener('click', function(e) {
        e.preventDefault();
        updateStateFilters();
        window.location.href = `api.php?action=export&${getQueryString()}`;
        showToast('success', 'กำลังส่งออกรายงาน CSV...');
    });

    // Sort
    document.querySelectorAll('th.sortable').forEach(th => {
        th.addEventListener('click', function() {
            const sortByField = this.getAttribute('data-sort');
            if (state.sortBy === sortByField) {
                state.sortOrder = (state.sortOrder === 'ASC') ? 'DESC' : 'ASC';
            } else {
                state.sortBy = sortByField;
                state.sortOrder = 'DESC';
            }
            state.page = 1; // Reset to page 1 on sort change
            document.querySelectorAll('th.sortable').forEach(el => {
                el.textContent = el.textContent.replace(' 🔼', '').replace(' 🔽', '');
            });
            this.textContent += (state.sortOrder === 'ASC') ? ' 🔼' : ' 🔽';
            fetchList();
        });
    });

    // Toast
    function showToast(type, message) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        const icon = type === 'success' ? '✅' : '❌';
        toast.innerHTML = `<span class="toast-icon">${icon}</span><span class="toast-message">${message}</span>`;
        elements.toastContainer.appendChild(toast);
        setTimeout(() => {
            toast.style.transition = 'all 0.3s ease';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            setTimeout(() => toast.remove(), 300);
        }, 4200);
    }

    // Boot
    loadDepartments();
    initDateFilters();
    fetchStats();
    fetchList();
});
