const http = require("node:http");
const fs = require("node:fs");
const path = require("node:path");
const crypto = require("node:crypto");

const root = process.cwd();
const port = Number(process.env.PORT || 4173);
const dbFile = process.env.MBM_DB_FILE ? path.resolve(process.env.MBM_DB_FILE) : path.join(root, "data", "db.json");
const authSecret = process.env.MBM_AUTH_SECRET || "dev-secret-change-me";
const appKey = process.env.APP_KEY || "dev-app-key-change-me";

const databaseUrl = process.env.DATABASE_URL || process.env.MBM_DATABASE_URL || "";
const mysqlConfig = {
  host: process.env.MYSQL_HOST || process.env.DB_HOST || "YOUR_PRIVATE_SERVER",
  port: Number(process.env.MYSQL_PORT || process.env.DB_PORT || 3306),
  database: process.env.MYSQL_DATABASE || process.env.DB_NAME || "drug_alert",
  user: process.env.MYSQL_USER || process.env.DB_USER || "sa",
  password: process.env.MYSQL_PASSWORD || process.env.DB_PASSWORD || "Maechaem@11120",
};
const useMySql = !databaseUrl;
const usePostgres = Boolean(databaseUrl);
const mysqlReady = Boolean(mysqlConfig.host && mysqlConfig.database && mysqlConfig.user);
const defaultHospitalName = "โรงพยาบาลเทพรัตนเวชชานุกูล เฉลิมพระเกียรติ ๖๐ พรรษา";
const defaultUserDisplayNames = {
  super_admin: "ผู้ดูแลระบบสูงสุด",
  dept_admin: "ผู้ดูแลระบบหน่วยงาน",
  pharmacist: "เภสัชกร",
  lab: "เจ้าหน้าที่ LAB",
  supply: "เจ้าหน้าที่พัสดุ/คลัง",
  viewer: "ผู้เข้าชมทั่วไป",
};
let pgPool = null;
let mysqlPool = null;

const mime = {
  ".html": "text/html; charset=utf-8",
  ".css": "text/css; charset=utf-8",
  ".js": "application/javascript; charset=utf-8",
  ".json": "application/json; charset=utf-8",
  ".png": "image/png",
  ".jpg": "image/jpeg",
  ".jpeg": "image/jpeg",
  ".svg": "image/svg+xml",
  ".ico": "image/x-icon",
  ".webmanifest": "application/manifest+json; charset=utf-8",
};

process.on("unhandledRejection", (reason) => {
  console.error("UNHANDLED_REJECTION", reason);
});

process.on("uncaughtException", (error) => {
  console.error("UNCAUGHT_EXCEPTION", error);
});

const dbDir = path.dirname(dbFile);
if (!fs.existsSync(dbDir)) {
  fs.mkdirSync(dbDir, { recursive: true });
}

function id(prefix) {
  return `${prefix}_${crypto.randomBytes(8).toString("hex")}`;
}

function nowIso() {
  return new Date().toISOString();
}

function hashPassword(password, salt = crypto.randomBytes(16).toString("hex")) {
  const hash = crypto.scryptSync(password, salt, 64).toString("hex");
  return { salt, hash };
}

function hashCid(cid) {
  if (!cid) return null;
  return crypto.createHash("sha256").update(cid + appKey).digest("hex");
}

function randomUrlSafe(size = 32) {
  return crypto.randomBytes(size).toString("base64url");
}

function sha256Base64Url(value) {
  return crypto.createHash("sha256").update(String(value)).digest("base64url");
}

function getRequestOrigin(req) {
  const proto = String(req.headers["x-forwarded-proto"] || "http").split(",")[0].trim() || "http";
  const host = String(req.headers["x-forwarded-host"] || req.headers.host || `localhost:${port}`).split(",")[0].trim();
  return `${proto}://${host}`;
}

function getProviderSpec(providerKey) {
  const isProviderId = providerKey === "provider_id";
  const prefix = isProviderId ? "PROVIDER_ID" : "THAID";
  const legacyPrefix = isProviderId ? "PROVIDER" : "THAID";
  return {
    key: providerKey,
    label: isProviderId ? "ProviderID" : "ThaID",
    clientId: process.env[`${prefix}_CLIENT_ID`] || process.env[`${legacyPrefix}_CLIENT_ID`] || "",
    clientSecret: process.env[`${prefix}_CLIENT_SECRET`] || process.env[`${legacyPrefix}_CLIENT_SECRET`] || "",
    issuer: process.env[`${prefix}_ISSUER`] || process.env[`${legacyPrefix}_ISSUER`] || "",
    authorizationUrl: process.env[`${prefix}_AUTHORIZATION_URL`] || process.env[`${legacyPrefix}_AUTHORIZATION_URL`] || "",
    tokenUrl: process.env[`${prefix}_TOKEN_URL`] || process.env[`${legacyPrefix}_TOKEN_URL`] || "",
    userinfoUrl: process.env[`${prefix}_USERINFO_URL`] || process.env[`${legacyPrefix}_USERINFO_URL`] || "",
    jwksUrl: process.env[`${prefix}_JWKS_URL`] || process.env[`${legacyPrefix}_JWKS_URL`] || "",
    scope: process.env[`${prefix}_SCOPE`] || process.env[`${legacyPrefix}_SCOPE`] || "openid profile email",
    redirectUri: process.env[`${prefix}_REDIRECT_URI`] || process.env[`${legacyPrefix}_REDIRECT_URI`] || "",
  };
}

function hasProviderConfig(spec) {
  return Boolean(spec.clientId && spec.clientSecret && (spec.issuer || (spec.authorizationUrl && spec.tokenUrl)));
}

function extractCidHash(claims = {}) {
  const candidates = [
    claims.cid,
    claims.cid_number,
    claims.citizen_id,
    claims.pid,
    claims.national_id,
    claims.nationalId,
    claims.id_card,
    claims.idcard,
  ]
    .filter((value) => typeof value === "string" && value.trim())
    .map((value) => value.trim());
  if (candidates.length === 0) return null;
  return hashCid(candidates[0]);
}

function sanitizeClaimsForStorage(claims = {}) {
  return {
    sub: claims.sub || null,
    iss: claims.iss || null,
    aud: claims.aud || null,
    email: claims.email || null,
    name: claims.name || null,
    preferred_username: claims.preferred_username || null,
    given_name: claims.given_name || null,
    family_name: claims.family_name || null,
    amr: claims.amr || null,
    acr: claims.acr || null,
  };
}

function asArray(value) {
  if (!value) return [];
  return Array.isArray(value) ? value : [value];
}

function mapRoleFromClaims(providerKey, claims = {}, fallbackRole = "viewer") {
  const validRoles = ["super_admin", "dept_admin", "pharmacist", "lab", "supply", "viewer"];
  const candidateValues = [
    claims.role,
    claims.system_role,
    claims.hospital_role,
    claims.access_role,
    claims.group,
    claims.department,
    claims.unit,
  ];
  for (const candidate of candidateValues) {
    for (const value of asArray(candidate)) {
      if (validRoles.includes(String(value))) return String(value);
    }
  }
  const joined = candidateValues.flatMap(asArray).map((value) => String(value).toLowerCase());
  if (joined.some((value) => value.includes("pharm"))) return "pharmacist";
  if (joined.some((value) => value.includes("lab") || value.includes("reagent") || value.includes("testkit"))) return "lab";
  if (joined.some((value) => value.includes("supply") || value.includes("stock"))) return "supply";
  if (providerKey === "provider_id") return "dept_admin";
  return fallbackRole;
}

function getDisplayNameFromClaims(claims = {}, fallback = "External User") {
  const preferred = [claims.name, claims.display_name, claims.displayName, claims.preferred_username, claims.email]
    .find((value) => typeof value === "string" && value.trim());
  if (preferred) return preferred.trim();
  const parts = [claims.given_name, claims.family_name].filter((value) => typeof value === "string" && value.trim());
  if (parts.length) return parts.join(" ");
  return fallback;
}

function getExternalUsername(providerKey, providerUserId) {
  return `${providerKey}_${sha256Base64Url(providerUserId).slice(0, 16)}`;
}

function getAuthIdentityByProviderSubject(db, providerType, providerUserId) {
  return (db.authIdentities || []).find((item) => item.provider === providerType && item.providerUserId === providerUserId);
}

function getAuthIdentityByCidHash(db, cidHash) {
  if (!cidHash) return null;
  return (db.authIdentities || []).find((item) => item.cidHash === cidHash);
}

function getUserByIdentity(db, providerType, providerUserId) {
  const identity = getAuthIdentityByProviderSubject(db, providerType, providerUserId);
  return identity ? getUserById(db, identity.userId) : null;
}

function upsertAuthIdentity(db, identity) {
  db.authIdentities = db.authIdentities || [];
  const existing = getAuthIdentityByProviderSubject(db, identity.provider, identity.providerUserId);
  const now = nowIso();
  if (existing) {
    existing.userId = identity.userId;
    existing.cidHash = identity.cidHash || existing.cidHash || null;
    existing.updatedAt = now;
    return existing;
  }
  const row = {
    id: id("identity"),
    userId: identity.userId,
    provider: identity.provider,
    providerUserId: identity.providerUserId,
    cidHash: identity.cidHash || null,
    createdAt: now,
    updatedAt: now,
  };
  db.authIdentities.unshift(row);
  return row;
}

function createExternalUserFromClaims(db, { providerKey, providerUserId, claims, cidHash }) {
  const displayName = getDisplayNameFromClaims(claims, `${providerKey} user`);
  const role = mapRoleFromClaims(providerKey, claims, "viewer");
  const password = hashPassword(randomUrlSafe(32));
  const user = {
    id: id("user"),
    username: getExternalUsername(providerKey, providerUserId),
    displayName,
    role,
    active: true,
    status: "pending",
    approvalStatus: "pending",
    locationId: null,
    createdAt: nowIso(),
    lastLoginAt: nowIso(),
    passwordSalt: password.salt,
    passwordHash: password.hash,
  };
  db.users.unshift(user);
  recordAudit(db, null, "auth.external_user_created", "user", user.id, {
    provider: providerKey,
    providerUserId,
    role,
    cidHash: cidHash ? "present" : null,
    claims: sanitizeClaimsForStorage(claims),
  });
  return user;
}

const oidcDiscoveryCache = new Map();

async function fetchJson(url, options = {}) {
  const response = await fetch(url, options);
  const raw = await response.text();
  let data = null;
  try {
    data = raw ? JSON.parse(raw) : null;
  } catch {
    data = raw;
  }
  if (!response.ok) {
    const message = typeof data === "string" ? data : JSON.stringify(data || {});
    throw new Error(`HTTP ${response.status} from ${url}: ${message}`);
  }
  return data;
}

async function discoverOidcProvider(spec) {
  if (oidcDiscoveryCache.has(spec.key)) {
    return oidcDiscoveryCache.get(spec.key);
  }
  let metadata = null;
  if (spec.issuer) {
    const issuer = spec.issuer.replace(/\/+$/, "");
    metadata = await fetchJson(`${issuer}/.well-known/openid-configuration`);
  } else if (spec.authorizationUrl && spec.tokenUrl) {
    metadata = {
      authorization_endpoint: spec.authorizationUrl,
      token_endpoint: spec.tokenUrl,
      userinfo_endpoint: spec.userinfoUrl || "",
      jwks_uri: spec.jwksUrl || "",
      issuer: spec.issuer || "",
    };
  } else {
    throw new Error(`Missing OAuth/OIDC configuration for ${spec.label}`);
  }
  oidcDiscoveryCache.set(spec.key, metadata);
  return metadata;
}

function createPkcePair() {
  const verifier = randomUrlSafe(48);
  return {
    verifier,
    challenge: sha256Base64Url(verifier),
  };
}

function buildRedirectUri(req, providerKey, spec = getProviderSpec(providerKey)) {
  return spec.redirectUri || `${getRequestOrigin(req)}/api/auth/${providerKey}/callback`;
}

function decodeJwtParts(token) {
  const [headerPart, payloadPart, signaturePart] = String(token || "").split(".");
  if (!headerPart || !payloadPart || !signaturePart) return null;
  try {
    return {
      header: JSON.parse(Buffer.from(headerPart.replace(/-/g, "+").replace(/_/g, "/"), "base64").toString("utf8")),
      payload: JSON.parse(Buffer.from(payloadPart.replace(/-/g, "+").replace(/_/g, "/"), "base64").toString("utf8")),
      signedData: `${headerPart}.${payloadPart}`,
      signature: Buffer.from(signaturePart.replace(/-/g, "+").replace(/_/g, "/"), "base64"),
    };
  } catch {
    return null;
  }
}

async function verifyIdToken(idToken, jwksUri, expectedIssuer, expectedAudience, expectedNonce) {
  const parts = decodeJwtParts(idToken);
  if (!parts) throw new Error("Invalid id_token");
  if (parts.header.alg !== "RS256") {
    throw new Error(`Unsupported id_token algorithm: ${parts.header.alg || "unknown"}`);
  }
  if (!jwksUri) throw new Error("Missing JWKS endpoint");
  const jwks = await fetchJson(jwksUri);
  const keys = Array.isArray(jwks.keys) ? jwks.keys : [];
  const jwk = keys.find((item) => !parts.header.kid || item.kid === parts.header.kid) || keys[0];
  if (!jwk) throw new Error("No matching JWKS key found");
  const publicKey = crypto.createPublicKey({ key: jwk, format: "jwk" });
  const verifier = crypto.createVerify("RSA-SHA256");
  verifier.update(parts.signedData);
  verifier.end();
  if (!verifier.verify(publicKey, parts.signature)) {
    throw new Error("Invalid id_token signature");
  }
  const now = Math.floor(Date.now() / 1000);
  if (expectedIssuer && parts.payload.iss !== expectedIssuer) {
    throw new Error("Invalid token issuer");
  }
  const audience = asArray(parts.payload.aud).map((value) => String(value));
  if (expectedAudience && !audience.includes(expectedAudience)) {
    throw new Error("Invalid token audience");
  }
  if (parts.payload.exp && now >= Number(parts.payload.exp)) {
    throw new Error("id_token has expired");
  }
  if (parts.payload.nonce && expectedNonce && parts.payload.nonce !== expectedNonce) {
    throw new Error("Invalid token nonce");
  }
  return parts.payload;
}

