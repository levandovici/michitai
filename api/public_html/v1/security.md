Absolutely.
Below is a **single, large, copy-paste–ready PROMPT for Windsurf** that clearly describes the **entire security update** for `api.michitai.com`.

You can paste this **as-is** into Windsurf Code Generator.

---

## 🔐 WINDSURF PROMPT — API SECURITY UPDATE (ADMIN + DEVICE FINGERPRINT)

**Project:** api.michitai.com
**Backend:** PHP (REST API)
**Goal:** Secure admin-only actions, add device fingerprinting, and implement IP / device bans in a safe, production-ready way.

---

### 🎯 OBJECTIVES

1. Secure sensitive API endpoints as **admin-only**
2. Separate **Game API** and **Admin API**
3. Implement **device fingerprinting** (no hardware IDs)
4. Add **IP ban**, **device ban**, and **account ban**
5. Add request-level security middleware
6. Log moderation actions for audit purposes

---

### 🔑 ADMIN-ONLY ENDPOINTS

The following endpoints must be accessible **ONLY** with an **Admin API Token**:

* `game_players.php/remove`
* `game_players.php/ban/device`
* `game_players.php/ban/ip`

Admin authentication must use:

```
Authorization: Bearer ADMIN_SECRET_TOKEN
```

Any request without a valid admin token must return:

```json
{ "error": "Admin access only" }
```

with HTTP `403`.

---

### 🧠 DEVICE IDENTIFICATION STRATEGY

❌ Do NOT attempt to read:

* Hardware IDs
* MAC address
* IMEI
* OS serial numbers

✅ Use **server-safe device fingerprinting** and **client-generated UUIDs**.

---

### 🖥️ CLIENT-SIDE REQUIREMENTS

Each client (Web / Unity / Mobile) must send the following headers on EVERY request:

```
X-Device-ID: <client-generated UUID, stored locally>
X-Device-Fingerprint: <SHA-256 hash of browser/app environment>
```

**Web fingerprint inputs may include:**

* User-Agent
* Platform
* Language
* Timezone
* Screen resolution
* Hardware concurrency
* Device memory

The fingerprint must be hashed client-side (SHA-256).

---

### 🧩 SERVER-SIDE IDENTITY HANDLING (PHP)

On every request:

* Read `REMOTE_ADDR` (IP)
* Read `HTTP_USER_AGENT`
* Read `HTTP_X_DEVICE_ID`
* Read `HTTP_X_DEVICE_FINGERPRINT`

Combine and normalize identity data:

```php
$identityHash = hash('sha256', $deviceId . $fingerprint . $userAgent);
```

---

### 🛡️ SECURITY MIDDLEWARE (MANDATORY)

Create a global security middleware that runs on **EVERY API request** and performs:

1. IP ban check
2. Device fingerprint ban check
3. Device ID ban check
4. Admin token validation (for admin routes)
5. Rate limiting
6. Identity logging (`last_seen`, `ip_last`)

Blocked requests must return HTTP `403`.

---

### 🗄️ DATABASE STRUCTURE

Create tables:

#### `banned_ips`

* `ip` (VARCHAR 45)
* `reason`
* `expires_at`
* `created_at`

#### `banned_devices`

* `device_id`
* `fingerprint`
* `reason`
* `created_at`

#### `device_fingerprints`

* `fingerprint`
* `device_id`
* `first_seen`
* `last_seen`
* `ip_first`
* `ip_last`
* `user_agent`
* `banned`

#### `admin_audit_log`

* `admin_id`
* `action`
* `target`
* `reason`
* `created_at`

---

### 🔨 ADMIN ACTIONS

Admin must be able to:

* Ban device fingerprint
* Ban device ID
* Ban IP (temporary)
* Remove player
* View audit logs

All admin actions must be logged.

---

### 🚫 BAN ENFORCEMENT RULES

* IP bans are **temporary**
* Device fingerprint bans are **persistent**
* Account bans override all other checks
* Any banned identity → request is denied

---

### 🔒 SECURITY RULES

* Never trust headers alone
* Never trust IP alone
* Never expose admin tokens publicly
* No client-side ban logic
* All checks must be server-side

---

### ✅ EXPECTED RESULT

A secure PHP API that:

* Prevents unauthorized admin access
* Identifies devices reliably without illegal tracking
* Allows effective moderation and bans
* Is compatible with Web, Unity, and Mobile clients
* Is scalable and production-ready