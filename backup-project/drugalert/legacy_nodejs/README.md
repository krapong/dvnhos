# Medical Box Monitor

ระบบติดตามกล่องยาฉุกเฉินแบบ full-stack พร้อม backend, auth, role, MySQL, audit log และ notification stream

## Runtime

- Frontend: static HTML/CSS/JS
- Backend: Node.js `server.js`
- Database: MySQL
- Reverse proxy: Apache 2.4

## Local Run

```bash
npm install
npm start
```

Open:

```text
http://127.0.0.1:4173
```

## Default Accounts

- `admin` / `admin123`
- `pharmacist` / `pharm123`
- `staff` / `staff123`
- `viewer` / `view123`

## Environment

Required for production:

- `PORT`
- `HOST`
- `MBM_AUTH_SECRET`
- `APP_KEY` (สำหรับการ Hash CID แบบ SHA256)
- `MYSQL_HOST`
- `MYSQL_PORT`
- `MYSQL_DATABASE`
- `MYSQL_USER`
- `MYSQL_PASSWORD`

**OAuth2 / OpenID Connect (ตัวเลือกเพิ่มเติม):**
- `THAID_ISSUER`
- `THAID_CLIENT_ID`
- `THAID_CLIENT_SECRET`
- `THAID_REDIRECT_URI` (ค่าเริ่มต้น: `http://localhost:4173/api/auth/thaid/callback`)
- `THAID_SCOPE` (ค่าเริ่มต้น: `openid profile email`)
- `PROVIDER_ID_ISSUER`
- `PROVIDER_ID_CLIENT_ID`
- `PROVIDER_ID_CLIENT_SECRET`
- `PROVIDER_ID_REDIRECT_URI` (ค่าเริ่มต้น: `http://localhost:4173/api/auth/provider_id/callback`)
- `PROVIDER_ID_SCOPE` (ค่าเริ่มต้น: `openid profile email`)

*หมายเหตุ: ระบบจะใช้ callback จริงเมื่อกำหนด issuer/client id/client secret ครบ หากยังไม่ตั้งค่า ระบบจะตอบ error ที่หน้า Login แทนการเข้าสู่ระบบอัตโนมัติ*

You can copy \`.env.example\` to \`/etc/sysconfig/drugalret\` on AlmaLinux and edit it there.

## AlmaLinux 9 + Apache + Node + MySQL

แนวทางแนะนำคือให้ Apache ทำ reverse proxy ไปยัง Node service ที่รันบน `127.0.0.1:4173`

### 1) ติดตั้ง package

```bash
sudo dnf install -y nodejs npm httpd
sudo systemctl enable --now httpd
```

### 2) วางไฟล์โปรเจกต์

```bash
sudo mkdir -p /opt/drugalret
sudo rsync -a --delete ./ /opt/drugalret/
cd /opt/drugalret
npm install
```

### 3) ตั้งค่า Node service

คัดลอกไฟล์ `deploy/systemd/drugalret.service` ไปที่:

```text
/etc/systemd/system/drugalret.service
```

จากนั้นแก้ค่า `ExecStart` และตัวแปร environment ให้ตรงกับเครื่องจริง แล้วสั่ง:

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now drugalret
sudo systemctl status drugalret
```

ไฟล์ environment ที่ service ใช้คือ:

```text
/etc/sysconfig/drugalret
```

### 4) ตั้ง Apache reverse proxy

คัดลอกไฟล์ `deploy/apache/drugalret.conf` ไปที่:

```text
/etc/httpd/conf.d/drugalret.conf
```

แล้วแก้ `ServerName` ให้เป็นโดเมนจริงของคุณ เช่น `drugalert.example.com`

เปิด module ที่จำเป็น:

```bash
sudo dnf install -y mod_proxy mod_proxy_http
sudo systemctl restart httpd
```

ถ้าเปิด SELinux อยู่ ต้องสั่งเพิ่ม:

```bash
sudo setsebool -P httpd_can_network_connect 1
```

### 5) เปิด firewall

```bash
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

## Apache Proxy Notes

- Node service ควร bind ที่ `127.0.0.1:4173`
- Apache จะรับ request จากภายนอก แล้ว forward `/`, `/api/*` และ `/api/events` ไปยัง Node
- SSE notification stream ใช้ `/api/events`
- Apache ต้องมีสิทธิ์ network connect ผ่าน SELinux (`httpd_can_network_connect`)

## Default Flow

- Login มี 2 รูปแบบ: 
  - OAuth2 / OIDC (ThaID, ProviderID) ระบบจะสร้างบัญชีใหม่เป็นสถานะ `pending` ผู้ดูแลระบบต้องเข้าไปกดอนุมัติและตั้งค่า Role ในหน้า จัดการผู้ใช้งาน
  - Local Admin `POST /api/auth/login` (ซ่อนปุ่มไว้ด้านล่างหน้า Login) สำหรับผู้ที่มีรหัสผ่าน
- Session ใช้ cookie `mbm_token`
- Dashboard, Users, Alerts, Notifications, Audit logs ทั้งหมดคุยกับ backend เดิม

## Database

MySQL seed ถูกย้ายเข้า database แล้ว และ account `admin/admin123` ใช้งานได้ทันที

## One-shot install

มีสคริปต์ติดตั้งเบื้องต้นสำหรับ AlmaLinux 9 อยู่ที่:

```text
deploy/scripts/install-almalinux9.sh
```

สคริปต์นี้จะติดตั้ง package, คัดลอกไฟล์ deploy, เปิด `httpd`, ตั้ง SELinux boolean และสร้าง systemd/service config ให้เริ่มต้นได้เร็วขึ้น
