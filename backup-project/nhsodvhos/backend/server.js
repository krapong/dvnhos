// =====================================================================
// NHSO Executive Dashboard - Node.js Express Backend Server
// =====================================================================
const express = require('express');
const mysql = require('mysql2/promise');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const cors = require('cors');
const dotenv = require('dotenv');
const fs = require('fs');
const path = require('path');
const fsPromises = require('fs/promises');

const backendEnvPath = path.join(__dirname, '.env');
const rootEnvPath = path.join(__dirname, '..', '.env');
const selectedEnvPath = fs.existsSync(backendEnvPath) ? backendEnvPath : rootEnvPath;
dotenv.config({ path: selectedEnvPath });
console.log(`[ENV] Loaded configuration from ${selectedEnvPath}`);

const app = express();
const PORT = process.env.PORT || 80;
const JWT_SECRET = process.env.JWT_SECRET || 'nhso_secret_key_default';

app.use(cors());
app.use(express.json({ limit: '50mb' }));
app.use(express.urlencoded({ limit: '50mb', extended: true }));

// Serve frontend static files
app.use(express.static(path.join(__dirname, '../')));

const LOG_STORE_DIR = path.join(__dirname, 'data');
const LOG_STORE_FILE = path.join(LOG_STORE_DIR, 'dashboard_logs.json');
const DATA_STORE_FILE = path.join(LOG_STORE_DIR, 'dashboard_data.json');
const UPLOAD_HISTORY_FILE = path.join(LOG_STORE_DIR, 'upload_history.json');

// Database connection pool config
const dbConfig = {
    host: process.env.DB_HOST || '127.0.0.1',
    port: parseInt(process.env.DB_PORT || '3306'),
    user: process.env.DB_USERNAME || '',
    password: process.env.DB_PASSWORD || '',
    database: process.env.DB_DATABASE || 'dvhospjl_nhsodashboard',
    charset: 'utf8mb4',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
};

let pool = null;

const SEAMLESS_AMOUNT_FIELD_MAP = {
    ckd: 'ckd_amt',
    ktp: 'ktp_amt',
    napp: 'napp_amt',
    tb: 'tb_amt',
    downs: 'downs_amt',
    thal_screen: 'thal_screen_amt',
    cervical: 'cervical_amt',
    psych: 'psych_amt',
    thal_disease: 'thal_disease_amt',
    rehab: 'rehab_amt',
    thai_med: 'thai_med_amt',
    morprom: 'morprom_amt',
    anc: 'anc_amt',
    ncd: 'ncd_amt'
};

function normalizeSeamlessMenuKey(value) {
    const cleaned = String(value || '').trim().toLowerCase().replace(/^seamless:/, '');
    return SEAMLESS_AMOUNT_FIELD_MAP[cleaned] ? cleaned : '';
}

function getSeamlessAmountField(menuKey, subFundId) {
    return SEAMLESS_AMOUNT_FIELD_MAP[normalizeSeamlessMenuKey(menuKey) || normalizeSeamlessMenuKey(subFundId)] || '';
}

function resolveSeamlessAmount(menuKey, subFundId, claimAmount, approvedAmount, currentAmount) {
    const field = getSeamlessAmountField(menuKey, subFundId);
    const baseAmount = Number(approvedAmount || claimAmount || currentAmount || 0) || 0;
    const current = Number(currentAmount || 0) || 0;
    if (field && current <= 0 && baseAmount > 0) {
        return baseAmount;
    }
    return current;
}

async function ensureLogStoreDir() {
    try {
        await fsPromises.mkdir(LOG_STORE_DIR, { recursive: true });
    } catch (err) {
        console.warn('Unable to create log store directory:', err.message);
    }
}

async function readLocalDashboardLogs() {
    try {
        await ensureLogStoreDir();
        const raw = await fsPromises.readFile(LOG_STORE_FILE, 'utf8');
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
    } catch (err) {
        if (err.code !== 'ENOENT') {
            console.warn('Unable to read local dashboard logs:', err.message);
        }
        return [];
    }
}

async function writeLocalDashboardLogs(logs) {
    try {
        await ensureLogStoreDir();
        const trimmed = Array.isArray(logs) ? logs.slice(0, 1000) : [];
        await fsPromises.writeFile(LOG_STORE_FILE, JSON.stringify(trimmed, null, 2), 'utf8');
        return true;
    } catch (err) {
        console.warn('Unable to write local dashboard logs:', err.message);
        return false;
    }
}

async function readLocalDashboardData() {
    try {
        await ensureLogStoreDir();
        const raw = await fsPromises.readFile(DATA_STORE_FILE, 'utf8');
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
    } catch (err) {
        if (err.code !== 'ENOENT') {
            console.warn('Unable to read local dashboard data:', err.message);
        }
        return [];
    }
}

async function writeLocalDashboardData(data) {
    try {
        await ensureLogStoreDir();
        await fsPromises.writeFile(DATA_STORE_FILE, JSON.stringify(data, null, 2), 'utf8');
        return true;
    } catch (err) {
        console.warn('Unable to write local dashboard data:', err.message);
        return false;
    }
}

async function readLocalUploadHistory() {
    try {
        await ensureLogStoreDir();
        const raw = await fsPromises.readFile(UPLOAD_HISTORY_FILE, 'utf8');
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed : [];
    } catch (err) {
        if (err.code !== 'ENOENT') {
            console.warn('Unable to read local upload history:', err.message);
        }
        return [];
    }
}

async function writeLocalUploadHistory(history) {
    try {
        await ensureLogStoreDir();
        const trimmed = Array.isArray(history) ? history.slice(0, 1000) : [];
        await fsPromises.writeFile(UPLOAD_HISTORY_FILE, JSON.stringify(trimmed, null, 2), 'utf8');
        return true;
    } catch (err) {
        console.warn('Unable to write local upload history:', err.message);
        return false;
    }
}

app.get('/api/health', (req, res) => {
    res.json({
        status: 'ok',
        mode: pool ? 'mysql' : 'sandbox',
        timestamp: new Date().toISOString()
    });
});

