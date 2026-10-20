<?php

namespace App\Http\Controllers;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class NhsoApiController extends Controller
{
    private const SEAMLESS_AMOUNT_FIELD_MAP = [
        'ckd' => 'ckd_amt',
        'ktp' => 'ktp_amt',
        'napp' => 'napp_amt',
        'tb' => 'tb_amt',
        'downs' => 'downs_amt',
        'thal_screen' => 'thal_screen_amt',
        'cervical' => 'cervical_amt',
        'psych' => 'psych_amt',
        'thal_disease' => 'thal_disease_amt',
        'rehab' => 'rehab_amt',
        'thai_med' => 'thai_med_amt',
        'morprom' => 'morprom_amt',
        'anc' => 'anc_amt',
        'ncd' => 'ncd_amt',
    ];

    private const SEAMLESS_KEYS = [
        'ckd', 'ktp', 'napp', 'tb', 'downs', 'thal_screen', 'cervical', 'psych',
        'thal_disease', 'rehab', 'thai_med', 'morprom', 'anc', 'ncd',
    ];

    public function health(Request $request): JsonResponse
    {
        $mode = DB::connection()->getDriverName();

        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $mode = 'offline';
        }

        return response()->json([
            'status' => 'ok',
            'mode' => $mode,
            'timestamp' => now()->toISOString(),
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->json()->all();
        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($username === '' || $password === '') {
            return $this->error('กรุณากรอกชื่อผู้ใช้และรหัสผ่าน', 400);
        }

        $user = DB::table('users')->where('username', $username)->first();
        if (! $user) {
            return $this->error('ไม่พบผู้ใช้งาน', 404);
        }

        $storedHash = (string) ($user->password_hash ?? $user->password ?? '');
        if ($storedHash === '' || ! password_verify($password, $storedHash)) {
            return $this->error('รหัสผ่านไม่ถูกต้อง', 401);
        }

        $status = strtolower((string) ($user->status ?? 'active'));
        if ($status === 'disabled') {
            return $this->error('บัญชีนี้ถูกปิดการใช้งาน', 403);
        }
        if ($status === 'pending') {
            return $this->error('บัญชีของคุณรอการอนุมัติจากผู้ดูแลระบบ', 403);
        }

        if ($status === 'rejected') {
            return $this->error('บัญชีของคุณถูกปฏิเสธการใช้งาน', 403);
        }

        $payload = [
            'username' => (string) $user->username,
            'role' => (string) ($user->role ?? 'USER'),
            'fullName' => (string) ($user->full_name ?? $user->name ?? $user->username),
            'phone' => (string) ($user->phone ?? ''),
            'position' => (string) ($user->position ?? ''),
            'exp' => now()->addHours(8)->timestamp,
        ];

        return response()->json([
            'status' => 'success',
            'token' => $this->createToken($payload),
            'user' => [
                'username' => $payload['username'],
                'fullName' => $payload['fullName'],
                'role' => $payload['role'],
                'phone' => $payload['phone'],
                'position' => $payload['position'],
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $authUser = $this->authenticate($request);

        return response()->json([
            'status' => 'success',
            'user' => $authUser,
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->json()->all();
        $username = trim((string) ($data['username'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $fullName = trim((string) ($data['fullName'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $position = trim((string) ($data['position'] ?? ''));
        $role = strtoupper(trim((string) ($data['role'] ?? 'USER')));

        if ($username === '' || $password === '' || $fullName === '' || $phone === '' || $position === '') {
            return $this->error('กรุณากรอกข้อมูลให้ครบถ้วน (ชื่อ, เบอร์โทร, ตำแหน่ง ฯลฯ)', 400);
        }

        if (! in_array($role, ['ADMIN', 'OFFICER', 'EXECUTIVE', 'USER'], true)) {
            return $this->error('ระดับสิทธิ์การใช้งานไม่ถูกต้อง', 400);
        }

        $isApproved = false;
        $authHeader = $request->header('Authorization');
        if ($authHeader) {
            $token = $this->extractBearerToken($authHeader);
            if ($token) {
                $payload = $this->decodeToken($token);
                if ($payload && (($payload['role'] ?? '') === 'ADMIN')) {
                    $isApproved = true;
                }
            }
        }

        if (DB::table('users')->where('username', $username)->exists()) {
            return $this->error('มีชื่อผู้ใช้นี้ในระบบแล้ว', 400);
        }

        $hash = Hash::make($password);
        DB::table('users')->insert([
            'username' => $username,
            'full_name' => $fullName,
            'phone' => $phone,
            'position' => $position,
            'role' => $role,
            'status' => $isApproved ? 'approved' : 'pending',
            'password_hash' => $hash,
            'created_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => $isApproved
                ? 'สร้างบัญชีผู้ใช้ใหม่เรียบร้อยแล้ว'
                : 'ลงทะเบียนสำเร็จ กรุณารอการอนุมัติจากผู้ดูแลระบบ',
            'finalStatus' => $isApproved ? 'approved' : 'pending',
        ], 201);
    }

    public function adminUsers(Request $request): JsonResponse
    {
        $this->authenticate($request, ['ADMIN']);

        $users = DB::table('users')
            ->select('username', 'full_name', 'phone', 'position', 'role', 'status', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($user) {
                return [
                    'username' => $user->username,
                    'full_name' => $user->full_name,
                    'phone' => $user->phone,
                    'position' => $user->position,
                    'role' => $user->role,
                    'status' => $user->status,
                    'created_at' => $user->created_at,
                ];
            })
            ->values();

        return response()->json([
            'status' => 'success',
            'users' => $users,
        ]);
    }

    public function updateUserStatus(Request $request, string $username): JsonResponse
    {
        $this->authenticate($request, ['ADMIN']);
        $data = $request->json()->all();
        $status = strtolower((string) ($data['status'] ?? ''));

        if (! in_array($status, ['approved', 'pending', 'rejected', 'disabled'], true)) {
            return $this->error('สถานะไม่ถูกต้อง', 400);
        }

        $updated = DB::table('users')->where('username', $username)->update([
            'status' => $status,
        ]);

        if (! $updated) {
            return $this->error('ไม่พบบัญชีผู้ใช้งาน', 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'อัปเดตสถานะบัญชีผู้ใช้สำเร็จ',
        ]);
    }

    public function resetUserPassword(Request $request, string $username): JsonResponse
    {
        $this->authenticate($request, ['ADMIN']);

        $tempPassword = 'CHANGE_ME_BEFORE_USE';
        $updated = DB::table('users')->where('username', $username)->update([
            'password_hash' => Hash::make($tempPassword),
        ]);

        if (! $updated) {
            return $this->error('ไม่พบบัญชีผู้ใช้งาน', 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'รีเซ็ตรหัสผ่านเรียบร้อย',
            'temp_password' => $tempPassword,
        ]);
    }

    public function getPreferences(Request $request): JsonResponse
    {
        $authUser = $this->authenticate($request);

        if (! Schema::hasTable('user_preferences')) {
            return response()->json([
                'status' => 'success',
                'preferences' => [],
            ]);
        }

        $rows = DB::table('user_preferences')
            ->where('username', $authUser['username'])
            ->get(['pref_key', 'pref_value']);

        $preferences = [];
        foreach ($rows as $row) {
            $key = trim((string) $row->pref_key);
            if ($key === '') {
                continue;
            }

            $value = $row->pref_value;
            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $value = $decoded;
                }
            }
            $preferences[$key] = $value;
        }

        return response()->json([
            'status' => 'success',
            'preferences' => $preferences,
        ]);
    }

    public function putPreferences(Request $request): JsonResponse
    {
        $authUser = $this->authenticate($request);
        $payload = $request->json()->all();
        $preferences = [];

        if (isset($payload['preferences']) && is_array($payload['preferences'])) {
            $preferences = $payload['preferences'];
        } elseif (isset($payload['key'])) {
            $preferences[(string) $payload['key']] = $payload['value'] ?? null;
        }

        foreach ($preferences as $key => $value) {
            $cleanKey = trim((string) $key);
            if ($cleanKey === '') {
                continue;
            }

            if (! Schema::hasTable('user_preferences')) {
                continue;
            }

            DB::table('user_preferences')->updateOrInsert(
                ['username' => $authUser['username'], 'pref_key' => $cleanKey],
                ['pref_value' => json_encode($value, JSON_UNESCAPED_UNICODE), 'updated_at' => now()]
            );
        }

        return response()->json([
            'status' => 'success',
            'preferences' => $preferences,
        ]);
    }

    public function dashboardData(Request $request): JsonResponse
    {
        $this->authenticate($request);

        $rows = DB::select(
            <<<SQL
            SELECT
                cs.id AS summary_id,
                cs.fiscal_year AS fiscalYear,
                cs.month_num AS month,
                h.hospital_name AS hospital_name,
                cs.hospital_code AS hospital_code,
                cs.sub_fund_id AS fund,
                cs.claim_amount AS claimAmount,
                cs.approved_amount AS approvedAmount,
                cs.pending_amount AS pendingAmount,
                CASE
                    WHEN LOWER(cs.sub_fund_id) IN ('ckd','ktp','napp','tb','downs','thal_screen','cervical','psych','thal_disease','anc','rehab','thai_med','morprom','ncd')
                    THEN 'seamless'
                    ELSE cs.sub_fund_id
                END AS baseFund,
                cs.op_amount AS op_amt, cs.hc_amount AS hc_amt, cs.hc_drug_amount AS hc_drug_amt,
                cs.ae_amount AS ae_amt, cs.ae_drug_amount AS ae_drug_amt, cs.pp_amount AS pp_amt, cs.fs_amount AS fs_amt,
                cs.ckd_amount AS ckd_amt, cs.ktp_amount AS ktp_amt, cs.napp_amount AS napp_amt, cs.tb_amount AS tb_amt,
                cs.downs_amount AS downs_amt, cs.thal_screen_amount AS thal_screen_amt, cs.cervical_amount AS cervical_amt,
                cs.psych_amount AS psych_amt, cs.thal_disease_amount AS thal_disease_amt, cs.rehab_amount AS rehab_amt,
                cs.thai_med_amount AS thai_med_amt, cs.morprom_amount AS morprom_amt, cs.anc_amount AS anc_amt,
                cs.ncd_amount AS ncd_amt,
                il.file_name AS fileName,
                il.imported_at AS importedAt,
                COALESCE(il.menu_key, cs.sub_fund_id) AS menuKey,
                COALESCE(il.file_size_bytes, 0) AS fileSizeBytes
            FROM compensation_summaries cs
            LEFT JOIN hospitals h ON cs.hospital_code = h.hospital_code
            LEFT JOIN import_logs il ON cs.import_log_id = il.id
            ORDER BY cs.id DESC
            SQL
        );

        $formattedData = [];
        foreach ($rows as $row) {
            $errors = DB::select(
                <<<SQL
                SELECT mel.error_code AS code, ec.description_th AS `desc`, mel.occurrence_count AS count
                FROM monthly_error_logs mel
                LEFT JOIN error_codes ec ON mel.error_code = ec.error_code
                WHERE mel.summary_id = ?
                SQL,
                [$row->summary_id]
            );

            $formattedData[] = [
                'id' => (int) $row->summary_id,
                'fiscalYear' => (string) $row->fiscalYear,
                'month' => (string) $row->month,
                'hospital' => trim((string) $row->hospital_code) . ' - ' . ($row->hospital_name ?: ('หน่วยงานรหัส ' . $row->hospital_code)),
                'fund' => (string) $row->fund,
                'baseFund' => (string) ($row->baseFund ?: $row->fund),
                'fileName' => (string) ($row->fileName ?: sprintf('%s_%s_%02d.xlsx', (string) $row->hospital_code, (string) $row->fiscalYear, (int) $row->month)),
                'importedAt' => (string) ($row->importedAt ?: sprintf('%s-%02d-01 00:00:00', (string) $row->fiscalYear, (int) $row->month)),
                'menuKey' => (string) ($row->menuKey ?? $row->fund ?? ''),
                'fileSizeBytes' => (int) ($row->fileSizeBytes ?? 0),
                'claimAmount' => $this->toFloat($row->claimAmount),
                'approvedAmount' => $this->toFloat($row->approvedAmount),
                'pendingAmount' => $this->toFloat($row->pendingAmount),
                'op_amt' => $this->toFloat($row->op_amt),
                'hc_amt' => $this->toFloat($row->hc_amt),
                'hc_drug_amt' => $this->toFloat($row->hc_drug_amt),
                'ae_amt' => $this->toFloat($row->ae_amt),
                'ae_drug_amt' => $this->toFloat($row->ae_drug_amt),
                'pp_amt' => $this->toFloat($row->pp_amt),
                'fs_amt' => $this->toFloat($row->fs_amt),
                'ckd_amt' => $this->toFloat($row->ckd_amt),
                'ktp_amt' => $this->toFloat($row->ktp_amt),
                'napp_amt' => $this->toFloat($row->napp_amt),
                'tb_amt' => $this->toFloat($row->tb_amt),
                'downs_amt' => $this->toFloat($row->downs_amt),
                'thal_screen_amt' => $this->toFloat($row->thal_screen_amt),
                'cervical_amt' => $this->toFloat($row->cervical_amt),
                'psych_amt' => $this->toFloat($row->psych_amt),
                'thal_disease_amt' => $this->toFloat($row->thal_disease_amt),
                'rehab_amt' => $this->toFloat($row->rehab_amt),
                'thai_med_amt' => $this->toFloat($row->thai_med_amt),
                'morprom_amt' => $this->toFloat($row->morprom_amt),
                'anc_amt' => $this->toFloat($row->anc_amt),
                'ncd_amt' => $this->toFloat($row->ncd_amt),
                'subFundsBreakdown' => $this->buildSubFundsBreakdown($row),
                'errors' => array_map(static function ($error) {
                    return [
                        'code' => (string) $error->code,
                        'desc' => (string) ($error->desc ?: 'ไม่ระบุคำอธิบาย'),
                        'count' => (int) $error->count,
                    ];
                }, $errors),
            ];
        }

        return response()->json([
            'status' => 'success',
            'source' => 'database',
            'data' => $formattedData,
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $authUser = $this->authenticate($request, ['OFFICER', 'ADMIN']);
        $data = $request->json()->all();

        $hospitalCode = trim((string) ($data['hospitalCode'] ?? ''));
        $hospitalName = trim((string) ($data['hospitalName'] ?? ''));
        $fiscalYear = trim((string) ($data['fiscalYear'] ?? ''));
        $monthNum = trim((string) ($data['monthNum'] ?? ''));
        $subFundId = trim((string) ($data['subFundId'] ?? ''));
        $claimAmount = $this->toFloat($data['claimAmount'] ?? 0);
        $approvedAmount = $this->toFloat($data['approvedAmount'] ?? 0);
        $pendingAmount = $this->toFloat($data['pendingAmount'] ?? 0);
        $menuKey = trim((string) ($data['menuKey'] ?? $subFundId)) ?: null;
        $fileName = trim((string) ($data['fileName'] ?? '')) ?: 'imported_file.xlsx';
        $fileBase64 = (string) ($data['fileBase64'] ?? '');
        $errors = is_array($data['errors'] ?? null) ? $data['errors'] : [];

        if ($hospitalCode === '' || $fiscalYear === '' || $monthNum === '' || $subFundId === '') {
            return $this->error('ข้อมูลการอัปโหลดไม่สมบูรณ์', 400);
        }

        $amountByField = [
            'op_amt' => $this->toFloat($data['op_amt'] ?? 0),
            'hc_amt' => $this->toFloat($data['hc_amt'] ?? 0),
            'hc_drug_amt' => $this->toFloat($data['hc_drug_amt'] ?? 0),
            'ae_amt' => $this->toFloat($data['ae_amt'] ?? 0),
            'ae_drug_amt' => $this->toFloat($data['ae_drug_amt'] ?? 0),
            'pp_amt' => $this->toFloat($data['pp_amt'] ?? 0),
            'fs_amt' => $this->toFloat($data['fs_amt'] ?? 0),
            'ckd_amt' => $this->toFloat($data['ckd_amt'] ?? 0),
            'ktp_amt' => $this->toFloat($data['ktp_amt'] ?? 0),
            'napp_amt' => $this->toFloat($data['napp_amt'] ?? 0),
            'tb_amt' => $this->toFloat($data['tb_amt'] ?? 0),
            'downs_amt' => $this->toFloat($data['downs_amt'] ?? 0),
            'thal_screen_amt' => $this->toFloat($data['thal_screen_amt'] ?? 0),
            'cervical_amt' => $this->toFloat($data['cervical_amt'] ?? 0),
            'psych_amt' => $this->toFloat($data['psych_amt'] ?? 0),
            'thal_disease_amt' => $this->toFloat($data['thal_disease_amt'] ?? 0),
            'rehab_amt' => $this->toFloat($data['rehab_amt'] ?? 0),
            'thai_med_amt' => $this->toFloat($data['thai_med_amt'] ?? 0),
            'morprom_amt' => $this->toFloat($data['morprom_amt'] ?? 0),
            'anc_amt' => $this->toFloat($data['anc_amt'] ?? 0),
            'ncd_amt' => $this->toFloat($data['ncd_amt'] ?? 0),
        ];

        $seamlessField = $this->getSeamlessAmountField($menuKey, $subFundId);
        if ($seamlessField !== '') {
            $amountByField[$seamlessField] = $this->resolveSeamlessAmount(
                $menuKey,
                $subFundId,
                $claimAmount,
                $approvedAmount,
                $amountByField[$seamlessField]
            );
        }

        $fileBuffer = $this->decodeFileBase64($fileBase64);

        DB::transaction(function () use (
            $authUser,
            $hospitalCode,
            $hospitalName,
            $fiscalYear,
            $monthNum,
            $subFundId,
            $claimAmount,
            $approvedAmount,
            $pendingAmount,
            $menuKey,
            $fileName,
            $fileBuffer,
            $errors,
            $amountByField
        ) {
            DB::table('hospitals')->updateOrInsert(
                ['hospital_code' => $hospitalCode],
                [
                    'hospital_name' => $hospitalName !== '' ? $hospitalName : ('หน่วยบริการรหัส ' . $hospitalCode),
                    'province' => 'แม่แจ่ม',
                    'nhso_zone' => 'เขต 1 เชียงใหม่',
                    'created_at' => now(),
                ]
            );

            $existingSummary = DB::table('compensation_summaries')
                ->where('hospital_code', $hospitalCode)
                ->where('fiscal_year', $fiscalYear)
                ->where('month_num', (int) $monthNum)
                ->where('sub_fund_id', $subFundId)
                ->first();

            if ($existingSummary) {
                DB::table('monthly_error_logs')->where('summary_id', $existingSummary->id)->delete();
                DB::table('compensation_summaries')->where('id', $existingSummary->id)->delete();
            }

            $importLogId = DB::table('import_logs')->insertGetId([
                'file_name' => $fileName,
                'imported_by' => $authUser['username'],
                'imported_at' => now(),
                'record_count' => 1,
                'status' => 'SUCCESS',
                'file_data' => $fileBuffer !== null ? $fileBuffer : null,
                'hospital_code' => $hospitalCode,
                'hospital_name' => $hospitalName !== '' ? $hospitalName : null,
                'fiscal_year' => $fiscalYear,
                'month_num' => $monthNum,
                'sub_fund_id' => $subFundId,
                'menu_key' => $menuKey,
                'claim_amount' => $claimAmount,
                'approved_amount' => $approvedAmount,
                'pending_amount' => $pendingAmount,
                'file_size_bytes' => $fileBuffer !== null ? strlen($fileBuffer) : 0,
            ]);

            $summaryId = DB::table('compensation_summaries')->insertGetId([
                'hospital_code' => $hospitalCode,
                'fiscal_year' => $fiscalYear,
                'month_num' => (int) $monthNum,
                'sub_fund_id' => $subFundId,
                'total_claims_count' => 1,
                'claim_amount' => $claimAmount,
                'approved_amount' => $approvedAmount,
                'pending_amount' => $pendingAmount,
                'op_amount' => $amountByField['op_amt'],
                'hc_amount' => $amountByField['hc_amt'],
                'hc_drug_amount' => $amountByField['hc_drug_amt'],
                'ae_amount' => $amountByField['ae_amt'],
                'ae_drug_amount' => $amountByField['ae_drug_amt'],
                'pp_amount' => $amountByField['pp_amt'],
                'fs_amount' => $amountByField['fs_amt'],
                'ckd_amount' => $amountByField['ckd_amt'],
                'ktp_amount' => $amountByField['ktp_amt'],
                'napp_amount' => $amountByField['napp_amt'],
                'tb_amount' => $amountByField['tb_amt'],
                'downs_amount' => $amountByField['downs_amt'],
                'thal_screen_amount' => $amountByField['thal_screen_amt'],
                'cervical_amount' => $amountByField['cervical_amt'],
                'psych_amount' => $amountByField['psych_amt'],
                'thal_disease_amount' => $amountByField['thal_disease_amt'],
                'rehab_amount' => $amountByField['rehab_amt'],
                'thai_med_amount' => $amountByField['thai_med_amt'],
                'morprom_amount' => $amountByField['morprom_amt'],
                'anc_amount' => $amountByField['anc_amt'],
                'ncd_amount' => $amountByField['ncd_amt'],
                'import_log_id' => $importLogId,
                'created_at' => now(),
            ]);

            foreach ($errors as $error) {
                $code = trim((string) ($error['code'] ?? ''));
                if ($code === '') {
                    continue;
                }

                DB::table('error_codes')->updateOrInsert(
                    ['error_code' => $code],
                    [
                        'fund_type' => $subFundId,
                        'description_th' => (string) ($error['desc'] ?? 'รหัสคลาดเคลื่อนประมวลผล'),
                    ]
                );

                DB::table('monthly_error_logs')->insert([
                    'summary_id' => $summaryId,
                    'error_code' => $code,
                    'occurrence_count' => (int) ($error['count'] ?? 1),
                ]);
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'บันทึกลง MySQL สำเร็จ',
            'savedToMySQL' => true,
        ]);
    }

    public function purge(Request $request): JsonResponse
    {
        $this->authenticate($request, ['OFFICER', 'ADMIN']);

        DB::transaction(function () {
            DB::table('monthly_error_logs')->delete();
            DB::table('compensation_summaries')->delete();
            DB::table('import_logs')->delete();
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Purge completed',
        ]);
    }

    public function purgeSelected(Request $request): JsonResponse
    {
        $this->authenticate($request, ['OFFICER', 'ADMIN']);
        $payload = $request->json()->all();
        $hospitalFilter = trim((string) ($payload['hospitalFilter'] ?? ''));
        $yearFilter = trim((string) ($payload['yearFilter'] ?? 'all'));
        $startMonth = (int) ($payload['startMonth'] ?? 0);
        $endMonth = (int) ($payload['endMonth'] ?? 0);
        $fundId = trim((string) ($payload['fundId'] ?? ''));

        $query = DB::table('compensation_summaries as cs')
            ->leftJoin('hospitals as h', 'h.hospital_code', '=', 'cs.hospital_code');

        if ($fundId !== '' && $fundId !== 'overview') {
            $query->where('cs.sub_fund_id', $fundId);
        }

        if ($hospitalFilter !== '') {
            $query->where(function ($q) use ($hospitalFilter) {
                $q->where('cs.hospital_code', 'like', '%' . $hospitalFilter . '%')
                    ->orWhere('h.hospital_name', 'like', '%' . $hospitalFilter . '%');
            });
        }

        if ($yearFilter !== '' && $yearFilter !== 'all') {
            $query->where('cs.fiscal_year', $yearFilter);
        }

        if ($startMonth > 0 && $endMonth > 0) {
            $query->whereBetween('cs.month_num', [$startMonth, $endMonth]);
        }

        $summaryIds = $query->pluck('cs.id')->all();
        $importLogIds = DB::table('compensation_summaries')
            ->whereIn('id', $summaryIds)
            ->pluck('import_log_id')
            ->filter()
            ->values()
            ->all();

        DB::transaction(function () use ($summaryIds, $importLogIds) {
            if (! empty($summaryIds)) {
                DB::table('monthly_error_logs')->whereIn('summary_id', $summaryIds)->delete();
                DB::table('compensation_summaries')->whereIn('id', $summaryIds)->delete();
            }

            if (! empty($importLogIds)) {
                DB::table('import_logs')->whereIn('id', $importLogIds)->delete();
            }
        });

        return response()->json([
            'status' => 'success',
            'removedSummaries' => count($summaryIds),
            'removedImportLogs' => count($importLogIds),
        ]);
    }

    public function uploadHistory(Request $request): JsonResponse
    {
        $this->authenticate($request, ['OFFICER', 'ADMIN']);
        $limit = min(max((int) $request->query('limit', 200), 1), 500);

        $rows = DB::table('import_logs as il')
            ->leftJoin('compensation_summaries as cs', 'cs.import_log_id', '=', 'il.id')
            ->leftJoin('hospitals as h', 'h.hospital_code', '=', 'il.hospital_code')
            ->select([
                'il.id',
                'il.imported_at',
                'il.file_name',
                'il.imported_by',
                'il.hospital_code',
                'il.hospital_name',
                'il.fiscal_year',
                'il.month_num',
                'il.sub_fund_id',
                'il.menu_key',
                'il.claim_amount',
                'il.approved_amount',
                'il.pending_amount',
                'il.record_count',
                'il.status',
                'il.file_size_bytes',
                'il.file_data',
                'cs.id as summary_id',
                'h.hospital_name as resolved_hospital_name',
            ])
            ->orderByDesc('il.imported_at')
            ->orderByDesc('il.id')
            ->limit($limit)
            ->get();

        $history = $rows->map(function ($row) {
            return [
                'id' => (string) $row->id,
                'importedAt' => $row->imported_at,
                'fileName' => $row->file_name,
                'uploadedBy' => $row->imported_by,
                'hospitalCode' => $row->hospital_code,
                'hospitalName' => $row->hospital_name ?: $row->resolved_hospital_name,
                'fiscalYear' => $row->fiscal_year,
                'monthNum' => $row->month_num,
                'subFundId' => $row->sub_fund_id,
                'menuKey' => $row->menu_key ?: '',
                'claimAmount' => $this->toFloat($row->claim_amount),
                'approvedAmount' => $this->toFloat($row->approved_amount),
                'pendingAmount' => $this->toFloat($row->pending_amount),
                'recordCount' => (int) $row->record_count,
                'status' => $row->status,
                'fileSizeBytes' => (int) $row->file_size_bytes,
                'fileDataStored' => ! empty($row->file_data),
                'source' => 'database',
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'source' => 'database',
            'history' => $history,
        ]);
    }

    public function deleteUploadHistory(Request $request, string $id): JsonResponse
    {
        $this->authenticate($request, ['OFFICER', 'ADMIN']);

        $rawFileName = trim((string) $request->query('fileName', ''));
        $historyRow = DB::table('import_logs')->where('id', $id)->first();

        if (! $historyRow && $rawFileName !== '') {
            $historyRow = DB::table('import_logs')
                ->whereRaw('LOWER(TRIM(file_name)) = LOWER(TRIM(?))', [$rawFileName])
                ->first();
        }

        if (! $historyRow) {
            return $this->error('Upload history not found', 404);
        }

        $summaryIds = DB::table('compensation_summaries')
            ->where('import_log_id', $historyRow->id)
            ->pluck('id')
            ->all();

        DB::transaction(function () use ($historyRow, $summaryIds) {
            if (! empty($summaryIds)) {
                DB::table('monthly_error_logs')->whereIn('summary_id', $summaryIds)->delete();
            }

            DB::table('compensation_summaries')->where('import_log_id', $historyRow->id)->delete();
            DB::table('import_logs')->where('id', $historyRow->id)->delete();
        });

        return response()->json([
            'status' => 'success',
            'removedHistory' => 1,
            'removedSummaries' => count($summaryIds),
        ]);
    }

    public function deleteUploadFile(Request $request, string $fileName): JsonResponse
    {
        $this->authenticate($request, ['OFFICER', 'ADMIN']);
        $cleanFileName = trim((string) urldecode($fileName));

        $logIds = DB::table('import_logs')
            ->whereRaw('LOWER(TRIM(file_name)) = LOWER(TRIM(?))', [$cleanFileName])
            ->pluck('id')
            ->all();

        $summaryIds = DB::table('compensation_summaries')
            ->whereIn('import_log_id', $logIds)
            ->pluck('id')
            ->all();

        DB::transaction(function () use ($logIds, $summaryIds) {
            if (! empty($summaryIds)) {
                DB::table('monthly_error_logs')->whereIn('summary_id', $summaryIds)->delete();
                DB::table('compensation_summaries')->whereIn('id', $summaryIds)->delete();
            }
            if (! empty($logIds)) {
                DB::table('import_logs')->whereIn('id', $logIds)->delete();
            }
        });

        return response()->json([
            'status' => 'success',
            'removedHistory' => count($logIds),
            'removedSummaries' => count($summaryIds),
        ]);
    }

    public function cleanupOldData(Request $request): JsonResponse
    {
        $this->authenticate($request, ['OFFICER', 'ADMIN']);
        $payload = $request->json()->all();
        $retentionDays = max(1, min((int) ($payload['retentionDays'] ?? 180), 3650));
        $scope = strtolower(trim((string) ($payload['scope'] ?? 'all')));
        $cutoff = now()->subDays($retentionDays);

        $prunedUploads = 0;
        $removedLogs = 0;

        if ($scope === 'all' || $scope === 'uploads') {
            $prunedUploads = DB::table('import_logs')
                ->where('imported_at', '<', $cutoff)
                ->whereNotNull('file_data')
                ->update(['file_data' => null]);
        }

        if ($scope === 'all' || $scope === 'logs') {
            $removedLogs = DB::table('dashboard_logs')
                ->where('log_ts', '<', $cutoff)
                ->delete();
        }

        return response()->json([
            'status' => 'success',
            'source' => 'database',
            'retentionDays' => $retentionDays,
            'prunedUploads' => $prunedUploads,
            'removedLogs' => $removedLogs,
        ]);
    }

    public function log(Request $request): JsonResponse
    {
        $payload = $request->json()->all();
        $message = (string) ($payload['message'] ?? $payload['error'] ?? '');
        $file = (string) ($payload['file'] ?? $payload['fileName'] ?? '');
        $level = strtolower((string) ($payload['level'] ?? 'info'));
        $action = (string) ($payload['action'] ?? 'general');
        $fund = (string) ($payload['fund'] ?? '');
        $fiscalYear = (string) ($payload['fiscalYear'] ?? '');
        $month = (string) ($payload['month'] ?? '');
        $tab = (string) ($payload['tab'] ?? '');
        $user = (string) ($payload['user'] ?? 'CLIENT');
        $details = $payload['details'] ?? null;

        $normalizedLog = [
            'log_ts' => now(),
            'level' => $level,
            'action' => $action,
            'message' => $message,
            'file_name' => $file !== '' ? $file : null,
            'fund' => $fund !== '' ? $fund : null,
            'fiscal_year' => $fiscalYear !== '' ? $fiscalYear : null,
            'month_num' => $month !== '' ? $month : null,
            'tab_name' => $tab !== '' ? $tab : null,
            'username' => $user,
            'details_json' => $details !== null ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
        ];

        DB::table('dashboard_logs')->insert($normalizedLog);
        DB::table('import_logs')->insert([
            'file_name' => $file !== '' ? $file : 'client_error.log',
            'imported_by' => 'CLIENT',
            'imported_at' => now(),
            'record_count' => 0,
            'status' => $payload['error'] ?? null ? 'FAILED' : 'SUCCESS',
            'file_data' => null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'log received',
        ]);
    }

    public function getLogs(Request $request): JsonResponse
    {
        $this->authenticate($request);
        $limit = min(max((int) $request->query('limit', 200), 1), 500);

        $rows = DB::table('dashboard_logs')
            ->orderByDesc('log_ts')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $logs = $rows->map(function ($row) {
            return [
                'id' => $row->id,
                'ts' => $row->log_ts,
                'level' => $row->level,
                'action' => $row->action,
                'message' => $row->message,
                'fileName' => $row->file_name,
                'fund' => $row->fund,
                'fiscalYear' => $row->fiscal_year,
                'month' => $row->month_num,
                'tab' => $row->tab_name,
                'user' => $row->username,
                'details' => $row->details_json ? json_decode($row->details_json, true) : null,
                'createdAt' => $row->created_at,
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'source' => 'database',
            'logs' => $logs,
        ]);
    }

    public function clearLogs(Request $request): JsonResponse
    {
        $this->authenticate($request, ['ADMIN']);
        DB::table('dashboard_logs')->delete();

        return response()->json([
            'status' => 'success',
            'source' => 'database',
            'removed' => 0,
        ]);
    }

    public function repairFiscalYears(Request $request): JsonResponse
    {
        $this->authenticate($request, ['ADMIN']);

        $updatedSummaries = 0;
        $updatedLogs = 0;

        $summaries = DB::table('compensation_summaries as cs')
            ->leftJoin('import_logs as il', 'il.id', '=', 'cs.import_log_id')
            ->select('cs.id', 'cs.fiscal_year', 'cs.month_num', 'il.file_name')
            ->get();

        foreach ($summaries as $summary) {
            [$year, $month] = $this->extractYearMonthFromFileName((string) $summary->file_name);
            $changes = [];
            if ($year !== null && (string) $summary->fiscal_year !== $year) {
                $changes['fiscal_year'] = $year;
            }
            if ($month !== null && (string) $summary->month_num !== (string) $month) {
                $changes['month_num'] = $month;
            }
            if ($changes !== []) {
                DB::table('compensation_summaries')->where('id', $summary->id)->update($changes);
                $updatedSummaries++;
            }
        }

        $logs = DB::table('dashboard_logs')->select('id', 'log_ts', 'message', 'file_name', 'fiscal_year', 'month_num')->get();
        foreach ($logs as $log) {
            [$year, $month] = $this->extractYearMonthFromFileName((string) $log->file_name);
            $changes = [];
            if ($year !== null && (string) $log->fiscal_year !== $year) {
                $changes['fiscal_year'] = $year;
            }
            if ($month !== null && (string) $log->month_num !== (string) $month) {
                $changes['month_num'] = $month;
            }
            if ($changes !== []) {
                DB::table('dashboard_logs')->where('id', $log->id)->update($changes);
                $updatedLogs++;
            }
        }

        return response()->json([
            'status' => 'success',
            'result' => [
                'updatedSummaries' => $updatedSummaries,
                'updatedLogs' => $updatedLogs,
            ],
        ]);
    }

    private function authenticate(Request $request, ?array $roles = null): array
    {
        $token = $this->extractBearerToken((string) $request->header('Authorization', ''));
        if (! $token) {
            throw new HttpResponseException($this->error('กรุณาเข้าสู่ระบบก่อนใช้งาน', 401));
        }

        $payload = $this->decodeToken($token);
        if (! $payload) {
            throw new HttpResponseException($this->error('เซสชันหมดอายุหรือไม่ถูกต้อง', 403));
        }

        $user = DB::table('users')->where('username', $payload['username'])->first();
        if (! $user) {
            throw new HttpResponseException($this->error('session_invalid', 401));
        }

        if ($roles !== null && ! in_array((string) $user->role, $roles, true)) {
            throw new HttpResponseException($this->error('คุณไม่มีสิทธิ์เข้าถึงฟังก์ชันนี้', 403));
        }

        return [
            'username' => (string) $user->username,
            'fullName' => (string) ($user->full_name ?? $user->name ?? $user->username),
            'role' => (string) ($user->role ?? 'USER'),
            'phone' => (string) ($user->phone ?? ''),
            'position' => (string) ($user->position ?? ''),
            'status' => (string) ($user->status ?? 'active'),
        ];
    }

    private function createToken(array $payload): string
    {
        $body = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_UNICODE));
        $sig = hash_hmac('sha256', $body, $this->tokenSecret());
        return $body . '.' . $sig;
    }

    private function decodeToken(string $token): ?array
    {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$body, $sig] = $parts;
        $expected = hash_hmac('sha256', $body, $this->tokenSecret());
        if (! hash_equals($expected, $sig)) {
            return null;
        }

        $decoded = json_decode($this->base64UrlDecode($body), true);
        if (! is_array($decoded)) {
            return null;
        }

        $exp = (int) ($decoded['exp'] ?? 0);
        if ($exp > 0 && $exp < now()->timestamp) {
            return null;
        }

        return $decoded;
    }

    private function extractBearerToken(string $header): string
    {
        if (preg_match('/Bearer\s+(.+)$/i', trim($header), $matches)) {
            return trim($matches[1]);
        }

        return '';
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;
        if ($remainder) {
            $value .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($value, '-_', '+/')) ?: '';
    }

    private function tokenSecret(): string
    {
        $key = (string) config('app.key', '');
        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            if (is_string($decoded) && $decoded !== '') {
                return $decoded;
            }
        }

        return $key !== '' ? $key : 'nhso_dashboard_secret';
    }

    private function getSeamlessAmountField(?string $menuKey, ?string $subFundId): string
    {
        $key = $this->normalizeSeamlessMenuKey($menuKey ?: $subFundId);
        return self::SEAMLESS_AMOUNT_FIELD_MAP[$key] ?? '';
    }

    private function normalizeSeamlessMenuKey(?string $value): string
    {
        $clean = strtolower(trim((string) $value));
        $clean = preg_replace('/^seamless:/', '', $clean) ?: '';
        return in_array($clean, self::SEAMLESS_KEYS, true) ? $clean : '';
    }

    private function resolveSeamlessAmount(?string $menuKey, ?string $subFundId, float $claimAmount, float $approvedAmount, float $currentAmount): float
    {
        $field = $this->getSeamlessAmountField($menuKey, $subFundId);
        $baseAmount = $approvedAmount > 0 ? $approvedAmount : $claimAmount;
        if ($field !== '' && $currentAmount <= 0 && $baseAmount > 0) {
            return $baseAmount;
        }

        return $currentAmount;
    }

    private function decodeFileBase64(string $fileBase64): ?string
    {
        $fileBase64 = trim($fileBase64);
        if ($fileBase64 === '') {
            return null;
        }

        $clean = preg_replace('/^data:.*?;base64,/', '', $fileBase64) ?: $fileBase64;
        $decoded = base64_decode($clean, true);
        return $decoded === false ? null : $decoded;
    }

    private function toFloat(mixed $value): float
    {
        return (float) ($value ?? 0);
    }

    private function buildSubFundsBreakdown(object $row): array
    {
        $mapping = [
            'op_amt' => ['code' => 'op', 'name' => 'ผู้ป่วยนอก'],
            'hc_amt' => ['code' => 'hc', 'name' => 'HC'],
            'hc_drug_amt' => ['code' => 'hc_drug', 'name' => 'HC Drug'],
            'ae_amt' => ['code' => 'ae', 'name' => 'AE'],
            'ae_drug_amt' => ['code' => 'ae_drug', 'name' => 'AE Drug'],
            'pp_amt' => ['code' => 'pp', 'name' => 'PPFS'],
            'fs_amt' => ['code' => 'fs', 'name' => 'FS'],
            'ckd_amt' => ['code' => 'ckd', 'name' => 'CKD'],
            'ktp_amt' => ['code' => 'ktp', 'name' => 'KTP'],
            'napp_amt' => ['code' => 'napp', 'name' => 'napp'],
            'tb_amt' => ['code' => 'tb', 'name' => 'TB'],
            'downs_amt' => ['code' => 'downs', 'name' => 'คัดกรองดาวน์'],
            'thal_screen_amt' => ['code' => 'thal_screen', 'name' => 'คัดกรองธาลัสซีเมีย'],
            'cervical_amt' => ['code' => 'cervical', 'name' => 'คัดกรองมะเร็งปากมดลูก'],
            'psych_amt' => ['code' => 'psych', 'name' => 'จิตเวช'],
            'thal_disease_amt' => ['code' => 'thal_disease', 'name' => 'ธาลัสซีเมีย'],
            'rehab_amt' => ['code' => 'rehab', 'name' => 'ฟื้นฟู'],
            'thai_med_amt' => ['code' => 'thai_med', 'name' => 'แพทย์แผนไทย'],
            'morprom_amt' => ['code' => 'morprom', 'name' => 'หมอพร้อม'],
            'anc_amt' => ['code' => 'anc', 'name' => 'ฝากครรภ์'],
            'ncd_amt' => ['code' => 'ncd', 'name' => 'เบาหวาน/ความดัน'],
        ];

        $breakdown = [];
        $recordCount = max(1, (int) ($row->total_claims_count ?? 1));

        foreach ($mapping as $field => $meta) {
            $amount = $this->toFloat($row->{$field} ?? 0);
            if ($amount <= 0) {
                continue;
            }

            $breakdown[] = [
                'subCode' => $meta['code'],
                'subName' => $meta['name'],
                'count' => $recordCount,
                'claimAmt' => $amount,
                'approvedAmt' => $amount,
                'amt' => $amount,
            ];
        }

        return $breakdown;
    }

    private function error(string $message, int $status = 400): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
        ], $status);
    }

    private function buildEmailFromUsername(string $username): string
    {
        $clean = preg_replace('/[^a-zA-Z0-9._-]+/', '', strtolower($username)) ?: 'user';
        return $clean . '@nhso.local';
    }

    private function extractYearMonthFromFileName(string $fileName): array
    {
        if (preg_match('/(25\d{2})(\d{2})(\d{2})/', $fileName, $matches)) {
            return [$matches[1], (int) $matches[2]];
        }

        if (preg_match('/_(25\d{2})[_-]?(\d{2})/', $fileName, $matches)) {
            return [$matches[1], (int) $matches[2]];
        }

        return [null, null];
    }
}