async function exchangeOidcCode({ spec, discovery, code, codeVerifier, redirectUri }) {
  const body = new URLSearchParams({
    grant_type: "authorization_code",
    code,
    redirect_uri: redirectUri,
    client_id: spec.clientId,
    client_secret: spec.clientSecret,
    code_verifier: codeVerifier,
  });
  return fetchJson(discovery.token_endpoint, {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
      Accept: "application/json",
    },
    body,
  });
}

async function fetchOidcProfile({ discovery, accessToken, idTokenClaims }) {
  let profile = { ...idTokenClaims };
  if (discovery.userinfo_endpoint && accessToken) {
    const userInfo = await fetchJson(discovery.userinfo_endpoint, {
      headers: { Authorization: `Bearer ${accessToken}`, Accept: "application/json" },
    });
    profile = { ...profile, ...userInfo };
  }
  return profile;
}

function buildStateToken(payload) {
  return signToken({
    kind: "oidc_state",
    ...payload,
    exp: Date.now() + 10 * 60 * 1000,
  });
}

function verifyStateToken(token, providerKey) {
  const payload = verifyToken(token);
  if (!payload || payload.kind !== "oidc_state" || payload.providerKey !== providerKey) {
    return null;
  }
  return payload;
}

async function startExternalLogin(req, res, db, providerKey) {
  const spec = getProviderSpec(providerKey);
  if (!hasProviderConfig(spec)) {
    sendJson(res, 503, { error: `${spec.label} ยังไม่ได้ตั้งค่า OAuth/OIDC` });
    return;
  }

  const discovery = await discoverOidcProvider(spec);
  const redirectUri = buildRedirectUri(req, providerKey, spec);
  const pkce = createPkcePair();
  const nonce = randomUrlSafe(24);
  const stateToken = buildStateToken({
    providerKey,
    codeVerifier: pkce.verifier,
    nonce,
    redirectUri,
  });

  const authorization = new URL(discovery.authorization_endpoint);
  authorization.searchParams.set("response_type", "code");
  authorization.searchParams.set("client_id", spec.clientId);
  authorization.searchParams.set("redirect_uri", redirectUri);
  authorization.searchParams.set("scope", spec.scope);
  authorization.searchParams.set("state", stateToken);
  authorization.searchParams.set("code_challenge", pkce.challenge);
  authorization.searchParams.set("code_challenge_method", "S256");
  authorization.searchParams.set("nonce", nonce);

  recordLoginLog(db, {
    providerType: providerKey,
    action: "oidc_start",
    result: "started",
    ipAddress: req.headers["x-forwarded-for"] || req.socket.remoteAddress || null,
    userAgent: req.headers["user-agent"] || null,
    details: { redirectUri, authorizationEndpoint: discovery.authorization_endpoint },
  });
  await saveDb(db);

  res.writeHead(302, { Location: authorization.toString() });
  res.end();
}

async function finishExternalLogin(req, res, db, providerKey) {
  const spec = getProviderSpec(providerKey);
  if (!hasProviderConfig(spec)) {
    sendJson(res, 503, { error: `${spec.label} ยังไม่ได้ตั้งค่า OAuth/OIDC` });
    return;
  }

  const url = new URL(req.url, `http://${req.headers.host || "127.0.0.1"}`);
  const error = url.searchParams.get("error");
  const errorDescription = url.searchParams.get("error_description") || error || "";
  const stateToken = url.searchParams.get("state");
  const code = url.searchParams.get("code");
  const ipAddress = req.headers["x-forwarded-for"] || req.socket.remoteAddress || null;
  const userAgent = req.headers["user-agent"] || null;

  if (error) {
    recordLoginLog(db, {
      providerType: providerKey,
      action: "oidc_callback",
      result: "failed",
      errorMessage: errorDescription,
      ipAddress,
      userAgent,
      details: { error, errorDescription },
    });
    await saveDb(db);
    const redirect = new URL("/", getRequestOrigin(req));
    redirect.searchParams.set("error", errorDescription || "Login cancelled");
    res.writeHead(302, { Location: redirect.toString() });
    res.end();
    return;
  }

  const state = verifyStateToken(stateToken, providerKey);
  if (!state || !code) {
    recordLoginLog(db, {
      providerType: providerKey,
      action: "oidc_callback",
      result: "failed",
      errorMessage: "Invalid state or missing code",
      ipAddress,
      userAgent,
      details: { hasState: Boolean(stateToken), hasCode: Boolean(code) },
    });
    await saveDb(db);
    sendJson(res, 400, { error: "Invalid login state" });
    return;
  }

  const discovery = await discoverOidcProvider(spec);
  const tokens = await exchangeOidcCode({
    spec,
    discovery,
    code,
    codeVerifier: state.codeVerifier,
    redirectUri: state.redirectUri,
  });

  let idTokenClaims = {};
  if (tokens.id_token) {
    idTokenClaims = await verifyIdToken(tokens.id_token, discovery.jwks_uri || spec.jwksUrl, discovery.issuer || spec.issuer, spec.clientId, state.nonce);
  }
  const claims = await fetchOidcProfile({
    discovery,
    accessToken: tokens.access_token,
    idTokenClaims,
  });
  const providerUserId = String(claims.sub || "");
  if (!providerUserId) {
    throw new Error("OIDC profile is missing sub");
  }

  const cidHash = extractCidHash(claims);
  let identity = getAuthIdentityByProviderSubject(db, providerKey, providerUserId);
  if (!identity && cidHash) {
    identity = getAuthIdentityByCidHash(db, cidHash);
  }

  let user = identity ? getUserById(db, identity.userId) : null;
  const createdUser = !user;
  if (!user) {
    user = createExternalUserFromClaims(db, { providerKey, providerUserId, claims, cidHash });
    identity = upsertAuthIdentity(db, { userId: user.id, provider: providerKey, providerUserId, cidHash });
  } else {
    identity = upsertAuthIdentity(db, { userId: user.id, provider: providerKey, providerUserId, cidHash });
  }

  user.lastLoginAt = nowIso();
  if (createdUser) {
    user.status = "pending";
    user.approvalStatus = "pending";
  } else {
    user.status = user.status || user.approvalStatus || "active";
    user.approvalStatus = user.approvalStatus || user.status || "active";
  }

  const loginResult = user.status === "pending" ? "pending" : "success";
  recordLoginLog(db, {
    providerType: providerKey,
    userId: user.id,
    identityId: identity.id,
    action: "oidc_login",
    result: loginResult,
    ipAddress,
    userAgent,
    details: sanitizeClaimsForStorage(claims),
  });
  recordAudit(db, user.id, "auth.oidc_login", "user", user.id, {
    provider: providerKey,
    identityId: identity.id,
    result: loginResult,
  });

  const token = signToken({ sub: user.id, role: user.role, exp: Date.now() + 1000 * 60 * 60 * 8 });
  await saveDb(db);
  res.writeHead(302, {
    Location: "/",
    "Set-Cookie": sendCookie(req, token),
  });
  res.end();
}

