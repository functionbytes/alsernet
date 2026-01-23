# Root Optimization Checklist - Exact Package Status

**Current Root Packages:** 30 (with google/analytics-data found!)

---

## 🎯 Three Categories

### ✅ CATEGORY 1: DEFINITELY STAY IN ROOT (15 packages)

These are **infrastructure essentials** used by all/most modules:

```
✓ laravel/framework              - Core framework (ALL modules need)
✓ laravel/sanctum                - API auth (required for all APIs)
✓ laravel/reverb                 - WebSocket server (infrastructure)
✓ laravel/horizon                - Queue management UI (system-wide)
✓ nwidart/laravel-modules        - Module loader (ESSENTIAL)
✓ tymon/jwt-auth                 - JWT tokens (Auth + APIs)
✓ spatie/laravel-permission      - RBAC system (Auth + all modules)
✓ spatie/laravel-activitylog     - Audit logging (system-wide)
✓ nesbot/carbon                  - Date handling (everywhere)
✓ doctrine/dbal                  - Database schema (Database module)
✓ guzzlehttp/psr7                - HTTP base (http client foundation)
✓ symfony/mime                   - MIME types (system-wide)
✓ symfony/finder                 - File utilities (system-wide)
✓ laravel/tinker                 - Developer REPL (dev tool)
✓ laravel/telescope              - Debugging tool (dev-require candidate)
```

---

### ⚠️ CATEGORY 2: CANDIDATES FOR MOVING (10 packages)

These packages are **used by specific modules** or utilities:

| Package | Module(s) | Decision | Action |
|---------|-----------|----------|--------|
| `guzzlehttp/guzzle` | Supplier, Erp | **KEEP in root OR Move to Supplier** | Check usage |
| `spatie/laravel-query-builder` | Campaign | ✅ **Already in Campaign module** | ✓ Correct |
| `league/pipeline` | Campaign | ✅ **Already in Campaign module** | ✓ Correct |
| `laravel/pulse` | Pulse | ✅ **Already in Pulse module** | ✓ Correct |
| `spatie/laravel-health` | System/Monitoring | **MOVE to System module** | Create/Add |
| `spatie/laravel-rate-limited-job-middleware` | Queue | **MOVE to System module** | Create/Add |
| `spatie/laravel-cookie-consent` | Media/Frontend | **MOVE to Media module** | Add |
| `aerni/cloudflared` | DevOps/System | **MOVE to System module** | Create/Add |
| `torann/geoip` | Supplier/System | **MOVE to Supplier or System** | Check |
| `geoip2/geoip2` | Supplier/System | **MOVE to Supplier or System** | Check |

---

### 🆘 CATEGORY 3: UNKNOWN/INVESTIGATE (5 packages)

These need **usage investigation**:

| Package | Status | Action |
|---------|--------|--------|
| `artesaos/seotools` | **Unknown** | Find usage → Decide module |
| `laravel/mcp` | **Unknown** | Find usage → Decide module |
| `laravel/ui` | Dev/Scaffold | Move to `require-dev` |
| `google/analytics-data` | **NEW! Not yet found** | Find usage |

---

## 📊 Current State (30 packages)

```
MUST STAY (15):
├─ Laravel core (7)
├─ Authentication (3)
├─ Database (1)
├─ Logging (1)
├─ Utilities (3)

SHOULD MOVE (10):
├─ Query builder (0) - Already moved ✓
├─ Pipeline (0) - Already moved ✓
├─ Health/Monitoring (2) - PENDING
├─ Rate limiting (1) - PENDING
├─ Cookie consent (1) - PENDING
├─ Cloudflare (1) - PENDING
├─ Geolocation (2) - PENDING
├─ Guzzle HTTP (1) - Check usage

UNKNOWN (5):
├─ SEO tools (1)
├─ Laravel MCP (1)
├─ Laravel UI (1)
└─ Google Analytics (1)
```

---

## 🔍 Verify Package Usage

### Step 1: Check `guzzlehttp/guzzle`

```bash
# Is guzzle used in root code?
grep -r "GuzzleHttp\|guzzlehttp\|new Client" app/ --include="*.php" | head -5

# Is it ONLY in Supplier?
grep -r "GuzzleHttp\|guzzlehttp" modules/*/app --include="*.php" | grep -v Supplier
```

**Result:**
- If only in Supplier → ✅ Already correct (Supplier has it)
- If in multiple modules → ✓ Keep in root
- If in app/ → ✓ Keep in root

### Step 2: Check `spatie/laravel-health`

```bash
# Find all health check usage
grep -r "health\|Health" app/ modules/ --include="*.php" | head -5
```

**Likely location:** System module or DevOps monitoring

### Step 3: Check `spatie/laravel-rate-limited-job-middleware`

```bash
# Find rate limiting usage
grep -r "RateLimited\|rate.limit" app/ modules/ --include="*.php" | head -5
```

**Likely location:** System or Backup module (for scheduled jobs)

### Step 4: Check `spatie/laravel-cookie-consent`

```bash
# Find cookie consent usage
grep -r "cookie.*consent\|Cookie" app/ modules/ --include="*.php" | head -5
```

**Likely location:** Media or Frontend module

### Step 5: Check Geolocation

```bash
# Find GeoIP usage
grep -r "geoip\|GeoIP\|Torann\|geoip2" app/ modules/ --include="*.php" | head -5
```

