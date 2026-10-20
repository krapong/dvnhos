<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ระบบจัดการเว็บไซต์</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Sarabun', sans-serif; }
        body {
            background: linear-gradient(135deg, #00695C 0%, #004D40 50%, #003D33 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 420px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(135deg, #00695C, #004D40);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-header i { font-size: 2.5rem; margin-bottom: 0.5rem; display: block; }
        .login-header h4 { font-size: 1rem; font-weight: 600; margin: 0; line-height: 1.5; }
        .login-body { padding: 2rem; }
        .form-control:focus { border-color: #00695C; box-shadow: 0 0 0 0.2rem rgba(0,105,92,0.25); }
        .btn-login {
            background: #00695C;
            border: none;
            padding: 0.75rem;
            font-size: 1.1rem;
            font-weight: 600;
        }
        .btn-login:hover { background: #004D40; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="bi bi-hospital"></i>
            <h4>ระบบจัดการเว็บไซต์<br>โรงพยาบาลเทพรัตนเวชชานุกูล<br>เฉลิมพระเกียรติ ๖๐ พรรษา</h4>
        </div>
        <div class="login-body">
            @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-medium">อีเมล</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email') }}" placeholder="email@dvhospital.go.th" required autofocus>
                    </div>
                    @error('email')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">รหัสผ่าน</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control"
                               placeholder="รหัสผ่าน" required>
                    </div>
                </div>

                <div class="form-check mb-4">
                    <input type="checkbox" name="remember" class="form-check-input" id="remember">
                    <label class="form-check-label" for="remember">จดจำการเข้าสู่ระบบ</label>
                </div>

                <button type="submit" class="btn btn-login btn-primary w-100">
                    <i class="bi bi-box-arrow-in-right me-2"></i>เข้าสู่ระบบ
                </button>
            </form>

            <div class="text-center mt-3">
                <a href="{{ route('home') }}" class="text-muted text-decoration-none small">
                    <i class="bi bi-arrow-left me-1"></i>กลับหน้าเว็บไซต์
                </a>
            </div>
        </div>
    </div>
</body>
</html>