// Initialize Database connection and verify schemas
async function initDatabase() {
    try {
        console.log(`Connecting to MySQL database at ${dbConfig.host}:${dbConfig.port}...`);
        pool = mysql.createPool(dbConfig);
        
        // Force connection pool to use utf8mb4 for proper Thai character encoding
        pool.on('connection', (conn) => {
            conn.query('SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci');
        });
        
        // Test connection
        const connection = await pool.getConnection();
        console.log('Database connection successful!');
        try {
            await connection.query(`ALTER DATABASE \`${dbConfig.database}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`);
        } catch (e) {
            console.warn('Unable to enforce database utf8mb4 charset:', e.message);
        }
        const utf8Tables = [
            'users',
            'hospitals',
            'sub_funds',
            'error_codes',
            'import_logs',
            'compensation_summaries',
            'monthly_error_logs',
            'dashboard_logs',
            'user_preferences'
        ];
        for (const tableName of utf8Tables) {
            try {
                await connection.query(`ALTER TABLE \`${tableName}\` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`);
            } catch (e) {
                // Table may not exist yet during a clean install; ignore and continue.
            }
        }
        
        // 1.5 Alter table to add phone and position if they don't exist
        try {
            await connection.query(`ALTER TABLE users ADD COLUMN phone VARCHAR(20)`);
            await connection.query(`ALTER TABLE users ADD COLUMN position VARCHAR(100)`);
        } catch (e) {
            // Columns likely already exist, ignore error
        }

        await connection.query(`
            CREATE TABLE IF NOT EXISTS user_preferences (
                username VARCHAR(50) NOT NULL,
                pref_key VARCHAR(100) NOT NULL,
                pref_value LONGTEXT NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (username, pref_key),
                CONSTRAINT fk_user_preferences_username FOREIGN KEY (username) REFERENCES users(username) ON DELETE CASCADE
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        `);

        // 1.6 Alter import_logs table to add file_data if it doesn't exist
        try {
            await connection.query(`ALTER TABLE import_logs ADD COLUMN file_data LONGBLOB`);
        } catch (e) {
            // Column likely already exists, ignore error
        }
        const importLogColumns = [
            `ALTER TABLE import_logs ADD COLUMN hospital_code VARCHAR(10) DEFAULT NULL`,
            `ALTER TABLE import_logs ADD COLUMN hospital_name VARCHAR(150) DEFAULT NULL`,
            `ALTER TABLE import_logs ADD COLUMN fiscal_year VARCHAR(10) DEFAULT NULL`,
            `ALTER TABLE import_logs ADD COLUMN month_num VARCHAR(10) DEFAULT NULL`,
            `ALTER TABLE import_logs ADD COLUMN sub_fund_id VARCHAR(20) DEFAULT NULL`,
            `ALTER TABLE import_logs ADD COLUMN menu_key VARCHAR(50) DEFAULT NULL`,
            `ALTER TABLE import_logs ADD COLUMN claim_amount DECIMAL(15,2) NOT NULL DEFAULT 0`,
            `ALTER TABLE import_logs ADD COLUMN approved_amount DECIMAL(15,2) NOT NULL DEFAULT 0`,
            `ALTER TABLE import_logs ADD COLUMN pending_amount DECIMAL(15,2) NOT NULL DEFAULT 0`,
            `ALTER TABLE import_logs ADD COLUMN file_size_bytes BIGINT NOT NULL DEFAULT 0`
        ];
        for (const sql of importLogColumns) {
            try {
                await connection.query(sql);
            } catch (e) {
                // Column likely already exists, ignore error
            }
        }

        // 1.6.1 Create dashboard_logs table for persistent system log history
        await connection.query(`
            CREATE TABLE IF NOT EXISTS dashboard_logs (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                log_ts DATETIME NOT NULL,
                level VARCHAR(20) NOT NULL DEFAULT 'info',
                action VARCHAR(100) NOT NULL,
                message TEXT,
                file_name VARCHAR(255) DEFAULT NULL,
                fund VARCHAR(50) DEFAULT NULL,
                fiscal_year VARCHAR(10) DEFAULT NULL,
                month_num VARCHAR(10) DEFAULT NULL,
                tab_name VARCHAR(50) DEFAULT NULL,
                username VARCHAR(100) DEFAULT NULL,
                details_json LONGTEXT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_dashboard_logs_ts (log_ts),
                INDEX idx_dashboard_logs_level (level),
                INDEX idx_dashboard_logs_action (action),
                INDEX idx_dashboard_logs_file (file_name)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        `);

        // Keep backend aligned with UI fields that already exist in uploads and reports
        try {
            await connection.query(`ALTER TABLE compensation_summaries ADD COLUMN anc_amount DECIMAL(15,2) NOT NULL DEFAULT 0`);
        } catch (e) {}
        try {
            await connection.query(`ALTER TABLE compensation_summaries ADD COLUMN thai_med_amount DECIMAL(15,2) NOT NULL DEFAULT 0`);
        } catch (e) {}
        try {
            await connection.query(`ALTER TABLE compensation_summaries ADD COLUMN ncd_amount DECIMAL(15,2) NOT NULL DEFAULT 0`);
        } catch (e) {}

        // 1.7 Alter users table to add status if it doesn't exist
        try {
            await connection.query(`ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'approved'`);
        } catch (e) {
            // Column likely already exists, ignore error
        }

        // Seed sub_funds so seamless subcategories can be stored without FK errors
        await connection.query(`
            INSERT IGNORE INTO sub_funds (fund_id, fund_name, description) VALUES
            ('smartmoney', 'Smart Money', 'โครงการบริหารการเงินเพื่อความคุ้มค่า'),
            ('op', 'ผู้ป่วยนอก (OP)', 'บริการชดเชยผู้ป่วยนอกทั่วไป'),
            ('ip', 'ผู้ป่วยใน (IP)', 'บริการชดเชยผู้ป่วยใน'),
            ('ppfs', 'ส่งเสริมสุขภาพและป้องกันโรค (PPFS)', 'บริการชดเชยงานส่งเสริมป้องกัน'),
            ('ppfs_sub', 'PPFS รพสต.', 'บริการชดเชยย่อยสำหรับ รพ.สต.'),
            ('seamless', 'ชดเชยไร้รอยต่อ (Seamless)', 'ระบบชดเชยการรักษาข้ามเขต/ข้ามเครือข่าย'),
            ('ckd', 'CKD', 'คัดกรอง/ชดเชยโรคไตเรื้อรัง'),
            ('ktp', 'KTP', 'คัดกรอง KTP'),
            ('napp', 'napp', 'คัดกรอง NAPP'),
            ('tb', 'TB', 'คัดกรองวัณโรค'),
            ('downs', 'คัดกรองดาวน์', 'คัดกรองดาวน์ซินโดรม'),
            ('thal_screen', 'คัดกรองธาลัสซีเมีย', 'คัดกรองธาลัสซีเมีย'),
            ('cervical', 'คัดกรองมะเร็งปากมดลูก', 'คัดกรองมะเร็งปากมดลูก'),
            ('psych', 'จิตเวช', 'งานจิตเวช'),
            ('thal_disease', 'ธาลัสซีเมีย', 'บริการธาลัสซีเมีย'),
            ('rehab', 'ฟื้นฟู', 'บริการฟื้นฟูสมรรถภาพ'),
            ('thai_med', 'แพทย์แผนไทย', 'บริการแพทย์แผนไทย'),
            ('morprom', 'หมอพร้อม', 'บริการหมอพร้อม'),
            ('anc', 'ฝากครรภ์', 'บริการฝากครรภ์'),
            ('ncd', 'เบาหวาน/ความดัน', 'บริการคัดกรอง NCD')
        `);
        
        // 2. Insert initial users if table is empty
        const [rows] = await connection.query('SELECT COUNT(*) as count FROM users');
        if (rows[0].count === 0) {
            console.log('Seeding initial system users...');
            
            const defaultUsers = [
                { username: 'admin', rawPassword: 'CHANGE_ME_BEFORE_USE', fullName: 'ผู้ดูแลระบบสูงสุด', phone: '0812345678', position: 'ผู้ดูแลระบบ', role: 'ADMIN' },
                { username: 'officer', rawPassword: 'CHANGE_ME_BEFORE_USE', fullName: 'เจ้าหน้าที่บันทึกข้อมูล สปสช.', phone: '0811111111', position: 'เจ้าหน้าที่', role: 'OFFICER' },
                { username: 'executive', rawPassword: 'CHANGE_ME_BEFORE_USE', fullName: 'ผู้บริหารหน่วยงานบริการ', phone: '0822222222', position: 'ผู้บริหาร', role: 'EXECUTIVE' }
            ];
            
            for (const user of defaultUsers) {
                const salt = await bcrypt.genSalt(10);
                const hash = await bcrypt.hash(user.rawPassword, salt);
                await connection.query(
                    'INSERT INTO users (username, password_hash, full_name, phone, position, role) VALUES (?, ?, ?, ?, ?, ?)',
                    [user.username, hash, user.fullName, user.phone, user.position, user.role]
                );
                console.log(`Created default user: ${user.username} (${user.role})`);
            }
        } else {
            const systemUsers = [
                { username: 'admin', fullName: 'ผู้ดูแลระบบสูงสุด', phone: '0812345678', position: 'ผู้ดูแลระบบ', role: 'ADMIN' },
                { username: 'officer', fullName: 'เจ้าหน้าที่บันทึกข้อมูล สปสช.', phone: '0811111111', position: 'เจ้าหน้าที่', role: 'OFFICER' },
                { username: 'executive', fullName: 'ผู้บริหารหน่วยงานบริการ', phone: '0822222222', position: 'ผู้บริหาร', role: 'EXECUTIVE' }
            ];
            for (const user of systemUsers) {
                await connection.query(
                    `
                        INSERT INTO users (username, password_hash, full_name, phone, position, role, status)
                        VALUES (
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            ?,
                            'approved'
                        )
                        ON DUPLICATE KEY UPDATE
                            full_name = VALUES(full_name),
                            phone = VALUES(phone),
                            position = VALUES(position),
                            role = VALUES(role),
                            status = VALUES(status)
                    `,
                    [user.username, await bcrypt.hash(`${user.username}1234`, 10), user.fullName, user.phone, user.position, user.role]
                );
            }
        }
        
        connection.release();
    } catch (err) {
        console.error('*** DATABASE INITIALIZATION WARNING ***');
        console.error('MySQL Connection failed. Server running in Sandbox Mode.');
        console.error('Error Details:', err.message);
        pool = null; // Mark database as unavailable to trigger mock fallback logic
    }
}

// Authentication Middlewares
function authenticateToken(req, res, next) {
    const authHeader = req.headers['authorization'];
    const token = authHeader && authHeader.split(' ')[1];
    
    if (!token) {
        return res.status(401).json({ status: 'error', message: 'กรุณาเข้าสู่ระบบก่อนใช้งาน' });
    }
    
    jwt.verify(token, JWT_SECRET, (err, user) => {
        if (err) {
            return res.status(403).json({ status: 'error', message: 'เซสชันหมดอายุหรือไม่มีสิทธิ์การใช้งาน' });
        }
        req.user = user;
        next();
    });
}

function requireRole(roles) {
    return (req, res, next) => {
        if (!req.user || !roles.includes(req.user.role)) {
            return res.status(403).json({ status: 'error', message: 'คุณไม่มีสิทธิ์เข้าถึงฟังก์ชันนี้' });
        }
        next();
    };
}

// --- API Endpoints ---

