@extends('frontend.layouts.app')
@section('title', 'ITA - การประเมินคุณธรรมและความโปร่งใส')

@section('content')
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4 flex-wrap gap-3">
            <div>
                <h2 class="section-title mb-1">ITA - การประเมินคุณธรรมและความโปร่งใส</h2>
                <p class="text-muted mb-0">Integrity and Transparency Assessment (ITA) - การประเมินคุณธรรมและความโปร่งใสในการดำเนินงานของหน่วยงานภาครัฐ</p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <!-- ปีงบประมาณ Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-premium dropdown-toggle px-4" type="button" id="yearDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-calendar-event me-2"></i> ปีงบประมาณ {{ $year }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg mt-2" aria-labelledby="yearDropdown">
                        <li><a class="dropdown-item {{ $year == '2569' ? 'fw-bold active' : '' }} py-2" href="{{ route('ita', ['year' => '2569']) }}">
                            <i class="bi {{ $year == '2569' ? 'bi-check2-circle' : 'bi-calendar' }} me-2"></i> ปีงบประมาณ 2569</a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item {{ $year == '2568' ? 'fw-bold active' : '' }} py-2" href="{{ route('ita', ['year' => '2568']) }}">
                            <i class="bi {{ $year == '2568' ? 'bi-check2-circle' : 'bi-calendar' }} me-2"></i> ปีงบประมาณ 2568</a>
                        </li>
                        <li><a class="dropdown-item {{ $year == '2567' ? 'fw-bold active' : '' }} py-2" href="{{ route('ita', ['year' => '2567']) }}">
                            <i class="bi {{ $year == '2567' ? 'bi-check2-circle' : 'bi-calendar' }} me-2"></i> ปีงบประมาณ 2567</a>
                        </li>
                    </ul>
                </div>

                @if(auth()->check() && (auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('staff_ita')))
                <a href="{{ route('admin.pages.create', ['section' => $year === '2569' ? 'ita' : 'ita_'.$year]) }}" class="btn btn-outline-primary border-2 shadow-sm fw-bold">
                    <i class="bi bi-plus-circle me-1"></i> เพิ่มรายการ
                </a>
                @endif
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-8 col-lg-6">
                <div class="input-group input-group-lg shadow-sm rounded-pill overflow-hidden border border-primary border-opacity-25">
                    <span class="input-group-text bg-white border-0 text-primary ps-4"><i class="bi bi-search"></i></span>
                    <input type="text" id="itaSearchInput" class="form-control border-0 ps-2" placeholder="ค้นหาข้อ, หัวข้อ หรือชื่อเอกสาร...">
                </div>
            </div>
        </div>

        @foreach($pages as $page)
        <div class="card mb-4 border-0 shadow-sm overflow-hidden ita-card-container">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-4 border-bottom pb-3">
                    <div class="flex-grow-1">
                        <h5 class="fw-bold text-primary mb-1 ita-card-title">{{ $page->title }}</h5>
                        <div class="small text-muted"><i class="bi bi-calendar-event me-1"></i> ปรับปรุงล่าสุด: {{ $page->updated_at->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
                
                <div class="ita-content mb-3">
                @php
                    $rawContent = trim($page->content);
                    $lines = [];
                    // ใช้เลข MOIT จากชื่อการ์ดเพื่อเติมหน้าหัวข้อย่อยตอนแสดงผลเท่านั้น
                    // ไม่แก้ไขค่า content เดิม เพื่อไม่ให้การจับคู่ไฟล์แนบเปลี่ยนแปลง
                    $moitLabel = null;
                    if (preg_match('/\bMOIT\s*([0-9]+)/iu', $page->title, $moitMatch)) {
                        $moitLabel = 'MOIT ' . $moitMatch[1];
                    }
                        
                        // ตรวจสอบว่าเป็น JSON array หรือไม่ (รองรับข้อมูลที่คัดลอกมาจากระบบอื่น)
                        if (str_starts_with($rawContent, '[') && str_ends_with($rawContent, ']')) {
                            $json = json_decode($rawContent, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                                // ฟังก์ชันแปลง JSON Tree เป็นบรรทัดข้อความพร้อมตัวเลขกำกับ
                                $flattenJson = function($items, $prefix = '') use (&$flattenJson) {
                                    $result = [];
                                    $index = 1;
                                    foreach ($items as $item) {
                                        $currentPrefix = $prefix === '' ? $index . '.' : $prefix . $index;
                                        $text = trim($item['text'] ?? '');
                                        
                                        if (!empty($text)) {
                                            // หากข้อความยังไม่มีตัวเลขกำกับ ให้เติมเข้าไป
                                            if (!preg_match('/^(ข้อ\s*\d+|\d+(?:\.\d+)*)/', $text)) {
                                                $text = $currentPrefix . ' ' . $text;
                                            }
                                            $result[] = $text;
                                        }
                                        
                                        if (!empty($item['children']) && is_array($item['children'])) {
                                            $newPrefix = $prefix === '' ? $index . '.' : $prefix . $index . '.';
                                            $result = array_merge($result, $flattenJson($item['children'], $newPrefix));
                                        }
                                        $index++;
                                    }
                                    return $result;
                                };
                                $lines = $flattenJson($json);
                            } else {
                                $lines = explode("\n", $rawContent);
                            }
                        } else {
                            $lines = explode("\n", $rawContent);
                        }
                    @endphp
                    <div class="list-group list-group-flush border-0">
                        @foreach($lines as $line)
                            @php
                                $line = trim($line);
                                if(empty($line)) continue;
                                
                                // ตรวจสอบลำดับหัวข้อย่อยเพื่อจัดรูปแบบและการแสดงผลปุ่ม
                                $isLevel2 = preg_match('/^\d+\.\d+/', $line);
                                $isSubItem = preg_match('/^(ข้อ\s*\d+|\d+)/', $line);
                                $itemKey = '';
                                if(preg_match('/^(ข้อ\s*\d+|\d+(?:\.\d+)*)/', $line, $matches)) {
                                    $itemKey = trim($matches[1]);
                                }
                                // หัวข้อย่อยใช้ข้อความเดิม ส่วนชื่อไฟล์แนบค่อยเติม MOIT แยกต่างหาก
                                $displayLine = $line;
                            @endphp
                            <div class="list-group-item border-0 px-0 py-2 {{ $isSubItem ? ($isLevel2 ? 'ms-4 border-bottom border-light' : 'ms-2 fw-medium border-bottom border-light') : 'fw-bold text-dark' }}">
                                <div class="d-flex align-items-start justify-content-between flex-column flex-md-row">
                                    <div class="d-flex align-items-start flex-grow-1">
                                        <div class="me-2 text-primary"><i class="bi {{ $isSubItem ? 'bi-dot' : 'bi-chevron-right' }} fs-5"></i></div>
                                        <div class="flex-grow-1">
                                            <span>{{ $displayLine }}</span>
                                            
                                            {{-- ค้นหาเอกสารแนบที่ตรงกับข้อนี้ --}}
                                            @if($page->attachments && $itemKey)
                                                <div class="mt-2 d-flex flex-column gap-2 align-items-start">
                                                     @foreach($page->attachments as $file)
                                                         @if(preg_match('/(?:^|\s)' . preg_quote($itemKey, '/') . '(?!\d|\.)/u', trim($file['name'])))
                                                             @php
                                                                 $displayFileName = $file['name'];
                                                                 if ($moitLabel && !preg_match('/^MOIT\s*\d+\s*:/iu', trim($displayFileName))) {
                                                                     $displayFileName = $moitLabel . ' : ' . $displayFileName;
                                                                 }
                                                             @endphp
                                                             <a href="{{ Storage::url($file['path']) }}" class="btn btn-white btn-sm border shadow-sm py-1 px-3 rounded-pill hover-primary text-start w-auto" target="_blank">
                                                                 <i class="bi bi-file-earmark-pdf text-primary me-1"></i>
                                                                 <span class="small">{{ $displayFileName }}</span>
                                                             </a>
                                                         @endif
                                                     @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    @if($isSubItem && auth()->check() && (auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('staff_ita')))
                                        <div class="ms-md-3 mt-2 mt-md-0 d-flex gap-1 align-self-end align-self-md-center flex-shrink-0">
                                            <button type="button" class="btn btn-xs btn-outline-success rounded-pill px-2 py-0.5 btn-upload-attachment" 
                                                data-page-id="{{ $page->id }}" 
                                                data-item-key="{{ $itemKey }}" 
                                                data-item-text="{{ $displayLine }}">
                                                <i class="bi bi-cloud-upload small"></i> <span class="small">uploadเอกสาร</span>
                                            </button>
                                            <button type="button" class="btn btn-xs btn-outline-warning rounded-pill px-2 py-0.5 btn-edit-attachments" 
                                                data-page-id="{{ $page->id }}" 
                                                data-item-key="{{ $itemKey }}"
                                                data-page-attachments="{{ json_encode($page->attachments ?? []) }}">
                                                <i class="bi bi-pencil small"></i> <span class="small">แก้ไข</span>
                                            </button>
                                            <button type="button" class="btn btn-xs btn-outline-danger rounded-pill px-2 py-0.5 btn-delete-attachments" 
                                                data-page-id="{{ $page->id }}" 
                                                data-item-key="{{ $itemKey }}"
                                                data-page-attachments="{{ json_encode($page->attachments ?? []) }}">
                                                <i class="bi bi-trash small"></i> <span class="small">ลบ</span>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                @php
                    $mappedFiles = [];
                    if($page->attachments) {
                        foreach($page->attachments as $file) {
                            $isMapped = false;
                            foreach($lines as $line) {
                                if(preg_match('/^(ข้อ\s*\d+|\d+(?:\.\d+)*)/', trim($line), $matches)) {
                                    if(preg_match('/(?:^|\s)' . preg_quote(trim($matches[1]), '/') . '(?!\d|\.)/u', trim($file['name']))) {
                                        $isMapped = true;
                                        break;
                                    }
                                }
                            }
                            if(!$isMapped) $mappedFiles[] = $file;
                        }
                    }
                @endphp

                @if(!empty($mappedFiles))
                <div class="mt-4 p-3 bg-light rounded-3 border-start border-4 border-secondary">
                    <h6 class="small fw-bold text-muted mb-3"><i class="bi bi-folder-symlink me-1"></i> เอกสารแนบทั่วไป:</h6>
                    <div class="d-flex flex-column gap-2 align-items-start">
                         @foreach($mappedFiles as $file)
                         @php
                             $displayFileName = $file['name'];
                             if ($moitLabel && !preg_match('/^MOIT\s*\d+\s*:/iu', trim($displayFileName))) {
                                 $displayFileName = $moitLabel . ' : ' . $displayFileName;
                             }
                         @endphp
                         <a href="{{ Storage::url($file['path']) }}" class="btn btn-white btn-sm border shadow-sm py-1 px-3 rounded-pill hover-primary text-start w-auto" target="_blank">
                             <i class="bi bi-file-earmark-pdf text-primary me-1"></i>
                             <span class="small">{{ $displayFileName }}</span>
                         </a>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</section>

@push('styles')
<style>
    .btn-xs {
        padding: 0.15rem 0.4rem;
        font-size: 0.75rem;
        line-height: 1.2;
    }
    .btn-xs i {
        font-size: 0.75rem;
        vertical-align: -0.1em;
    }
    .modal-content {
        border-radius: 12px;
    }
</style>
@endpush

@if(auth()->check() && (auth()->user()->hasRole('super_admin') || auth()->user()->hasRole('staff_ita')))
<!-- Upload Attachment Modal -->
<div class="modal fade" id="uploadAttachmentModal" tabindex="-1" aria-labelledby="uploadAttachmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-success text-white border-0 py-3">
                <h5 class="modal-title fw-bold" id="uploadAttachmentModalLabel">
                    <i class="bi bi-cloud-upload me-2"></i>อัปโหลดเอกสารสำหรับข้อ <span id="uploadModalKey"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="uploadAttachmentForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="item_key" id="uploadFormKey">
                <input type="hidden" name="page_id" id="uploadFormPageId">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted">หัวข้อข้อกำหนด:</label>
                        <p class="p-3 bg-light rounded text-dark small" id="uploadModalText" style="white-space: normal; word-break: break-all;"></p>
                    </div>
                    <div class="mb-3">
                        <label for="attachment_name" class="form-label fw-bold">ชื่อแสดงของไฟล์แนบ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="attachment_name" id="attachment_name" required placeholder="เช่น 1.1 บันทึกข้อความ..." style="border-radius: 8px;">
                        <div class="form-text small text-muted">ชื่อแสดงบนเว็บไซต์ ควรขึ้นต้นด้วยรหัสข้อย่อย เช่น "1.1" เป็นต้น</div>
                    </div>
                    <div class="mb-3">
                        <label for="attachment_file" class="form-label fw-bold">เลือกไฟล์เอกสาร <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" name="attachment_file" id="attachment_file" required accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg">
                        <div class="form-text small text-muted">รองรับไฟล์ PDF, Word, Excel หรือรูปภาพ ขนาดไม่เกิน 10MB</div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-3 bg-light d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal" style="border-radius: 8px;">ยกเลิก</button>
                    <button type="submit" class="btn btn-success px-4" style="border-radius: 8px;">
                        <span class="spinner-border spinner-border-sm d-none me-1" role="status" aria-hidden="true" id="uploadSpinner"></span>
                        <i class="bi bi-check-circle me-1" id="uploadIcon"></i> อัปโหลดเอกสาร
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Attachment Modal -->
<div class="modal fade" id="editAttachmentModal" tabindex="-1" aria-labelledby="editAttachmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-warning text-dark border-0 py-3">
                <h5 class="modal-title fw-bold" id="editAttachmentModalLabel">
                    <i class="bi bi-pencil me-2"></i>แก้ไขเอกสารแนบสำหรับข้อ <span id="editModalKey"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="editModalBody">
                <!-- Content dynamically populated -->
            </div>
            <div class="modal-footer border-0 p-3 bg-light">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal" style="border-radius: 8px;">ปิด</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Attachment Modal -->
<div class="modal fade" id="deleteAttachmentModal" tabindex="-1" aria-labelledby="deleteAttachmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-danger text-white border-0 py-3">
                <h5 class="modal-title fw-bold" id="deleteAttachmentModalLabel">
                    <i class="bi bi-trash me-2"></i>ลบเอกสารแนบสำหรับข้อ <span id="deleteModalKey"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="deleteModalBody">
                <!-- Content dynamically populated -->
            </div>
            <div class="modal-footer border-0 p-3 bg-light">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal" style="border-radius: 8px;">ปิด</button>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function confirmDelete(id, title) {
        Swal.fire({
            title: 'ยืนยันการลบ?',
            text: `คุณต้องการลบรายการ "${title}" ใช่หรือไม่?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ยืนยันการลบ',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/admin/pages/${id}`;
                form.innerHTML = `
                    @csrf
                    @method('DELETE')
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const csrfToken = '{{ csrf_token() }}';

        // Open Upload Modal
        document.querySelectorAll('.btn-upload-attachment').forEach(btn => {
            btn.addEventListener('click', function() {
                const pageId = this.getAttribute('data-page-id');
                const itemKey = this.getAttribute('data-item-key');
                const itemText = this.getAttribute('data-item-text');

                document.getElementById('uploadFormPageId').value = pageId;
                document.getElementById('uploadFormKey').value = itemKey;
                document.getElementById('uploadModalKey').textContent = itemKey;
                document.getElementById('uploadModalText').textContent = itemText;
                document.getElementById('attachment_name').value = itemText;

                const modal = new bootstrap.Modal(document.getElementById('uploadAttachmentModal'));
                modal.show();
            });
        });

        // Upload Form Submit
        const uploadForm = document.getElementById('uploadAttachmentForm');
        if (uploadForm) {
            uploadForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const pageId = document.getElementById('uploadFormPageId').value;
                const formData = new FormData(this);

                const spinner = document.getElementById('uploadSpinner');
                const icon = document.getElementById('uploadIcon');
                const submitBtn = uploadForm.querySelector('button[type="submit"]');

                const fileInput = uploadForm.querySelector('input[type="file"]');
                if (fileInput && fileInput.files.length > 0) {
                    if (fileInput.files[0].size > 10 * 1024 * 1024) { // 10MB
                        Swal.fire({
                            icon: 'error',
                            title: 'ขนาดไฟล์ใหญ่เกินไป',
                            text: 'กรุณาอัปโหลดไฟล์ขนาดไม่เกิน 10MB',
                            confirmButtonText: 'ตกลง'
                        });
                        return;
                    }
                }

                spinner.classList.remove('d-none');
                icon.classList.add('d-none');
                submitBtn.disabled = true;

                fetch(`{{ request()->getBaseUrl() }}/admin/pages/${pageId}/upload-attachment`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ',
                            text: data.message,
                            confirmButtonText: 'ตกลง'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        throw new Error(data.message || 'เกิดข้อผิดพลาด');
                    }
                })
                .catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: err.message || 'เกิดข้อผิดพลาดทางเทคนิค',
                        confirmButtonText: 'ตกลง'
                    });
                    spinner.classList.add('d-none');
                    icon.classList.remove('d-none');
                    submitBtn.disabled = false;
                });
            });
        }

        // Open Edit Modal
        document.querySelectorAll('.btn-edit-attachments').forEach(btn => {
            btn.addEventListener('click', function() {
                const pageId = this.getAttribute('data-page-id');
                const itemKey = this.getAttribute('data-item-key');
                const attachments = JSON.parse(this.getAttribute('data-page-attachments'));

                document.getElementById('editModalKey').textContent = itemKey;
                
                let html = '';
                let found = false;

                // Use strict regex matching similar to PHP backend
                const escapeRegExp = (string) => string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                const keyRegex = new RegExp(`(?:^|\\s)${escapeRegExp(itemKey)}(?!\\d|\\.)`);
                
                attachments.forEach((file, index) => {
                    if (keyRegex.test(file.name.trim())) {
                        found = true;
                        html += `
                            <div class="card mb-3 shadow-sm border border-light">
                                <div class="card-body p-3">
                                    <form class="edit-attachment-single-form" data-page-id="${pageId}" data-index="${index}">
                                        <input type="hidden" name="_token" value="${csrfToken}">
                                        <input type="hidden" name="attachment_index" value="${index}">
                                        <div class="mb-2">
                                            <label class="form-label small fw-bold">ชื่อไฟล์แนบ:</label>
                                            <input type="text" class="form-control form-control-sm" name="attachment_name" value="${file.name}" required style="border-radius: 6px;">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small fw-bold">เปลี่ยนไฟล์เอกสาร (ถ้าต้องการ):</label>
                                            <input type="file" class="form-control form-control-sm" name="attachment_file" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg">
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <a href="/storage/${file.path}" target="_blank" class="btn btn-sm btn-link p-0 small text-decoration-none"><i class="bi bi-eye"></i> ดูไฟล์ปัจจุบัน</a>
                                            <button type="submit" class="btn btn-sm btn-warning px-3 rounded-pill text-dark">
                                                <i class="bi bi-save me-1"></i> บันทึก
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        `;
                    }
                });

                if (!found) {
                    html = '<p class="text-muted text-center py-4">ไม่พบเอกสารแนบสำหรับข้อนี้</p>';
                }

                document.getElementById('editModalBody').innerHTML = html;
                
                // Add submit handlers to newly generated forms
                document.querySelectorAll('.edit-attachment-single-form').forEach(form => {
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        const formPageId = this.getAttribute('data-page-id');
                        const formFormData = new FormData(this);
                        const btnSubmit = this.querySelector('button[type="submit"]');
                        const fileInput = this.querySelector('input[type="file"]');
                        if (fileInput && fileInput.files.length > 0) {
                            if (fileInput.files[0].size > 10 * 1024 * 1024) { // 10MB
                                Swal.fire({
                                    icon: 'error',
                                    title: 'ขนาดไฟล์ใหญ่เกินไป',
                                    text: 'กรุณาอัปโหลดไฟล์ขนาดไม่เกิน 10MB',
                                    confirmButtonText: 'ตกลง'
                                });
                                return;
                            }
                        }

                        btnSubmit.disabled = true;
                        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';

                        fetch(`{{ request()->getBaseUrl() }}/admin/pages/${formPageId}/update-attachment`, {
                            method: 'POST',
                            body: formFormData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'สำเร็จ',
                                    text: data.message,
                                    confirmButtonText: 'ตกลง'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                throw new Error(data.message || 'เกิดข้อผิดพลาด');
                            }
                        })
                        .catch(err => {
                            Swal.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาด',
                                text: err.message || 'เกิดข้อผิดพลาดทางเทคนิค',
                                confirmButtonText: 'ตกลง'
                            });
                            btnSubmit.disabled = false;
                            btnSubmit.innerHTML = '<i class="bi bi-save me-1"></i> บันทึก';
                        });
                    });
                });

                const modal = new bootstrap.Modal(document.getElementById('editAttachmentModal'));
                modal.show();
            });
        });

        // Open Delete Modal
        document.querySelectorAll('.btn-delete-attachments').forEach(btn => {
            btn.addEventListener('click', function() {
                const pageId = this.getAttribute('data-page-id');
                const itemKey = this.getAttribute('data-item-key');
                const attachments = JSON.parse(this.getAttribute('data-page-attachments'));

                document.getElementById('deleteModalKey').textContent = itemKey;

                let html = '';
                let found = false;

                // Use strict regex matching similar to PHP backend
                const escapeRegExp = (string) => string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                const keyRegex = new RegExp(`(?:^|\\s)${escapeRegExp(itemKey)}(?!\\d|\\.)`);

                attachments.forEach((file, index) => {
                    if (keyRegex.test(file.name.trim())) {
                        found = true;
                        html += `
                            <div class="d-flex justify-content-between align-items-center p-3 mb-2 bg-light rounded border border-light">
                                <div class="text-truncate me-3" style="max-width: 75%;">
                                    <span class="small fw-semibold text-dark">${file.name}</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-danger px-3 rounded-pill btn-delete-single-file" data-page-id="${pageId}" data-path="${encodeURIComponent(file.path)}" data-name="${encodeURIComponent(file.name)}">
                                    <i class="bi bi-trash me-1"></i> ลบ
                                </button>
                            </div>
                        `;
                    }
                });

                if (!found) {
                    html = '<p class="text-muted text-center py-4">ไม่พบเอกสารแนบสำหรับข้อนี้</p>';
                }

                document.getElementById('deleteModalBody').innerHTML = html;

                // Add delete handlers to newly generated buttons
                document.querySelectorAll('.btn-delete-single-file').forEach(delBtn => {
                    delBtn.addEventListener('click', function() {
                        const delPageId = this.getAttribute('data-page-id');
                        const delPath = decodeURIComponent(this.getAttribute('data-path'));
                        const delName = decodeURIComponent(this.getAttribute('data-name'));
                        const button = this;

                        Swal.fire({
                            title: 'ยืนยันการลบไฟล์?',
                            text: `คุณแน่ใจว่าต้องการลบไฟล์ "${delName}" ใช่หรือไม่?`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d33',
                            cancelButtonColor: '#3085d6',
                            confirmButtonText: 'ยืนยัน',
                            cancelButtonText: 'ยกเลิก'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                button.disabled = true;
                                button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';

                                fetch(`{{ request()->getBaseUrl() }}/admin/pages/${delPageId}/delete-attachment`, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': csrfToken,
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify({ attachment_path: delPath })
                                })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'สำเร็จ',
                                            text: data.message,
                                            confirmButtonText: 'ตกลง'
                                        }).then(() => {
                                            // Refresh from the server and avoid a cached HTML response.
                                            location.reload(true);
                                        });
                                    } else {
                                        throw new Error(data.message || 'เกิดข้อผิดพลาด');
                                    }
                                })
                                .catch(err => {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'เกิดข้อผิดพลาด',
                                        text: err.message || 'เกิดข้อผิดพลาดทางเทคนิค',
                                        confirmButtonText: 'ตกลง'
                                    });
                                    button.disabled = false;
                                    button.innerHTML = '<i class="bi bi-trash me-1"></i> ลบ';
                                });
                            }
                        });
                    });
                });

                const modal = new bootstrap.Modal(document.getElementById('deleteAttachmentModal'));
                modal.show();
            });
        });
        // Search Functionality
        const searchInput = document.getElementById('itaSearchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const term = this.value.toLowerCase().trim();
                const cards = document.querySelectorAll('.ita-card-container');
                
                cards.forEach(card => {
                    let cardHasMatch = false;
                    
                    const titleText = card.querySelector('.ita-card-title').textContent.toLowerCase();
                    const titleMatches = titleText.includes(term);
                    
                    const items = card.querySelectorAll('.list-group-item');
                    items.forEach(item => {
                        const itemText = item.textContent.toLowerCase();
                        if (term === '' || titleMatches || itemText.includes(term)) {
                            item.style.display = '';
                            cardHasMatch = true;
                        } else {
                            item.style.display = 'none';
                        }
                    });
                    
                    if (term === '' || titleMatches || cardHasMatch) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        }
    });
</script>
@endpush
@endsection