function base64url(input) {
  return Buffer.from(input).toString("base64").replace(/\+/g, "-").replace(/\//g, "_").replace(/=+$/g, "");
}

function signToken(payload) {
  const body = base64url(JSON.stringify(payload));
  const signature = crypto.createHmac("sha256", authSecret).update(body).digest("base64").replace(/\+/g, "-").replace(/\//g, "_").replace(/=+$/g, "");
  return `${body}.${signature}`;
}

function verifyToken(token) {
  if (!token || typeof token !== "string" || !token.includes(".")) return null;
  const [body, signature] = token.split(".");
  const expected = crypto.createHmac("sha256", authSecret).update(body).digest("base64").replace(/\+/g, "-").replace(/\//g, "_").replace(/=+$/g, "");
  const left = Buffer.from(signature);
  const right = Buffer.from(expected);
  if (left.length !== right.length) return null;
  if (!crypto.timingSafeEqual(left, right)) return null;
  try {
    const payload = JSON.parse(Buffer.from(body.replace(/-/g, "+").replace(/_/g, "/"), "base64").toString("utf8"));
    if (payload.exp && Date.now() > payload.exp) return null;
    return payload;
  } catch {
    return null;
  }
}

function parseCookies(cookieHeader = "") {
  return cookieHeader.split(";").reduce((acc, item) => {
    const [rawKey, ...rest] = item.trim().split("=");
    if (!rawKey) return acc;
    acc[rawKey] = decodeURIComponent(rest.join("=") || "");
    return acc;
  }, {});
}

function readBody(req) {
  return new Promise((resolve, reject) => {
    let raw = "";
    req.on("data", (chunk) => {
      raw += chunk;
      if (raw.length > 1_000_000) {
        reject(new Error("Payload too large"));
        req.destroy();
      }
    });
    req.on("end", () => {
      if (!raw) {
        resolve(null);
        return;
      }
      try {
        resolve(JSON.parse(raw));
      } catch {
        reject(new Error("Invalid JSON"));
      }
    });
    req.on("error", reject);
  });
}

function sendJson(res, statusCode, data, headers = {}) {
  res.writeHead(statusCode, {
    "Content-Type": "application/json; charset=utf-8",
    ...headers,
  });
  res.end(JSON.stringify(data));
}

function sendText(res, statusCode, text, headers = {}) {
  res.writeHead(statusCode, {
    "Content-Type": "text/plain; charset=utf-8",
    ...headers,
  });
  res.end(text);
}

function getPgPool() {
  if (!usePostgres) {
    throw new Error("PostgreSQL is not configured. Set DATABASE_URL or MBM_DATABASE_URL.");
  }
  if (!pgPool) {
    let Pool;
    try {
      ({ Pool } = require("pg"));
    } catch (error) {
      throw new Error("Missing dependency: pg. Run npm install before using PostgreSQL.");
    }
    pgPool = new Pool({
      connectionString: databaseUrl,
      ssl: process.env.PGSSLMODE === "require" ? { rejectUnauthorized: false } : undefined,
    });
  }
  return pgPool;
}

function getMySqlPool() {
  if (!mysqlReady) {
    throw new Error("MySQL is not configured. Set MYSQL_HOST, MYSQL_DATABASE, MYSQL_USER, and MYSQL_PASSWORD.");
  }
  if (!mysqlPool) {
    let mysql;
    try {
      mysql = require("mysql2/promise");
    } catch (error) {
      throw new Error("Missing dependency: mysql2. Run npm install before using MySQL.");
    }
    mysqlPool = mysql.createPool({
      host: mysqlConfig.host,
      port: mysqlConfig.port,
      database: mysqlConfig.database,
      user: mysqlConfig.user,
      password: mysqlConfig.password,
      charset: "utf8mb4",
      multipleStatements: true,
      waitForConnections: true,
      connectionLimit: 10,
      timezone: "Z",
    });
    const originalGetConnection = mysqlPool.getConnection.bind(mysqlPool);
    mysqlPool.getConnection = async () => {
      const conn = await originalGetConnection();
      if (!conn.__charsetSet) {
        await conn.query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        conn.__charsetSet = true;
      }
      return conn;
    };

    const originalQuery = mysqlPool.query.bind(mysqlPool);
    mysqlPool.query = async (sql, values) => {
      const conn = await mysqlPool.getConnection();
      try {
        return await conn.query(sql, values);
      } finally {
        conn.release();
      }
    };
  }
  return mysqlPool;
}

async function ensureJsonDb() {
  if (!fs.existsSync(dbFile)) {
    fs.writeFileSync(dbFile, JSON.stringify(seedDb(), null, 2), "utf8");
  }
}

function parseJsonValue(value, fallback = null) {
  if (value === null || value === undefined || value === "") return fallback;
  if (typeof value === "object") return value;
  try {
    return JSON.parse(value);
  } catch {
    return fallback;
  }
}

function asIso(value) {
  if (!value) return null;
  if (value instanceof Date) return value.toISOString();
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? value : date.toISOString();
}

async function ensureMySqlColumn(pool, table, column, definition) {
  const [rows] = await pool.query(
    `SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1`,
    [mysqlConfig.database, table, column],
  );
  if (rows.length === 0) {
    await pool.query(`ALTER TABLE \`${table}\` ADD COLUMN \`${column}\` ${definition}`);
  }
}

async function ensureMySqlSchema() {
  const pool = getMySqlPool();
  await pool.query(`
    CREATE TABLE IF NOT EXISTS settings (
      \`key\` varchar(64) NOT NULL,
      \`value\` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(\`value\`)),
      PRIMARY KEY (\`key\`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    CREATE TABLE IF NOT EXISTS users (
      id varchar(64) NOT NULL,
      username varchar(191) NOT NULL,
      display_name varchar(191) NOT NULL,
      role varchar(32) NOT NULL,
      active tinyint(1) NOT NULL DEFAULT 1,
      created_at datetime(3) NOT NULL,
      last_login_at datetime(3) DEFAULT NULL,
      deleted_at datetime(3) DEFAULT NULL,
      password_salt varchar(64) NOT NULL,
      password_hash varchar(255) NOT NULL,
      PRIMARY KEY (id),
      UNIQUE KEY username (username)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    CREATE TABLE IF NOT EXISTS locations (
      id varchar(64) NOT NULL,
      name varchar(191) NOT NULL,
      detail text NOT NULL,
      status varchar(64) NOT NULL,
      updated_at datetime(3) NOT NULL,
      PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    CREATE TABLE IF NOT EXISTS drugs (
      id varchar(64) NOT NULL,
      name varchar(191) NOT NULL,
      unit varchar(64) NOT NULL,
      price varchar(64) NOT NULL,
      category varchar(64) NOT NULL DEFAULT 'drug',
      deleted_at datetime(3) DEFAULT NULL,
      updated_at datetime(3) NOT NULL,
      PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    CREATE TABLE IF NOT EXISTS boxes (
      id varchar(64) NOT NULL,
      name varchar(191) NOT NULL,
      location_id varchar(64) DEFAULT NULL,
      status varchar(64) NOT NULL,
      value varchar(64) NOT NULL,
      drug_count int NOT NULL DEFAULT 0,
      updated_at datetime(3) NOT NULL,
      PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    CREATE TABLE IF NOT EXISTS alerts (
      id varchar(64) NOT NULL,
      box_id varchar(64) DEFAULT NULL,
      box_name varchar(191) NOT NULL,
      location_name varchar(191) NOT NULL,
      drug_name varchar(191) NOT NULL,
      quantity varchar(64) NOT NULL,
      unit varchar(32) NOT NULL,
      severity varchar(32) NOT NULL,
      status varchar(64) NOT NULL,
      created_at datetime(3) NOT NULL,
      PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    CREATE TABLE IF NOT EXISTS notifications (
      id varchar(64) NOT NULL,
      title varchar(255) NOT NULL,
      body text NOT NULL,
      severity varchar(32) NOT NULL,
      target_view varchar(64) DEFAULT NULL,
      read_by longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(read_by)),
      created_at datetime(3) NOT NULL,
      PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    CREATE TABLE IF NOT EXISTS audit_logs (
      id varchar(64) NOT NULL,
      actor_user_id varchar(64) DEFAULT NULL,
      action varchar(128) NOT NULL,
      entity_type varchar(64) NOT NULL,
      entity_id varchar(64) DEFAULT NULL,
      details longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(details)),
      created_at datetime(3) NOT NULL,
      PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    CREATE TABLE IF NOT EXISTS auth_identities (
      id varchar(64) NOT NULL,
      user_id varchar(64) NOT NULL,
      provider varchar(64) NOT NULL,
      provider_user_id varchar(191) NOT NULL,
      cid_hash varchar(255) DEFAULT NULL,
      created_at datetime(3) NOT NULL,
      updated_at datetime(3) NOT NULL,
      PRIMARY KEY (id),
      UNIQUE KEY provider_uid (provider, provider_user_id),
      KEY user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    CREATE TABLE IF NOT EXISTS login_logs (
      id varchar(64) NOT NULL,
      user_id varchar(64) DEFAULT NULL,
      provider varchar(64) NOT NULL,
      ip_address varchar(64) DEFAULT NULL,
      user_agent text DEFAULT NULL,
      status varchar(32) NOT NULL,
      created_at datetime(3) NOT NULL,
      PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  `);
  await ensureMySqlColumn(pool, "users", "status", "varchar(32) NOT NULL DEFAULT 'active'");
  await ensureMySqlColumn(pool, "users", "location_id", "varchar(64) DEFAULT NULL");
  await ensureMySqlColumn(pool, "drugs", "category", "varchar(64) NOT NULL DEFAULT 'drug'");
  await ensureMySqlColumn(pool, "drugs", "deleted_at", "datetime(3) DEFAULT NULL");
  await ensureMySqlColumn(pool, "alerts", "category", "varchar(64) NOT NULL DEFAULT 'drug'");
  await ensureMySqlColumn(pool, "alerts", "expiry_date", "varchar(32) DEFAULT NULL");
  await ensureMySqlColumn(pool, "alerts", "resolved_at", "datetime(3) DEFAULT NULL");
  await ensureMySqlColumn(pool, "boxes", "drug_count", "int NOT NULL DEFAULT 0");
  await ensureMySqlColumn(pool, "boxes", "deleted_at", "datetime(3) DEFAULT NULL");
  await ensureMySqlColumn(pool, "locations", "deleted_at", "datetime(3) DEFAULT NULL");
  await repairDefaultMySqlUserNames(pool);
}

function isCorruptedDisplayName(value) {
  return typeof value !== "string" || value.length > 64 || value.includes("\uFFFD") || value.includes("๏ฟฝ");
}

async function repairDefaultMySqlUserNames(pool) {
  const [rows] = await pool.query(
    "SELECT username, display_name FROM users WHERE username IN ('admin', 'dept_admin', 'pharmacist', 'lab', 'supply', 'viewer')",
  );
  for (const row of rows) {
    const displayName = defaultUserDisplayNames[row.username];
    if (displayName && isCorruptedDisplayName(row.display_name)) {
      await pool.query("UPDATE users SET display_name = ? WHERE username = ?", [displayName, row.username]);
    }
  }
}

async function ensurePgSchema() {
  const pool = getPgPool();
  await pool.query(`
    CREATE TABLE IF NOT EXISTS settings (
      key TEXT PRIMARY KEY,
      value JSONB NOT NULL
    );
    CREATE TABLE IF NOT EXISTS users (
      id TEXT PRIMARY KEY,
      username TEXT UNIQUE NOT NULL,
      display_name TEXT NOT NULL,
      role TEXT NOT NULL,
      active BOOLEAN NOT NULL DEFAULT TRUE,
      created_at TIMESTAMPTZ NOT NULL,
      last_login_at TIMESTAMPTZ,
      deleted_at TIMESTAMPTZ,
      password_salt TEXT NOT NULL,
      password_hash TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS locations (
      id TEXT PRIMARY KEY,
      name TEXT NOT NULL,
      detail TEXT NOT NULL DEFAULT '',
      status TEXT NOT NULL,
      updated_at TIMESTAMPTZ NOT NULL
    );
    CREATE TABLE IF NOT EXISTS drugs (
      id TEXT PRIMARY KEY,
      name TEXT NOT NULL,
      unit TEXT NOT NULL,
      price TEXT NOT NULL,
      category TEXT NOT NULL DEFAULT 'drug',
      deleted_at TIMESTAMPTZ,
      updated_at TIMESTAMPTZ NOT NULL
    );
    CREATE TABLE IF NOT EXISTS boxes (
      id TEXT PRIMARY KEY,
      code TEXT NOT NULL,
      location_id TEXT NOT NULL,
      value NUMERIC NOT NULL DEFAULT 0,
      drug_count INTEGER NOT NULL DEFAULT 0,
      status TEXT NOT NULL,
      updated_at TIMESTAMPTZ NOT NULL
    );
    CREATE TABLE IF NOT EXISTS alerts (
      id TEXT PRIMARY KEY,
      box_id TEXT NOT NULL,
      drug_name TEXT NOT NULL,
      quantity INTEGER NOT NULL,
      unit TEXT NOT NULL,
      severity TEXT NOT NULL,
      status TEXT NOT NULL,
      expiry_date TEXT NOT NULL,
      created_at TIMESTAMPTZ NOT NULL,
      resolved_at TIMESTAMPTZ
    );
    CREATE TABLE IF NOT EXISTS notifications (
      id TEXT PRIMARY KEY,
      title TEXT NOT NULL,
      body TEXT NOT NULL,
      severity TEXT NOT NULL,
      created_at TIMESTAMPTZ NOT NULL,
      target_view TEXT NOT NULL,
      read_by JSONB NOT NULL DEFAULT '[]'::jsonb
    );
    CREATE TABLE IF NOT EXISTS audit_logs (
      id TEXT PRIMARY KEY,
      actor_user_id TEXT,
      action TEXT NOT NULL,
      entity_type TEXT NOT NULL,
      entity_id TEXT,
      details JSONB NOT NULL DEFAULT '{}'::jsonb,
      created_at TIMESTAMPTZ NOT NULL
    );
    CREATE TABLE IF NOT EXISTS auth_identities (
      id TEXT PRIMARY KEY,
      user_id TEXT NOT NULL,
      provider TEXT NOT NULL,
      provider_user_id TEXT NOT NULL,
      cid_hash TEXT,
      created_at TIMESTAMPTZ NOT NULL,
      updated_at TIMESTAMPTZ NOT NULL,
      UNIQUE (provider, provider_user_id)
    );
    CREATE TABLE IF NOT EXISTS login_logs (
      id TEXT PRIMARY KEY,
      user_id TEXT,
      provider TEXT NOT NULL,
      ip_address TEXT,
      user_agent TEXT,
      status TEXT NOT NULL,
      created_at TIMESTAMPTZ NOT NULL
    );
  `);
  
  // Add status column to users if it doesn't exist (simplistic approach for PG)
  try {
    await pool.query(`ALTER TABLE users ADD COLUMN status TEXT NOT NULL DEFAULT 'active'`);
  } catch (err) {
    // Column might already exist
  }
  try {
    await pool.query(`ALTER TABLE users ADD COLUMN location_id TEXT`);
  } catch (err) {}
  try {
    await pool.query(`ALTER TABLE drugs ADD COLUMN category TEXT NOT NULL DEFAULT 'drug'`);
  } catch (err) {}
  try {
    await pool.query(`ALTER TABLE drugs ADD COLUMN deleted_at TIMESTAMPTZ`);
  } catch (err) {}
  try {
    await pool.query(`ALTER TABLE alerts ADD COLUMN category TEXT NOT NULL DEFAULT 'drug'`);
  } catch (err) {}
  try {
    await pool.query(`ALTER TABLE boxes ADD COLUMN drug_count INTEGER NOT NULL DEFAULT 0`);
  } catch (err) {}
}

function normalizeDbShape(db) {
  return {
    settings: db.settings || { hospitalName: defaultHospitalName },
    users: db.users || [],
    authIdentities: db.authIdentities || db.auth_identities || [],
    loginLogs: db.loginLogs || db.login_logs || [],
    locations: db.locations || [],
    drugs: db.drugs || [],
    boxes: db.boxes || [],
    alerts: db.alerts || [],
    notifications: db.notifications || [],
    auditLogs: db.auditLogs || [],
  };
}

function loadDbFromFile() {
  const raw = fs.readFileSync(dbFile, "utf8");
  const db = JSON.parse(raw);
  return normalizeDbShape(db);
}

function mapFileDbToMemory(db) {
  const normalized = normalizeDbShape(db);
  normalized.settings ||= { hospitalName: defaultHospitalName };
  normalized.users = (normalized.users || []).map((user) => ({
    ...user,
    status: user.approvalStatus || user.status || (user.active === false ? "inactive" : "active"),
    approvalStatus: user.approvalStatus || user.status || (user.active === false ? "inactive" : "active"),
    locationId: user.locationId || user.location_id || null,
    createdAt: user.createdAt || user.created_at || nowIso(),
    lastLoginAt: user.lastLoginAt || user.last_login_at || null,
    deletedAt: user.deletedAt || user.deleted_at || null,
  }));
  normalized.authIdentities = (normalized.authIdentities || []).map((identity) => ({
    ...identity,
    userId: identity.userId || identity.user_id,
    providerUserId: identity.providerUserId || identity.provider_user_id,
    cidHash: identity.cidHash || identity.cid_hash || null,
    createdAt: identity.createdAt || identity.created_at || nowIso(),
    updatedAt: identity.updatedAt || identity.updated_at || nowIso(),
  }));
  normalized.loginLogs = (normalized.loginLogs || []).map((log) => ({
    ...log,
    userId: log.userId || log.user_id || null,
    providerType: log.providerType || log.provider || "local",
    provider: log.provider || log.providerType || "local",
    ipAddress: log.ipAddress || log.ip_address || null,
    userAgent: log.userAgent || log.user_agent || null,
    createdAt: log.createdAt || log.created_at || nowIso(),
  }));
  normalized.locations = (normalized.locations || []).map((location) => ({
    ...location,
    updatedAt: location.updatedAt || location.updated_at || nowIso(),
  }));
  normalized.drugs = (normalized.drugs || []).map((drug) => ({
    ...drug,
    category: drug.category || "drug",
    updatedAt: drug.updatedAt || drug.updated_at || nowIso(),
  }));
  normalized.boxes = (normalized.boxes || []).map((box) => ({
    ...box,
    code: box.code || box.name || "",
    locationId: box.locationId || box.location_id || null,
    drugCount: Number(box.drugCount ?? box.drug_count ?? box.alertCount ?? 0),
    updatedAt: box.updatedAt || box.updated_at || nowIso(),
  }));
  normalized.alerts = (normalized.alerts || []).map((alert) => ({
    ...alert,
    boxId: alert.boxId || alert.box_id || null,
    category: alert.category || "drug",
    expiryDate: alert.expiryDate || alert.expiry_date || alert.createdAt || alert.created_at || nowIso(),
    createdAt: alert.createdAt || alert.created_at || nowIso(),
    resolvedAt: alert.resolvedAt || alert.resolved_at || null,
  }));
  normalized.notifications = (normalized.notifications || []).map((notification) => ({
    ...notification,
    createdAt: notification.createdAt || notification.created_at || nowIso(),
    readBy: Array.isArray(notification.readBy) ? notification.readBy : parseJsonValue(notification.read_by, []),
  }));
  normalized.auditLogs = (normalized.auditLogs || normalized.audit_logs || []).map((log) => ({
    ...log,
    createdAt: log.createdAt || log.created_at || nowIso(),
    details: log.details || parseJsonValue(log.details, {}),
  }));
  return normalized;
}

async function loadDbFromMySql() {
  await ensureMySqlSchema();
  const pool = getMySqlPool();
  const [settingsRes, usersRes, locationsRes, drugsRes, boxesRes, alertsRes, notificationsRes, auditLogsRes, authIdentitiesRes, loginLogsRes] = await Promise.all([
    pool.query("SELECT `key`, `value` FROM settings"),
    pool.query("SELECT * FROM users ORDER BY created_at ASC"),
    pool.query("SELECT * FROM locations ORDER BY updated_at DESC"),
    pool.query("SELECT * FROM drugs ORDER BY updated_at DESC"),
    pool.query("SELECT * FROM boxes ORDER BY updated_at DESC"),
    pool.query("SELECT * FROM alerts ORDER BY created_at DESC"),
    pool.query("SELECT * FROM notifications ORDER BY created_at DESC"),
    pool.query("SELECT * FROM audit_logs ORDER BY created_at DESC"),
    pool.query("SELECT * FROM auth_identities ORDER BY created_at DESC"),
    pool.query("SELECT * FROM login_logs ORDER BY created_at DESC"),
  ]);

  const db = {
    settings: settingsRes[0].reduce((acc, row) => {
      const value = parseJsonValue(row.value, {});
      if (value && typeof value === "object" && !Array.isArray(value)) {
        Object.assign(acc, value);
      } else {
        acc[row.key] = value;
      }
      return acc;
    }, {}),
    users: usersRes[0].map((row) => ({
      id: row.id,
      username: row.username,
      displayName: row.display_name,
      role: row.role,
      active: Boolean(row.active),
      status: row.status || "active",
      approvalStatus: row.status || "active",
      locationId: row.location_id || null,
      createdAt: asIso(row.created_at),
      lastLoginAt: asIso(row.last_login_at),
      deletedAt: asIso(row.deleted_at),
      passwordSalt: row.password_salt,
      passwordHash: row.password_hash,
    })),
    locations: locationsRes[0].map((row) => ({
      id: row.id,
      name: row.name,
      detail: row.detail,
      status: row.status,
      updatedAt: asIso(row.updated_at),
      deletedAt: asIso(row.deleted_at),
    })),
    drugs: drugsRes[0].map((row) => ({
      id: row.id,
      name: row.name,
      unit: row.unit,
      price: row.price,
      category: row.category || 'drug',
      deletedAt: asIso(row.deleted_at),
      updatedAt: asIso(row.updated_at),
    })),
    boxes: boxesRes[0].map((row) => ({
      id: row.id,
      code: row.name,
      locationId: row.location_id,
      value: Number(row.value),
      drugCount: Number(row.drug_count || 0),
      status: row.status,
      updatedAt: asIso(row.updated_at),
      deletedAt: asIso(row.deleted_at),
    })),
    alerts: alertsRes[0].map((row) => ({
      id: row.id,
      boxId: row.box_id,
      drugName: row.drug_name,
      quantity: Number(row.quantity),
      unit: row.unit,
      severity: row.severity,
      status: row.status,
      category: row.category || 'drug',
      expiryDate: row.expiry_date || asIso(row.created_at),
      createdAt: asIso(row.created_at),
      resolvedAt: asIso(row.resolved_at),
    })),
    notifications: notificationsRes[0].map((row) => ({
      id: row.id,
      title: row.title,
      body: row.body,
      severity: row.severity,
      createdAt: asIso(row.created_at),
      targetView: row.target_view,
      readBy: parseJsonValue(row.read_by, []),
    })),
    auditLogs: auditLogsRes[0].map((row) => ({
      id: row.id,
      actorUserId: row.actor_user_id,
      action: row.action,
      entityType: row.entity_type,
      entityId: row.entity_id,
      details: parseJsonValue(row.details, {}),
      createdAt: asIso(row.created_at),
    })),
    authIdentities: authIdentitiesRes[0].map((row) => ({
      id: row.id,
      userId: row.user_id,
      provider: row.provider,
      providerUserId: row.provider_user_id,
      cidHash: row.cid_hash,
      createdAt: asIso(row.created_at),
      updatedAt: asIso(row.updated_at),
    })),
    loginLogs: loginLogsRes[0].map((row) => ({
      id: row.id,
      userId: row.user_id,
      provider: row.provider,
      providerType: row.provider,
      ipAddress: row.ip_address,
      userAgent: row.user_agent,
      status: row.status,
      createdAt: asIso(row.created_at),
    })),
  };

  if (!db.settings.hospitalName) {
    db.settings.hospitalName = defaultHospitalName;
  }

  if (db.users.length === 0) {
    const sourceDb = fs.existsSync(dbFile) ? mapFileDbToMemory(loadDbFromFile()) : seedDb();
    await saveDbToMySql(sourceDb);
    return sourceDb;
  }

  return db;
}

async function saveDbToMySql(db) {
  const deduplicate = (arr, key = "id") => {
    const seen = new Set();
    return arr.filter((item) => {
      const val = item[key];
      if (seen.has(val)) return false;
      seen.add(val);
      return true;
    });
  };
  const usersToSave = deduplicate(db.users || []);
  const locationsToSave = deduplicate(db.locations || []);
  const drugsToSave = deduplicate(db.drugs || []);
  const boxesToSave = deduplicate(db.boxes || []);
  const alertsToSave = deduplicate(db.alerts || []);
  const notificationsToSave = deduplicate(db.notifications || []);
  const auditLogsToSave = deduplicate(db.auditLogs || []);
  const authIdentitiesToSave = deduplicate(db.authIdentities || []);
  const loginLogsToSave = deduplicate(db.loginLogs || []);

  const pool = getMySqlPool();
  const client = await pool.getConnection();
  try {
    await client.beginTransaction();
    // DELETE remains transactional in MySQL. TRUNCATE commits implicitly and
    // briefly exposed empty tables to concurrent API requests during saves.
    await client.query("DELETE FROM login_logs");
    await client.query("DELETE FROM auth_identities");
    await client.query("DELETE FROM audit_logs");
    await client.query("DELETE FROM notifications");
    await client.query("DELETE FROM alerts");
    await client.query("DELETE FROM boxes");
    await client.query("DELETE FROM drugs");
    await client.query("DELETE FROM locations");
    await client.query("DELETE FROM users");
    await client.query("DELETE FROM settings");

    await client.query("INSERT INTO settings (`key`, `value`) VALUES (?, ?)", [
      "hospitalName",
      JSON.stringify(db.settings || { hospitalName: defaultHospitalName }),
    ]);

    for (const user of usersToSave) {
      await client.query(
        `INSERT INTO users (id, username, display_name, role, active, status, location_id, created_at, last_login_at, deleted_at, password_salt, password_hash)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
        [user.id, user.username, user.displayName, user.role, user.active ? 1 : 0, user.status || 'active', user.locationId || null, user.createdAt, user.lastLoginAt || null, user.deletedAt || null, user.passwordSalt, user.passwordHash],
      );
    }

    for (const location of locationsToSave) {
      await client.query(
        `INSERT INTO locations (id, name, detail, status, updated_at, deleted_at)
         VALUES (?, ?, ?, ?, ?, ?)`,
        [location.id, location.name, location.detail || "", location.status, location.updatedAt, location.deletedAt || null],
      );
    }

    for (const drug of drugsToSave) {
      await client.query(
        `INSERT INTO drugs (id, name, unit, price, category, deleted_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?)`,
        [drug.id, drug.name, drug.unit, String(drug.price), drug.category || 'drug', drug.deletedAt || null, drug.updatedAt],
      );
    }

    for (const box of boxesToSave) {
      await client.query(
        `INSERT INTO boxes (id, name, location_id, status, value, drug_count, updated_at, deleted_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
        [box.id, box.code || box.name || "", box.locationId || null, box.status, String(box.value || 0), Number(box.drugCount || 0), box.updatedAt, box.deletedAt || null],
      );
    }

    for (const alert of db.alerts) {
      const box = db.boxes.find((item) => item.id === alert.boxId);
      const location = box ? db.locations.find((item) => item.id === box.locationId) : null;
      await client.query(
        `INSERT INTO alerts (id, box_id, box_name, location_name, drug_name, quantity, unit, severity, status, category, created_at, expiry_date, resolved_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
        [
          alert.id,
          alert.boxId || null,
          box ? box.code : "-",
          location ? location.name : "-",
          alert.drugName,
          String(alert.quantity),
          alert.unit,
          alert.severity,
          alert.status,
          alert.category || 'drug',
          alert.createdAt,
          alert.expiryDate || alert.createdAt,
          alert.resolvedAt || null,
        ],
      );
    }

    for (const notification of notificationsToSave) {
      await client.query(
        `INSERT INTO notifications (id, title, body, severity, created_at, target_view, read_by)
         VALUES (?, ?, ?, ?, ?, ?, ?)`,
        [notification.id, notification.title, notification.body, notification.severity, notification.createdAt, notification.targetView || null, JSON.stringify(notification.readBy || [])],
      );
    }

    for (const log of auditLogsToSave) {
      await client.query(
        `INSERT INTO audit_logs (id, actor_user_id, action, entity_type, entity_id, details, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?)`,
        [log.id, log.actorUserId || null, log.action, log.entityType, log.entityId || null, JSON.stringify(log.details || {}), log.createdAt],
      );
    }

    for (const identity of authIdentitiesToSave) {
      await client.query(
        `INSERT INTO auth_identities (id, user_id, provider, provider_user_id, cid_hash, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?)`,
        [identity.id, identity.userId, identity.provider, identity.providerUserId, identity.cidHash || null, identity.createdAt, identity.updatedAt],
      );
    }

    for (const log of loginLogsToSave) {
      await client.query(
        `INSERT INTO login_logs (id, user_id, provider, ip_address, user_agent, status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?)`,
        [log.id, log.userId || null, log.providerType || log.provider || "local", log.ipAddress || null, log.userAgent || null, log.status || log.result || "unknown", log.createdAt],
      );
    }

    await client.commit();
  } catch (error) {
    await client.rollback();
    throw error;
  } finally {
    client.release();
  }
}

let globalDbCache = null;

async function fetchDb() {
  if (useMySql) {
    return loadDbFromMySql();
  }
  if (!usePostgres) {
    await ensureJsonDb();
    const raw = fs.readFileSync(dbFile, "utf8");
    const db = JSON.parse(raw);
    db.settings ||= { hospitalName: defaultHospitalName };
    db.auditLogs ||= [];
    db.notifications ||= [];
    db.users ||= [];
    db.authIdentities ||= [];
    db.loginLogs ||= [];
    db.locations ||= [];
    db.drugs ||= [];
    db.boxes ||= [];
    db.alerts ||= [];
    return db;
  }

  await ensurePgSchema();
  const pool = getPgPool();
  const [settingsRes, usersRes, locationsRes, drugsRes, boxesRes, alertsRes, notificationsRes, auditLogsRes, authIdentitiesRes, loginLogsRes] = await Promise.all([
    pool.query(`SELECT key, value FROM settings`),
    pool.query(`SELECT * FROM users ORDER BY created_at ASC`),
    pool.query(`SELECT * FROM locations ORDER BY updated_at DESC`),
    pool.query(`SELECT * FROM drugs ORDER BY updated_at DESC`),
    pool.query(`SELECT * FROM boxes ORDER BY updated_at DESC`),
    pool.query(`SELECT * FROM alerts ORDER BY created_at DESC`),
    pool.query(`SELECT * FROM notifications ORDER BY created_at DESC`),
    pool.query(`SELECT * FROM audit_logs ORDER BY created_at DESC`),
    pool.query(`SELECT * FROM auth_identities ORDER BY created_at DESC`),
    pool.query(`SELECT * FROM login_logs ORDER BY created_at DESC`),
  ]);

  const db = {
    settings: settingsRes.rows.reduce((acc, row) => {
      if (row.value && typeof row.value === "object") {
        Object.assign(acc, row.value);
      } else {
        acc[row.key] = row.value;
      }
      return acc;
    }, {}),
    users: usersRes.rows.map((row) => ({
      id: row.id,
      username: row.username,
      displayName: row.display_name,
      role: row.role,
      active: row.active,
      status: row.status || "active",
      approvalStatus: row.status || "active",
      locationId: row.location_id || null,
      createdAt: row.created_at,
      lastLoginAt: row.last_login_at,
      passwordSalt: row.password_salt,
      passwordHash: row.password_hash,
      deletedAt: row.deleted_at || null,
    })),
    locations: locationsRes.rows.map((row) => ({
      id: row.id,
      name: row.name,
      detail: row.detail,
      status: row.status,
      updatedAt: row.updated_at,
    })),
    drugs: drugsRes.rows.map((row) => ({
      id: row.id,
      name: row.name,
      unit: row.unit,
      price: row.price,
      category: row.category || 'drug',
      updatedAt: row.updated_at,
    })),
    boxes: boxesRes.rows.map((row) => ({
      id: row.id,
      code: row.code,
      locationId: row.location_id,
      value: Number(row.value),
      drugCount: Number(row.drug_count || 0),
      status: row.status,
      updatedAt: row.updated_at,
    })),
    alerts: alertsRes.rows.map((row) => ({
      id: row.id,
      boxId: row.box_id,
      drugName: row.drug_name,
      quantity: row.quantity,
      unit: row.unit,
      severity: row.severity,
      status: row.status,
      category: row.category || 'drug',
      expiryDate: row.expiry_date,
      createdAt: row.created_at,
      resolvedAt: row.resolved_at,
    })),
    notifications: notificationsRes.rows.map((row) => ({
      id: row.id,
      title: row.title,
      body: row.body,
      severity: row.severity,
      createdAt: row.created_at,
      targetView: row.target_view,
      readBy: Array.isArray(row.read_by) ? row.read_by : row.read_by || [],
    })),
    auditLogs: auditLogsRes.rows.map((row) => ({
      id: row.id,
      actorUserId: row.actor_user_id,
      action: row.action,
      entityType: row.entity_type,
      entityId: row.entity_id,
      details: row.details || {},
      createdAt: row.created_at,
    })),
    authIdentities: authIdentitiesRes.rows.map((row) => ({
      id: row.id,
      userId: row.user_id,
      provider: row.provider,
      providerUserId: row.provider_user_id,
      cidHash: row.cid_hash,
      createdAt: row.created_at,
      updatedAt: row.updated_at,
    })),
    loginLogs: loginLogsRes.rows.map((row) => ({
      id: row.id,
      userId: row.user_id,
      provider: row.provider,
      providerType: row.provider,
      ipAddress: row.ip_address,
      userAgent: row.user_agent,
      status: row.status,
      createdAt: row.created_at,
    })),
  };

  if (!db.settings.hospitalName) {
    db.settings.hospitalName = defaultHospitalName;
  }
  return db;
}

async function loadDb() {
  if (!globalDbCache) {
    globalDbCache = await fetchDb();
  }
  return globalDbCache;
}

let saveDbQueue = Promise.resolve();
let saveDbTimeout = null;

async function saveDb(db) {
  globalDbCache = db;
  if (!saveDbTimeout) {
    saveDbTimeout = setTimeout(() => {
      saveDbTimeout = null;
      saveDbQueue = saveDbQueue.then(() => saveDbNow(globalDbCache)).catch(err => {
        console.error("Background DB save error:", err);
        return undefined;
      });
    }, 1000); // 1-second debounce
  }
  return Promise.resolve(); // Return instantly for fast API responses
}

async function saveDbNow(db) {
  if (useMySql) {
    await saveDbToMySql(db);
    return;
  }
  if (!usePostgres) {
    fs.writeFileSync(dbFile, JSON.stringify(db, null, 2), "utf8");
    return;
  }

  const pool = getPgPool();
  const client = await pool.connect();
  try {
    await client.query("BEGIN");
    await client.query("TRUNCATE TABLE settings, users, locations, drugs, boxes, alerts, notifications, audit_logs, auth_identities, login_logs RESTART IDENTITY");
    await client.query("INSERT INTO settings (key, value) VALUES ($1, $2)", [
      "hospitalName",
      JSON.stringify(db.settings || { hospitalName: defaultHospitalName }),
    ]);

    for (const user of db.users) {
      await client.query(
        `INSERT INTO users (id, username, display_name, role, active, status, location_id, created_at, last_login_at, deleted_at, password_salt, password_hash)
         VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12)`,
        [user.id, user.username, user.displayName, user.role, user.active, user.status || 'active', user.locationId || null, user.createdAt, user.lastLoginAt || null, user.deletedAt || null, user.passwordSalt, user.passwordHash],
      );
    }

    for (const location of db.locations) {
      await client.query(
        `INSERT INTO locations (id, name, detail, status, updated_at)
         VALUES ($1,$2,$3,$4,$5)`,
        [location.id, location.name, location.detail || "", location.status, location.updatedAt],
      );
    }

    for (const drug of db.drugs) {
      await client.query(
        `INSERT INTO drugs (id, name, unit, price, category, deleted_at, updated_at)
         VALUES ($1,$2,$3,$4,$5,$6,$7)`,
        [drug.id, drug.name, drug.unit, String(drug.price), drug.category || 'drug', drug.deletedAt || null, drug.updatedAt],
      );
    }

    for (const box of db.boxes) {
      await client.query(
        `INSERT INTO boxes (id, code, location_id, value, drug_count, status, updated_at)
         VALUES ($1,$2,$3,$4,$5,$6,$7)`,
        [box.id, box.code, box.locationId, box.value, Number(box.drugCount || 0), box.status, box.updatedAt],
      );
    }

    for (const alert of db.alerts) {
      await client.query(
        `INSERT INTO alerts (id, box_id, drug_name, quantity, unit, severity, status, category, expiry_date, created_at, resolved_at)
         VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11)`,
        [alert.id, alert.boxId, alert.drugName, alert.quantity, alert.unit, alert.severity, alert.status, alert.category || 'drug', alert.expiryDate, alert.createdAt, alert.resolvedAt || null],
      );
    }

    for (const notification of db.notifications) {
      await client.query(
        `INSERT INTO notifications (id, title, body, severity, created_at, target_view, read_by)
         VALUES ($1,$2,$3,$4,$5,$6,$7::jsonb)`,
        [notification.id, notification.title, notification.body, notification.severity, notification.createdAt, notification.targetView, JSON.stringify(notification.readBy || [])],
      );
    }

    for (const log of db.auditLogs || []) {
      await client.query(
        `INSERT INTO audit_logs (id, actor_user_id, action, entity_type, entity_id, details, created_at)
         VALUES ($1,$2,$3,$4,$5,$6::jsonb,$7)`,
        [log.id, log.actorUserId || null, log.action, log.entityType, log.entityId || null, JSON.stringify(log.details || {}), log.createdAt],
      );
    }

    for (const identity of db.authIdentities || []) {
      await client.query(
        `INSERT INTO auth_identities (id, user_id, provider, provider_user_id, cid_hash, created_at, updated_at)
         VALUES ($1,$2,$3,$4,$5,$6,$7)`,
        [identity.id, identity.userId, identity.provider, identity.providerUserId, identity.cidHash || null, identity.createdAt, identity.updatedAt],
      );
    }

    for (const log of db.loginLogs || []) {
      await client.query(
        `INSERT INTO login_logs (id, user_id, provider, ip_address, user_agent, status, created_at)
         VALUES ($1,$2,$3,$4,$5,$6,$7)`,
        [log.id, log.userId || null, log.providerType || log.provider || "local", log.ipAddress || null, log.userAgent || null, log.status || log.result || "unknown", log.createdAt],
      );
    }

    await client.query("COMMIT");
  } catch (error) {
    await client.query("ROLLBACK");
    throw error;
  } finally {
    client.release();
  }
}

function seedDb() {
  const adminPassword = hashPassword("admin123");
  const deptAdminPassword = hashPassword("dept123");
  const pharmacistPassword = hashPassword("pharm123");
  const labPassword = hashPassword("lab123");
  const supplyPassword = hashPassword("supply123");
  const viewerPassword = hashPassword("view123");

  const locations = [
    { id: id("loc"), name: "หอผู้ป่วยในหญิง", detail: "ชั้น 2", status: "ใช้งาน", updatedAt: nowIso() },
    { id: id("loc"), name: "หอผู้ป่วยในชาย", detail: "ชั้น 3", status: "ใช้งาน", updatedAt: nowIso() },
    { id: id("loc"), name: "ห้องหัตถการ เบอร์ 8", detail: "ER", status: "ใช้งาน", updatedAt: nowIso() },
    { id: id("loc"), name: "ตึกฟอกไต", detail: "OPD", status: "ใช้งาน", updatedAt: nowIso() },
    { id: id("loc"), name: "ห้องจ่ายยาผู้ป่วยนอก", detail: "OPD Dispensing", status: "ใช้งาน", updatedAt: nowIso() },
    { id: id("loc"), name: "ห้องฉุกเฉิน", detail: "ER", status: "ใช้งาน", updatedAt: nowIso() },
    { id: id("loc"), name: "คลินิก NCD", detail: "OPD", status: "ใช้งาน", updatedAt: nowIso() },
    { id: id("loc"), name: "ห้องคลอด", detail: "L&D", status: "ใช้งาน", updatedAt: nowIso() },
  ];

  const users = [
    {
      id: id("user"),
      username: "admin",
      displayName: "ผู้ดูแลระบบสูงสุด",
      role: "super_admin",
      active: true,
      createdAt: nowIso(),
      passwordSalt: adminPassword.salt,
      passwordHash: adminPassword.hash,
      locationId: null,
    },
    {
      id: id("user"),
      username: "dept_admin",
      displayName: "แอดมินแผนกฉุกเฉิน",
      role: "dept_admin",
      active: true,
      createdAt: nowIso(),
      passwordSalt: deptAdminPassword.salt,
      passwordHash: deptAdminPassword.hash,
      locationId: locations[5].id, // ห้องฉุกเฉิน
    },
    {
      id: id("user"),
      username: "pharmacist",
      displayName: "เภสัชกรคลังยา",
      role: "pharmacist",
      active: true,
      createdAt: nowIso(),
      passwordSalt: pharmacistPassword.salt,
      passwordHash: pharmacistPassword.hash,
      locationId: locations[4].id, // ห้องจ่ายยาผู้ป่วยนอก
    },
    {
      id: id("user"),
      username: "lab",
      displayName: "นักเทคนิคการแพทย์",
      role: "lab",
      active: true,
      createdAt: nowIso(),
      passwordSalt: labPassword.salt,
      passwordHash: labPassword.hash,
      locationId: locations[2].id, // ห้องหัตถการ เบอร์ 8
    },
    {
      id: id("user"),
      username: "supply",
      displayName: "เจ้าหน้าที่พัสดุ",
      role: "supply",
      active: true,
      createdAt: nowIso(),
      passwordSalt: supplyPassword.salt,
      passwordHash: supplyPassword.hash,
      locationId: locations[4].id,
    },
    {
      id: id("user"),
      username: "viewer",
      displayName: "ผู้เข้าชมหอผู้ป่วยในหญิง",
      role: "viewer",
      active: true,
      createdAt: nowIso(),
      passwordSalt: viewerPassword.salt,
      passwordHash: viewerPassword.hash,
      locationId: locations[0].id, // หอผู้ป่วยในหญิง
    },
  ];

  const drugs = [
    { id: id("drug"), name: "50% Dextrose 50 ml.", unit: "Vial", price: "19.00", category: "drug", updatedAt: nowIso() },
    { id: id("drug"), name: "Diazepam inj. 10mg/2ml.", unit: "Ampule", price: "4.50", category: "drug", updatedAt: nowIso() },
    { id: id("drug"), name: "Adrenaline inj. 1mg/ml", unit: "Ampule", price: "4.25", category: "drug", updatedAt: nowIso() },
    { id: id("drug"), name: "Amiodarone inj. 150mg/3ml.", unit: "Ampule", price: "97.00", category: "drug", updatedAt: nowIso() },
    { id: id("drug"), name: "10% Calcium gluconate", unit: "Ampule", price: "8.25", category: "drug", updatedAt: nowIso() },
    { id: id("drug"), name: "Adenosine inj. 6mg/2ml.", unit: "Vial", price: "515.00", category: "drug", updatedAt: nowIso() },
    { id: id("drug"), name: "50% MgSo4 2 ml.", unit: "Ampule", price: "8.25", category: "drug", updatedAt: nowIso() },
    { id: id("drug"), name: "7.5% Sodium bicarbonate inj", unit: "Ampule", price: "32.00", category: "drug", updatedAt: nowIso() },
    { id: id("drug"), name: "Atropine inj. 0.6mg/ml.", unit: "Ampule", price: "3.00", category: "drug", updatedAt: nowIso() },
    { id: id("drug"), name: "Dopamine inj. 250 mg/10ml.", unit: "Ampule", price: "16.00", category: "drug", updatedAt: nowIso() },
    // Medical supplies
    { id: id("drug"), name: "สายยางดูดเสมหะ (Suction Tube No.14)", unit: "Piece", price: "12.50", category: "medical_supply", updatedAt: nowIso() },
    { id: id("drug"), name: "ถุงมือปราศจากเชื้อ (Sterile Gloves No.7)", unit: "Pair", price: "15.00", category: "medical_supply", updatedAt: nowIso() },
    // Reagents
    { id: id("drug"), name: "น้ำยาตรวจน้ำตาล (Glucose Reagent)", unit: "Bottle", price: "850.00", category: "reagent", updatedAt: nowIso() },
    // Lab test kits
    { id: id("drug"), name: "ชุดตรวจโควิด (ATK Test Kit)", unit: "Box", price: "250.00", category: "lab_test_kit", updatedAt: nowIso() },
  ];

  const boxes = [
    { id: id("box"), code: "5", locationId: locations[4].id, value: 3252.5, drugCount: 2, status: "พร้อมใช้", updatedAt: nowIso() },
    { id: id("box"), code: "3", locationId: locations[4].id, value: 3252.5, drugCount: 10, status: "พร้อมใช้", updatedAt: nowIso() },
    { id: id("box"), code: "4", locationId: locations[5].id, value: 3252.5, drugCount: 1, status: "พร้อมใช้", updatedAt: nowIso() },
    { id: id("box"), code: "2", locationId: locations[4].id, value: 2222.5, drugCount: 5, status: "พร้อมใช้", updatedAt: nowIso() },
  ];

  const alerts = [
    {
      id: id("alert"),
      boxId: boxes[0].id,
      drugName: "Atropine inj. 0.6mg/ml.",
      quantity: 2,
      unit: "ampules",
      severity: "warning",
      status: "ใกล้หมดอายุ",
      category: "drug",
      expiryDate: "2569-07-25",
      createdAt: nowIso(),
      resolvedAt: null,
    },
    {
      id: id("alert"),
      boxId: boxes[1].id,
      drugName: "ถุงมือปราศจากเชื้อ (Sterile Gloves No.7)",
      quantity: 10,
      unit: "pairs",
      severity: "danger",
      status: "หมดอายุ",
      category: "medical_supply",
      expiryDate: "2569-05-10",
      createdAt: nowIso(),
      resolvedAt: null,
    },
    {
      id: id("alert"),
      boxId: boxes[2].id,
      drugName: "น้ำยาตรวจน้ำตาล (Glucose Reagent)",
      quantity: 1,
      unit: "bottle",
      severity: "warning",
      status: "ใกล้หมดอายุ",
      category: "reagent",
      expiryDate: "2569-08-15",
      createdAt: nowIso(),
      resolvedAt: null,
    },
    {
      id: id("alert"),
      boxId: boxes[3].id,
      drugName: "ชุดตรวจโควิด (ATK Test Kit)",
      quantity: 5,
      unit: "boxes",
      severity: "danger",
      status: "หมดอายุ",
      category: "lab_test_kit",
      expiryDate: "2569-06-01",
      createdAt: nowIso(),
      resolvedAt: null,
    },
  ];

  const notifications = buildNotifications({ users, locations, drugs, boxes, alerts });

  return {
    users,
    locations,
    drugs,
    boxes,
    alerts,
    notifications,
    auditLogs: [],
    authIdentities: [],
    loginLogs: [],
    settings: {
      hospitalName: defaultHospitalName,
    },
  };
}

function publicUser(user) {
  return {
    id: user.id,
    username: user.username,
    displayName: user.displayName,
    role: user.role,
    active: user.active,
    status: user.status || user.approvalStatus || "active",
    approvalStatus: user.approvalStatus || user.status || "active",
    locationId: user.locationId || null,
    createdAt: user.createdAt,
  };
}

function getUserById(db, userId) {
  return db.users.find((user) => user.id === userId);
}

function getUserFromRequest(req, db, options = {}) {
  const cookies = parseCookies(req.headers.cookie || "");
  const payload = verifyToken(cookies.mbm_token);
  if (!payload) return null;
  const user = getUserById(db, payload.sub);
  if (!user || !user.active) return null;
  const approvalStatus = user.approvalStatus || user.status || "active";
  if (!options.allowPending && approvalStatus !== "active") return null;
  return user;
}

function requireAuth(req, res, db) {
  const user = getUserFromRequest(req, db);
  if (!user) {
    sendJson(res, 401, { error: "Unauthorized" });
    return null;
  }
  return user;
}

function requireRole(user, roles) {
  return roles.includes(user.role);
}

function buildLocationMap(db) {
  return Object.fromEntries(db.locations.map((item) => [item.id, item]));
}

function buildBoxMap(db) {
  return Object.fromEntries(db.boxes.map((item) => [item.id, item]));
}

function buildNotifications(db) {
  const notifications = [];
  const locationMap = buildLocationMap(db);
  const boxMap = buildBoxMap(db);

  db.alerts
    .filter((alert) => !alert.resolvedAt)
    .forEach((alert) => {
      const box = boxMap[alert.boxId];
      if (!box) return;
      const location = locationMap[box.locationId];
      notifications.push({
        id: alert.id,
        title: alert.status,
        body: `${alert.drugName} ในกล่อง ${box.code}${location ? ` - ${location.name}` : ""}`,
        severity: alert.severity,
        createdAt: alert.createdAt,
        targetView: "alerts",
        readBy: alert.readBy || [],
      });
    });

  return notifications;
}

function ensureNotifications(db) {
  const current = buildNotifications(db);
  const map = new Map(db.notifications.map((item) => [item.id, item]));
  current.forEach((item) => {
    const existing = map.get(item.id);
    if (existing) {
      existing.title = item.title;
      existing.body = item.body;
      existing.severity = item.severity;
      existing.createdAt = item.createdAt;
      existing.targetView = item.targetView;
      if (!Array.isArray(existing.readBy)) existing.readBy = [];
    } else {
      db.notifications.unshift({ ...item, readBy: [] });
    }
  });
  const currentIds = new Set(current.map((item) => item.id));
  db.notifications = db.notifications.filter((item) => currentIds.has(item.id) || item.readBy?.length);
  db.notifications.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
}

function getFilteredDbForUser(db, user) {
  if (!user || user.role === "super_admin") {
    const activeBoxes = db.boxes.filter(b => !b.deletedAt);
    const activeBoxIds = new Set(activeBoxes.map(b => b.id));
    return { ...db, boxes: activeBoxes, alerts: db.alerts.filter(a => activeBoxIds.has(a.boxId)) };
  }

  const role = user.role;
  const locId = user.locationId;

  // Filter boxes first
  let filteredBoxes = db.boxes.filter(b => !b.deletedAt);
  if (role === "dept_admin" || role === "viewer") {
    filteredBoxes = filteredBoxes.filter(b => b.locationId === locId);
  } else if (role === "pharmacist") {
    filteredBoxes = filteredBoxes.filter(b => b.locationId === locId || db.alerts.some(a => a.boxId === b.id && a.category === "drug"));
  } else if (role === "lab") {
    filteredBoxes = filteredBoxes.filter(b => db.alerts.some(a => a.boxId === b.id && (a.category === "reagent" || a.category === "lab_test_kit")));
  } else if (role === "supply") {
    filteredBoxes = filteredBoxes.filter(b => db.alerts.some(a => a.boxId === b.id && a.category === "medical_supply"));
  }

  const allowedBoxIds = new Set(filteredBoxes.map(b => b.id));

  // Filter alerts
  let filteredAlerts = db.alerts.filter(a => allowedBoxIds.has(a.boxId));
  if (role === "pharmacist") {
    filteredAlerts = filteredAlerts.filter(a => a.category === "drug" || (a.category === "medical_supply" && db.boxes.find(b => b.id === a.boxId)?.locationId === locId));
  } else if (role === "lab") {
    filteredAlerts = filteredAlerts.filter(a => a.category === "reagent" || a.category === "lab_test_kit");
  } else if (role === "supply") {
    filteredAlerts = filteredAlerts.filter(a => a.category === "medical_supply");
  }

  return {
    ...db,
    boxes: filteredBoxes,
    alerts: filteredAlerts,
  };
}

function computeDashboard(db, user) {
  const filteredDb = getFilteredDbForUser(db, user);
  const locationMap = buildLocationMap(filteredDb);
  const boxMap = buildBoxMap(filteredDb);
  const unreadNotifications = filteredDb.notifications.filter((n) => !(n.readBy || []).includes(user.id));
  const latestAlerts = filteredDb.alerts
    .filter((alert) => !alert.resolvedAt)
    .slice(0, 5)
    .map((alert) => {
      const box = boxMap[alert.boxId];
      const location = box ? locationMap[box.locationId] : null;
      return {
        box: box ? box.code : "-",
        location: location ? location.name : "-",
        drug: alert.drugName,
        qty: alert.quantity,
        status: alert.status,
        date: formatThaiDate(alert.expiryDate),
      };
    });

  const totalValue = filteredDb.boxes.reduce((sum, item) => sum + Number(item.value || 0), 0);
  const expiringSoon = filteredDb.alerts.filter((item) => item.severity === "warning" && !item.resolvedAt).length;
  const expired = filteredDb.alerts.filter((item) => item.severity === "danger" && !item.resolvedAt).length;
  const atRiskBoxes = new Set(filteredDb.alerts.filter((item) => !item.resolvedAt).map((item) => item.boxId)).size;

  return {
    metrics: [
      { label: "มูลค่าทั้งหมด", value: formatMoney(totalValue), meta: `รวม ${filteredDb.drugs.length} รายการ`, tone: "blue", icon: "money" },
      { label: "มูลค่าใกล้หมดอายุ", value: formatMoney(sumWarningValue(filteredDb)), meta: `${expiringSoon} รายการ`, tone: "amber", icon: "hourglass" },
      { label: "รายการหมดอายุ", value: String(expired), meta: "นับจากวันปัจจุบัน", tone: "red", icon: "alert" },
      { label: "มูลค่าแยกตามกล่องฉุกเฉิน", value: String(atRiskBoxes), meta: `กล่องยาฉุกเฉินมูลค่าสูงสุด ${formatMoney(Math.max(...filteredDb.boxes.map((b) => b.value), 0))}`, tone: "green", icon: "box" },
    ],
    boxSummary: filteredDb.boxes.map((box) => {
      const location = locationMap[box.locationId];
      return {
        id: box.code,
        name: location ? location.name : "-",
        value: formatMoney(box.value),
        status: box.status,
      };
    }),
    latestAlerts,
    quickActions: [
      { label: "ดูกล่องยาฉุกเฉิน", icon: "box", action: "boxes", primary: true },
      { label: "จัดการสถานที่", icon: "pin", action: "locations", primary: false },
      { label: "ดูแจ้งเตือน", icon: "bell", action: "alerts", primary: false },
    ],
    unreadNotifications,
  };
}

function sumWarningValue(db) {
  const warningBoxIds = new Set(db.alerts.filter((item) => item.severity === "warning" && !item.resolvedAt).map((item) => item.boxId));
  return db.boxes.filter((box) => warningBoxIds.has(box.id)).reduce((sum, item) => sum + Number(item.value || 0), 0);
}

function formatMoney(amount) {
  return `฿${Number(amount || 0).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function formatThaiDate(isoDate) {
  const date = new Date(isoDate);
  return Number.isNaN(date.getTime()) ? isoDate : date.toLocaleDateString("th-TH");
}

function sendCookie(req, token) {
  const forwardedProto = String(req.headers["x-forwarded-proto"] || "").split(",")[0].trim();
  const secureEnabled = process.env.MBM_COOKIE_SECURE === "true" || forwardedProto === "https";
  const secure = secureEnabled ? "; Secure" : "";
  const cookie = `mbm_token=${encodeURIComponent(token)}; HttpOnly; Path=/; SameSite=Lax${secure}`;
  return cookie;
}

function recordLoginLog(db, userIdOrEntry, provider, ipAddress, userAgent, status) {
  db.loginLogs = db.loginLogs || [];
  const entry =
    userIdOrEntry && typeof userIdOrEntry === "object" && !Array.isArray(userIdOrEntry)
      ? userIdOrEntry
      : {
          userId: userIdOrEntry || null,
          providerType: provider || "local",
          ipAddress: ipAddress || null,
          userAgent: userAgent || null,
          result: status || "unknown",
          action: "password_login",
          details: {},
        };
  db.loginLogs.unshift({
    id: id("login"),
    providerType: entry.providerType || entry.provider || "local",
    provider: entry.provider || entry.providerType || "local",
    userId: entry.userId || null,
    identityId: entry.identityId || null,
    action: entry.action || "password_login",
    result: entry.result || entry.status || "unknown",
    errorMessage: entry.errorMessage || null,
    ipAddress: entry.ipAddress || null,
    userAgent: entry.userAgent || null,
    details: entry.details || {},
    createdAt: nowIso(),
  });
}

function recordAudit(db, actorUserId, action, entityType, entityId, details = {}) {
  db.auditLogs = db.auditLogs || [];
  db.auditLogs.unshift({
    id: id("audit"),
    actorUserId: actorUserId || null,
    action,
    entityType,
    entityId: entityId || null,
    details,
    createdAt: nowIso(),
  });
}

const sseClients = new Set();

function broadcast(event, data = {}) {
  const payload = `event: ${event}\ndata: ${JSON.stringify(data)}\n\n`;
  for (const res of sseClients) {
    res.write(payload);
  }
}

async function handleApi(req, res, pathname) {
  const db = await loadDb();
  if (usePostgres && (!db.users || db.users.length === 0)) {
    const seeded = seedDb();
    await saveDb(seeded);
    return handleApi(req, res, pathname);
  }
  ensureNotifications(db);

  if (pathname === "/api/auth/me" && req.method === "GET") {
    const user = getUserFromRequest(req, db, { allowPending: true });
    if (!user) {
      sendJson(res, 200, null);
      return;
    }
    const unreadCount = db.notifications.filter((item) => !(item.readBy || []).includes(user.id)).length;
    sendJson(res, 200, { ...publicUser(user), unreadCount, settings: db.settings });
    return;
  }

  if (pathname === "/api/auth/provider_id/start" && req.method === "GET") {
    await startExternalLogin(req, res, db, "provider_id");
    return;
  }

  if (pathname === "/api/auth/provider_id/callback" && req.method === "GET") {
    await finishExternalLogin(req, res, db, "provider_id");
    return;
  }

  if (pathname === "/api/auth/providerid/start" && req.method === "GET") {
    await startExternalLogin(req, res, db, "provider_id");
    return;
  }

  if (pathname === "/api/auth/providerid/callback" && req.method === "GET") {
    await finishExternalLogin(req, res, db, "provider_id");
    return;
  }

  if (pathname === "/api/auth/thaid/start" && req.method === "GET") {
    await startExternalLogin(req, res, db, "thaid");
    return;
  }

  if (pathname === "/api/auth/thaid/callback" && req.method === "GET") {
    await finishExternalLogin(req, res, db, "thaid");
    return;
  }

  if (pathname === '/api/auth/login' && req.method === 'POST') {
    const ipAddress = req.headers["x-forwarded-for"] || req.socket.remoteAddress;
    const userAgent = req.headers["user-agent"];
    readBody(req)
      .then(async (body) => {
        const { username, password } = body || {};
        if (!username || !password) {
          sendJson(res, 400, { error: "กรุณากรอกชื่อผู้ใช้และรหัสผ่าน" });
          return;
        }
        const user = db.users.find((item) => item.username === username && item.active);
        const approvalStatus = user ? (user.approvalStatus || user.status || "active") : null;
        if (!user || approvalStatus !== "active") {
          recordLoginLog(db, user ? user.id : null, "local", ipAddress, userAgent, "failed");
          await saveDb(db);
          sendJson(res, 401, { error: "ไม่พบผู้ใช้หรือบัญชีถูกปิดใช้งาน" });
          return;
        }
        const computed = hashPassword(password, user.passwordSalt);
        if (computed.hash !== user.passwordHash) {
          recordLoginLog(db, user.id, "local", ipAddress, userAgent, "failed");
          await saveDb(db);
          sendJson(res, 401, { error: "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง" });
          return;
        }
        recordLoginLog(db, user.id, "local", ipAddress, userAgent, "success");
        const token = signToken({ sub: user.id, role: user.role, exp: Date.now() + 1000 * 60 * 60 * 8 });
        user.lastLoginAt = nowIso();
        recordAudit(db, user.id, "auth.login", "user", user.id, { username: user.username, role: user.role });
        await saveDb(db);
        sendJson(
          res,
          200,
          {
            user: publicUser(user),
            unreadCount: db.notifications.filter((item) => !(item.readBy || []).includes(user.id)).length,
          },
          { "Set-Cookie": sendCookie(req, token) },
        );
      })
      .catch((error) => sendJson(res, 400, { error: error.message }));
    return;
  }

  if (pathname === "/api/auth/register" && req.method === "POST") {
    readBody(req)
      .then(async (body) => {
        const { providerId, displayName, password } = body || {};
        if (!providerId || !displayName || !password) {
          sendJson(res, 400, { error: "กรุณากรอกข้อมูลให้ครบถ้วน" });
          return;
        }
        if (db.users.some((item) => item.username === providerId)) {
          sendJson(res, 409, { error: "Provider ID นี้ได้รับการลงทะเบียนแล้ว" });
          return;
        }
        const pw = hashPassword(password);
        const user = {
          id: id("user"),
          username: providerId,
          displayName: displayName,
          role: "viewer",
          active: true,
          status: "pending",
          approvalStatus: "pending",
          createdAt: nowIso(),
          lastLoginAt: nowIso(),
          passwordSalt: pw.salt,
          passwordHash: pw.hash,
        };
        db.users.unshift(user);
        recordAudit(db, user.id, "auth.register", "user", user.id, { username: user.username, role: user.role });
        await saveDb(db);

        sendJson(
          res,
          202,
          {
            user: publicUser(user),
            unreadCount: 0,
            pendingApproval: true,
          },
          { "Set-Cookie": sendCookie(req, signToken({ sub: user.id, role: user.role, exp: Date.now() + 1000 * 60 * 60 * 8 })) },
        );
      })
      .catch((error) => sendJson(res, 400, { error: error.message }));
    return;
  }


  if (pathname === "/api/auth/logout" && req.method === "POST") {
    const user = getUserFromRequest(req, db, { allowPending: true });
    if (user) {
      recordAudit(db, user.id, "auth.logout", "session", user.id, {});
      await saveDb(db);
    }
    sendJson(res, 200, { ok: true }, {
      "Set-Cookie": "mbm_token=; HttpOnly; Path=/; SameSite=Lax; Max-Age=0",
    });
    return;
  }

  const authedUser = requireAuth(req, res, db);
  if (!authedUser) return;

  if (pathname === "/api/dashboard" && req.method === "GET") {
    const dashboard = computeDashboard(db, authedUser);
    const notifications = db.notifications.map((item) => ({
      id: item.id,
      title: item.title,
      body: item.body,
      severity: item.severity,
      createdAt: item.createdAt,
      readBy: item.readBy || [],
      targetView: item.targetView,
    }));
    sendJson(res, 200, { dashboard, notifications, settings: db.settings });
    return;
  }

  if (pathname === "/api/locations" && req.method === "GET") {
    if (authedUser.role === "dept_admin" || authedUser.role === "viewer") {
      sendJson(res, 200, db.locations.filter(loc => loc.id === authedUser.locationId && !loc.deletedAt));
    } else {
      sendJson(res, 200, db.locations.filter(loc => !loc.deletedAt));
    }
    return;
  }

  if (pathname === "/api/locations" && req.method === "POST") {
    if (!requireRole(authedUser, ["super_admin"])) {
      sendJson(res, 403, { error: "Forbidden" });
      return;
    }
    readBody(req)
      .then(async (body) => {
        if (!body?.name) {
          sendJson(res, 400, { error: "ชื่อสถานที่จำเป็น" });
          return;
        }
        db.locations.unshift({
          id: id("loc"),
          name: body.name,
          detail: body.detail || "",
          status: body.status || "ใช้งาน",
          updatedAt: nowIso(),
        });
        recordAudit(db, authedUser.id, "location.create", "location", db.locations[0].id, { name: body.name, detail: body.detail || "" });
        await saveDb(db);
        broadcast("refresh", { view: "locations" });
        sendJson(res, 201, db.locations[0]);
      })
      .catch((error) => sendJson(res, 400, { error: error.message }));
    return;
  }

  if (pathname.startsWith("/api/locations/") && req.method === "PATCH") {
    if (!requireRole(authedUser, ["super_admin"])) {
      sendJson(res, 403, { error: "Forbidden" });
      return;
    }
    const locationId = pathname.split("/")[3];
    const location = db.locations.find((item) => item.id === locationId);
    if (!location) {
      sendJson(res, 404, { error: "Location not found" });
      return;
    }
    readBody(req)
      .then(async (body) => {
        if (!body?.name) {
          sendJson(res, 400, { error: "ชื่อสถานที่จำเป็น" });
          return;
        }
        const previous = { ...location };
        location.name = body.name;
        location.detail = body.detail || "";
        location.status = body.status || location.status;
        location.updatedAt = nowIso();
        recordAudit(db, authedUser.id, "location.update", "location", location.id, { before: previous, after: location });
        await saveDb(db);
        broadcast("refresh", { view: "locations" });
        sendJson(res, 200, location);
      })
      .catch((error) => sendJson(res, 400, { error: error.message }));
    return;
  }

  if (pathname.startsWith("/api/locations/") && req.method === "DELETE") {
    if (!requireRole(authedUser, ["super_admin"])) {
      sendJson(res, 403, { error: "Forbidden" });
      return;
    }
    const locationId = pathname.split("/")[3];
    const location = db.locations.find((item) => item.id === locationId);
    if (!location) {
      sendJson(res, 404, { error: "Location not found" });
      return;
    }
    location.deletedAt = nowIso();
    location.updatedAt = nowIso();
    recordAudit(db, authedUser.id, "location.delete", "location", location.id, { name: location.name });
    saveDb(db).then(() => {
      broadcast("refresh", { view: "locations" });
      sendJson(res, 200, { ok: true });
    }).catch((error) => sendJson(res, 400, { error: error.message }));
    return;
  }

  if (pathname === "/api/boxes" && req.method === "GET") {
    const activeBoxes = db.boxes.filter(box => !box.deletedAt).map(box => ({
      ...box,
      name: box.code || box.name || "",
    }));
    sendJson(res, 200, activeBoxes);
    return;
  }

  if (pathname === "/api/boxes" && req.method === "POST") {
    if (!requireRole(authedUser, ["super_admin", "pharmacist"])) {
      sendJson(res, 403, { error: "Forbidden" });
      return;
    }
    readBody(req)
      .then(async (body) => {
        if (!body?.name) {
          sendJson(res, 400, { error: "กรุณาระบุชื่อกล่องยา" });
          return;
        }
        if (db.boxes.some((box) => !box.deletedAt && (box.code || "").trim() === body.name.trim())) {
          sendJson(res, 409, { error: "ชื่อกล่องยานี้มีอยู่แล้ว" });
          return;
        }
        const newBox = {
          id: id("box"),
          code: body.name,
          locationId: body.locationId || null,
          status: body.status || "พร้อมใช้งาน",
          value: 0,
          drugCount: Math.max(0, Number.parseInt(body.drugCount, 10) || 0),
          updatedAt: nowIso(),
        };
        db.boxes.unshift(newBox);
        recordAudit(db, authedUser.id, "box.create", "box", newBox.id, { code: newBox.code });
        await saveDb(db);
        broadcast("refresh", { view: "boxes" });
        sendJson(res, 201, { ...newBox, name: newBox.code });
      })
      .catch((error) => sendJson(res, 400, { error: error.message }));
    return;
  }

  if (pathname.startsWith("/api/boxes/") && req.method === "PATCH") {
    if (!requireRole(authedUser, ["super_admin", "pharmacist"])) {
      sendJson(res, 403, { error: "Forbidden" });
      return;
    }
    const boxId = pathname.split("/")[3];
    const box = db.boxes.find((item) => item.id === boxId);
    if (!box) {
      sendJson(res, 404, { error: "Box not found" });
      return;
    }
    readBody(req)
      .then(async (body) => {
        const previous = { ...box };
        if (body?.name !== undefined) box.code = body.name;
        if (body?.locationId !== undefined) box.locationId = body.locationId || null;
        if (body?.status !== undefined) box.status = body.status;
        if (body?.drugCount !== undefined) {
          const drugCount = Number.parseInt(body.drugCount, 10);
          if (!Number.isInteger(drugCount) || drugCount < 0) {
            sendJson(res, 400, { error: "จำนวนเม็ดยาต้องเป็นจำนวนเต็มตั้งแต่ 0 ขึ้นไป" });
            return;
          }
          box.drugCount = drugCount;
        }
        box.updatedAt = nowIso();
        recordAudit(db, authedUser.id, "box.update", "box", box.id, { before: previous, after: box });
        await saveDb(db);
        broadcast("refresh", { view: "boxes" });
        sendJson(res, 200, { ...box, name: box.code });
      })
      .catch((error) => sendJson(res, 400, { error: error.message }));
    return;
  }

  if (pathname.startsWith("/api/boxes/") && req.method === "DELETE") {
    if (!requireRole(authedUser, ["super_admin", "pharmacist"])) {
      sendJson(res, 403, { error: "Forbidden" });
      return;
    }
    const boxId = pathname.split("/")[3];
    const box = db.boxes.find((item) => item.id === boxId);
    if (!box) {
      sendJson(res, 404, { error: "Box not found" });
      return;
    }
    box.deletedAt = nowIso();
    box.updatedAt = nowIso();
    recordAudit(db, authedUser.id, "box.delete", "box", box.id, { code: box.code });
    saveDb(db).then(() => {
      broadcast("refresh", { view: "boxes" });
      sendJson(res, 200, { ok: true });
    }).catch((error) => sendJson(res, 400, { error: error.message }));
    return;
  }

  if (pathname === "/api/drugs" && req.method === "GET") {
    sendJson(res, 200, db.drugs.filter((drug) => !drug.deletedAt));
    return;
  }

  if (pathname === "/api/drugs" && req.method === "POST") {
    readBody(req)
      .then(async (body) => {
        if (!body?.name || !body?.unit || !body?.price) {
          sendJson(res, 400, { error: "ข้อมูลยาไม่ครบ" });
          return;
        }
        const category = body.category || "drug";
        const role = authedUser.role;
        const allowed =
          role === "super_admin" ||
          (role === "pharmacist" && ["drug", "medical_supply"].includes(category)) ||
          (role === "lab" && ["reagent", "lab_test_kit"].includes(category)) ||
          (role === "supply" && ["medical_supply"].includes(category));

        if (!allowed) {
          sendJson(res, 403, { error: "คุณไม่มีสิทธิ์เพิ่มรายการในหมวดหมู่นี้" });
          return;
        }

        db.drugs.unshift({
          id: id("drug"),
          name: body.name,
          unit: body.unit,
          price: String(body.price).replace(/[^\d.]/g, ""),
          category: category,
          updatedAt: nowIso(),
        });
        recordAudit(db, authedUser.id, "drug.create", "drug", db.drugs[0].id, { name: body.name, unit: body.unit, price: String(body.price).replace(/[^\d.]/g, ""), category });
        await saveDb(db);
        broadcast("refresh", { view: "drugs" });
        sendJson(res, 201, db.drugs[0]);
      })
      .catch((error) => sendJson(res, 400, { error: error.message }));
    return;
  }

  if (pathname.startsWith("/api/drugs/") && req.method === "PATCH") {
    const drugId = pathname.split("/")[3];
    const drug = db.drugs.find((item) => item.id === drugId);
    if (!drug) {
      sendJson(res, 404, { error: "Drug not found" });
      return;
    }
    readBody(req)
      .then(async (body) => {
        if (!body?.name || !body?.unit || !body?.price) {
          sendJson(res, 400, { error: "ข้อมูลยาไม่ครบ" });
          return;
        }
        const newCategory = body.category || drug.category || "drug";
        const role = authedUser.role;

        const canEditCategory = (cat) =>
          role === "super_admin" ||
          (role === "pharmacist" && ["drug", "medical_supply"].includes(cat)) ||
          (role === "lab" && ["reagent", "lab_test_kit"].includes(cat)) ||
          (role === "supply" && ["medical_supply"].includes(cat));

        if (!canEditCategory(drug.category) || !canEditCategory(newCategory)) {
          sendJson(res, 403, { error: "คุณไม่มีสิทธิ์แก้ไขรายการในหมวดหมู่นี้" });
          return;
        }

        const previous = { ...drug };
        drug.name = body.name;
        drug.unit = body.unit;
        drug.price = String(body.price).replace(/[^\d.]/g, "");
        drug.category = newCategory;
        drug.updatedAt = nowIso();
        recordAudit(db, authedUser.id, "drug.update", "drug", drug.id, { before: previous, after: drug });
        await saveDb(db);
        broadcast("refresh", { view: "drugs" });
        sendJson(res, 200, drug);
      })
      .catch((error) => sendJson(res, 400, { error: error.message }));
    return;
  }

  if (pathname.startsWith("/api/drugs/") && req.method === "DELETE") {
    const drugId = pathname.split("/")[3];
    const drug = db.drugs.find((item) => item.id === drugId);
    if (!drug) {
      sendJson(res, 404, { error: "Drug not found" });
      return;
    }
    const role = authedUser.role;
    const canDeleteCategory = (cat) =>
      role === "super_admin" ||
      (role === "pharmacist" && ["drug", "medical_supply"].includes(cat)) ||
      (role === "lab" && ["reagent", "lab_test_kit"].includes(cat)) ||
      (role === "supply" && ["medical_supply"].includes(cat));

    if (!canDeleteCategory(drug.category || "drug")) {
      sendJson(res, 403, { error: "Forbidden" });
      return;
    }
    drug.deletedAt = nowIso();
    drug.updatedAt = nowIso();
    recordAudit(db, authedUser.id, "drug.delete", "drug", drug.id, { name: drug.name, category: drug.category || "drug" });
    saveDb(db).then(() => {
      broadcast("refresh", { view: "drugs" });
      sendJson(res, 200, { ok: true });
    }).catch((error) => sendJson(res, 400, { error: error.message }));
    return;
  }


  if (pathname === "/api/alerts" && req.method === "GET") {
    const filteredDb = getFilteredDbForUser(db, authedUser);
    const locationMap = buildLocationMap(filteredDb);
    const boxMap = buildBoxMap(filteredDb);
    const alerts = filteredDb.alerts
      .filter((alert) => !alert.resolvedAt)
      .map((alert) => {
        const box = boxMap[alert.boxId];
        const location = box ? locationMap[box.locationId] : null;
        return {
          id: alert.id,
          boxName: box ? `${box.code} - ${location ? location.name : "-"}` : "-",
          locationId: location ? location.id : null,
          drugName: alert.drugName,
          quantity: alert.quantity,
          unit: alert.unit,
          severity: alert.severity,
          status: alert.status,
          category: alert.category || "drug",
          expiryDate: alert.expiryDate,
        };
      });
    const notifications = filteredDb.notifications.map((item) => ({
      id: item.id,
      title: item.title,
      body: item.body,
      severity: item.severity,
      createdAt: item.createdAt,
      readBy: item.readBy || [],
      targetView: item.targetView,
    }));
    sendJson(res, 200, { alerts, notifications });
    return;
  }

  if (pathname.startsWith("/api/alerts/") && pathname.endsWith("/resolve") && req.method === "POST") {
    const alertId = pathname.split("/")[3];
    const alert = db.alerts.find((item) => item.id === alertId);
    if (!alert) {
      sendJson(res, 404, { error: "Alert not found" });
      return;
    }

    const canResolve = (user, alertItem) => {
      if (user.role === "super_admin") return true;
      if (user.role === "viewer") return false;

      const box = db.boxes.find(b => b.id === alertItem.boxId);
      const locId = box ? box.locationId : null;

      if (user.role === "dept_admin") {
        return locId === user.locationId;
      }
      if (user.role === "pharmacist") {
        if (alertItem.category === "drug") return true;
        if (alertItem.category === "medical_supply") return locId === user.locationId;
      }
      if (user.role === "lab") {
        return alertItem.category === "reagent" || alertItem.category === "lab_test_kit";
      }
      if (user.role === "supply") {
        return alertItem.category === "medical_supply";
      }
      return false;
    };

    if (!canResolve(authedUser, alert)) {
      sendJson(res, 403, { error: "Forbidden" });
      return;
    }

    alert.resolvedAt = nowIso();
    alert.status = "ปิดรายการแล้ว";
    recordAudit(db, authedUser.id, "alert.resolve", "alert", alert.id, { drugName: alert.drugName, boxId: alert.boxId });
    await saveDb(db);
    broadcast("refresh", { view: "alerts" });
    sendJson(res, 200, { ok: true });
    return;
  }

  if (pathname === "/api/settings" && req.method === "POST") {
    if (!requireRole(authedUser, ["super_admin"])) {
      sendJson(res, 403, { error: "Forbidden" });
      return;
    }
    readBody(req)
      .then(async (body) => {
        const { hospitalName, telegramBotToken, telegramChatId } = body || {};
        if (!hospitalName) {
          sendJson(res, 400, { error: "กรุณาระบุชื่อโรงพยาบาล" });
          return;
        }
        db.settings = {
          hospitalName,
          telegramBotToken: telegramBotToken || "",
          telegramChatId: telegramChatId || "",
        };
        recordAudit(db, authedUser.id, "settings.update", "settings", null, db.settings);
        await saveDb(db);
        broadcast("refresh", { view: "settings" });
        sendJson(res, 200, { ok: true });
      })
      .catch((error) => sendJson(res, 400, { error: error.message }));
    return;
  }

  if (pathname === "/api/notifications" && req.method === "GET") {
    sendJson(
      res,
      200,
      {
        notifications: db.notifications.map((item) => ({
          id: item.id,
          title: item.title,
          body: item.body,
          severity: item.severity,
          createdAt: item.createdAt,
          readBy: item.readBy || [],
          targetView: item.targetView,
        })),
      },
    );
    return;
  }

  if (pathname === "/api/notifications/read-all" && req.method === "POST") {
    db.notifications.forEach((item) => {
      item.readBy = Array.from(new Set([...(item.readBy || []), authedUser.id]));
    });
    recordAudit(db, authedUser.id, "notification.read_all", "notification", null, {});
    await saveDb(db);
    broadcast("notification", { userId: authedUser.id });
    sendJson(res, 200, { ok: true });
    return;
  }

  if (pathname === "/api/reports/usage" && req.method === "GET") {
    const usage = [
      {
        kind: "good",
        title: "เดือนนี้ใช้ Atropine 2 ampules",
        text: "มีการเบิกจากห้องจ่ายยาผู้ป่วยนอก 1 ครั้ง และห้องฉุกเฉิน 1 ครั้ง",
      },
      {
        kind: "warn",
        title: "ไม่มีรายการขาดสต็อก",
        text: "สถานะคลังโดยรวมยังพร้อมใช้งานในทุกหน่วยงานที่กำหนดไว้",
      },
    ];
    sendJson(res, 200, {
      usage,
      summary: "แสดงมูลค่าคงเหลือและรายการที่ต้องตรวจรอบถัดไป",
    });
    return;
  }

  if (pathname === "/api/users" && req.method === "GET") {
    if (!requireRole(authedUser, ["super_admin"])) {
      sendJson(res, 403, { error: "Forbidden" });
      return;
    }
    sendJson(res, 200, db.users.filter((u) => !u.deletedAt).map(publicUser));
    return;
  }

  if (pathname === "/api/users" && req.method === "POST") {
    if (!requireRole(authedUser, ["super_admin"])) {
      sendJson(res, 403, { error: "Forbidden" });
      return;
    }
    readBody(req)
      .then(async (body) => {
        if (!body?.username || !body?.displayName || !body?.role || !body?.password) {
          sendJson(res, 400, { error: "ข้อมูลผู้ใช้ไม่ครบ" });
          return;
        }
        if (db.users.some((user) => user.username === body.username)) {
          sendJson(res, 409, { error: "username ซ้ำ" });
          return;
        }
        const password = hashPassword(body.password);
        const user = {
          id: id("user"),
          username: body.username,
          displayName: body.displayName,
          role: body.role,
          active: true,
          locationId: body.locationId || null,
          createdAt: nowIso(),
          passwordSalt: password.salt,
          passwordHash: password.hash,
        };
        db.users.unshift(user);
        recordAudit(db, authedUser.id, "user.create", "user", user.id, { username: user.username, role: user.role, active: user.active });
        await saveDb(db);
        sendJson(res, 201, publicUser(user));
      })
      .catch((error) => sendJson(res, 400, { error: error.message }));
    return;
  }

  if (pathname.startsWith("/api/users/") && pathname.endsWith("/approve") && req.method === "PATCH") {
    if (!requireRole(authedUser, ["super_admin"])) {
      sendJson(res, 403, { error: "Forbidden" });
      return;
    }
    const userId = pathname.split("/")[3];
    const target = db.users.find((user) => user.id === userId);
    if (!target) {
      sendJson(res, 404, { error: "User not found" });
      return;
    }
    readBody(req)
      .then(async (body) => {
        const { role, active, status, locationId } = body || {};
        if (role) target.role = role;
        if (typeof active === "boolean") target.active = active;
        if (status) target.status = status;
        if (locationId !== undefined) target.locationId = locationId || null;
        recordAudit(db, authedUser.id, "user.approve", "user", target.id, { role: target.role, status: target.status, locationId: target.locationId });
        await saveDb(db);
        sendJson(res, 200, publicUser(target));
      })
      .catch((error) => sendJson(res, 400, { error: error.message }));
    return;
  }

  if (pathname.startsWith("/api/users/") && req.method === "PATCH" && !pathname.endsWith("/approve") && !pathname.endsWith("/reset-password")) {
    if (!requireRole(authedUser, ["super_admin"])) {
      sendJson(res, 403, { error: "Forbidden" });
      return;
    }
    const userId = pathname.split("/")[3];
    const target = db.users.find((user) => user.id === userId);
    if (!target) {
      sendJson(res, 404, { error: "User not found" });
      return;
    }
    readBody(req)
      .then(async (body) => {
        const previous = { ...publicUser(target) };
        if (body?.username && db.users.some((user) => user.username === body.username && user.id !== target.id)) {
          sendJson(res, 409, { error: "username ซ้ำ" });
          return;
        }
        if (body?.role && !["super_admin", "dept_admin", "pharmacist", "lab", "supply", "viewer"].includes(body.role)) {
          sendJson(res, 400, { error: "Invalid role" });
          return;
        }
        if (body?.username) target.username = body.username;
        if (body?.displayName) target.displayName = body.displayName;
        if (body?.role) target.role = body.role;
        if (body?.locationId !== undefined) target.locationId = body.locationId || null;
        if (typeof body?.active === "boolean") target.active = body.active;
        if (body?.password) {
          const password = hashPassword(body.password);
          target.passwordSalt = password.salt;
          target.passwordHash = password.hash;
        }
        target.updatedAt = nowIso();
        recordAudit(db, authedUser.id, "user.update", "user", target.id, { before: previous, after: publicUser(target) });
        await saveDb(db);
        sendJson(res, 200, publicUser(target));
      })
      .catch((error) => sendJson(res, 400, { error: error.message }));
    return;
  }

  if (pathname.startsWith("/api/users/") && pathname.endsWith("/reset-password") && req.method === "POST") {
    if (!requireRole(authedUser, ["super_admin"])) {
      sendJson(res, 403, { error: "Forbidden" });
      return;
    }
    const userId = pathname.split("/")[3];
    const target = db.users.find((user) => user.id === userId);
    if (!target) {
      sendJson(res, 404, { error: "User not found" });
      return;
    }
    readBody(req)
      .then(async (body) => {
        if (!body?.password) {
          sendJson(res, 400, { error: "Password is required" });
          return;
        }
        const password = hashPassword(body.password);
        target.passwordSalt = password.salt;
        target.passwordHash = password.hash;
        target.updatedAt = nowIso();
        recordAudit(db, authedUser.id, "user.reset_password", "user", target.id, { username: target.username });
        await saveDb(db);
        sendJson(res, 200, { ok: true });
      })
      .catch((error) => sendJson(res, 400, { error: error.message }));
    return;
  }

  if (pathname.startsWith("/api/users/") && req.method === "DELETE") {
    if (!requireRole(authedUser, ["super_admin"])) {
      sendJson(res, 403, { error: "Forbidden" });
      return;
    }
    const userId = pathname.split("/")[3];
    const target = db.users.find((user) => user.id === userId);
    if (!target) {
      sendJson(res, 404, { error: "User not found" });
      return;
    }
    target.active = false;
    target.deletedAt = nowIso();
    target.updatedAt = nowIso();
    recordAudit(db, authedUser.id, "user.delete", "user", target.id, { username: target.username });
    await saveDb(db);
    sendJson(res, 200, { ok: true });
    return;
  }

  if (pathname === "/api/audit-logs" && req.method === "GET") {
    if (!requireRole(authedUser, ["super_admin"])) {
      sendJson(res, 403, { error: "Forbidden" });
      return;
    }
    const limit = Math.max(1, Math.min(200, Number(new URL(req.url, `http://${req.headers.host || "127.0.0.1"}`).searchParams.get("limit") || 50)));
    const actorMap = new Map(db.users.map((user) => [user.id, user]));
    const logs = (db.auditLogs || []).slice(0, limit).map((log) => {
      const actor = actorMap.get(log.actorUserId);
      return {
        id: log.id,
        action: log.action,
        entityType: log.entityType,
        entityId: log.entityId,
        details: log.details,
        createdAt: log.createdAt,
        actor: actor ? { id: actor.id, displayName: actor.displayName, username: actor.username, role: actor.role } : null,
      };
    });
    sendJson(res, 200, { logs });
    return;
  }

  if (pathname === "/api/events" && req.method === "GET") {
    res.writeHead(200, {
      "Content-Type": "text/event-stream; charset=utf-8",
      "Cache-Control": "no-cache, no-transform",
      Connection: "keep-alive",
    });
    res.write("\n");
    sseClients.add(res);
    req.on("close", () => {
      sseClients.delete(res);
    });
    return;
  }

  sendJson(res, 404, { error: "Not found" });
}

function serveStatic(req, res, pathname) {
  const filePath = pathname === "/" ? path.join(root, "index.html") : path.join(root, pathname);
  if (!filePath.startsWith(root)) {
    sendText(res, 403, "Forbidden");
    return;
  }
  fs.readFile(filePath, (error, buffer) => {
    if (error) {
      if (pathname.startsWith("/api/")) {
        sendJson(res, 404, { error: "Not found" });
      } else {
        fs.readFile(path.join(root, "index.html"), (fallbackError, fallback) => {
          if (fallbackError) {
            sendText(res, 500, "Missing index.html");
            return;
          }
          res.writeHead(200, { "Content-Type": "text/html; charset=utf-8" });
          res.end(fallback);
        });
      }
      return;
    }
    const type = mime[path.extname(filePath).toLowerCase()] || "application/octet-stream";
    res.writeHead(200, { "Content-Type": type });
    res.end(buffer);
  });
}

const server = http.createServer((req, res) => {
  const url = new URL(req.url, `http://${req.headers.host || "127.0.0.1"}`);
  if (url.pathname.startsWith("/api/")) {
    handleApi(req, res, url.pathname).catch((error) => {
      console.error(error);
      if (!res.headersSent) {
        sendJson(res, 500, { error: "Internal server error" });
      } else {
        res.end();
      }
    });
    return;
  }
  serveStatic(req, res, url.pathname);
});

const host = process.env.HOST || "0.0.0.0";

server.listen(port, host, () => {
  console.log(`Medical Box Monitor running on http://${host}:${port}`);
});
