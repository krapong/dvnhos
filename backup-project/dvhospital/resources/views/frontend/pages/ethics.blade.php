@extends('frontend.layouts.app')
@section('title', 'ชมรมจริยธรรม')

@push('styles')
<style>
    .ethics-hero {
        background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
        color: white;
        padding: 5rem 0 4rem 0;
        border-radius: 0 0 40px 40px;
        position: relative;
        overflow: hidden;
    }
    .ethics-hero::before {
        content: '';
        position: absolute;
        width: 300px;
        height: 300px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 50%;
        top: -100px;
        right: -50px;
    }
    .ethics-hero::after {
        content: '';
        position: absolute;
        width: 150px;
        height: 150px;
        background: rgba(255, 255, 255, 0.03);
        border-radius: 50%;
        bottom: -30px;
        left: 10%;
    }
    .ethics-badge {
        background: rgba(255, 255, 255, 0.15);
        color: white;
        padding: 6px 16px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.85rem;
        letter-spacing: 0.05em;
        backdrop-filter: blur(10px);
        display: inline-flex;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    .doc-card {
        transition: all 0.3s ease;
        border-radius: 16px;
        border: 1px solid rgba(0,0,0,0.06);
    }
    .doc-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 137, 123, 0.08);
        border-color: var(--primary-color);
    }
    .pdf-viewer-container {
        border-radius: 16px;
        overflow: hidden;
        border: 1px solid rgba(0,0,0,0.08);
        box-shadow: var(--shadow-premium);
        display: none;
    }
    .pdf-viewer-header {
        background: #374151;
        color: white;
        padding: 10px 15px;
    }
    .value-card {
        border-radius: 16px;
        transition: all 0.3s ease;
        border-left: 4px solid var(--accent-color) !important;
    }
    .value-card:hover {
        background-color: var(--primary-light) !important;
    }
</style>
@endpush

@section('content')
<!-- Hero Header -->
<div class="ethics-hero shadow-sm">
    <div class="container text-center py-4">
        <span class="ethics-badge shadow-sm"><i class="bi bi-shield-check me-2"></i>คุณธรรมต้นแบบ & ความโปร่งใส</span>
        <h1 class="display-5 fw-bold text-white mb-3" style="font-family: 'Sarabun', sans-serif;">ชมรมจริยธรรม</h1>
        <p class="lead opacity-90 mx-auto" style="max-width: 800px; font-weight: 400; font-size: 1.15rem;">
            มุ่งมั่นส่งเสริมหลักจริยธรรม ความโปร่งใส และค่านิยมความซื่อสัตย์สุจริตภายในองค์กร เพื่อการบริการสุขภาพที่เปี่ยมด้วยคุณภาพและจริยธรรมวิชาชีพ
        </p>
    </div>
</div>

