@extends('frontend.layouts.app')
@section('title', 'งานคลินิก NCD')

@section('content')

<!-- Hero Section -->
<section class="py-5" style="background: linear-gradient(135deg, #fce7f3 0%, #fdf2f8 50%, #fff1f2 100%);">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">หน้าแรก</a></li>
                <li class="breadcrumb-item active">งานคลินิก NCD</li>
            </ol>
        </nav>
        <div class="text-center py-4">
            <div class="d-flex align-items-center justify-content-center rounded-circle mx-auto mb-4" style="width:100px;height:100px;background:linear-gradient(135deg,#fce7f3,#fbcfe8);box-shadow:0 10px 30px rgba(219,39,119,0.15);">
                <i class="bi bi-heart-pulse" style="font-size:3rem;color:#db2777;"></i>
            </div>
            <h1 class="fw-bold mb-3" style="color:#831843;">งานคลินิกโรคไม่ติดต่อเรื้อรัง (NCD)</h1>
            <p class="text-muted mx-auto" style="max-width:700px;font-size:1.1rem;">
                คลินิกเฉพาะทางภายใต้กลุ่มงาน NCD ของโรงพยาบาลเทพรัตนเวชชานุกูลฯ
                ให้บริการดูแลผู้ป่วยโรคเรื้อรังอย่างครบวงจร โดยทีมสหวิชาชีพ
            </p>
        </div>
    </div>
</section>

<!-- NCD Clinic Cards -->
<section class="py-5">
    <div class="container">
        <div class="row g-4">

            <!-- Asthma -->
            <div class="col-md-6 col-lg-4">
                <div class="ncd-clinic-card h-100">
                    <div class="ncd-clinic-icon" style="background:linear-gradient(135deg,#fef3c7,#fde68a);">
                        <i class="bi bi-lungs" style="color:#d97706;"></i>
                    </div>
                    <h4 class="fw-bold mt-3 mb-2">คลินิกโรคหืด</h4>
                    <span class="badge rounded-pill mb-3" style="background:#fef3c7;color:#92400e;">Asthma Clinic</span>
                    <p class="text-muted small mb-3">ให้บริการตรวจวินิจฉัย ประเมินความรุนแรง วางแผนการรักษา และให้ความรู้ผู้ป่วยโรคหืดตามแนวทาง GINA Guidelines</p>
                    <ul class="ncd-clinic-services">
                        <li>ตรวจสมรรถภาพปอด (Peak Flow Meter)</li>
                        <li>ประเมินระดับการควบคุมโรค</li>
                        <li>สอนการใช้ยาพ่นสูดอย่างถูกวิธี</li>
                        <li>วางแผนการจัดการอาการกำเริบ</li>
                    </ul>
                </div>
            </div>

            <!-- COPD -->
            <div class="col-md-6 col-lg-4">
                <div class="ncd-clinic-card h-100">
                    <div class="ncd-clinic-icon" style="background:linear-gradient(135deg,#dbeafe,#bfdbfe);">
                        <i class="bi bi-wind" style="color:#2563eb;"></i>
                    </div>
                    <h4 class="fw-bold mt-3 mb-2">คลินิก COPD</h4>
                    <span class="badge rounded-pill mb-3" style="background:#dbeafe;color:#1e40af;">COPD Clinic</span>
                    <p class="text-muted small mb-3">ดูแลผู้ป่วยโรคปอดอุดกั้นเรื้อรัง (Chronic Obstructive Pulmonary Disease) ครบวงจร ตั้งแต่การวินิจฉัยจนถึงการฟื้นฟู</p>
                    <ul class="ncd-clinic-services">
                        <li>ตรวจสมรรถภาพปอดด้วย Spirometry</li>
                        <li>ประเมินระดับความรุนแรง GOLD Classification</li>
                        <li>โปรแกรมฟื้นฟูสมรรถภาพปอด</li>
                        <li>ให้คำปรึกษาเรื่องการเลิกบุหรี่</li>
                    </ul>
                </div>
            </div>

            <!-- Warfarin -->
            <div class="col-md-6 col-lg-4">
                <div class="ncd-clinic-card h-100">
                    <div class="ncd-clinic-icon" style="background:linear-gradient(135deg,#fce7f3,#fbcfe8);">
                        <i class="bi bi-droplet" style="color:#db2777;"></i>
                    </div>
                    <h4 class="fw-bold mt-3 mb-2">คลินิก Warfarin</h4>
                    <span class="badge rounded-pill mb-3" style="background:#fce7f3;color:#9d174d;">Warfarin Clinic</span>
                    <p class="text-muted small mb-3">ดูแลผู้ป่วยที่ใช้ยาต้านการแข็งตัวของเลือด Warfarin เพื่อให้ค่า INR อยู่ในระดับที่เหมาะสมและปลอดภัย</p>
                    <ul class="ncd-clinic-services">
                        <li>ตรวจค่า INR อย่างสม่ำเสมอ</li>
                        <li>ปรับขนาดยาตามค่า INR</li>
                        <li>ให้ความรู้เรื่องอาหารและยาที่มีผลต่อ Warfarin</li>
                        <li>เฝ้าระวังภาวะเลือดออกผิดปกติ</li>
                    </ul>
                </div>
            </div>

            <!-- Hypertension (HT) -->
            <div class="col-md-6 col-lg-4">
                <div class="ncd-clinic-card h-100">
                    <div class="ncd-clinic-icon" style="background:linear-gradient(135deg,#fee2e2,#fecaca);">
                        <i class="bi bi-activity" style="color:#dc2626;"></i>
                    </div>
                    <h4 class="fw-bold mt-3 mb-2">คลินิกความดันโลหิตสูง</h4>
                    <span class="badge rounded-pill mb-3" style="background:#fee2e2;color:#991b1b;">Hypertension Clinic</span>
                    <p class="text-muted small mb-3">คัดกรอง วินิจฉัย และดูแลผู้ป่วยโรคความดันโลหิตสูง เพื่อป้องกันภาวะแทรกซ้อนที่สำคัญ เช่น โรคหลอดเลือดสมอง</p>
                    <ul class="ncd-clinic-services">
                        <li>วัดความดันโลหิตและประเมินความเสี่ยง</li>
                        <li>ปรับเปลี่ยนพฤติกรรม ลดเค็ม ออกกำลังกาย</li>
                        <li>ติดตามผลการรักษาอย่างต่อเนื่อง</li>
                        <li>คัดกรองภาวะแทรกซ้อนจากความดันสูง</li>
                    </ul>
                </div>
            </div>

            <!-- CKD -->
            <div class="col-md-6 col-lg-4">
                <div class="ncd-clinic-card h-100">
                    <div class="ncd-clinic-icon" style="background:linear-gradient(135deg,#d1fae5,#a7f3d0);">
                        <i class="bi bi-capsule" style="color:#059669;"></i>
                    </div>
                    <h4 class="fw-bold mt-3 mb-2">คลินิกโรคไต</h4>
                    <span class="badge rounded-pill mb-3" style="background:#d1fae5;color:#065f46;">CKD Clinic</span>
                    <p class="text-muted small mb-3">ดูแลผู้ป่วยโรคไตเรื้อรัง (Chronic Kidney Disease) ทุกระยะ เพื่อชะลอการเสื่อมของไตและป้องกันไตวาย</p>
                    <ul class="ncd-clinic-services">
                        <li>ตรวจค่าการทำงานของไต (eGFR, Cr)</li>
                        <li>ให้คำแนะนำโภชนาการผู้ป่วยโรคไต</li>
                        <li>ปรับยาให้เหมาะกับค่า eGFR</li>
                        <li>คัดกรองและส่งต่อผู้ป่วยที่ต้องฟอกไต</li>
                    </ul>
                </div>
            </div>

            <!-- Diabetes (DM) -->
            <div class="col-md-6 col-lg-4">
                <div class="ncd-clinic-card h-100">
                    <div class="ncd-clinic-icon" style="background:linear-gradient(135deg,#ede9fe,#ddd6fe);">
                        <i class="bi bi-eyedropper" style="color:#7c3aed;"></i>
                    </div>
                    <h4 class="fw-bold mt-3 mb-2">คลินิกเบาหวาน</h4>
                    <span class="badge rounded-pill mb-3" style="background:#ede9fe;color:#5b21b6;">Diabetes Clinic (DM)</span>
                    <p class="text-muted small mb-3">ดูแลผู้ป่วยเบาหวานอย่างครบวงจร ทั้งการควบคุมระดับน้ำตาลในเลือด และป้องกันภาวะแทรกซ้อนจากเบาหวาน</p>
                    <ul class="ncd-clinic-services">
                        <li>ตรวจ HbA1c และน้ำตาลสะสม</li>
                        <li>ตรวจคัดกรองจอประสาทตา เท้า ไต</li>
                        <li>ให้ความรู้เรื่องอาหารและการดูแลตนเอง</li>
                        <li>สอนการฉีดอินซูลินอย่างถูกวิธี</li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Contact Section -->
