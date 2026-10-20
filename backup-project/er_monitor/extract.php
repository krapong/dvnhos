<?php
$logPath = 'C:\\Users\\Lenovo\\.gemini\\antigravity-ide\\brain\\dba2d671-7400-4597-a526-713b64274a42\\.system_generated\\logs\\transcript.jsonl';
$lines = file($logPath);
$css = '';
$js = '';

foreach ($lines as $line) {
    $j = json_decode($line, true);
    if ($j['type'] === 'TOOL_RESPONSE' && isset($j['tool_responses'])) {
        foreach ($j['tool_responses'] as $resp) {
            $out = $resp['output'] ?? '';
            if (strpos($out, 'assets/css/monitor.css') !== false && !$css) {
                // Find "Showing lines X to Y" and take the rest until the end or "The above content"
                if (preg_match('/Showing lines \d+ to \d+\n(.*?)(\nThe above content|\z)/s', $out, $m)) {
                    $css = preg_replace('/^\d+: /m', '', $m[1]);
                }
            }
            if (strpos($out, 'assets/js/monitor.js') !== false && !$js) {
                if (preg_match('/Showing lines \d+ to \d+\n(.*?)(\nThe above content|\z)/s', $out, $m)) {
                    $js = preg_replace('/^\d+: /m', '', $m[1]);
                }
            }
        }
    }
}
file_put_contents('recover_css.css', $css);
file_put_contents('recover_js.js', $js);
