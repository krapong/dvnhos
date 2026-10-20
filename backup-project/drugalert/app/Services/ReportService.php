<?php

namespace App\Services;

use App\Repositories\ReportRepository;

class ReportService
{
    protected ReportRepository $repository;

    public function __construct(ReportRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get advanced dashboard metrics for reports
     */
    public function getDashboardMetrics(): array
    {
        $inventory = $this->repository->getInventorySummary();
        $alerts = $this->repository->getAlertsSummary();

        // Calculate ready vs not ready boxes
        $ready = 0;
        $notReady = 0;
        foreach ($inventory['status_counts'] as $status => $count) {
            if (in_array($status, ['พร้อมใช้', 'พร้อมใช้งาน', 'ใช้งานปกติ', 'normal', 'active'])) {
                $ready += $count;
            } else {
                $notReady += $count;
            }
        }

        return [
            'inventory' => [
                'total_boxes' => $inventory['total_boxes'],
                'total_value' => $inventory['total_value'],
                'ready_boxes' => $ready,
                'not_ready_boxes' => $notReady,
            ],
            'alerts' => [
                'total' => $alerts['total_alerts'],
                'active' => $alerts['active_alerts'],
                'resolved' => $alerts['resolved_alerts'],
            ]
        ];
    }

    /**
     * Build the usage array required by the old frontend
     */
    public function getLegacyUsageData(): array
    {
        $inventory = $this->repository->getInventorySummary();
        $alerts = $this->repository->getAlertsSummary();
        
        $usage = [];

        if ($alerts['active_alerts'] > 0) {
            $usage[] = [
                'kind' => 'warn',
                'title' => "ตรวจพบรายการหมดอายุ/ใกล้หมดอายุ {$alerts['active_alerts']} รายการ",
                'text' => "กรุณาตรวจสอบรายละเอียดที่เมนูรายการคงคลังหมดอายุและดำเนินการแก้ไขโดยด่วน"
            ];
        } else {
            $usage[] = [
                'kind' => 'good',
                'title' => 'ไม่มีรายการขาดสต็อกหรือหมดอายุ',
                'text' => 'สถานะคลังโดยรวมยังพร้อมใช้งานในทุกหน่วยงานที่กำหนดไว้'
            ];
        }

        if ($inventory['total_value'] > 0) {
            $usage[] = [
                'kind' => 'good',
                'title' => 'รวมมูลค่าคงคลังยาฉุกเฉิน',
                'text' => 'มูลค่าคงเหลือในกล่องยาฉุกเฉินทั้งหมดคิดเป็น ฿' . number_format($inventory['total_value'], 2)
            ];
        }

        $usage[] = [
            'kind' => 'info',
            'title' => 'รายงานการใช้งานระบบล่าสุด',
            'text' => 'ระบบพร้อมให้บริการและบันทึกข้อมูลแบบ Real-time ตามสิทธิ์ผู้ใช้งาน'
        ];

        // Summary string
        $ready = $inventory['status_counts']['พร้อมใช้'] ?? ($inventory['status_counts']['พร้อมใช้งาน'] ?? 0);
        $refill = $inventory['status_counts']['รอเติมยา'] ?? ($inventory['status_counts']['ส่งเติมยา'] ?? 0);
        $checking = $inventory['status_counts']['กำลังตรวจสอบ'] ?? ($inventory['status_counts']['ตรวจสอบ'] ?? 0);

        $summary = "มีกล่องยาฉุกเฉินทั้งหมด {$inventory['total_boxes']} กล่อง (พร้อมใช้งาน {$ready} กล่อง, รอเติมยา {$refill} กล่อง, กำลังตรวจสอบ {$checking} กล่อง)";

        return [
            'usage' => $usage,
            'summary' => $summary,
        ];
    }
}