<section class="py-5" style="background:var(--primary-light);">
    <div class="container">
        <div class="glass-card p-5 text-center">
            <h3 class="fw-bold mb-3" style="color:var(--primary-dark);"><i class="bi bi-info-circle me-2"></i>ข้อมูลการให้บริการ</h3>
            <p class="text-muted mb-4" style="max-width:600px;margin:0 auto;">
                คลินิก NCD เปิดให้บริการทุกวันราชการ ตามตารางนัดหมายของแต่ละคลินิก
                สามารถสอบถามข้อมูลเพิ่มเติมได้ที่เคาน์เตอร์ OPD หรือโทร
            </p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="tel:{{ config('hospital.phone') }}" class="btn btn-premium px-4">
                    <i class="bi bi-telephone me-2"></i>โทรสอบถาม
                </a>
                <a href="{{ route('contact') }}" class="btn btn-outline-success px-4" style="border-radius:12px;">
                    <i class="bi bi-geo-alt me-2"></i>ดูแผนที่
                </a>
            </div>
        </div>
    </div>
</section>

@endsection

@push('styles')
<style>
    .ncd-clinic-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        border: 1px solid rgba(0,0,0,0.05);
        box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        position: relative;
        overflow: hidden;
    }
    .ncd-clinic-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, transparent, currentColor, transparent);
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .ncd-clinic-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 30px rgba(0,0,0,0.1);
    }
    .ncd-clinic-card:hover::before {
        opacity: 0.3;
    }
    .ncd-clinic-icon {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        transition: transform 0.3s ease;
    }
    .ncd-clinic-card:hover .ncd-clinic-icon {
        transform: scale(1.1) rotate(5deg);
    }
    .ncd-clinic-services {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .ncd-clinic-services li {
        padding: 0.4rem 0;
        padding-left: 1.5rem;
        position: relative;
        font-size: 0.88rem;
        color: #555;
        border-bottom: 1px dashed rgba(0,0,0,0.06);
    }
    .ncd-clinic-services li:last-child {
        border-bottom: none;
    }
    .ncd-clinic-services li::before {
        content: '\F26E'; /* bi-check-circle-fill */
        font-family: 'bootstrap-icons';
        position: absolute;
        left: 0;
        color: #10b981;
        font-size: 0.85rem;
    }
</style>
@endpush