<section class="py-5" style="background-color: #f8fafc;">
    <div class="container">
        <div class="row g-4">
            <!-- Left Column: Pages Content -->
            <div class="col-lg-8">
                @foreach($pages as $page)
                <div class="card glass-card mb-4 border-0 p-3">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary text-white p-3 rounded-3 me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; background-color: var(--primary-color) !important;">
                                <i class="bi bi-heart-fill fs-4"></i>
                            </div>
                            <h3 class="fw-bold text-dark m-0" style="font-size: 1.5rem;">{{ $page->title }}</h3>
                        </div>
                        
                        <div class="text-muted mb-4" style="line-height: 1.8; text-align: justify; font-size: 1.05rem; white-space: pre-line;">
                            {!! $page->content !!}
                        </div>

                        @if($page->attachments && count($page->attachments) > 0)
                        <div class="border-top pt-4">
                            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-folder-symlink-fill text-primary me-2"></i> เอกสารชมรมจริยธรรม</h5>
                            <div class="row g-3">
                                @foreach($page->attachments as $index => $file)
                                <div class="col-md-6">
                                    <div class="card doc-card h-100 bg-white">
                                        <div class="card-body p-3 d-flex flex-column justify-content-between">
                                            <div class="d-flex align-items-start gap-3">
                                                <div class="bg-light p-2 rounded-3 text-danger">
                                                    <i class="bi bi-file-earmark-pdf-fill fs-2"></i>
                                                </div>
                                                <div class="flex-grow-1" style="min-width: 0;">
                                                    <h6 class="fw-bold text-dark mb-1 text-truncate-2 small" style="line-height: 1.4;">{{ $file['name'] }}</h6>
                                                    <small class="text-muted d-block mb-2">ขนาด: {{ number_format(($file['size'] ?? 0) / 1024, 1) }} KB</small>
                                                </div>
                                            </div>
                                            <div class="d-flex gap-2 mt-2 pt-2 border-top">
                                                <a href="/storage/{{ $file['path'] }}" class="btn btn-outline-success btn-sm flex-fill rounded-pill" target="_blank">
                                                    <i class="bi bi-download me-1"></i> ดาวน์โหลด
                                                </a>
                                                <button type="button" class="btn btn-primary btn-sm flex-fill rounded-pill btn-preview-pdf" data-pdf-url="/storage/{{ $file['path'] }}" data-pdf-title="{{ $file['name'] }}">
                                                    <i class="bi bi-eye me-1"></i> เปิดดูออนไลน์
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach

                <!-- PDF Live Viewer -->
                <div class="pdf-viewer-container bg-white mb-4 shadow" id="pdfViewerBlock">
                    <div class="pdf-viewer-header d-flex justify-content-between align-items-center">
                        <span class="fw-bold small text-truncate me-3" id="pdfViewerTitle"><i class="bi bi-file-earmark-pdf me-1"></i> แสดงเอกสารออนไลน์</span>
                        <button type="button" class="btn-close btn-close-white btn-sm" id="btnClosePdfViewer"></button>
                    </div>
                    <div class="ratio ratio-16x9" style="height: 600px;">
                        <iframe id="pdfViewerIframe" src="" frameborder="0" allowfullscreen></iframe>
                    </div>
                </div>

                @if($pages->isEmpty())
                <div class="card glass-card text-center py-5 border-0">
                    <div class="card-body py-5 text-muted">
                        <i class="bi bi-heart text-danger mb-3" style="font-size: 3.5rem;"></i>
                        <h4 class="fw-bold text-dark">ชมรมจริยธรรม</h4>
                        <p class="mt-2 text-muted">กำลังเตรียมความพร้อมของข้อมูลและจัดส่งขึ้นระบบหลังบ้าน...</p>
                    </div>
                </div>
                @endif
            </div>

            <!-- Right Column: Institutional Core Value & Reporting -->
            <div class="col-lg-4">
                <!-- Core Values -->
                <div class="card border-0 shadow-sm rounded-4 mb-4 p-3 bg-white">
                    <div class="card-body">
                        <h5 class="fw-bold text-dark mb-4 border-bottom pb-2"><i class="bi bi-bookmark-star-fill text-warning me-2"></i> ค่านิยมกระทรวงสาธารณสุข</h5>
                        
                        <div class="card value-card bg-light border-0 mb-3 p-2 shadow-xs">
                            <div class="card-body py-2">
                                <h6 class="fw-bold text-primary mb-1">M - Mastery (เป็นนายตนเอง)</h6>
                                <p class="small text-muted mb-0">ฝึกฝนตนเอง ทักษะการปฏิบัติงาน บริหารอารมณ์ และเป็นแบบอย่างที่ดี</p>
                            </div>
                        </div>

                        <div class="card value-card bg-light border-0 mb-3 p-2 shadow-xs">
                            <div class="card-body py-2">
                                <h6 class="fw-bold text-primary mb-1">O - Originality (เร่งสร้างสิ่งใหม่)</h6>
                                <p class="small text-muted mb-0">คิดค้นนวัตกรรม เทคโนโลยี และการปรับปรุงระบบบริการที่เป็นเลิศ</p>
                            </div>
                        </div>

                        <div class="card value-card bg-light border-0 mb-3 p-2 shadow-xs">
                            <div class="card-body py-2">
                                <h6 class="fw-bold text-primary mb-1">P - People Centered (ใส่ใจประชาชน)</h6>
                                <p class="small text-muted mb-0">ยึดประชาชนเป็นศูนย์กลาง ดูแลด้วยความเอื้ออาทรและเคารพศักดิ์ศรี</p>
                            </div>
                        </div>

                        <div class="card value-card bg-light border-0 mb-3 p-2 shadow-xs">
                            <div class="card-body py-2">
                                <h6 class="fw-bold text-primary mb-1">H - Humility (ถ่อมตนอ่อนน้อม)</h6>
                                <p class="small text-muted mb-0">มีสัมมาคารวะ อดทน เปิดรับฟังความคิดเห็น และช่วยเหลือผู้อื่น</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ethics Reporting Contacts -->
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-top border-4 border-success">
                    <div class="card-body">
                        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-telephone-inbound-fill text-success me-2"></i> ติดต่อประสานงาน</h5>
                        <p class="small text-muted mb-4">หากท่านพบเห็นการกระทำทุจริต หรือประพฤติมิชอบ สามารถแจ้งข้อมูลประสานงานไปยังชมรมจริยธรรมได้โดยตรง</p>
                        
                        <div class="d-flex gap-3 align-items-center mb-3">
                            <div class="bg-light p-3 rounded-circle text-primary">
                                <i class="bi bi-geo-alt-fill fs-5"></i>
                            </div>
                            <div>
                                <span class="d-block small text-muted">สถานที่ติดต่อ</span>
                                <span class="fw-bold text-dark small">กลุ่มงานบริหารทั่วไป โรงพยาบาลเทพรัตนเวชชานุกูลฯ</span>
                            </div>
                        </div>

                        <div class="d-flex gap-3 align-items-center mb-3">
                            <div class="bg-light p-3 rounded-circle text-success">
                                <i class="bi bi-telephone-fill fs-5"></i>
                            </div>
                            <div>
                                <span class="d-block small text-muted">เบอร์โทรศัพท์</span>
                                <span class="fw-bold text-dark small">053-XXX-XXX (ต่อ งานพัฒนาคุณธรรมจริยธรรม)</span>
                            </div>
                        </div>

                        <div class="d-flex gap-3 align-items-center">
                            <div class="bg-light p-3 rounded-circle text-info">
                                <i class="bi bi-envelope-fill fs-5"></i>
                            </div>
                            <div>
                                <span class="d-block small text-muted">อีเมลประสานงาน</span>
                                <span class="fw-bold text-dark small">ethics@dvhospital.go.th</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const previewButtons = document.querySelectorAll('.btn-preview-pdf');
        const pdfViewerBlock = document.getElementById('pdfViewerBlock');
        const pdfViewerIframe = document.getElementById('pdfViewerIframe');
        const pdfViewerTitle = document.getElementById('pdfViewerTitle');
        const btnClosePdfViewer = document.getElementById('btnClosePdfViewer');

        previewButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const pdfUrl = this.getAttribute('data-pdf-url');
                const pdfTitle = this.getAttribute('data-pdf-title');

                pdfViewerTitle.innerHTML = `<i class="bi bi-file-earmark-pdf text-danger me-1"></i> ${pdfTitle}`;
                pdfViewerIframe.src = pdfUrl;
                pdfViewerBlock.style.display = 'block';
                
                // Scroll to viewer smoothly
                pdfViewerBlock.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });

        if (btnClosePdfViewer) {
            btnClosePdfViewer.addEventListener('click', function() {
                pdfViewerBlock.style.display = 'none';
                pdfViewerIframe.src = '';
            });
        }
    });
</script>
@endpush
