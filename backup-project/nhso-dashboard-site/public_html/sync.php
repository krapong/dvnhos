<?php
// อัปเดตข้อมูลแดชบอร์ดจาก GitHub อัตโนมัติ (ไม่ต้องตั้ง cron)
// ทำงานเมื่อมีคนเปิดหน้าเว็บ: ตรวจ GitHub ไม่เกินชั่วโมงละครั้ง ถ้ามีข้อมูลใหม่จะดาวน์โหลดมาวางทับ data/
// ต้องมีไฟล์ตั้งค่า ../private/nhso_config.php (อยู่นอก public_html จึงไม่มีใครเปิดผ่านเว็บได้)
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$dir   = __DIR__ . '/data';
$meta  = $dir . '/meta.json';
$cfgf  = dirname(__DIR__) . '/private/nhso_config.php';
$FILES = ['smartmoney_transfers', 'eclaim_summary', 'eclaim_errors', 'ppfs_items', 'anc_patterns'];
$out = ['status' => 'local'];

function gh_get($repo, $token, $path) {
    $url = "https://api.github.com/repos/$repo/contents/$path?ref=main";
    $hdr = ["Authorization: Bearer $token", "Accept: application/vnd.github.raw", "User-Agent: nhso-dashboard"];
    if (function_exists('curl_init')) {
        $c = curl_init($url);
        curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $hdr, CURLOPT_TIMEOUT => 25,
                               CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_FOLLOWLOCATION => true]);
        $b = curl_exec($c); $code = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c);
        return ($code == 200) ? $b : false;
    }
    $ctx = stream_context_create(['http' => ['header' => implode("\r\n", $hdr), 'timeout' => 25]]);
    return @file_get_contents($url, false, $ctx);
}

if (is_file($cfgf)) {
    $cfg = include $cfgf;
    $lockf = $dir . '/.sync_lock';
    $lock = @fopen($lockf, 'c');
    $stale = !is_file($lockf) || (time() - filemtime($lockf)) > 3600 || isset($_GET['force']);
    if ($lock && $stale && flock($lock, LOCK_EX | LOCK_NB)) {
        touch($lockf);
        $out['status'] = 'checked';
        $remote = gh_get($cfg['repo'], $cfg['token'], 'data/meta.json');
        $rm = $remote ? json_decode($remote, true) : null;
        $lm = is_file($meta) ? json_decode(file_get_contents($meta), true) : null;
        if (!$rm) {
            $out['status'] = 'github_error';
        } elseif (!$lm || ($rm['updated'] ?? '') !== ($lm['updated'] ?? '')) {
            $got = [];
            foreach ($FILES as $f) {
                $b = gh_get($cfg['repo'], $cfg['token'], "data/$f.json");
                if ($b === false || json_decode($b) === null) { $got = false; break; }
                $got[$f] = $b;
            }
            if ($got) {
                foreach ($got as $f => $b) { file_put_contents("$dir/$f.json.tmp", $b); rename("$dir/$f.json.tmp", "$dir/$f.json"); }
                file_put_contents($meta, $remote);
                $out['status'] = 'updated';
            } else {
                $out['status'] = 'download_error';
            }
        }
        flock($lock, LOCK_UN);
    }
    if ($lock) fclose($lock);
} else {
    $out['status'] = 'no_config';
}
$out['meta'] = is_file($meta) ? json_decode(file_get_contents($meta), true) : null;
echo json_encode($out, JSON_UNESCAPED_UNICODE);
