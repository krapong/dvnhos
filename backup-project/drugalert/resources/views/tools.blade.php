<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Drug Alert Tools</title>
    <style>
        :root { --bg:#f4f7fb; --card:#ffffff; --ink:#162033; --muted:#607089; --line:#dbe4f0; --accent:#1357d1; --soft:#eff5ff; }
        * { box-sizing:border-box; }
        body { margin:0; font-family:Arial, Helvetica, sans-serif; background:linear-gradient(180deg,#eef4ff, #f7fafc 42%, #eef4ff); color:var(--ink); }
        .wrap { max-width:1200px; margin:0 auto; padding:32px 20px 56px; }
        h1 { margin:0 0 8px; font-size:32px; }
        p { margin:0 0 16px; color:var(--muted); }
        .grid { display:grid; gap:16px; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); }
        .card { background:var(--card); border:1px solid var(--line); border-radius:18px; padding:18px; box-shadow:0 10px 30px rgba(13,31,66,.06); }
        .card h2 { margin:0 0 14px; font-size:18px; }
        .row { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:10px; }
        input, select, button { border-radius:12px; border:1px solid var(--line); padding:10px 12px; font-size:14px; }
        input, select { background:#fff; flex:1; min-width:180px; }
        button, .btn { background:var(--accent); color:white; border:none; text-decoration:none; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; padding:10px 14px; font-weight:700; }
        .btn.secondary { background:#eef3fb; color:var(--ink); border:1px solid var(--line); }
        .stack { display:flex; flex-direction:column; gap:10px; }
        .status { font-size:13px; color:var(--muted); min-height:18px; }
        table { width:100%; border-collapse:collapse; font-size:13px; }
        th, td { border-bottom:1px solid var(--line); text-align:left; padding:8px; vertical-align:top; }
        th { background:#f8fbff; }
        .split { display:grid; gap:16px; grid-template-columns:1.1fr .9fr; }
        .muted { color:var(--muted); }
        .notice { background:var(--soft); border:1px solid #c8dcff; padding:12px 14px; border-radius:14px; margin-bottom:16px; }
        @media (max-width: 900px) { .split { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Drug Alert Tools</h1>
    <p>ทดสอบ import/export และ audit log บนหน้าเดียว</p>

    @if (session('error'))
        <div class="notice">{{ session('error') }}</div>
    @endif

    @if (session('status'))
        <div class="notice">{{ session('status') }}</div>
    @endif

    @if ($user)
        <div class="notice">
            Logged in as <strong>{{ $user->username ?? $user->display_name ?? $user->name ?? $user->id }}</strong>
            <form method="POST" action="/tools/logout" style="display:inline;margin-left:10px;">
                @csrf
                <button type="submit" class="btn secondary">Logout</button>
            </form>
        </div>
    @else
        <div class="card" style="margin-bottom:16px;">
            <h2>Login</h2>
            <form method="POST" action="/tools/login" class="row">
                @csrf
                <input id="login-username" name="username" placeholder="username" value="{{ old('username') }}">
                <input id="login-password" name="password" type="password" placeholder="password">
                <button id="login-submit" type="submit">Login</button>
            </form>
            <div class="status">หลังล็อกอินแล้วจะเห็นปุ่ม export / import และ latest audit logs</div>
        </div>
    @endif

    @if ($user)
        <div class="grid">
            <div class="card">
                <h2>Export</h2>
                <div class="stack">
                    <a id="export-boxes" class="btn" href="/tools/export/boxes.csv">Export Boxes CSV</a>
                    <a id="export-drugs" class="btn" href="/tools/export/drugs.csv">Export Drugs CSV</a>
                    <a id="export-report" class="btn" href="/tools/report.pdf" target="_blank" rel="noopener">Export Report PDF</a>
                </div>
                <div class="status" style="margin-top:10px;">CSV เปิดกับ Excel ได้ และ PDF เป็นรายงานสรุปจากข้อมูลจริง</div>
            </div>

            <div class="card">
                <h2>Import</h2>
                <form method="POST" action="/tools/import/boxes" enctype="multipart/form-data" class="stack">
                    @csrf
                    <div class="row">
                        <select name="resource" disabled>
                            <option selected>boxes</option>
                        </select>
                        <input id="import-file" name="file" type="file" accept=".csv,text/csv" required>
                    </div>
                    <button id="import-submit" type="submit">Import Boxes CSV</button>
                </form>
                <div class="small" style="margin-top:10px;color:var(--muted);">
                    หน้า demo นี้เน้น import boxes เพื่อให้คุณกดทดสอบได้เร็ว ถ้าต้องการทดสอบ drugs/locations ใช้ endpoint เดียวกันได้เลย
                </div>
            </div>
        </div>

        <div class="split" style="margin-top:16px;">
            <div class="card">
                <h2>Summary</h2>
                <table>
                    <tbody>
                        <tr><th>Boxes</th><td>{{ $summary['boxes'] }}</td></tr>
                        <tr><th>Drugs</th><td>{{ $summary['drugs'] }}</td></tr>
                        <tr><th>Locations</th><td>{{ $summary['locations'] }}</td></tr>
                        <tr><th>Open Alerts</th><td>{{ $summary['openAlerts'] }}</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="card">
                <h2>Latest Audit Logs</h2>
                <table>
                    <thead><tr><th>Action</th><th>Entity</th><th>At</th></tr></thead>
                    <tbody>
                        @forelse ($auditLogs as $log)
                            <tr>
                                <td>{{ $log->action }}</td>
                                <td>{{ $log->entity_type }}</td>
                                <td>{{ $log->created_at }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="muted">No audit logs yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
</body>
</html>
