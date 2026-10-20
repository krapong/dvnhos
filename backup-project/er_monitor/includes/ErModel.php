<?php
require_once __DIR__ . '/Database.php';

class ErModel {

    /**
     * ดึงรายชื่อผู้ป่วย ER จาก HOSxP
     * ใช้ SQL เดิมที่ได้รับ พร้อมปรับปรุงเพิ่มเติม
     */
    public static function getPatients(string $date): array {
        $db = Database::getInstance();

        $sql = "SELECT DISTINCT
                    o.hn                            AS hn,
                    ROW_NUMBER() OVER (ORDER BY eet.export_code) AS queue,
                    o.vstdate                       AS date,
                    TIME(er.enter_er_time)          AS time,
                    CONCAT(p.pname, p.fname)        AS p_name,
                    p.lname                         AS l_name,
                    el.er_emergency_level_name      AS level,
                    eet.export_code                 AS color,
                    ed.`name`                       AS dch_list,
                    lh.lab_order_number             AS lab,
                    v.pdx                           AS dx,
                    xh.xray_order_number            AS xr,
                    er.observe                      AS observe,
                    er.er_dch_type                  AS er_dch_type,
                    TIME(er.enter_er_time)          AS time2,
                    oot.`name`                      AS ovst_status,
                    TIMESTAMPDIFF(MINUTE, er.enter_er_time, NOW()) AS elapsed_min
                FROM er_regist er
                INNER JOIN ovst o          ON o.vn  = er.vn
                INNER JOIN patient p       ON p.hn  = o.hn
                INNER JOIN vn_stat v       ON v.vn  = o.vn
                LEFT JOIN er_emergency_type eet
                    ON eet.er_emergency_type     = er.er_emergency_type
                LEFT JOIN er_emergency_level el
                    ON el.er_emergency_level_id  = er.er_emergency_level_id
                LEFT JOIN er_pt_type et
                    ON et.er_pt_type             = er.er_pt_type
                LEFT JOIN er_dch_type ed
                    ON ed.er_dch_type            = er.er_dch_type
                LEFT JOIN lab_head lh
                    ON lh.vn = o.vn
                LEFT JOIN xray_head xh
                    ON xh.vn = o.vn
                LEFT JOIN ovstost oot
                    ON oot.ovstost = o.ovstost
                WHERE o.vstdate = :date
                  AND (ed.er_dch_type <> '1' OR ed.er_dch_type IS NULL)
                  AND (oot.`name` NOT LIKE '%Admit%' OR oot.`name` IS NULL)
                  AND (ed.`name` NOT IN ('เสียชีวิต') OR ed.`name` IS NULL)
                GROUP BY o.hn
                ORDER BY color ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute([':date' => $date]);
        $rows = $stmt->fetchAll();

        // คำนวณเวลาคงเหลือและ ESI class
        foreach ($rows as &$row) {
            $elapsed = (int)($row['elapsed_min'] ?? 0);
            $remaining = LOS_LIMIT_MIN - $elapsed;
            $row['elapsed_min']   = $elapsed;
            $row['remaining_min'] = $remaining;
            $row['los_status']    = self::getLosStatus($remaining);
            $row['esi_class']     = self::getEsiClass($row['color'] ?? '');
            $row['esi_label']     = self::getEsiLabel($row['color'] ?? '');
        }
        unset($row);

        return $rows;
    }

    /**
     * นับจำนวนผู้ป่วยแยกตาม ESI Level (สำหรับ summary)
     */
    public static function getSummary(string $date): array {
        $patients = self::getPatients($date);
        $summary  = ['total' => count($patients), 'alerts' => 0, 'by_level' => []];
        foreach ($patients as $p) {
            $c = $p['color'] ?? '?';
            $summary['by_level'][$c] = ($summary['by_level'][$c] ?? 0) + 1;
            if (($p['remaining_min'] ?? 120) < 20) {
                $summary['alerts']++;
            }
        }
        return $summary;
    }

    // ──────────────────────────────────────────
    // Helper: ESI color code → CSS class
    // ──────────────────────────────────────────
    private static function getEsiClass(string $color): string {
        return match(strtoupper(trim($color))) {
            '1', 'R' => 'esi1',
            '2', 'E' => 'esi2',
            '3', 'U' => 'esi3',
            '4', 'S' => 'esi4',
            '5', 'N' => 'esi5',
            default  => 'esi3',
        };
    }

    private static function getEsiLabel(string $color): string {
        return match(strtoupper(trim($color))) {
            '1', 'R' => 'Level 1 Resuscitation',
            '2', 'E' => 'Level 2 Emergent',
            '3', 'U' => 'Level 3 Urgent',
            '4', 'S' => 'Level 4 Less Urgent',
            '5', 'N' => 'Level 5 Non-Urgent',
            default  => 'Level 3 Urgent',
        };
    }

    private static function getLosStatus(int $remaining): string {
        if ($remaining <= 0)  return 'over';
        if ($remaining <= 20) return 'critical';
        if ($remaining <= 45) return 'warning';
        return 'ok';
    }
}