**Likely location:** Supplier or System module

### Step 6: Check `artesaos/seotools`

```bash
# Find SEO usage
grep -r "seotools\|SEO\|SEOTools" app/ modules/ --include="*.php" | head -5
```

**Likely location:** Analytics or Document module

### Step 7: Check `laravel/mcp`

```bash
# Find MCP usage
grep -r "mcp\|MCP\|Mcp" app/ modules/ --include="*.php" | head -5
```

**Likely location:** System or Supplier module (AI features)

### Step 8: Check `google/analytics-data` (NEW!)

```bash
# Find Google Analytics usage
grep -r "google\|analytics\|Google\|Analytics" app/ modules/ --include="*.php" | head -10
```

**Likely location:** Analytics or System module

---

## ✅ Action Plan by Priority

### PRIORITY 1: Quick Wins (No Dependencies to Check)

These are **already handled or safe to move**:

```
✅ spatie/laravel-query-builder → Already in Campaign
✅ league/pipeline              → Already in Campaign
✅ laravel/pulse                → Already in Pulse

Action: NOTHING - Already correct!
```

### PRIORITY 2: Investigation Required (5 packages)

These need you to **grep and find usage**:

```
❓ artesaos/seotools            → Run grep commands above
❓ google/analytics-data        → Run grep commands above
❓ laravel/mcp                  → Run grep commands above
❓ torann/geoip + geoip2/geoip2 → Run grep commands above
❓ guzzlehttp/guzzle            → Run grep commands above
```

### PRIORITY 3: Move After Investigation (5 packages)

Once you know where they're used:

```
📍 spatie/laravel-health        → Move to: System module
📍 spatie/laravel-rate-limited-job-middleware → Move to: System module
📍 spatie/laravel-cookie-consent → Move to: Media module
📍 aerni/cloudflared            → Move to: System module
📍 laravel/ui + laravel/telescope → Move to: require-dev
```

---

## 🛠️ Quick Command: Check Everything at Once

```bash
# Find all usage of potentially movable packages
echo "=== GUZZLE ===" && grep -r "guzzle\|Guzzle" app/ modules/ --include="*.php" | head -3
echo "=== GEOIP ===" && grep -r "geoip\|GeoIP" app/ modules/ --include="*.php" | head -3
echo "=== HEALTH ===" && grep -r "health\|Health" app/ modules/ --include="*.php" | head -3
echo "=== COOKIES ===" && grep -r "cookie.*consent\|Cookie" app/ modules/ --include="*.php" | head -3
echo "=== SEO ===" && grep -r "seotools\|SEO" app/ modules/ --include="*.php" | head -3
echo "=== MCP ===" && grep -r "mcp\|MCP" app/ modules/ --include="*.php" | head -3
echo "=== ANALYTICS ===" && grep -r "google.*analytics\|Analytics" app/ modules/ --include="*.php" | head -3
echo "=== RATE LIMIT ===" && grep -r "RateLimited\|rate.limit" app/ modules/ --include="*.php" | head -3
echo "=== CLOUDFLARE ===" && grep -r "cloudflare\|cloudflared" app/ modules/ --include="*.php" | head -3
```

---

## 📊 Decision Matrix for Each Package

Use this format to decide:

```
Package: spatie/laravel-health

Where used?
  app/: NO
  modules/Document/: NO
  modules/System/: MAYBE
  modules/Supplier/: NO

Used by how many modules?
  1 module → MOVE to that module
  2+ modules → KEEP in root
  0 modules → CHECK where used

Decision: _____________
Target Module: _____________
```

---

## 🎯 Final Status After Optimization

### Ideal Final Root (15 packages)

```
✓ laravel/framework
✓ laravel/sanctum
✓ laravel/reverb
✓ laravel/horizon
✓ nwidart/laravel-modules
✓ tymon/jwt-auth
✓ spatie/laravel-permission
✓ spatie/laravel-activitylog
✓ nesbot/carbon
✓ doctrine/dbal
✓ guzzlehttp/psr7
✓ symfony/mime
✓ symfony/finder
✓ laravel/tinker
✓ (1 more flexible)
```

### Reduction

```
Current:  30 packages in root
Target:   15 packages in root
Gain:     -50% root dependencies
Result:   Much clearer architecture!
```

---

## ✅ Checklist Before Optimizing

- [ ] Run all grep commands from Priority 2
- [ ] Document findings for each package
- [ ] Identify where each package is used
- [ ] Decide target module for each
- [ ] Create System module if needed
- [ ] Update module composer.json files
- [ ] Remove from root composer.json
- [ ] Run `composer update`
- [ ] Test thoroughly
- [ ] Commit changes

---

## 🚀 Expected Timeline

| Phase | Task | Time |
|-------|------|------|
| 1 | Investigation (grep commands) | 30 min |
| 2 | Update modules | 30 min |
| 3 | Update root | 15 min |
| 4 | Testing & validation | 30 min |
| **TOTAL** | **From here to optimization** | **~2 hours** |

---

## 📝 Notes

- **30 packages currently** in root (found google/analytics-data!)
- **15 must stay** (framework essentials)
- **10 should move** (module-specific)
- **5 unknown** (need investigation)

**Next step:** Run the grep commands to identify usage!

---

**Last Updated:** January 3, 2025
**Ready for:** Investigation phase

