<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <title>รายงานคลังยา</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1f2937;
        }
        .header {
            margin-bottom: 18px;
            border-bottom: 2px solid #0f766e;
            padding-bottom: 10px;
        }
        h1, h2 {
            margin: 0 0 8px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            vertical-align: top;
        }
        th {
            background: #e6fffa;
        }
        .muted {
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>รายงานคลังยา</h1>
        <div class="muted">สร้างเมื่อ {{ now()->format('d/m/Y H:i') }}</div>
    </div>

    <h2>สรุปภาพรวม</h2>
    <table>
        <thead>
        <tr>
            <th>รายการ</th>
            <th>ค่า</th>
        </tr>
        </thead>
        <tbody>
        @foreach($report['summary_rows'] as $row)
            <tr>
                <td>{{ $row[0] }}</td>
                <td>{{ $row[1] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h2>ยาใกล้หมดอายุ / หมดอายุ</h2>
    <table>
        <thead>
        <tr>
            <th>ยา</th>
            <th>Lot</th>
            <th>คลัง</th>
            <th>คงเหลือ</th>
            <th>วันหมดอายุ</th>
        </tr>
        </thead>
        <tbody>
        @foreach($report['expiry_rows'] as $row)
            <tr>
                <td>{{ $row[0] }}</td>
                <td>{{ $row[1] }}</td>
                <td>{{ $row[2] }}</td>
                <td>{{ $row[3] }}</td>
                <td>{{ $row[4] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h2>รายการตัด Stock</h2>
    <table>
        <thead>
        <tr>
            <th>เลขที่</th>
            <th>คลัง</th>
            <th>ผู้บันทึก</th>
            <th>จำนวน</th>
            <th>วันที่</th>
        </tr>
        </thead>
        <tbody>
        @foreach($report['adjustment_rows'] as $row)
            <tr>
                <td>{{ $row[0] }}</td>
                <td>{{ $row[1] }}</td>
                <td>{{ $row[2] }}</td>
                <td>{{ $row[3] }}</td>
                <td>{{ $row[4] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h2>รายการแจ้งเตือน</h2>
    <table>
        <thead>
        <tr>
            <th>ประเภท</th>
            <th>หัวข้อ</th>
            <th>สถานะ</th>
            <th>วันที่แจ้ง</th>
        </tr>
        </thead>
        <tbody>
        @foreach($report['alert_rows'] as $row)
            <tr>
                <td>{{ $row[0] }}</td>
                <td>{{ $row[1] }}</td>
                <td>{{ $row[2] }}</td>
                <td>{{ $row[3] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