// POST: Login Route
app.post('/api/auth/login', async (req, res) => {
    const { username, password } = req.body;
    
    if (!username || !password) {
        return res.status(400).json({ status: 'error', message: 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน' });
    }

    if (!pool) {
        console.log('Running auth request in Sandbox Mode (No MySQL database)...');
        const mockUsers = {
            admin: { password: 'CHANGE_ME_BEFORE_USE', fullName: 'ผู้ดูแลระบบ (Sandbox)', role: 'ADMIN', phone: '0812345678', position: 'ผู้ดูแลระบบ' },
            officer: { password: 'CHANGE_ME_BEFORE_USE', fullName: 'เจ้าหน้าที่ สปสช. (Sandbox)', role: 'OFFICER', phone: '0811111111', position: 'เจ้าหน้าที่' },
            executive: { password: 'CHANGE_ME_BEFORE_USE', fullName: 'ผู้บริหาร (Sandbox)', role: 'EXECUTIVE', phone: '0822222222', position: 'ผู้บริหาร' }
        };
        
        const foundMock = mockUsers[username];
        if (foundMock && foundMock.password === password) {
            if (foundMock.status === 'pending') {
                return res.status(403).json({ status: 'error', message: 'บัญชีของคุณรอการอนุมัติจากผู้ดูแลระบบ' });
            }
            if (foundMock.status === 'rejected') {
                return res.status(403).json({ status: 'error', message: 'บัญชีของคุณถูกปฏิเสธการใช้งาน' });
            }
            const token = jwt.sign({ username, role: foundMock.role }, JWT_SECRET, { expiresIn: '8h' });
            return res.json({
                status: 'success',
                token,
                user: { username, fullName: foundMock.fullName, role: foundMock.role, phone: foundMock.phone, position: foundMock.position }
            });
        }
        return res.status(400).json({ status: 'error', message: 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง (Sandbox Mode)' });
    }

    try {
        const [rows] = await pool.query('SELECT * FROM users WHERE username = ?', [username]);
        if (rows.length === 0) {
            return res.status(400).json({ status: 'error', message: 'ไม่พบชื่อผู้ใช้นี้ในระบบ' });
        }
        
        const user = rows[0];
        const isMatch = await bcrypt.compare(password, user.password_hash);
        if (!isMatch) {
            return res.status(400).json({ status: 'error', message: 'รหัสผ่านไม่ถูกต้อง' });
        }
        
        if (user.status === 'pending') {
            return res.status(403).json({ status: 'error', message: 'บัญชีของคุณรอการอนุมัติจากผู้ดูแลระบบ' });
        }
        if (user.status === 'rejected') {
            return res.status(403).json({ status: 'error', message: 'บัญชีของคุณถูกปฏิเสธการใช้งาน' });
        }
        
        const token = jwt.sign({ username: user.username, role: user.role }, JWT_SECRET, { expiresIn: '8h' });
        res.json({
            status: 'success',
            token,
            user: { username: user.username, fullName: user.full_name, role: user.role }
        });
    } catch (err) {
        res.status(500).json({ status: 'error', message: err.message });
    }
});

// GET: Validate current authenticated session
app.get('/api/auth/me', authenticateToken, async (req, res) => {
    const username = req.user?.username || '';
    const role = req.user?.role || '';

    if (!pool) {
        return res.json({
            status: 'success',
            user: {
                username,
                role,
                fullName: username === 'admin'
                    ? 'ผู้ดูแลระบบ (Sandbox)'
                    : (username === 'officer'
                        ? 'เจ้าหน้าที่บันทึกข้อมูล (Sandbox)'
                        : (username === 'executive' ? 'ผู้บริหาร (Sandbox)' : username))
            }
        });
    }

    try {
        const [rows] = await pool.query(
            'SELECT username, full_name, role, status FROM users WHERE username = ? LIMIT 1',
            [username]
        );

        if (!rows.length) {
            return res.status(401).json({ status: 'error', message: 'session_invalid' });
        }

        const user = rows[0];
        if (!['approved', 'pending', 'rejected'].includes(String(user.status || '').toLowerCase()) && !user.role) {
            return res.status(401).json({ status: 'error', message: 'session_invalid' });
        }

        return res.json({
            status: 'success',
            user: {
                username: user.username,
                fullName: user.full_name,
                role: user.role
            }
        });
    } catch (err) {
        return res.status(500).json({ status: 'error', message: err.message });
    }
});

// POST: Add New User / Public Register
app.post('/api/auth/register', async (req, res) => {
    const { username, password, fullName, phone, position, role } = req.body;
    
    if (!username || !password || !fullName || !role || !phone || !position) {
        return res.status(400).json({ status: 'error', message: 'กรุณากรอกข้อมูลให้ครบถ้วน (ชื่อ, เบอร์โทร, ตำแหน่ง ฯลฯ)' });
    }
    
    if (!['ADMIN', 'OFFICER', 'EXECUTIVE', 'USER'].includes(role)) {
        return res.status(400).json({ status: 'error', message: 'ระดับสิทธิ์การใช้งานไม่ถูกต้อง' });
    }

    let isApproved = false;
    const authHeader = req.headers['authorization'];
    if (authHeader) {
        const token = authHeader.split(' ')[1];
        try {
            const decoded = jwt.verify(token, JWT_SECRET);
            if (decoded.role === 'ADMIN') {
                isApproved = true;
            }
        } catch (e) {}
    }
    const finalStatus = isApproved ? 'approved' : 'pending';

    if (!pool) {
        return res.status(200).json({ 
            status: 'success', 
            message: isApproved ? `สร้างบัญชีผู้ใช้ ${username} สำเร็จ` : `ลงทะเบียนผู้ใช้ ${username} สำเร็จ รอการอนุมัติ (Sandbox Mode)`,
            finalStatus: finalStatus
        });
    }

    try {
        const [exists] = await pool.query('SELECT id FROM users WHERE username = ?', [username]);
        if (exists.length > 0) {
            return res.status(400).json({ status: 'error', message: 'มีชื่อผู้ใช้นี้ในระบบแล้ว' });
        }
        
        const salt = await bcrypt.genSalt(10);
        const hash = await bcrypt.hash(password, salt);
        
        await pool.query(
            'INSERT INTO users (username, password_hash, full_name, phone, position, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [username, hash, fullName, phone, position, role, finalStatus]
        );
        
        res.status(201).json({ 
            status: 'success', 
            message: isApproved ? 'สร้างบัญชีผู้ใช้งานใหม่เรียบร้อยแล้ว' : 'ลงทะเบียนสำเร็จ กรุณารอการอนุมัติจากผู้ดูแลระบบ' 
        });
    } catch (err) {
        res.status(500).json({ status: 'error', message: err.message });
    }
});

// GET: Retrieve Users list (Admin Only)
app.get('/api/admin/users', authenticateToken, requireRole(['ADMIN']), async (req, res) => {
    if (!pool) {
        return res.json({
            status: 'success',
            users: [
                { username: 'admin', full_name: 'ผู้ดูแลระบบสูงสุด', role: 'ADMIN', status: 'approved', created_at: new Date() },
                { username: 'officer', full_name: 'เจ้าหน้าที่บันทึกข้อมูล สปสช.', role: 'OFFICER', status: 'approved', created_at: new Date() },
                { username: 'executive', full_name: 'ผู้บริหารหน่วยงานบริการ', role: 'EXECUTIVE', status: 'approved', created_at: new Date() }
            ]
        });
    }

    try {
        const [rows] = await pool.query('SELECT username, full_name, role, status, created_at FROM users');
        res.json({ status: 'success', users: rows });
    } catch (err) {
        res.status(500).json({ status: 'error', message: err.message });
    }
});

// PUT: Update User Status (Admin Only)
app.put('/api/admin/users/:username/status', authenticateToken, requireRole(['ADMIN']), async (req, res) => {
    const { username } = req.params;
    const { status } = req.body;
    
    if (!['approved', 'pending', 'rejected'].includes(status)) {
        return res.status(400).json({ status: 'error', message: 'สถานะไม่ถูกต้อง' });
    }

    if (!pool) {
        return res.json({ status: 'success', message: `อัปเดตสถานะบัญชี ${username} เป็น ${status} เรียบร้อย (Sandbox Mode)` });
    }

    try {
        await pool.query('UPDATE users SET status = ? WHERE username = ?', [status, username]);
        res.json({ status: 'success', message: 'อัปเดตสถานะบัญชีสำเร็จ' });
    } catch (err) {
        res.status(500).json({ status: 'error', message: err.message });
    }
});

// GET/PUT: Shared UI preferences by username to keep browser state consistent
app.get('/api/user/preferences', authenticateToken, async (req, res) => {
    if (!pool) {
        return res.json({ status: 'success', preferences: {} });
    }

    try {
        const username = req.user?.username || '';
        const [rows] = await pool.query(
            `SELECT pref_key, pref_value FROM user_preferences WHERE username = ?`,
            [username]
        );
        const preferences = {};
        rows.forEach(row => {
            const key = String(row.pref_key || '').trim();
            if (!key) return;
            let value = row.pref_value;
            if (typeof value === 'string') {
                try {
                    value = JSON.parse(value);
                } catch (e) {}
            }
            preferences[key] = value;
        });
        res.json({ status: 'success', preferences });
    } catch (err) {
        res.status(500).json({ status: 'error', message: err.message });
    }
});

app.put('/api/user/preferences', authenticateToken, async (req, res) => {
    if (!pool) {
        return res.json({ status: 'success', preferences: req.body || {} });
    }

    const username = req.user?.username || '';
    const payload = req.body && typeof req.body === 'object' ? req.body : {};
    const prefs = payload.preferences && typeof payload.preferences === 'object'
        ? payload.preferences
        : (payload.key ? { [String(payload.key)]: payload.value } : {});

    try {
        const entries = Object.entries(prefs).filter(([key]) => String(key || '').trim());
        for (const [prefKey, prefValue] of entries) {
            await pool.query(
                `INSERT INTO user_preferences (username, pref_key, pref_value)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE pref_value = VALUES(pref_value), updated_at = CURRENT_TIMESTAMP`,
                [username, String(prefKey), JSON.stringify(prefValue)]
            );
        }

        res.json({ status: 'success', preferences: prefs });
    } catch (err) {
        res.status(500).json({ status: 'error', message: err.message });
    }
});

// GET: Retrieve Dashboard summary data (All Authenticated users)
app.get('/api/dashboard/data', authenticateToken, async (req, res) => {
    if (!pool) {
        const sandboxData = await readLocalDashboardData();
        return res.json({ status: 'success', source: 'sandbox', data: sandboxData });
    }
    
    try {
        const [rows] = await pool.query(`
            SELECT cs.id as summary_id, cs.fiscal_year as fiscalYear, cs.month_num as month, h.hospital_name as hospital_name, 
                   cs.hospital_code as hospital_code, cs.sub_fund_id as fund, cs.claim_amount as claimAmount, 
                   cs.approved_amount as approvedAmount, cs.pending_amount as pendingAmount,
                   CASE
                       WHEN LOWER(cs.sub_fund_id) IN ('ckd','ktp','napp','tb','downs','thal_screen','cervical','psych','thal_disease','anc','rehab','thai_med','morprom','ncd')
                       THEN 'seamless'
                       ELSE cs.sub_fund_id
                   END as baseFund,
                   cs.op_amount as op_amt, cs.hc_amount as hc_amt, cs.hc_drug_amount as hc_drug_amt,
                   cs.ae_amount as ae_amt, cs.ae_drug_amount as ae_drug_amt, cs.pp_amount as pp_amt, cs.fs_amount as fs_amt,
                   cs.ckd_amount as ckd_amt, cs.ktp_amount as ktp_amt, cs.napp_amount as napp_amt, cs.tb_amount as tb_amt,
                   cs.downs_amount as downs_amt, cs.thal_screen_amount as thal_screen_amt, cs.cervical_amount as cervical_amt,
                   cs.psych_amount as psych_amt, cs.thal_disease_amount as thal_disease_amt, cs.rehab_amount as rehab_amt,
                   cs.thai_med_amount as thai_med_amt, cs.morprom_amount as morprom_amt, cs.anc_amount as anc_amt,
                   cs.ncd_amount as ncd_amt,
                   COALESCE(il.file_name, CONCAT(cs.hospital_code, '_', cs.fiscal_year, '_', cs.month_num, '.xlsx')) as fileName,
                   COALESCE(il.imported_at, CONCAT(cs.fiscal_year, '-', LPAD(cs.month_num, 2, '0'), '-01 00:00:00')) as importedAt,
                   COALESCE(il.menu_key, cs.sub_fund_id) as menuKey,
                   COALESCE(il.file_size_bytes, 0) as fileSizeBytes
            FROM compensation_summaries cs
            LEFT JOIN hospitals h ON cs.hospital_code = h.hospital_code
            LEFT JOIN import_logs il ON cs.import_log_id = il.id
        `);
        
        // Loop through summaries to query error logs for each
        const formattedData = [];
        for (const r of rows) {
            const [errorRows] = await pool.query(`
                SELECT mel.error_code as code, ec.description_th as \`desc\`, mel.occurrence_count as count
                FROM monthly_error_logs mel
                LEFT JOIN error_codes ec ON mel.error_code = ec.error_code
                WHERE mel.summary_id = ?
            `, [r.summary_id]);

            formattedData.push({
                id: r.summary_id,
                fiscalYear: r.fiscalYear,
                month: r.month,
                hospital: `${r.hospital_code} - ${r.hospital_name || `หน่วยงานรหัส ${r.hospital_code}`}`,
                fund: r.fund,
                baseFund: r.baseFund || r.fund,
                fileName: r.fileName || '',
                importedAt: r.importedAt || '',
                menuKey: r.menuKey || r.fund || '',
                fileSizeBytes: parseInt(r.fileSizeBytes || 0, 10) || 0,
                claimAmount: parseFloat(r.claimAmount),
                approvedAmount: parseFloat(r.approvedAmount),
                pendingAmount: parseFloat(r.pendingAmount),
                op_amt: parseFloat(r.op_amt || 0),
                hc_amt: parseFloat(r.hc_amt || 0),
                hc_drug_amt: parseFloat(r.hc_drug_amt || 0),
                ae_amt: parseFloat(r.ae_amt || 0),
                ae_drug_amt: parseFloat(r.ae_drug_amt || 0),
                pp_amt: parseFloat(r.pp_amt || 0),
                fs_amt: parseFloat(r.fs_amt || 0),
                ckd_amt: (parseFloat(r.ckd_amt || 0) > 0 ? parseFloat(r.ckd_amt || 0) : (normalizeSeamlessMenuKey(r.menuKey || r.fund) === 'ckd' ? Math.max(parseFloat(r.approvedAmount || 0), parseFloat(r.claimAmount || 0)) : 0)),
                ktp_amt: parseFloat(r.ktp_amt || 0),
                napp_amt: parseFloat(r.napp_amt || 0),
                tb_amt: parseFloat(r.tb_amt || 0),
                downs_amt: parseFloat(r.downs_amt || 0),
                thal_screen_amt: parseFloat(r.thal_screen_amt || 0),
                cervical_amt: parseFloat(r.cervical_amt || 0),
                psych_amt: parseFloat(r.psych_amt || 0),
                thal_disease_amt: parseFloat(r.thal_disease_amt || 0),
                rehab_amt: parseFloat(r.rehab_amt || 0),
                thai_med_amt: parseFloat(r.thai_med_amt || 0),
                morprom_amt: parseFloat(r.morprom_amt || 0),
                anc_amt: parseFloat(r.anc_amt || 0),
                ncd_amt: parseFloat(r.ncd_amt || 0),
                errors: errorRows.map(e => ({
                    code: e.code,
                    desc: e.desc || 'ไม่ระบุคำอธิบาย',
                    count: parseInt(e.count)
                }))
            });
        }
        
        res.json({ status: 'success', source: 'database', data: formattedData });
    } catch (err) {
        res.status(500).json({ status: 'error', message: err.message });
    }
});

// POST: Upload and save aggregated Excel logs (Officer and Admin only)
app.post('/api/dashboard/upload', authenticateToken, requireRole(['OFFICER', 'ADMIN']), async (req, res) => {
    const { 
        hospitalCode, hospitalName, fiscalYear, monthNum, subFundId, claimAmount, approvedAmount, pendingAmount, errors,
        menuKey,
        op_amt, hc_amt, hc_drug_amt, ae_amt, ae_drug_amt, pp_amt, fs_amt, fileName, fileBase64,
        ckd_amt, ktp_amt, napp_amt, tb_amt, downs_amt, thal_screen_amt, cervical_amt, psych_amt, thal_disease_amt,
        rehab_amt, thai_med_amt, morprom_amt, anc_amt, ncd_amt
    } = req.body;
    const uploadedBy = req.user?.username || 'SYSTEM';
    const normalizedMenuKey = String(menuKey || subFundId || '').trim() || null;
    const seamlessField = getSeamlessAmountField(normalizedMenuKey, subFundId);
    const amountByField = {
        op_amt: Number(op_amt || 0),
        hc_amt: Number(hc_amt || 0),
        hc_drug_amt: Number(hc_drug_amt || 0),
        ae_amt: Number(ae_amt || 0),
        ae_drug_amt: Number(ae_drug_amt || 0),
        pp_amt: Number(pp_amt || 0),
        fs_amt: Number(fs_amt || 0),
        ckd_amt: Number(ckd_amt || 0),
        ktp_amt: Number(ktp_amt || 0),
        napp_amt: Number(napp_amt || 0),
        tb_amt: Number(tb_amt || 0),
        downs_amt: Number(downs_amt || 0),
        thal_screen_amt: Number(thal_screen_amt || 0),
        cervical_amt: Number(cervical_amt || 0),
        psych_amt: Number(psych_amt || 0),
        thal_disease_amt: Number(thal_disease_amt || 0),
        rehab_amt: Number(rehab_amt || 0),
        thai_med_amt: Number(thai_med_amt || 0),
        morprom_amt: Number(morprom_amt || 0),
        anc_amt: Number(anc_amt || 0),
        ncd_amt: Number(ncd_amt || 0)
    };
    if (seamlessField) {
        amountByField[seamlessField] = resolveSeamlessAmount(
            normalizedMenuKey,
            subFundId,
            claimAmount,
            approvedAmount,
            amountByField[seamlessField]
        );
    }
    
    if (!hospitalCode || !fiscalYear || !monthNum || !subFundId) {
        return res.status(400).json({ status: 'error', message: 'ข้อมูลระบุตัวตนไฟล์อัปโหลดไม่สมบูรณ์' });
    }

    if (!pool) {
        const currentData = await readLocalDashboardData();
        const currentHistory = await readLocalUploadHistory();
        const localFileBuffer = fileBase64 ? Buffer.from(fileBase64.replace(/^data:.*?;base64,/, ''), 'base64') : null;
        
        // Remove old data for the same hospital/year/month/fund
        const filteredData = currentData.filter(d => 
            !(String(d.hospital).startsWith(hospitalCode) && 
              String(d.fiscalYear) === String(fiscalYear) && 
              String(d.month) === String(monthNum) && 
              String(d.fund) === String(subFundId))
        );

        // Add new summary object
        const newSummary = {
            id: Date.now(),
            fiscalYear,
            month: monthNum,
            hospital: hospitalName ? `${hospitalCode} - ${hospitalName}` : `หน่วยงานรหัส ${hospitalCode}`,
            fund: subFundId,
            fileName: fileName || 'imported_file.xlsx',
            importedAt: new Date().toISOString(),
            menuKey: normalizedMenuKey,
            fileSizeBytes: localFileBuffer ? localFileBuffer.length : 0,
            claimAmount: parseFloat(claimAmount) || 0,
            approvedAmount: parseFloat(approvedAmount) || 0,
            pendingAmount: parseFloat(pendingAmount) || 0,
            op_amt: amountByField.op_amt,
            hc_amt: amountByField.hc_amt,
            hc_drug_amt: amountByField.hc_drug_amt,
            ae_amt: amountByField.ae_amt,
            ae_drug_amt: amountByField.ae_drug_amt,
            pp_amt: amountByField.pp_amt,
            fs_amt: amountByField.fs_amt,
            ckd_amt: amountByField.ckd_amt,
            ktp_amt: amountByField.ktp_amt,
            napp_amt: amountByField.napp_amt,
            tb_amt: amountByField.tb_amt,
            downs_amt: amountByField.downs_amt,
            thal_screen_amt: amountByField.thal_screen_amt,
            cervical_amt: amountByField.cervical_amt,
            psych_amt: amountByField.psych_amt,
            thal_disease_amt: amountByField.thal_disease_amt,
            rehab_amt: amountByField.rehab_amt,
            thai_med_amt: amountByField.thai_med_amt,
            morprom_amt: amountByField.morprom_amt,
            anc_amt: amountByField.anc_amt,
            ncd_amt: amountByField.ncd_amt,
            errors: (errors && Array.isArray(errors)) ? errors.map(e => ({
                code: e.code,
                desc: e.desc || 'ไม่ระบุคำอธิบาย',
                count: parseInt(e.count) || 1
            })) : []
        };
        
        filteredData.push(newSummary);
        await writeLocalDashboardData(filteredData);
        currentHistory.unshift({
            id: Date.now(),
            importedAt: new Date().toISOString(),
            fileName: fileName || 'imported_file.xlsx',
            uploadedBy,
            hospitalCode,
            hospitalName: hospitalName || '',
            fiscalYear: String(fiscalYear),
            monthNum: String(monthNum),
            subFundId: String(subFundId),
            menuKey: normalizedMenuKey,
            claimAmount: parseFloat(claimAmount) || 0,
            approvedAmount: parseFloat(approvedAmount) || 0,
            pendingAmount: parseFloat(pendingAmount) || 0,
            recordCount: 1,
            status: 'SUCCESS',
            fileSizeBytes: localFileBuffer ? localFileBuffer.length : 0,
            fileDataStored: Boolean(localFileBuffer)
        });
        await writeLocalUploadHistory(currentHistory);
        
        return res.json({ status: 'success', message: 'บันทึกเรียบร้อย (Sandbox Mode)' });
    }

    const connection = await pool.getConnection();
    try {
        await connection.beginTransaction();

        // 1. Verify or create Hospital entry
        await connection.query(`
            INSERT INTO hospitals (hospital_code, hospital_name, province, nhso_zone)
            VALUES (?, ?, 'แม่แจ่ม', 'เขต 1 เชียงใหม่')
            ON DUPLICATE KEY UPDATE hospital_name = VALUES(hospital_name)
        `, [hospitalCode, hospitalName || `หน่วยบริการรหัส ${hospitalCode}`]);

        // Process Base64 File Data if provided
        let fileBuffer = null;
        if (fileBase64) {
            // Typically base64 strings from FileReader come with data URI scheme (e.g. "data:application/vnd.ms-excel;base64,.....")
            // We need to strip out the prefix before decoding
            const base64Data = fileBase64.replace(/^data:.*?;base64,/, "");
            fileBuffer = Buffer.from(base64Data, 'base64');
        }
        const fileSizeBytes = fileBuffer ? fileBuffer.length : 0;

        // Remove any existing summary for the same hospital / period / fund first
        // so a re-upload replaces old data instead of accumulating duplicates.
        await connection.query(
            `
                DELETE FROM compensation_summaries
                WHERE hospital_code = ?
                  AND fiscal_year = ?
                  AND month_num = ?
                  AND sub_fund_id = ?
            `,
            [hospitalCode, fiscalYear, monthNum, subFundId]
        );

        // 2. Create import log entry for file tracking
        const [logResult] = await connection.query(`
            INSERT INTO import_logs (
                file_name, imported_by, record_count, status, file_data,
                hospital_code, hospital_name, fiscal_year, month_num, sub_fund_id, menu_key,
                claim_amount, approved_amount, pending_amount, file_size_bytes
            )
            VALUES (?, ?, 1, 'SUCCESS', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        `, [
            fileName || 'imported_file.xlsx',
            uploadedBy,
            fileBuffer,
            hospitalCode,
            hospitalName || null,
            String(fiscalYear),
            String(monthNum),
            String(subFundId),
            normalizedMenuKey,
            parseFloat(claimAmount) || 0,
            parseFloat(approvedAmount) || 0,
            parseFloat(pendingAmount) || 0,
            fileSizeBytes
        ]);
        const importLogId = logResult.insertId;

        // 3. Insert or update summary totals (including the 7 sub-categories and import_log_id link)
        const [sumResult] = await connection.query(`
            INSERT INTO compensation_summaries 
                (hospital_code, fiscal_year, month_num, sub_fund_id, total_claims_count, claim_amount, approved_amount, pending_amount,
                 op_amount, hc_amount, hc_drug_amount, ae_amount, ae_drug_amount, pp_amount, fs_amount, 
                 ckd_amount, ktp_amount, napp_amount, tb_amount, downs_amount, thal_screen_amount, cervical_amount, psych_amount, thal_disease_amount, rehab_amount, thai_med_amount, morprom_amount, anc_amount, ncd_amount,
                 import_log_id)
            VALUES (?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        `, [
            hospitalCode, fiscalYear, monthNum, subFundId, claimAmount, approvedAmount, pendingAmount,
            amountByField.op_amt, amountByField.hc_amt, amountByField.hc_drug_amt, amountByField.ae_amt, amountByField.ae_drug_amt, amountByField.pp_amt, amountByField.fs_amt,
            amountByField.ckd_amt, amountByField.ktp_amt, amountByField.napp_amt, amountByField.tb_amt, amountByField.downs_amt, amountByField.thal_screen_amt, amountByField.cervical_amt, amountByField.psych_amt, amountByField.thal_disease_amt, amountByField.rehab_amt, amountByField.thai_med_amt, amountByField.morprom_amt, amountByField.anc_amt, amountByField.ncd_amt,
            importLogId
        ]);
        const summaryId = sumResult.insertId;

        // 3. Clear and write new monthly error counts
        await connection.query('DELETE FROM monthly_error_logs WHERE summary_id = ?', [summaryId]);

        if (errors && Array.isArray(errors)) {
            for (const err of errors) {
                // Ensure error code exists in catalog first to prevent foreign key issues
                await connection.query(`
                    INSERT IGNORE INTO error_codes (error_code, fund_type, description_th)
                    VALUES (?, ?, 'รหัสคลาดเคลื่อนประมวลผล')
                `, [err.code, subFundId]);

                await connection.query(`
                    INSERT INTO monthly_error_logs (summary_id, error_code, occurrence_count)
                    VALUES (?, ?, ?)
                `, [summaryId, err.code, err.count]);
            }
        }

        await connection.commit();
        res.json({
            status: 'success',
            message: 'บันทึกลง MySQL สำเร็จ',
            savedToMySQL: true,
            importLogId,
            summaryId,
            hospitalCode,
            fiscalYear: String(fiscalYear),
            monthNum: String(monthNum),
            subFundId,
            menuKey: normalizedMenuKey,
            fileName: fileName || 'imported_file.xlsx',
            recordCount: 1
        });
    } catch (err) {
        await connection.rollback();
        res.status(500).json({ status: 'error', message: err.message });
    } finally {
        connection.release();
    }
});

// POST: Purge dashboard import data before a clean re-import
app.post('/api/dashboard/purge', authenticateToken, requireRole(['OFFICER', 'ADMIN']), async (req, res) => {
    if (!pool) {
        await writeLocalDashboardData([]);
        return res.json({
            status: 'success',
            message: 'Sandbox Mode: data purged'
        });
    }

    const connection = await pool.getConnection();
    try {
        await connection.beginTransaction();
        await connection.query('DELETE FROM monthly_error_logs');
        await connection.query('DELETE FROM compensation_summaries');
        await connection.query('DELETE FROM import_logs');
        await connection.commit();

        res.json({
            status: 'success',
            message: 'Purge completed',
        });
    } catch (err) {
        await connection.rollback();
        res.status(500).json({ status: 'error', message: err.message });
    } finally {
        connection.release();
    }
});

// POST: Purge only the selected hospital / year / month range / fund scope
app.post('/api/dashboard/purge-selected', authenticateToken, requireRole(['OFFICER', 'ADMIN']), async (req, res) => {
    const payload = req.body || {};
    const hospitalFilter = String(payload.hospitalFilter || '').trim();
    const yearFilter = String(payload.yearFilter || 'all').trim();
    const startMonth = Number.parseInt(payload.startMonth, 10);
    const endMonth = Number.parseInt(payload.endMonth, 10);
    const fundId = String(payload.fundId || '').trim();

    if (!pool) {
        return res.json({
            status: 'success',
            removedSummaries: 0,
            removedImportLogs: 0,
            sandboxOnly: true
        });
    }

    const connection = await pool.getConnection();
    try {
        await connection.beginTransaction();

        const where = [];
        const params = [];

        if (fundId && fundId !== 'overview') {
            where.push('cs.sub_fund_id = ?');
            params.push(fundId);
        }

        if (hospitalFilter) {
            where.push('(cs.hospital_code LIKE ? OR h.hospital_name LIKE ?)');
            params.push(`%${hospitalFilter}%`, `%${hospitalFilter}%`);
        }

        if (yearFilter && yearFilter !== 'all') {
            where.push('cs.fiscal_year = ?');
            params.push(yearFilter);
        }

        if (Number.isFinite(startMonth) && Number.isFinite(endMonth)) {
            where.push('cs.month_num BETWEEN ? AND ?');
            params.push(startMonth, endMonth);
        }

        const whereSql = where.length > 0 ? `WHERE ${where.join(' AND ')}` : '';

        const [matchedRows] = await connection.query(
            `
                SELECT cs.id, cs.import_log_id
                FROM compensation_summaries cs
                LEFT JOIN hospitals h ON h.hospital_code = cs.hospital_code
                ${whereSql}
            `,
            params
        );

        const summaryIds = matchedRows.map((row) => row.id);

        if (summaryIds.length > 0) {
            await connection.query('DELETE FROM compensation_summaries WHERE id IN (?)', [summaryIds]);
        }

        const [orphanLogDelete] = await connection.query(`
            DELETE il
            FROM import_logs il
            LEFT JOIN compensation_summaries cs ON cs.import_log_id = il.id
            WHERE cs.id IS NULL
        `);

        await connection.commit();

        res.json({
            status: 'success',
            removedSummaries: summaryIds.length,
            removedImportLogs: orphanLogDelete.affectedRows || 0
        });
    } catch (err) {
        await connection.rollback();
        res.status(500).json({ status: 'error', message: err.message });
    } finally {
        connection.release();
    }
});

// GET: Retrieve upload history records
app.get('/api/dashboard/upload-history', authenticateToken, requireRole(['OFFICER', 'ADMIN']), async (req, res) => {
    const limit = Math.min(parseInt(req.query.limit || '200', 10) || 200, 500);

    try {
        if (!pool) {
            const history = (await readLocalUploadHistory()).slice(0, limit).map(row => ({
                id: row.id,
                importedAt: row.importedAt || row.imported_at || new Date().toISOString(),
                fileName: row.fileName || row.file_name || '',
                uploadedBy: row.uploadedBy || row.imported_by || '',
                hospitalCode: row.hospitalCode || row.hospital_code || '',
                hospitalName: row.hospitalName || row.hospital_name || '',
                fiscalYear: row.fiscalYear || row.fiscal_year || '',
                monthNum: row.monthNum || row.month_num || '',
                subFundId: row.subFundId || row.sub_fund_id || '',
                menuKey: row.menuKey || row.menu_key || '',
                claimAmount: parseFloat(row.claimAmount ?? row.claim_amount ?? 0),
                approvedAmount: parseFloat(row.approvedAmount ?? row.approved_amount ?? 0),
                pendingAmount: parseFloat(row.pendingAmount ?? row.pending_amount ?? 0),
                recordCount: parseInt(row.recordCount ?? row.record_count ?? 0, 10) || 0,
                status: row.status || 'SUCCESS',
                fileSizeBytes: parseInt(row.fileSizeBytes ?? row.file_size_bytes ?? 0, 10) || 0,
                fileDataStored: Boolean(row.fileDataStored ?? row.file_data_stored ?? false),
                source: 'sandbox'
            }));
            return res.json({ status: 'success', source: 'sandbox', history });
        }

        const [rows] = await pool.query(
            `
                SELECT
                    id, imported_at, file_name, imported_by, hospital_code, hospital_name,
                    fiscal_year, month_num, sub_fund_id, menu_key, claim_amount, approved_amount,
                    pending_amount, record_count, status, file_size_bytes, file_data
                FROM import_logs
                ORDER BY imported_at DESC, id DESC
                LIMIT ?
            `,
            [limit]
        );

        let history = rows.map(row => ({
            id: row.id,
            importedAt: row.imported_at,
            fileName: row.file_name,
            uploadedBy: row.imported_by,
            hospitalCode: row.hospital_code,
            hospitalName: row.hospital_name,
            fiscalYear: row.fiscal_year,
            monthNum: row.month_num,
            subFundId: row.sub_fund_id,
            menuKey: row.menu_key || '',
            claimAmount: parseFloat(row.claim_amount || 0),
            approvedAmount: parseFloat(row.approved_amount || 0),
            pendingAmount: parseFloat(row.pending_amount || 0),
            recordCount: parseInt(row.record_count || 0, 10) || 0,
            status: row.status,
            fileSizeBytes: parseInt(row.file_size_bytes || 0, 10) || 0,
            fileDataStored: Boolean(row.file_data && row.file_data.length),
            source: 'database'
        }));

        if (history.length === 0) {
            const [summaryRows] = await pool.query(
                `
                    SELECT
                        cs.id as summary_id,
                        cs.import_log_id,
                        cs.hospital_code,
                        h.hospital_name,
                        cs.fiscal_year,
                        cs.month_num,
                        cs.sub_fund_id,
                        cs.claim_amount,
                        cs.approved_amount,
                        cs.pending_amount,
                        COALESCE(il.id, cs.id) as import_id,
                        COALESCE(il.imported_at, CONCAT(cs.fiscal_year, '-', LPAD(cs.month_num, 2, '0'), '-01 00:00:00')) as imported_at,
                        COALESCE(il.file_name, CONCAT(cs.hospital_code, '_', cs.fiscal_year, '_', cs.month_num, '.xlsx')) as file_name,
                        COALESCE(il.imported_by, 'SYSTEM') as imported_by,
                        COALESCE(il.menu_key, cs.sub_fund_id) as menu_key,
                        COALESCE(il.file_size_bytes, 0) as file_size_bytes
                    FROM compensation_summaries cs
                    LEFT JOIN import_logs il ON cs.import_log_id = il.id
                    LEFT JOIN hospitals h ON cs.hospital_code = h.hospital_code
                    ORDER BY imported_at DESC, cs.id DESC
                    LIMIT ?
                `,
                [limit]
            );

            history = summaryRows.map(row => ({
                id: row.import_id,
                importedAt: row.imported_at,
                fileName: row.file_name,
                uploadedBy: row.imported_by,
                hospitalCode: row.hospital_code,
                hospitalName: row.hospital_name,
                fiscalYear: row.fiscal_year,
                monthNum: row.month_num,
                subFundId: row.sub_fund_id,
                menuKey: row.menu_key || '',
                claimAmount: parseFloat(row.claim_amount || 0),
                approvedAmount: parseFloat(row.approved_amount || 0),
                pendingAmount: parseFloat(row.pending_amount || 0),
                recordCount: 1,
                status: 'SUCCESS',
                fileSizeBytes: parseInt(row.file_size_bytes || 0, 10) || 0,
                fileDataStored: false,
                source: 'summary-fallback'
            }));
        }

        const currentHistory = await readLocalUploadHistory();
        const existingKeys = new Set(currentHistory.map(item => `${item.importedAt}|${item.fileName}|${item.hospitalCode}|${item.fiscalYear}|${item.monthNum}|${item.subFundId}`));
        history.forEach(item => {
            const key = `${item.importedAt}|${item.fileName}|${item.hospitalCode}|${item.fiscalYear}|${item.monthNum}|${item.subFundId}`;
            if (!existingKeys.has(key)) {
                currentHistory.unshift({
                    id: item.id,
                    importedAt: item.importedAt,
                    fileName: item.fileName,
                    uploadedBy: item.uploadedBy,
                    hospitalCode: item.hospitalCode,
                    hospitalName: item.hospitalName,
                    fiscalYear: item.fiscalYear,
                    monthNum: item.monthNum,
                    subFundId: item.subFundId,
                    menuKey: item.menuKey,
                    claimAmount: item.claimAmount,
                    approvedAmount: item.approvedAmount,
                    pendingAmount: item.pendingAmount,
                    recordCount: item.recordCount,
                    status: item.status,
                    fileSizeBytes: item.fileSizeBytes,
                    fileDataStored: item.fileDataStored,
                    source: 'database'
                });
            }
        });
        await writeLocalUploadHistory(currentHistory);

        res.json({ status: 'success', source: 'database', history });
    } catch (err) {
        res.status(500).json({ status: 'error', message: err.message });
    }
});

// DELETE: Remove a single upload history entry and its linked summary data
app.delete('/api/dashboard/upload-history/:id', authenticateToken, requireRole(['OFFICER', 'ADMIN']), async (req, res) => {
    const rawId = String(req.params.id || '').trim();
    const rawFileName = String(req.query.fileName || '').trim();
    if (!rawId) {
        return res.status(400).json({ status: 'error', message: 'Missing upload history id' });
    }

    if (!pool) {
        const history = await readLocalUploadHistory();
        const target = history.find(item => String(item.id) === rawId);
        if (!target) {
            return res.status(404).json({ status: 'error', message: 'Upload history not found' });
        }

        const remainingHistory = history.filter(item => String(item.id) !== rawId);
        const data = await readLocalDashboardData();
        const remainingData = data.filter(item => {
            const hospitalValue = String(item.hospital || item.hospitalName || item.hospital_code || '');
            const matchesHospital = target.hospitalCode
                ? hospitalValue.startsWith(String(target.hospitalCode))
                : true;
            const matchesYear = String(item.fiscalYear || item.fiscal_year || '') === String(target.fiscalYear || target.fiscal_year || '');
            const matchesMonth = String(item.month || item.monthNum || item.month_num || '') === String(target.monthNum || target.month_num || '');
            const matchesFund = String(item.fund || item.subFundId || item.sub_fund_id || '') === String(target.subFundId || target.sub_fund_id || '');
            return !(matchesHospital && matchesYear && matchesMonth && matchesFund);
        });

        await writeLocalUploadHistory(remainingHistory);
        await writeLocalDashboardData(remainingData);

        return res.json({
            status: 'success',
            source: 'sandbox',
            removedHistory: 1,
            removedSummaries: data.length - remainingData.length
        });
    }

    const connection = await pool.getConnection();
    try {
        await connection.beginTransaction();

        const [historyRows] = await connection.query(
            `SELECT id, imported_at, file_name, imported_by, hospital_code, hospital_name, fiscal_year, month_num, sub_fund_id, claim_amount, approved_amount, pending_amount, record_count, status, file_size_bytes
             FROM import_logs
             WHERE id = ?`,
            [rawId]
        );

        let historyRow = historyRows && historyRows.length > 0 ? historyRows[0] : null;

        if (!historyRow && rawFileName) {
            const [fallbackRows] = await connection.query(
                `SELECT id, imported_at, file_name, imported_by, hospital_code, hospital_name, fiscal_year, month_num, sub_fund_id, claim_amount, approved_amount, pending_amount, record_count, status, file_size_bytes
                 FROM import_logs
                 WHERE LOWER(TRIM(file_name)) = LOWER(TRIM(?))
                 LIMIT 1`,
                [rawFileName]
            );
            historyRow = fallbackRows && fallbackRows.length > 0 ? fallbackRows[0] : null;
        }

        if (!historyRow) {
            await connection.rollback();
            return res.status(404).json({ status: 'error', message: 'Upload history not found' });
        }
        const [summaryRows] = await connection.query(
            `SELECT id FROM compensation_summaries WHERE import_log_id = ?`,
            [historyRow.id]
        );
        const summaryIds = summaryRows.map(row => row.id);

        if (summaryIds.length > 0) {
            await connection.query(`DELETE FROM monthly_error_logs WHERE summary_id IN (?)`, [summaryIds]);
        }

        const [summaryDelete] = await connection.query(
            `DELETE FROM compensation_summaries WHERE import_log_id = ?`,
            [historyRow.id]
        );
        const [historyDelete] = await connection.query(
            `DELETE FROM import_logs WHERE id = ?`,
            [historyRow.id]
        );

        await connection.commit();

        const currentHistory = await readLocalUploadHistory();
        const remainingHistory = currentHistory.filter(item => String(item.id) !== String(historyRow.id));
        await writeLocalUploadHistory(remainingHistory);

        const currentData = await readLocalDashboardData();
        const remainingData = currentData.filter(item => {
            const hospitalValue = String(item.hospital || item.hospitalName || item.hospital_code || '');
            const matchesHospital = historyRow.hospital_code
                ? hospitalValue.startsWith(String(historyRow.hospital_code))
                : true;
            const matchesYear = String(item.fiscalYear || item.fiscal_year || '') === String(historyRow.fiscal_year || '');
            const matchesMonth = String(item.month || item.monthNum || item.month_num || '') === String(historyRow.month_num || '');
            const matchesFund = String(item.fund || item.subFundId || item.sub_fund_id || '') === String(historyRow.sub_fund_id || '');
            return !(matchesHospital && matchesYear && matchesMonth && matchesFund);
        });
        await writeLocalDashboardData(remainingData);

        res.json({
            status: 'success',
            source: 'database',
            removedHistory: historyDelete.affectedRows || 0,
            removedSummaries: summaryDelete.affectedRows || summaryIds.length
        });
    } catch (err) {
        await connection.rollback();
        res.status(500).json({ status: 'error', message: err.message });
    } finally {
        connection.release();
    }
});

// DELETE: Remove all upload data linked to a file name
app.delete('/api/dashboard/upload-history/file/:fileName', authenticateToken, requireRole(['OFFICER', 'ADMIN']), async (req, res) => {
    const rawFileName = String(req.params.fileName || '').trim();
    if (!rawFileName) {
        return res.status(400).json({ status: 'error', message: 'Missing file name' });
    }

    if (!pool) {
        const history = await readLocalUploadHistory();
        const remainingHistory = history.filter(item => String(item.fileName || '').trim() !== rawFileName);
        const data = await readLocalDashboardData();
        const remainingData = data.filter(item => String(item.fileName || '').trim() !== rawFileName);
        await writeLocalUploadHistory(remainingHistory);
        await writeLocalDashboardData(remainingData);

        return res.json({
            status: 'success',
            source: 'sandbox',
            removedHistory: history.length - remainingHistory.length,
            removedSummaries: data.length - remainingData.length
        });
    }

    const connection = await pool.getConnection();
    try {
        await connection.beginTransaction();

        const [logRows] = await connection.query(
            `SELECT id FROM import_logs WHERE LOWER(TRIM(file_name)) = LOWER(TRIM(?))`,
            [rawFileName]
        );
        const logIds = logRows.map(row => row.id);
        let removedSummaries = 0;

        if (logIds.length > 0) {
            const [summaryRows] = await connection.query(
                `SELECT id FROM compensation_summaries WHERE import_log_id IN (?)`,
                [logIds]
            );
            const summaryIds = summaryRows.map(row => row.id);

            if (summaryIds.length > 0) {
                await connection.query(`DELETE FROM monthly_error_logs WHERE summary_id IN (?)`, [summaryIds]);
            }

            const [summaryDelete] = await connection.query(
                `DELETE FROM compensation_summaries WHERE import_log_id IN (?)`,
                [logIds]
            );
            removedSummaries = summaryDelete.affectedRows || 0;
        }

        const [historyDelete] = await connection.query(
            `DELETE FROM import_logs WHERE LOWER(TRIM(file_name)) = LOWER(TRIM(?))`,
            [rawFileName]
        );

        await connection.commit();

        const currentHistory = await readLocalUploadHistory();
        const remainingHistory = currentHistory.filter(item => String(item.fileName || '').trim() !== rawFileName);
        await writeLocalUploadHistory(remainingHistory);

        const currentData = await readLocalDashboardData();
        const remainingData = currentData.filter(item => String(item.fileName || '').trim() !== rawFileName);
        await writeLocalDashboardData(remainingData);

        res.json({
            status: 'success',
            source: 'database',
            removedHistory: historyDelete.affectedRows || 0,
            removedSummaries
        });
    } catch (err) {
        await connection.rollback();
        res.status(500).json({ status: 'error', message: err.message });
    } finally {
        connection.release();
    }
});

// POST: Cleanup old upload history and dashboard logs
app.post('/api/dashboard/cleanup-old-data', authenticateToken, requireRole(['OFFICER', 'ADMIN']), async (req, res) => {
    const payload = req.body || {};
    const retentionDays = Math.max(1, Math.min(parseInt(payload.retentionDays || '180', 10) || 180, 3650));
    const scope = String(payload.scope || 'all').toLowerCase();
    const cutoff = new Date(Date.now() - (retentionDays * 24 * 60 * 60 * 1000));

    if (!pool) {
        const result = {
            status: 'success',
            source: 'sandbox',
            retentionDays,
            prunedUploads: 0,
            removedLogs: 0
        };

        if (scope === 'all' || scope === 'uploads') {
            const history = await readLocalUploadHistory();
            let prunedUploads = 0;
            const keptHistory = history.map(item => {
                const ts = new Date(item.importedAt || item.imported_at || 0).getTime();
                if (Number.isNaN(ts) || ts >= cutoff.getTime()) {
                    return item;
                }
                if (item.fileDataStored === false) {
                    return item;
                }
                prunedUploads += 1;
                return {
                    ...item,
                    fileDataStored: false
                };
            });
            result.prunedUploads = prunedUploads;
            await writeLocalUploadHistory(keptHistory);
        }

        if (scope === 'all' || scope === 'logs') {
            const logs = await readLocalDashboardLogs();
            const keptLogs = logs.filter(item => {
                const ts = new Date(item.log_ts || item.ts || 0).getTime();
                return Number.isNaN(ts) || ts >= cutoff.getTime();
            });
            result.removedLogs = logs.length - keptLogs.length;
            await writeLocalDashboardLogs(keptLogs);
        }

        return res.json(result);
    }

    const connection = await pool.getConnection();
    try {
        await connection.beginTransaction();

        let prunedUploads = 0;
        let removedLogs = 0;

        if (scope === 'all' || scope === 'uploads') {
            const [pruneResult] = await connection.query(
                `UPDATE import_logs
                 SET file_data = NULL
                 WHERE imported_at < ? AND file_data IS NOT NULL`,
                [cutoff]
            );
            prunedUploads = pruneResult.affectedRows || 0;
        }

        if (scope === 'all' || scope === 'logs') {
            const [logDelete] = await connection.query(
                `DELETE FROM dashboard_logs WHERE log_ts < ?`,
                [cutoff]
            );
            removedLogs = logDelete.affectedRows || 0;
        }

        await connection.commit();
        res.json({
            status: 'success',
            source: 'database',
            retentionDays,
            prunedUploads,
            removedLogs
        });
    } catch (err) {
        await connection.rollback();
        res.status(500).json({ status: 'error', message: err.message });
    } finally {
        connection.release();
    }
});

// POST: Store client-side dashboard/error logs
app.post('/api/dashboard/log', async (req, res) => {
    const payload = req.body || {};
    const {
        file,
        error,
        level = 'info',
        action = 'general',
        message = '',
        fileName = '',
        fund = '',
        fiscalYear = '',
        month = '',
        tab = '',
        user = '',
        details = null,
        ts = new Date().toISOString()
    } = payload;

    try {
        const normalizedLog = {
            log_ts: new Date(ts).toISOString(),
            level: String(level || 'info').toLowerCase(),
            action: String(action || 'general'),
            message: String(message || error || ''),
            file_name: fileName || file || null,
            fund: fund || null,
            fiscal_year: fiscalYear || null,
            month_num: month || null,
            tab_name: tab || null,
            username: user || 'CLIENT',
            details_json: details != null ? JSON.stringify(details) : null
        };

        if (!pool) {
            const currentLogs = await readLocalDashboardLogs();
            currentLogs.unshift({ id: Date.now(), ...normalizedLog, source: 'sandbox' });
            await writeLocalDashboardLogs(currentLogs);
            return res.json({
                status: 'success',
                message: 'Sandbox Mode: log received',
            });
        }

        await pool.query(
            `INSERT INTO dashboard_logs
                (log_ts, level, action, message, file_name, fund, fiscal_year, month_num, tab_name, username, details_json)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
            [
                normalizedLog.log_ts,
                normalizedLog.level,
                normalizedLog.action,
                normalizedLog.message,
                normalizedLog.file_name,
                normalizedLog.fund,
                normalizedLog.fiscal_year,
                normalizedLog.month_num,
                normalizedLog.tab_name,
                normalizedLog.username,
                normalizedLog.details_json
            ]
        );

        await pool.query(
            'INSERT INTO import_logs (file_name, imported_by, record_count, status) VALUES (?, ?, ?, ?)',
            [
                file || 'client_error.log',
                'CLIENT',
                0,
                error ? 'FAILED' : 'SUCCESS',
            ]
        );

        const currentLogs = await readLocalDashboardLogs();
        currentLogs.unshift({ id: Date.now(), ...normalizedLog, source: 'database' });
        await writeLocalDashboardLogs(currentLogs);

        res.json({ status: 'success' });
    } catch (err) {
        res.status(500).json({ status: 'error', message: err.message });
    }
});

// GET: Retrieve recent dashboard logs
app.get('/api/dashboard/log', authenticateToken, async (req, res) => {
    const limit = Math.min(parseInt(req.query.limit || '200', 10) || 200, 500);

    try {
        if (!pool) {
            const logs = (await readLocalDashboardLogs()).slice(0, limit).map(row => ({
                id: row.id,
                ts: row.log_ts,
                level: row.level,
                action: row.action,
                message: row.message,
                fileName: row.file_name,
                fund: row.fund,
                fiscalYear: row.fiscal_year,
                month: row.month_num,
                tab: row.tab_name,
                user: row.username,
                details: row.details_json ? (() => { try { return JSON.parse(row.details_json); } catch { return row.details_json; } })() : null,
                source: row.source || 'sandbox'
            }));
            return res.json({ status: 'success', source: 'sandbox', logs });
        }

        const [rows] = await pool.query(
            `SELECT id, log_ts, level, action, message, file_name, fund, fiscal_year, month_num, tab_name, username, details_json, created_at
             FROM dashboard_logs
             ORDER BY log_ts DESC, id DESC
             LIMIT ?`,
            [limit]
        );

        const logs = rows.map(row => ({
            id: row.id,
            ts: row.log_ts,
            level: row.level,
            action: row.action,
            message: row.message,
            fileName: row.file_name,
            fund: row.fund,
            fiscalYear: row.fiscal_year,
            month: row.month_num,
            tab: row.tab_name,
            user: row.username,
            details: row.details_json ? (() => { try { return JSON.parse(row.details_json); } catch { return row.details_json; } })() : null,
            createdAt: row.created_at
        }));

        const currentLogs = await readLocalDashboardLogs();
        const existingKeys = new Set(currentLogs.map(item => `${item.log_ts}|${item.action}|${item.message}|${item.file_name || ''}`));
        logs.forEach(log => {
            const key = `${new Date(log.ts).toISOString()}|${log.action}|${log.message}|${log.fileName || ''}`;
            if (!existingKeys.has(key)) {
                currentLogs.unshift({
                    id: log.id,
                    log_ts: new Date(log.ts).toISOString(),
                    level: log.level,
                    action: log.action,
                    message: log.message,
                    file_name: log.fileName,
                    fund: log.fund,
                    fiscal_year: log.fiscalYear,
                    month_num: log.month,
                    tab_name: log.tab,
                    username: log.user,
                    details_json: log.details ? JSON.stringify(log.details) : null,
                    source: 'database'
                });
            }
        });
        await writeLocalDashboardLogs(currentLogs);

        res.json({ status: 'success', source: 'database', logs });
    } catch (err) {
        res.status(500).json({ status: 'error', message: err.message });
    }
});

// POST: Clear all dashboard logs
app.post('/api/dashboard/log/clear', authenticateToken, requireRole(['ADMIN']), async (req, res) => {
    try {
        if (!pool) {
            await writeLocalDashboardLogs([]);
            return res.json({ status: 'success', source: 'sandbox', removed: 0 });
        }

        const [result] = await pool.query('DELETE FROM dashboard_logs');
        await writeLocalDashboardLogs([]);
        res.json({
            status: 'success',
            source: 'database',
            removed: result.affectedRows || 0
        });
    } catch (err) {
        res.status(500).json({ status: 'error', message: err.message });
    }
});

// Start listening and init DB
app.listen(PORT, async () => {
    console.log(`NHSO Dashboard Backend Server running on port ${PORT}...`);
    await initDatabase();
});
