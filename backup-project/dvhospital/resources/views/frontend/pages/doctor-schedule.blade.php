@extends('frontend.layouts.app')
@section('title', 'ตารางแพทย์ออกตรวจ')

@section('content')
<section class="py-5">
    <div class="container">
        <h2 class="section-title">ตารางแพทย์ออกตรวจ</h2>
        <p class="text-muted mb-4">ตารางออกตรวจของแพทย์ประจำโรงพยาบาลเทพรัตนเวชชานุกูลฯ (อาจมีการเปลี่ยนแปลง กรุณาโทรสอบถามก่อนเข้ารับบริการ)</p>

        @if($doctors->isNotEmpty())
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0" style="font-size: 0.95rem;">
                        <thead>
                            <tr class="text-center" style="background: var(--primary); color: white;">
                                <th style="min-width: 180px;">แพทย์ / ความเชี่ยวชาญ</th>
                                <th>จันทร์</th>
                                <th>อังคาร</th>
                                <th>พุธ</th>
                                <th>พฤหัสบดี</th>
                                <th>ศุกร์</th>
                                <th>เสาร์</th>
                                <th>อาทิตย์</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($doctors as $doctor)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if($doctor->photo)
                                        <img src="{{ Storage::url($doctor->photo) }}" class="rounded-circle me-2"
                                             style="width: 40px; height: 40px; object-fit: cover;" alt="">
                                        @else
                                        <div class="rounded-circle bg-light me-2 d-flex align-items-center justify-content-center"
                                             style="width: 40px; height: 40px;">
                                            <i class="bi bi-person text-muted"></i>
                                        </div>
                                        @endif
                                        <div>
                                            <strong>{{ $doctor->full_name }}</strong><br>
                                            <small class="text-primary">{{ $doctor->specialty }}</small>
                                        </div>
                                    </div>
                                </td>
                                @foreach(['monday','tuesday','wednesday','thursday','friday','saturday','sunday'] as $day)
                                @php
                                    $schedule = $doctor->schedules->firstWhere('day_of_week', $day);
                                @endphp
                                <td class="text-center small {{ $schedule ? 'table-success' : '' }}">
                                    @if($schedule)
                                    <strong>{{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }}</strong>
                                    <span class="text-muted">-</span>
                                    <strong>{{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}</strong>
                                    @if($schedule->clinic)
                                    <br><span class="badge bg-light text-dark" style="font-size: 0.7rem;">{{ $schedule->clinic }}</span>
                                    @endif
                                    @if($schedule->note)
                                    <br><small class="text-muted">{{ $schedule->note }}</small>
                                    @endif
                                    @else
                                    <span class="text-muted">-</span>
                                    @endif
                                </td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="alert alert-info mt-4">
            <i class="bi bi-info-circle me-2"></i>
            <strong>หมายเหตุ:</strong> ตารางแพทย์อาจมีการเปลี่ยนแปลงตามความเหมาะสม กรุณาติดต่อสอบถามก่อนเข้ารับบริการ
            โทร: <a href="tel:{{ config('hospital.phone') }}">{{ config('hospital.phone') }}</a>
        </div>
        @else
        <div class="text-center py-5 text-muted">
            <i class="bi bi-clipboard2-pulse" style="font-size: 3rem;"></i>
            <p class="mt-2">กำลังอัปเดตตารางแพทย์...</p>
        </div>
        @endif
    </div>
</section>
@endsection
