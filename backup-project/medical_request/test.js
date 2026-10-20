
        /**
         * แปะ URL ของ Google Apps Script Web App ของคุณที่นี่
         * (ไปที่ Deploy > Manage Deploys > คัดลอก Web App URL)
         */
        const API_URL = "api.php";

        // Global State
        let currentUser = null;
        let selectedRequestId = null;
        let adminRequestsData = [];
        let adminUsersData = []; // เพิ่มตัวแปรเก็บข้อมูลรายชื่อผู้ใช้
        let deptChart = null;
        let existingFiles = [];  // คอลเลกชันไฟล์ที่แนบอยู่เดิม (จากฐานข้อมูล)
        let selectedFiles = [];  // คอลเลกชันไฟล์ใหม่ที่เลือกจาก admin
        let deptPieChart = null;
        let csvUploadData = null; // เก็บข้อมูล CSV ที่จะอัปโหลด

        const STATUS_TH = {
            'Pending': 'รอดำเนินการ',
            'Processing': 'กำลังดำเนินการ',
            'Approved': 'อนุมัติ/เสร็จสิ้น',
            'Rejected': 'ยกเลิกคำขอ'
        };

        const CHART_COLORS = [
            'rgba(14, 165, 233, 0.7)', // Blue
            'rgba(16, 185, 129, 0.7)', // Emerald
            'rgba(245, 158, 11, 0.7)', // Amber
            'rgba(239, 68, 68, 0.7)',  // Rose
            'rgba(99, 102, 241, 0.7)', // Indigo
            'rgba(139, 92, 246, 0.7)', // Violet
            'rgba(236, 72, 153, 0.7)', // Pink
            'rgba(100, 116, 139, 0.7)' // Slate
        ];
        const BORDER_COLORS = [
            'rgb(14, 165, 233)', 'rgb(16, 185, 129)', 'rgb(245, 158, 11)', 'rgb(239, 68, 68)',
            'rgb(99, 102, 241)', 'rgb(139, 92, 246)', 'rgb(236, 72, 153)', 'rgb(100, 116, 139)'
        ];

        // Initialize
        document.addEventListener('DOMContentLoaded', async () => {
            document.getElementById('login-form').addEventListener('submit', handleLogin);
            document.getElementById('request-form').addEventListener('submit', handleRequestSubmit);
            document.getElementById('register-form').addEventListener('submit', handleRegister);
            document.getElementById('feedback-form').addEventListener('submit', handleFeedbackSubmit);
            document.getElementById('password-form').addEventListener('submit', handleChangePassword);

            // Set min date for target_date to 3 days in the future
            const targetDateInput = document.getElementById('target_date');
            if (targetDateInput) {
                const minDate = new Date();
                minDate.setDate(minDate.getDate() + 3);
                const yyyy = minDate.getFullYear();
                const mm = String(minDate.getMonth() + 1).padStart(2, '0');
                const dd = String(minDate.getDate()).padStart(2, '0');
                targetDateInput.min = `${yyyy}-${mm}-${dd}`;
            }

            // Check Session
            try {
                const res = await callServer('checkSession');
                
                // อัปเดตลิงก์ ชื่อบอท และ QR Code ของ Telegram แบบไดนามิกอัตโนมัติ
                if (res.botUsername && res.botUsername !== 'YOUR_BOT_USERNAME') {
                    const tgLinks = document.querySelectorAll('.tg-bot-link');
                    tgLinks.forEach(link => {
                        link.href = `https://t.me/${res.botUsername}`;
                    });
                    const tgNames = document.querySelectorAll('.tg-bot-name');
                    tgNames.forEach(span => {
                        span.innerText = `@${res.botUsername}`;
                    });
                    const tgQr = document.getElementById('tg-bot-qr');
                    if (tgQr) {
                        tgQr.src = `https://quickchart.io/qr?text=https%3A%2F%2Ft.me%2F${res.botUsername}&size=100`;
                    }
                }

                if (res.success) {
                    currentUser = res.user;
                    showView(currentUser.role === 'admin' ? 'admin' : 'user');
                    loadRequests();
                }
            } catch (err) {
                console.error("Session check failed", err);
            }
        });

        function openFeedbackModal(requestId) {
            document.getElementById('feedback-req-id').innerText = requestId;
            document.getElementById('feedback-modal').classList.remove('hidden');
        }

        function closeFeedbackModal() {
            document.getElementById('feedback-modal').classList.add('hidden');
            document.getElementById('feedback-form').reset();
        }

        async function handleFeedbackSubmit(e) {
            e.preventDefault();
            const requestId = document.getElementById('feedback-req-id').innerText;
            const speedRating = document.querySelector('input[name="speedRating"]:checked').value;
            const accuracyRating = document.querySelector('input[name="accuracyRating"]:checked').value;
            const serviceRating = document.querySelector('input[name="serviceRating"]:checked').value;
            const comment = document.getElementById('feedback-comment').value;
            const btn = document.getElementById('feedback-submit-btn');

            btn.disabled = true;
            btn.innerHTML = 'กำลังส่ง...';

            try {
                const res = await callServer('submitFeedback', {
                    formData: {
                        requestId,
                        speedRating,
                        accuracyRating,
                        serviceRating,
                        comment,
                        user: currentUser.username,
                        dept: currentUser.dept
                    }
                });
                if (res.success) {
                    showToast('ขอบคุณสำหรับการประเมินครับ');
                    closeFeedbackModal();
                    loadRequests(); // อัปเดตตารางประวัติทันทีเพื่อเปลี่ยนปุ่มประเมินเป็นสถานะประเมินแล้ว
                } else {
                    showToast(res.message || 'เกิดข้อผิดพลาดในการส่งประเมิน');
                }
            } catch (err) {
                showToast('เกิดข้อผิดพลาดในการส่งประเมิน');
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'ส่งประเมิน';
            }
        }

        function openRegisterModal() {
            document.getElementById('register-modal').classList.remove('hidden');
        }

        function closeRegisterModal() {
            document.getElementById('register-modal').classList.add('hidden');
            document.getElementById('register-form').reset();
            document.getElementById('reg-error').classList.add('hidden');
        }

        // Change Password Modal
        function openChangePasswordModal() {
            document.getElementById('password-modal').classList.remove('hidden');
        }

        function closeChangePasswordModal() {
            document.getElementById('password-modal').classList.add('hidden');
            document.getElementById('password-form').reset();
            document.getElementById('pwd-error').classList.add('hidden');
        }

        async function handleChangePassword(e) {
            e.preventDefault();
            const currentPassword = document.getElementById('current-password').value;
            const newPassword = document.getElementById('new-password').value;
            const confirmNewPassword = document.getElementById('confirm-new-password').value;
            const btn = document.getElementById('pwd-submit-btn');
            const err = document.getElementById('pwd-error');

            if (newPassword !== confirmNewPassword) {
                err.innerText = 'รหัสผ่านใหม่ไม่ตรงกัน';
                err.classList.remove('hidden');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = 'กำลังบันทึก...';
            err.classList.add('hidden');

            try {
                const res = await callServer('changePassword', {
                    username: currentUser.username,
                    currentPassword,
                    newPassword
                });
                if (res.success) {
                    showToast('เปลี่ยนรหัสผ่านสำเร็จแล้ว');
                    closeChangePasswordModal();
                } else {
                    err.innerText = res.message;
                    err.classList.remove('hidden');
                }
            } catch (err) {
                showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ');
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'บันทึก';
            }
        }

        async function handleRegister(e) {
            e.preventDefault();
            const fullName = document.getElementById('reg-fullname').value;
            const dept = document.getElementById('reg-dept').value;
            const username = document.getElementById('reg-username').value;
            const password = document.getElementById('reg-password').value;
            const confirmPassword = document.getElementById('reg-confirm-password').value;
            const btn = document.getElementById('reg-submit-btn');
            const err = document.getElementById('reg-error');

            if (password !== confirmPassword) {
                err.innerText = 'รหัสผ่านไม่ตรงกัน';
                err.classList.remove('hidden');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = 'กำลังประมวลผล...';
            err.classList.add('hidden');

            try {
                const res = await callServer('register', {
                    formData: { fullName, dept, username, password }
                });
                btn.disabled = false;
                btn.innerHTML = 'ลงทะเบียน';

                if (res.success) {
                    showToast('ลงทะเบียนสำเร็จแล้ว โปรดเข้าสู่ระบบ');
                    closeRegisterModal();
                    document.getElementById('username').value = username;
                    document.getElementById('password').value = password;
                } else {
                    err.innerText = res.message;
                    err.classList.remove('hidden');
                }
            } catch (err) {
                btn.disabled = false;
                btn.innerHTML = 'ลงทะเบียน';
                showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ');
            }
        }

        /**
         * ฟังก์ชันหลักสำหรับเรียกคุยกับ Backend
         */
        async function callServer(action, data = {}) {
            if (API_URL) {
                // สำหรับรันบน Server ภายนอก (ใช้ Fetch API)
                try {
                    const response = await fetch(API_URL, {
                        method: 'POST',
                        body: JSON.stringify({ action: action, data: data })
                    });
                    const result = await response.json();
                    return result;
                } catch (e) {
                    console.error("API Error: ", e);
                    throw e;
                }
            } else {
                // สำหรับรันบน Google Apps Script ปกติ (ใช้ google.script.run)
                return new Promise((resolve, reject) => {
                    google.script.run
                        .withSuccessHandler(resolve)
                        .withFailureHandler(reject)[action](...Object.values(data));
                });
            }
        }

        async function handleLogin(e) {
            e.preventDefault();
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const btn = document.getElementById('login-btn');

            btn.disabled = true;
            btn.innerHTML = 'Connecting...';

            try {
                const res = await callServer('login', { username, password });
                btn.disabled = false;
                btn.innerHTML = 'เข้าสู่ระบบ';
                if (res.success) {
                    currentUser = res.user;
                    showView(currentUser.role === 'admin' ? 'admin' : 'user');
                    loadRequests();
                } else {
                    const err = document.getElementById('login-error');
                    err.innerText = res.message;
                    err.classList.remove('hidden');
                }
            } catch (err) {
                btn.disabled = false;
                btn.innerHTML = 'เข้าสู่ระบบ';
                showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้');
            }
        }

        async function handleRequestSubmit(e) {
            e.preventDefault();
            const btn = document.getElementById('submit-request-btn');
            const editId = document.getElementById('edit_request_id').value;

            const selectedTypes = Array.from(document.querySelectorAll('input[name="type"]:checked')).map(el => el.value);
            const selectedCols = Array.from(document.querySelectorAll('input[name="cols"]:checked')).map(el => el.value);
            const isStats = document.getElementById('check-stats').checked;
            const statsInfo = isStats ? ` [รายละเอียดสรุป: ${document.getElementById('stats_details').value}]` : '';

            const formData = {
                user: currentUser.username,
                dept: document.getElementById('req_dept').value,
                requesterName: document.getElementById('req_name').value,
                contactNumber: document.getElementById('req_contact').value,
                fiscalYear: document.getElementById('fiscal_year').value,
                deliveryChannel: document.getElementById('delivery_channel').value + (document.getElementById('delivery_channel_details').value ? ' - ' + document.getElementById('delivery_channel_details').value : ''),
                start_date: document.getElementById('start_date').value,
                end_date: document.getElementById('end_date').value,
                target_date: document.getElementById('target_date').value,
                type: selectedTypes.join(' & ') + statsInfo,
                columns: selectedCols.join(', '),
                details: document.getElementById('additional_details').value,
                purpose: '' // Purpose can be integrated into additional details or separate field
            };

            if (document.getElementById('anonymize_data') && document.getElementById('anonymize_data').checked) {
                formData.details += (formData.details ? '\n' : '') + '[ต้องการให้ปิดบังข้อมูลระบุตัวตน (De-identification)]';
            }

            if (selectedTypes.length === 0) {
                showToast('โปรดเลือกประเภทรายการที่ต้องการ');
                return;
            }

            if (!document.getElementById('pdpa_consent').checked) {
                showToast('โปรดกดยินยอมเงื่อนไข PDPA ก่อนส่งคำขอ');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = 'กำลังบันทึกข้อมูล...';

            try {
                let res;
                if (editId) {
                    formData.id = editId;
                    res = await callServer('updateRequest', { formData });
                } else {
                    res = await callServer('createRequest', { formData });
                }
                
                btn.disabled = false;
                btn.innerHTML = editId ? 'บันทึกการแก้ไข' : 'ยืนยันส่งคำขอข้อมูล';
                
                if (res.success) {
                    showToast(editId ? 'บันทึกการแก้ไขเรียบร้อยแล้ว' : 'ส่งคำขอเรียบร้อยแล้ว');
                    cancelEdit();
                    loadRequests();
                } else {
                    showToast(res.message || 'เกิดข้อผิดพลาดในการส่งข้อมูล');
                }
            } catch (err) {
                btn.disabled = false;
                btn.innerHTML = editId ? 'บันทึกการแก้ไข' : 'ยืนยันส่งคำขอข้อมูล';
                showToast('เกิดข้อผิดพลาดในการส่งข้อมูล');
            }
        }

        function editUserRequest(id) {
            const req = userRequestsData.find(r => r.id === id);
            if (!req) return;
            
            document.getElementById('edit_request_id').value = req.id;
            document.getElementById('req_name').value = req.requester_name || '';
            document.getElementById('req_contact').value = req.contact_number || '';
            document.getElementById('req_dept').value = req.dept || '';
            document.getElementById('fiscal_year').value = req.fiscal_year || '';
            
            // split deliveryChannel
            const deliveryParts = (req.delivery_channel || '').split(' - ');
            document.getElementById('delivery_channel').value = deliveryParts[0] || 'Paper';
            checkDeliveryOther();
            if (deliveryParts.length > 1) {
                document.getElementById('delivery_channel_details').value = deliveryParts[1];
            } else {
                document.getElementById('delivery_channel_details').value = '';
            }

            document.getElementById('start_date').value = req.start_date || '';
            document.getElementById('end_date').value = req.end_date || '';
            document.getElementById('target_date').value = req.target_date || '';
            document.getElementById('additional_details').value = req.details || '';
            
            // checkboxes for cols
            document.querySelectorAll('input[name="cols"]').forEach(cb => {
                cb.checked = (req.columns || '').includes(cb.value);
            });
            
            // types
            document.querySelectorAll('input[name="type"]').forEach(cb => {
                cb.checked = (req.type || '').includes(cb.value);
            });
            const statsMatch = (req.type || '').match(/\[รายละเอียดสรุป: (.*?)\]/);
            if (statsMatch) {
                document.getElementById('stats_details').value = statsMatch[1];
            } else {
                document.getElementById('stats_details').value = '';
            }
            toggleStatsDetail();

            document.getElementById('submit-request-btn').innerHTML = 'บันทึกการแก้ไข';
            document.getElementById('cancel-edit-btn').classList.remove('hidden');
            
            document.getElementById('request-form').scrollIntoView({ behavior: 'smooth' });
        }

        function cancelEdit() {
            document.getElementById('edit_request_id').value = '';
            document.getElementById('request-form').reset();
            document.getElementById('submit-request-btn').innerHTML = 'ยืนยันส่งคำขอข้อมูล';
            document.getElementById('cancel-edit-btn').classList.add('hidden');
            
            // Reset to defaults
            const usr = currentUser;
            if (usr) {
                document.getElementById('req_name').value = usr.fullName || usr.username;
                document.getElementById('req_dept').value = usr.dept || '';
            }
            
            checkDeliveryOther();
            toggleStatsDetail();
        }

        async function loadRequests() {
            if (!currentUser) return;

            try {
                const data = await callServer('getRequests', { role: currentUser.role, username: currentUser.username });
                if (currentUser.role === 'admin') {
                    adminRequestsData = data;
                    renderAdminTable(data);
                    updateDashboard();
                } else {
                    renderUserTable(data);
                }
            } catch (err) {
                console.error("Load error:", err);
            }
        }

        let userRequestsData = [];

        function getFriendlyFileName(url) {
            if (!url) return '';
            const parts = url.split('/');
            const filename = parts[parts.length - 1];
            const underscoreIndex = filename.indexOf('_');
            if (underscoreIndex !== -1) {
                return filename.substring(underscoreIndex + 1);
            }
            return filename;
        }

        function renderUserTable(data) {
            userRequestsData = data;
            const tbody = document.getElementById('user-requests-table');
            tbody.innerHTML = data.map(row => `
                <tr>
                   <td>${row.request_date}</td>
                   <td>
                        <div style="font-weight: 700;">${row.type}</div>
                        <div style="font-size: 0.75rem; color: #64748b;">เริ่ม ${row.start_date} ถึง ${row.end_date}</div>
                   </td>
                   <td>
                        <div style="display: flex; flex-direction: column; gap: 5px;">
                            <span class="status-pill status-${row.status.toLowerCase()}">${STATUS_TH[row.status] || row.status}</span>
                            ${row.status === 'Approved' && row.file_url ? `
                                <div style="display: flex; flex-direction: column; gap: 4px; margin-top: 4px;">
                                    ${row.file_url.split(',').map(url => url.trim()).filter(Boolean).map(url => `
                                        <a href="${url}" target="_blank" class="btn btn-outline" style="font-size: 0.7rem; padding: 2px 6px; display: inline-flex; align-items: center; gap: 4px; border-color: var(--primary); color: var(--primary); text-decoration: none;">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                <polyline points="14 2 14 8 20 8"></polyline>
                                            </svg>
                                            ${getFriendlyFileName(url)}
                                        </a>
                                    `).join('')}
                                </div>
                            ` : ''}
                            <button class="btn btn-outline btn-sm" style="font-size: 0.7rem; padding: 2px 5px;" onclick="previewPDF('${row.id}')">
                                พิมพ์ PDF
                            </button>
                            ${row.status === 'Pending' ? `
                                <button class="btn btn-primary btn-sm" style="font-size: 0.7rem; padding: 2px 5px;" onclick="editUserRequest('${row.id}')">
                                    แก้ไขข้อมูล
                                </button>
                            ` : ''}
                            ${row.status === 'Approved' ? (
                                parseInt(row.has_feedback) > 0 ? `
                                    <span style="font-size: 0.75rem; color: #10b981; font-weight: 600; margin-top: 5px; display: inline-block; text-align: center;">✅ ประเมินแล้ว</span>
                                ` : `
                                    <button class="btn" style="padding: 2px 8px; font-size: 0.65rem; background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; margin-top: 5px;" onclick="openFeedbackModal('${row.id}')">
                                        ประเมิน
                                    </button>
                                `
                            ) : ''}
                        </div>
                   </td>
                </tr>
            `).join('');
        }

        function renderAdminTable(data) {
            const tbody = document.getElementById('admin-requests-table');
            const today = new Date();
            tbody.innerHTML = data.map(row => {
                let rowStyle = '';
                if ((row.status === 'Pending' || row.status === 'Processing') && row.target_date) {
                    const target = new Date(row.target_date);
                    const diffTime = target - today;
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    if (diffDays < 0) {
                        rowStyle = 'background-color: #fee2e2;'; // Overdue (Red)
                    } else if (diffDays <= 2) {
                        rowStyle = 'background-color: #ffedd5;'; // Warning (Orange)
                    }
                }
                return `
                <tr style="${rowStyle}">
                    <td><span style="color: var(--text-muted); font-size: 0.75rem;">${row.id}</span></td>
                    <td><span class="role-badge" style="font-size: 0.7rem;">${row.dept || '-'}</span></td>
                    <td><b>${row.user}</b></td>
                    <td>${row.request_date}</td>
                    <td>
                        <div><b>${row.type}</b></div>
                        <div style="font-size: 0.75rem;">คอลัมน์: ${row.columns || '-'}</div>
                    </td>
                    <td>${row.start_date} to ${row.end_date}</td>
                    <td><span class="status-pill status-${row.status.toLowerCase()}">${STATUS_TH[row.status] || row.status}</span></td>
                    <td>
                        <div style="display: flex; gap: 5px; flex-wrap: wrap; align-items: center; margin-bottom: 5px;">
                            ${row.status === 'Pending' || row.status === 'Processing'
                                ? `<button class="btn btn-primary btn-sm" style="font-size: 0.75rem; padding: 4px 8px;" onclick="openAdminModal('${row.id}')">จัดการ</button>`
                                : `<button class="btn btn-outline btn-sm" style="font-size: 0.75rem; padding: 4px 8px; border-color: var(--warning); color: var(--warning);" onclick="openAdminModal('${row.id}')">แก้ไข</button>`
                            }
                            <button class="btn btn-outline btn-sm" style="font-size: 0.75rem; padding: 4px 8px;" onclick="previewPDF('${row.id}')">PDF</button>
                        </div>
                        ${row.file_url ? `
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                ${row.file_url.split(',').map(url => url.trim()).filter(Boolean).map(url => `
                                    <a href="${url}" target="_blank" class="btn btn-outline" style="font-size: 0.7rem; padding: 2px 6px; display: inline-flex; align-items: center; gap: 4px; border-color: var(--primary); color: var(--primary); text-decoration: none;">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                            <polyline points="14 2 14 8 20 8"></polyline>
                                        </svg>
                                        ${getFriendlyFileName(url)}
                                    </a>
                                `).join('')}
                            </div>
                        ` : '<div style="font-size: 0.75rem; color: var(--text-muted);">ไม่มีไฟล์</div>'}
                    </td>
                </tr>
            }).join('');
        }

        function exportRequestsToCSV() {
            if (!adminRequestsData || adminRequestsData.length === 0) return;
            const headers = ['ID', 'User', 'Department', 'Type', 'Target Date', 'Status'];
            const rows = adminRequestsData.map(r => [
                r.id, r.user, r.dept, '"' + String(r.type || '').replace(/"/g, '""') + '"', r.target_date, r.status
            ]);
            let csvContent = "data:text/csv;charset=utf-8,\uFEFF" + headers.join(",") + "\n" + rows.map(e => e.join(",")).join("\n");
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "medical_requests_export.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        let currentMode = 'user';

        function toggleAdminMode() {
            if (currentMode === 'admin') {
                showView('user');
            } else {
                showView('admin');
            }
        }

        function showView(view) {
            currentMode = view;
            document.getElementById('login-view').classList.add('hidden');
            document.getElementById('user-view').classList.add('hidden');
            document.getElementById('admin-view').classList.add('hidden');
            document.getElementById('app-header').classList.remove('hidden');

            document.getElementById('display-name').innerText = currentUser.fullName || currentUser.username;
            document.getElementById('display-dept').innerText = currentUser.dept || 'N/A';

            if (currentUser && currentUser.role === 'admin') {
                const btnSwitch = document.getElementById('btn-switch-role');
                if (btnSwitch) {
                    btnSwitch.classList.remove('hidden');
                    if (view === 'admin') {
                        btnSwitch.innerText = '👤 สลับเป็นโหมดผู้ใช้งาน (เพื่อสร้างคำขอ)';
                    } else {
                        btnSwitch.innerText = '⚙️ กลับหน้าผู้ดูแลระบบ';
                    }
                }
            } else {
                const btnSwitch = document.getElementById('btn-switch-role');
                if (btnSwitch) btnSwitch.classList.add('hidden');
            }

            if (view === 'admin') {
                document.body.classList.remove('admin-dark-mode'); // Removed dark mode to keep it bright
            } else {
                document.body.classList.remove('admin-dark-mode');
            }

            // Pre-fill request form
            if (view === 'user') {
                document.getElementById('req_name').value = currentUser.fullName || '';
                document.getElementById('req_dept').value = currentUser.dept || '';
            }

            const brandHeader = document.getElementById('brand-header');
            if (view === 'user') {
                document.getElementById('user-view').classList.remove('hidden');
                brandHeader.innerHTML = `<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg> หน้าหลักผู้ใช้งาน`;
                switchUserTab('dashboard');
            }
            if (view === 'admin') {
                document.getElementById('admin-view').classList.remove('hidden');
                brandHeader.innerHTML = `Medical Admin Panel`;
                switchAdminTab('dashboard');
            }
        }

        async function logout() {
            try {
                await callServer('logout');
            } catch (err) {
                console.error("Logout error", err);
            }
            currentUser = null;
            document.getElementById('app-header').classList.add('hidden');
            document.getElementById('user-view').classList.add('hidden');
            document.getElementById('admin-view').classList.add('hidden');
            document.getElementById('login-view').classList.remove('hidden');
            document.getElementById('login-form').reset();
            document.getElementById('login-error').classList.add('hidden');
        }

        // Modal Logic
        function renderAdminModalFiles() {
            const existingContainer = document.getElementById('admin-existing-files');
            if (existingFiles.length > 0) {
                existingContainer.innerHTML = `
                    <div style="margin-top: 10px; margin-bottom: 5px; font-weight: 600; font-size: 0.85rem; color: var(--text-main);">ไฟล์ที่แนบอยู่เดิม:</div>
                    <div class="file-list">
                        ${existingFiles.map((url, idx) => `
                            <div class="file-item">
                                <a href="${url}" target="_blank" class="file-name-link">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                    </svg>
                                    ${getFriendlyFileName(url)}
                                </a>
                                <button type="button" class="file-delete-btn" onclick="removeExistingFile(${idx})" title="ลบไฟล์">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                </button>
                            </div>
                        `).join('')}
                    </div>
                `;
            } else {
                existingContainer.innerHTML = '';
            }

            const newContainer = document.getElementById('admin-new-files');
            if (selectedFiles.length > 0) {
                newContainer.innerHTML = `
                    <div style="margin-top: 10px; margin-bottom: 5px; font-weight: 600; font-size: 0.85rem; color: var(--primary);">ไฟล์ที่จะอัปโหลดใหม่:</div>
                    <div class="file-list">
                        ${selectedFiles.map((file, idx) => `
                            <div class="file-item" style="border-color: var(--primary); background: rgba(14, 165, 233, 0.05);">
                                <span class="file-name-link" style="color: var(--primary);">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                        <polyline points="14 2 14 8 20 8"></polyline>
                                    </svg>
                                    ${file.name} (${(file.size / 1024).toFixed(1)} KB)
                                </span>
                                <button type="button" class="file-delete-btn" onclick="removeSelectedFile(${idx})" title="ลบออกจากรายการ">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                </button>
                            </div>
                        `).join('')}
                    </div>
                `;
                document.getElementById('admin-file-name').innerText = `เลือกแล้ว ${selectedFiles.length} ไฟล์`;
                document.getElementById('admin-file-name').style.color = 'var(--primary)';
            } else {
                newContainer.innerHTML = '';
                document.getElementById('admin-file-name').innerText = 'ยังไม่ได้เลือกไฟล์';
                document.getElementById('admin-file-name').style.color = 'var(--text-muted)';
            }

            document.getElementById('file_url').value = existingFiles.join(', ');
        }

        function removeExistingFile(index) {
            existingFiles.splice(index, 1);
            renderAdminModalFiles();
        }

        function removeSelectedFile(index) {
            selectedFiles.splice(index, 1);
            renderAdminModalFiles();
        }

        function openAdminModal(id) {
            selectedRequestId = id;
            const row = adminRequestsData.find(r => r.id === id);
            if (!row) return;

            document.getElementById('modal-req-id').innerText = 'จัดการคำขอ: ' + id;

            // แสดงรายละเอียดแบบเต็ม
            document.getElementById('view-user').innerText = row.user;
            document.getElementById('view-type').innerText = row.type;
            document.getElementById('view-cols').innerText = row.columns || 'ไม่ได้ระบุ';
            document.getElementById('view-dates').innerText = `${row.start_date} ถึง ${row.end_date}`;
            document.getElementById('view-target').innerText = row.target_date || '-';
            document.getElementById('view-details').innerText = row.details || '-';
            document.getElementById('view-purpose').innerText = row.purpose || '-';

            // ดึงไฟล์เดิมที่มีอยู่
            existingFiles = row.file_url ? row.file_url.split(',').map(s => s.trim()).filter(Boolean) : [];
            selectedFiles = []; // รีเซ็ตไฟล์ใหม่ที่จะเลือกอัปโหลด

            renderAdminModalFiles();

            document.getElementById('admin-modal').classList.remove('hidden');
        }

        function handleAdminFileSelect(event) {
            const files = event.target.files;
            if (files.length > 0) {
                for (let i = 0; i < files.length; i++) {
                    const exists = selectedFiles.some(f => f.name === files[i].name && f.size === files[i].size);
                    if (!exists) {
                        selectedFiles.push(files[i]);
                    }
                }
                renderAdminModalFiles();
            }
            event.target.value = ''; // รีเซ็ตค่าเพื่อให้สามารถเลือกไฟล์เดิมได้
        }

        function closeModal() {
            document.getElementById('admin-modal').classList.add('hidden');
            document.getElementById('file_url').value = '';
            document.getElementById('upload_file').value = '';
            const nameSpan = document.getElementById('admin-file-name');
            nameSpan.innerText = 'ยังไม่ได้เลือกไฟล์';
            nameSpan.style.color = 'var(--text-muted)';
            const progressDiv = document.getElementById('upload-progress');
            progressDiv.classList.add('hidden');
            progressDiv.innerText = 'กำลังอัปโหลด... 0%';
            progressDiv.style.color = 'var(--primary)';
            
            existingFiles = [];
            selectedFiles = [];
        }

        async function submitAdminAction(status) {
            const inputVal = document.getElementById('file_url').value.trim();
            existingFiles = inputVal ? inputVal.split(',').map(s => s.trim()).filter(Boolean) : [];

            let uploadedUrls = [];

            if (selectedFiles.length > 0) {
                const progressDiv = document.getElementById('upload-progress');
                progressDiv.classList.remove('hidden');
                
                for (let i = 0; i < selectedFiles.length; i++) {
                    const file = selectedFiles[i];
                    progressDiv.innerText = `กำลังอัปโหลดไฟล์ (${i + 1}/${selectedFiles.length}): ${file.name}...`;
                    progressDiv.style.color = 'var(--primary)';

                    const formData = new FormData();
                    formData.append('file', file);

                    try {
                        const response = await fetch('upload.php', {
                            method: 'POST',
                            body: formData
                        });
                        const res = await response.json();
                        if (res.success) {
                            uploadedUrls.push(res.fileUrl);
                        } else {
                            showToast('อัปโหลดไฟล์ล้มเหลว: ' + res.message);
                            progressDiv.innerText = 'อัปโหลดล้มเหลว: ' + res.message;
                            progressDiv.style.color = 'var(--danger)';
                            return;
                        }
                    } catch (err) {
                        console.error("Upload error", err);
                        showToast('อัปโหลดไฟล์ล้มเหลว: ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้');
                        progressDiv.innerText = 'อัปโหลดล้มเหลว: ปัญหาทางเครือข่าย';
                        progressDiv.style.color = 'var(--danger)';
                        return;
                    }
                }

                progressDiv.innerText = 'อัปโหลดสำเร็จทั้งหมด!';
                progressDiv.style.color = 'var(--secondary)';
            }

            const finalUrls = [...existingFiles, ...uploadedUrls].join(', ');

            try {
                const res = await callServer('updateRequestStatus', {
                    id: selectedRequestId,
                    status: status,
                    fileUrl: finalUrls
                });
                if (res.success) {
                    showToast(`คำขอถูกเปลี่ยนสถานะเป็น ${STATUS_TH[status] || status}`);
                    closeModal();
                    loadRequests();
                }
            } catch (err) {
                console.error(err);
                showToast('ไม่สามารถอัปเดตสถานะได้');
            }
        }

        // --- User Management Logic ---

        async function loadLoginHistory() {
            try {
                const history = await callServer('getLoginHistory');
                renderLoginHistoryTable(history);
            } catch (err) {
                showToast('โหลดข้อมูลประวัติเข้าใช้งานไม่สำเร็จ');
            }
        }
        
        function renderLoginHistoryTable(history) {
            const tbody = document.getElementById('admin-login-history-table');
            if (history.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">ไม่พบประวัติเข้าใช้งาน</td></tr>';
                return;
            }
            tbody.innerHTML = history.map(h => `
                <tr>
                    <td><b>${h.username}</b></td>
                    <td>${h.ip_address}</td>
                    <td style="font-size: 0.75rem; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${h.user_agent}">${h.user_agent}</td>
                    <td>${h.login_time}</td>
                </tr>
            `).join('');
        }

        function switchUserTab(tab) {
            const tabs = ['requests', 'dashboard', 'feedback'];
            tabs.forEach(t => {
                const btn = document.getElementById('user-tab-' + t);
                const sec = document.getElementById('user-section-' + t);
                
                if (t === 'dashboard') {
                    const el = document.getElementById('shared-dashboard-content');
                    if (el && el.parentElement.id !== 'user-section-dashboard') {
                        document.getElementById('user-section-dashboard').appendChild(el);
                    }
                } else if (t === 'feedback') {
                    const el = document.getElementById('shared-feedback-content');
                    if (el && el.parentElement.id !== 'user-section-feedback') {
                        document.getElementById('user-section-feedback').appendChild(el);
                    }
                }

                if (btn && sec) {
                    if (t === tab) {
                        btn.classList.add('btn-primary');
                        btn.classList.remove('btn-outline');
                        sec.classList.remove('hidden');
                    } else {
                        btn.classList.remove('btn-primary');
                        btn.classList.add('btn-outline');
                        sec.classList.add('hidden');
                    }
                }
            });

            if (tab === 'dashboard' || tab === 'feedback') {
                updateDashboard();
            }
        }

        async function loadAuditLog() {
            try {
                const logs = await callServer('getAuditLog');
                const tbody = document.getElementById('admin-audit-log-table');
                if (logs.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">ไม่พบประวัติ</td></tr>';
                    return;
                }
                tbody.innerHTML = logs.map(l => `
                    <tr>
                        <td style="font-size: 0.8rem;">${l.timestamp}</td>
                        <td><b>${l.username}</b></td>
                        <td><span style="color: #3b82f6; font-weight: 600;">${l.action}</span></td>
                        <td style="font-size: 0.85rem;">${l.details}</td>
                    </tr>
                `).join('');
            } catch (err) {
                showToast('โหลดข้อมูล Audit Log ไม่สำเร็จ');
            }
        }

        function switchAdminTab(tab) {
            const tabs = ['dashboard', 'requests', 'feedback', 'login-history', 'audit-log', 'users'];
            tabs.forEach(t => {
                const btn = document.getElementById('tab-' + t);
                const sec = document.getElementById('admin-section-' + t);
                
                if (t === 'dashboard') {
                    const el = document.getElementById('shared-dashboard-content');
                    if (el && el.parentElement.id !== 'admin-section-dashboard') {
                        document.getElementById('admin-section-dashboard').appendChild(el);
                    }
                } else if (t === 'feedback') {
                    const el = document.getElementById('shared-feedback-content');
                    if (el && el.parentElement.id !== 'admin-section-feedback') {
                        document.getElementById('admin-section-feedback').appendChild(el);
                    }
                }

                if (btn && sec) {
                    if (t === tab) {
                        btn.classList.add('btn-primary');
                        btn.classList.remove('btn-outline');
                        sec.classList.remove('hidden');
                    } else {
                        btn.classList.remove('btn-primary');
                        btn.classList.add('btn-outline');
                        sec.classList.add('hidden');
                    }
                }
            });

            if (tab === 'dashboard') {
                updateDashboard();
            } else if (tab === 'requests') {
                loadRequests();
            } else if (tab === 'users') {
                loadUsers();
            } else if (tab === 'login-history') {
                loadLoginHistory();
            } else if (tab === 'audit-log') {
                loadAuditLog();
            }
        }

        async function loadUsers() {
            try {
                const users = await callServer('getAllUsers');
                adminUsersData = users;
                renderUserManagementTable(users);
            } catch (err) {
                showToast('โหลดข้อมูลผู้ใช้ไม่สำเร็จ');
            }
        }

        function renderUserManagementTable(users) {
            const tbody = document.getElementById('admin-users-table');
            if (users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">ไม่พบข้อมูลพนักงาน</td></tr>';
                return;
            }

            tbody.innerHTML = users.map(u => {
                const isActive = u.is_active === undefined ? true : u.is_active == 1;
                return `
                <tr style="${isActive ? '' : 'opacity: 0.5;'}">
                    <td>${u.id}</td>
                    <td><b>${u.fullName}</b> ${isActive ? '' : '<span style="color:var(--danger); font-size: 0.75rem; margin-left: 5px;">(ระงับ)</span>'}</td>
                    <td>${u.username}</td>
                    <td><code>${u.password}</code></td>
                    <td><span class="status-pill ${u.role === 'admin' ? 'status-approved' : 'status-processing'}">${u.role}</span></td>
                    <td style="font-size: 0.8rem;">${u.dept}</td>
                    <td>
                        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                            <button class="btn btn-outline btn-sm" style="font-size: 0.75rem; padding: 4px 8px;" onclick="openAdminResetPasswordModal('${u.username}')">รีเซ็ตรหัส</button>
                            <button class="btn btn-primary btn-sm" style="font-size: 0.75rem; padding: 4px 8px;" onclick="openAdminEditUserModal('${u.username}', '${u.fullName}', '${u.dept}', '${u.role}')">แก้ไข</button>
                            ${isActive ? 
                                `<button class="btn btn-danger btn-sm" style="font-size: 0.75rem; padding: 4px 8px;" onclick="adminToggleUserStatus('${u.username}', false)">ปิดการใช้งาน</button>` :
                                `<button class="btn btn-success btn-sm" style="font-size: 0.75rem; padding: 4px 8px;" onclick="adminToggleUserStatus('${u.username}', true)">เปิดการใช้งาน</button>`
                            }
                            <button class="btn btn-danger btn-sm" style="font-size: 0.75rem; padding: 4px 8px;" onclick="adminDeleteUser('${u.username}')">ลบ</button>
                        </div>
                    </td>
                </tr>
                `;
            }).join('');
        }

        function filterUserTable() {
            const query = document.getElementById('user-search').value.toLowerCase();
            const filtered = adminUsersData.filter(u =>
                u.fullName.toLowerCase().includes(query) ||
                u.username.toLowerCase().includes(query) ||
                u.dept.toLowerCase().includes(query)
            );
            renderUserManagementTable(filtered);
        }

        // --- CSV Import ---

        function openImportModal() {
            document.getElementById('import-modal').classList.remove('hidden');
        }

        function closeImportModal() {
            document.getElementById('import-modal').classList.add('hidden');
            document.getElementById('csv-file-input').value = '';
            document.getElementById('file-label-text').innerText = 'คลิกเพื่อเลือกไฟล์ CSV';
            document.getElementById('import-preview').classList.add('hidden');
            document.getElementById('confirm-import-btn').disabled = true;
            csvUploadData = null;
        }

        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;

            document.getElementById('file-label-text').innerText = file.name;

            const reader = new FileReader();
            reader.onload = function (e) {
                const text = e.target.result;
                parseCSV(text);
            };
            reader.readAsText(file);
        }

        function parseCSV(text) {
            const lines = text.split(/\r?\n/);
            const headers = lines[0].split(',').map(h => h.trim().toLowerCase());

            // Map headers to indices
            const mapping = {
                id: headers.indexOf('id'),
                username: headers.indexOf('username'),
                password: headers.indexOf('password'),
                role: headers.indexOf('role'),
                dept: headers.indexOf('dept'),
                full_name: headers.indexOf('full_name')
            };

            const result = [];
            const previewRows = [];

            for (let i = 1; i < lines.length; i++) {
                if (!lines[i].trim()) continue;
                const cols = lines[i].split(',').map(c => c.trim());
                if (cols.length < 2) continue;

                const user = [
                    cols[mapping.id] || '',
                    cols[mapping.username] || '',
                    cols[mapping.password] || '1234',
                    cols[mapping.role] || 'user',
                    cols[mapping.dept] || '',
                    cols[mapping.full_name] || ''
                ];

                result.push(user);
                if (previewRows.length < 5) {
                    previewRows.push(`<tr><td>${user[1]}</td><td>${user[5]}</td><td>${user[4]}</td></tr>`);
                }
            }

            if (result.length > 0) {
                csvUploadData = result;
                document.getElementById('preview-body').innerHTML = previewRows.join('');
                document.getElementById('import-preview').classList.remove('hidden');
                document.getElementById('confirm-import-btn').disabled = false;
                document.getElementById('confirm-import-btn').innerText = `ยืนยันนำเข้า (${result.length} รายการ)`;
            }
        }

        async function processImport() {
            if (!csvUploadData) return;

            const btn = document.getElementById('confirm-import-btn');
            btn.disabled = true;
            btn.innerText = 'กำลังส่งข้อมูล...';

            try {
                const res = await callServer('importUsers', { userData: csvUploadData });
                if (res.success) {
                    showToast(`นำเข้าสำเร็จ ${csvUploadData.length} รายการ`);
                    closeImportModal();
                    loadUsers();
                } else {
                    showToast('เกิดข้อผิดพลาด: ' + res.message);
                }
            } catch (err) {
                showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้');
            } finally {
                btn.disabled = false;
            }
        }

        // --- Admin Reset Password ---
        function openAdminResetPasswordModal(username) {
            document.getElementById('reset-pwd-title').innerText = `รีเซ็ตรหัสผ่าน: ${username}`;
            document.getElementById('reset-target-username').value = username;
            document.getElementById('admin-new-password').value = '';
            document.getElementById('admin-reset-password-modal').classList.remove('hidden');
        }

        function closeAdminResetPasswordModal() {
            document.getElementById('admin-reset-password-modal').classList.add('hidden');
        }

        async function submitAdminResetPassword(e) {
            e.preventDefault();
            const username = document.getElementById('reset-target-username').value;
            const newPassword = document.getElementById('admin-new-password').value;
            const btn = document.getElementById('admin-reset-pwd-btn');
            
            btn.disabled = true;
            btn.innerText = 'กำลังบันทึก...';
            
            try {
                const res = await callServer('adminResetPassword', { targetUsername: username, newPassword: newPassword });
                if (res.success) {
                    showToast(`รีเซ็ตรหัสผ่านของ ${username} สำเร็จ`);
                    closeAdminResetPasswordModal();
                } else {
                    showToast('เกิดข้อผิดพลาด: ' + (res.message || 'Unknown error'));
                }
            } catch (err) {
                showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้');
            } finally {
                btn.disabled = false;
                btn.innerText = 'บันทึกรหัสผ่านใหม่';
            }
        }

        // --- Admin Edit User ---
        function openAdminEditUserModal(username, fullName, dept, role) {
            document.getElementById('edit-target-username').value = username;
            document.getElementById('edit-user-fullname').value = fullName;
            document.getElementById('edit-user-dept').value = dept;
            document.getElementById('edit-user-role').value = role;
            document.getElementById('admin-edit-user-modal').classList.remove('hidden');
        }

        function closeAdminEditUserModal() {
            document.getElementById('admin-edit-user-modal').classList.add('hidden');
        }

        async function submitAdminEditUser(e) {
            e.preventDefault();
            const username = document.getElementById('edit-target-username').value;
            const fullName = document.getElementById('edit-user-fullname').value;
            const dept = document.getElementById('edit-user-dept').value;
            const role = document.getElementById('edit-user-role').value;
            const btn = document.getElementById('admin-edit-user-btn');
            
            btn.disabled = true;
            btn.innerText = 'กำลังบันทึก...';
            
            try {
                const res = await callServer('adminUpdateUser', { username, fullName, dept, role });
                if (res.success) {
                    showToast(`อัปเดตข้อมูล ${username} สำเร็จ`);
                    closeAdminEditUserModal();
                    loadUsers();
                } else {
                    showToast('เกิดข้อผิดพลาด: ' + (res.message || 'Unknown error'));
                }
            } catch (err) {
                showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้');
            } finally {
                btn.disabled = false;
                btn.innerText = 'บันทึกข้อมูล';
            }
        }

        async function adminToggleUserStatus(username, status) {
            if (!confirm(`คุณต้องการ ${status ? 'เปิดการใช้งาน' : 'ปิดการใช้งาน'} ผู้ใช้ ${username} ใช่หรือไม่?`)) return;
            try {
                const res = await callServer('adminToggleUserStatus', { username, status });
                if (res.success) {
                    showToast(`${status ? 'เปิดการใช้งาน' : 'ปิดการใช้งาน'} สำเร็จ`);
                    loadUsers();
                } else {
                    showToast('เกิดข้อผิดพลาด: ' + (res.message || 'Unknown error'));
                }
            } catch (err) {
                showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้');
            }
        }

        async function adminDeleteUser(username) {
            if (!confirm(`คุณต้องการลบผู้ใช้ ${username} ใช่หรือไม่?\n(ประวัติคำขอเก่าจะไม่ถูกลบ แต่ผู้ใช้นี้จะเข้าสู่ระบบไม่ได้อีก)`)) return;
            try {
                const res = await callServer('adminDeleteUser', { username });
                if (res.success) {
                    showToast(`ลบผู้ใช้ ${username} สำเร็จ`);
                    loadUsers();
                } else {
                    showToast('เกิดข้อผิดพลาด: ' + (res.message || 'Unknown error'));
                }
            } catch (err) {
                showToast('ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้');
            }
        }

        // UI Helpers
        function clearDashboardFilters() {
            if(document.getElementById('dash-filter-fiscal')) document.getElementById('dash-filter-fiscal').value = '';
            if(document.getElementById('dash-filter-start')) document.getElementById('dash-filter-start').value = '';
            if(document.getElementById('dash-filter-end')) document.getElementById('dash-filter-end').value = '';
            updateDashboard();
        }

        async function updateDashboard() {
            try {
                const fiscalYear = document.getElementById('dash-filter-fiscal') ? document.getElementById('dash-filter-fiscal').value : '';
                const startDate = document.getElementById('dash-filter-start') ? document.getElementById('dash-filter-start').value : '';
                const endDate = document.getElementById('dash-filter-end') ? document.getElementById('dash-filter-end').value : '';

                const data = await callServer('getDashboardData', { fiscalYear, startDate, endDate });
                document.getElementById('stat-total').innerText = data.total;
                document.getElementById('stat-avg-rating').innerText = data.avgRating || '0.0';

                renderChart(data.labels, data.counts);
                renderFeedbackTable(data.recentFeedback || []);
            } catch (err) {
                console.error("Dashboard error:", err);
            }
        }

        function renderFeedbackTable(feedbackList) {
            const tbody = document.getElementById('admin-feedback-table');
            if (feedbackList.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align: center;">ยังไม่มีการประเมิน</td></tr>';
                return;
            }
            tbody.innerHTML = feedbackList.map(fb => `
                <tr>
                    <td style="font-size: 0.75rem;">${fb.timestamp}</td>
                    <td><span style="color: #f59e0b; font-weight: 700;">${'★'.repeat(fb.rating)}</span></td>
                    <td><div style="font-size: 0.85rem;">${fb.comment || '-'}</div></td>
                    <td><div style="font-size: 0.75rem;"><b>${fb.user}</b> (${fb.dept})</div></td>
                </tr>
            `).join('');
        }

        function renderChart(labels, counts) {
            const ctxBar = document.getElementById('deptChart').getContext('2d');
            const ctxPie = document.getElementById('deptPieChart').getContext('2d');

            if (deptChart) deptChart.destroy();
            if (deptPieChart) deptPieChart.destroy();

            const backgroundColors = labels.map((_, i) => CHART_COLORS[i % CHART_COLORS.length]);
            const borderColors = labels.map((_, i) => BORDER_COLORS[i % BORDER_COLORS.length]);

            // บาร์ชาร์ตแบบแยกสี
            deptChart = new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'คำขอแยกรายแผนก',
                        data: counts,
                        backgroundColor: backgroundColors,
                        borderColor: borderColors,
                        borderWidth: 1,
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        title: { display: true, text: 'แนวโน้มยอดคำขอแยกตามแผนก', font: { size: 14 } }
                    },
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                }
            });

            // พายชาร์ต
            deptPieChart = new Chart(ctxPie, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: counts,
                        backgroundColor: backgroundColors,
                        borderColor: 'white',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12, padding: 15 } },
                        title: { display: true, text: 'สัดส่วนคำขอ (Percentage Allocation)', font: { size: 14 } }
                    }
                }
            });
        }

        function checkDeliveryOther() {
            const channel = document.getElementById('delivery_channel').value;
            const details = document.getElementById('delivery_channel_details');
            if (channel === 'อื่นๆ') {
                details.placeholder = "โปรดระบุช่องทางการส่งข้อมูลที่คุณต้องการ...";
                details.focus();
            } else {
                details.placeholder = "ระบุรายละเอียดเพิ่มเติม (เช่น ID Line, Email หรือหมายเหตุ)...";
            }
        }

        function showToast(msg) {
            const toast = document.getElementById('toast');
            document.getElementById('toast-msg').innerText = msg;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        function toggleStatsDetail() {
            const isChecked = document.getElementById('check-stats').checked;
            const box = document.getElementById('stats-detail-box');
            if (isChecked) {
                box.classList.remove('hidden');
            } else {
                box.classList.add('hidden');
                document.getElementById('stats_details').value = '';
            }
        }

        let currentPdfId = null;

        function previewPDF(id) {
            // Find the request from either userRequestsData or adminRequestsData
            const dataRow = (currentUser.role === 'admin' ? adminRequestsData : userRequestsData).find(r => r.id === id);
            if (!dataRow) return;

            currentPdfId = id;

            document.getElementById('pdf-req-id').innerText = dataRow.id;
            document.getElementById('pdf-req-date').innerText = dataRow.request_date;
            document.getElementById('pdf-user').innerText = `${dataRow.requester_name || '-'} (${dataRow.user || '-'})`;
            document.getElementById('pdf-dept').innerText = dataRow.dept || '-';
            
            // New fields
            document.getElementById('pdf-contact').innerText = dataRow.contact_number || '-';
            document.getElementById('pdf-fiscal').innerText = dataRow.fiscal_year || '-';
            document.getElementById('pdf-delivery').innerText = dataRow.delivery_channel || '-';
            document.getElementById('pdf-purpose').innerText = dataRow.purpose || '-';
            
            document.getElementById('pdf-type').innerText = dataRow.type;
            document.getElementById('pdf-cols').innerText = dataRow.columns || '-';
            document.getElementById('pdf-range').innerText = `${dataRow.start_date} ถึง ${dataRow.end_date}`;
            document.getElementById('pdf-target').innerText = dataRow.target_date || '-';
            document.getElementById('pdf-details').innerText = dataRow.details || '-';
            document.getElementById('pdf-status').innerText = STATUS_TH[dataRow.status] || dataRow.status;

            // Signature block pre-filling
            document.getElementById('pdf-sig-user').innerText = dataRow.requester_name || '................................................';
            
            // Format Thai Buddhist Era date for request date
            if (dataRow.request_date) {
                const datePart = dataRow.request_date.split(' ')[0];
                const parts = datePart.split('-');
                if (parts.length === 3) {
                    const thaiYear = parseInt(parts[0]) + 543;
                    document.getElementById('pdf-sig-date').innerText = `${parts[2]} / ${parts[1]} / ${thaiYear}`;
                } else {
                    document.getElementById('pdf-sig-date').innerText = '....... / ....... / .......';
                }
            } else {
                document.getElementById('pdf-sig-date').innerText = '....... / ....... / .......';
            }

            if (dataRow.approved_by) {
                document.getElementById('pdf-sig-admin').innerText = dataRow.approved_by;
                document.getElementById('pdf-sig-admin-date').innerText = '....... / ....... / .......';
            } else {
                document.getElementById('pdf-sig-admin').innerText = '................................................';
                document.getElementById('pdf-sig-admin-date').innerText = '....... / ....... / .......';
            }

            document.getElementById('pdf-preview-modal').classList.remove('hidden');
        }

        function closePdfPreviewModal() {
            document.getElementById('pdf-preview-modal').classList.add('hidden');
        }

        function downloadPDF() {
            const element = document.getElementById('pdf-template');
            const btn = document.getElementById('btn-download-pdf');
            const originalText = btn.innerText;

            btn.innerText = 'กำลังสร้าง PDF...';
            btn.disabled = true;

            const opt = {
                margin: 0,
                filename: `Medical_Request_${currentPdfId}.pdf`,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { 
                    scale: 2, 
                    useCORS: true, 
                    letterRendering: true,
                    scrollY: 0,
                    scrollX: 0
                },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save().then(() => {
                btn.innerText = originalText;
                btn.disabled = false;
            });
        }
    
