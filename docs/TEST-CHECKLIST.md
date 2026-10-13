# iMIS Bridge — Pre-Testing Checklist

Work top-down: each section gates the next. There is no point testing sync
before connectivity is green, or connectivity before the plugin installs.

This plugin **writes to a live iMIS instance** over SOAP. A bad run has
real-world side effects on member records, so test against a staging Moodle and,
wherever possible, a disposable iMIS test contact.

---

## 0. Before you start

- [ ] Take a **DB + `moodledata` backup** of the target site.
- [ ] Confirm the target is a **staging/test Moodle on Moodle 5.1–5.3 LTS**, not production.
- [ ] Have the **production WSDL URL** and a **known test member's iMIS ID** ready.
- [ ] Decide which iMIS records are *safe to touch* (ideally a disposable test contact in iMIS).

## 1. Install / upgrade

- [ ] Fresh install: place the plugin in `local/imisbridge`, run the upgrade — **no errors**; it appears under *Site admin → Plugins → Local plugins*.
- [ ] Upgrade path: install an older version first, then upgrade — confirm `db/upgrade.php` creates **`local_imisbridge_log`** and the savepoint lands (version → `2026101400` / release `1.5.0`).
- [ ] Verify the `local_imisbridge_log` table exists in the database.

## 2. Configuration

- [ ] Settings page loads; the **WSDL default is blank** (not a hard-coded URL).
- [ ] With WSDL **unset**, trigger an observer event (log in) → the `util::is_configured()` guard means **no SOAP call and no crash**.
- [ ] Set the WSDL; set **iMIS ID source** to match your SAML2 reality (`username` for this deployment).
- [ ] Audit-log settings visible: `log_enabled`, `log_retention_days` (default 90), `capture_payloads` (default **off**), and the **Open log** button.

## 3. Connectivity (SOAP)

- [ ] Confirm the site can reach the WSDL host (network policy allows it).
- [ ] First real event produces a SOAP call that **succeeds** (confirm via the audit log, §6) — this validates auth + wire format end-to-end.
- [ ] Deliberately break the WSDL (bad URL) → the call is caught, **logged as an error**, and the site does not fault.

## 4. Observer-driven sync (core)

Test each observer with the **test member** and verify both the iMIS-side effect
and an audit-log row:

- [ ] **Login** (`user_loggedin`) → `MoodleUpdate` fires with the resolved iMIS ID.
- [ ] **Course completion** (`course_completed`) → activity/completion pushed (`createActivity` / `MoodleUpdate`).
- [ ] **Quiz submit** (`quiz_attempt_submitted`) → expected call fires with the correct product code.
- [ ] The iMIS ID used matches the configured **source** (not blindly `$user->username` if you changed it).
- [ ] A user **with no iMIS ID** (blank username/idnumber/email) is handled gracefully — no bogus call.

## 5. Manual sync (`admin/sync.php`)

- [ ] Page requires login + `moodle/site:config` (try as a non-admin → **denied**).
- [ ] Submitting shows the **confirmation step** (`$OUTPUT->confirm()`) *before* any write.
- [ ] Per-user sync: enter the test member → confirm → write happens → audit row recorded.
- [ ] All-users sync with a **blank contactid** → the **all-users warning** appears on the confirm screen.
- [ ] Sesskey check holds (stale/absent sesskey → rejected).

## 6. Audit log (new in 1.5.0 — test hard)

- [ ] `admin/log.php` loads, **site-admin only** (non-admin → denied).
- [ ] After the §4 tests, rows appear: time, user link, operation, iMIS ID, product, status badge, detail.
- [ ] **Filters** work — by operation and by status (success/error).
- [ ] **Pagination** works with >50 rows.
- [ ] **Default (capture off):** request/response are **empty** — only metadata stored.
- [ ] Turn **`capture_payloads` on**, trigger an event → payloads appear in the collapsible detail, **redacted**: no `MO-…` tokens, no `<AuthToken>` / `<Token>` / `<getTokenResult>` values.
- [ ] Force an **error** (bad WSDL) with capture on → error text stored **redacted** (`MO-[redacted]`), status = error.
- [ ] Ambiguous iMIS ID (two users share the configured source value) → row stored with **userid 0** (unattributed), not mis-attributed.

## 7. Scheduled tasks

- [ ] `cleanup_log_task` is listed under *Site admin → Server → Scheduled tasks* (daily, ~03:45).
- [ ] Run it manually with retention = 90 → old rows pruned, recent kept (`mtrace` reports the count).
- [ ] Set `log_retention_days` = **0** → task reports "retention disabled; keeping all rows," nothing deleted.
- [ ] Any other scheduled/adhoc sync tasks run clean.

## 8. Privacy

- [ ] *Site admin → Privacy → Plugin privacy registry* shows `local_imisbridge` with the log-table fields **and** the external iMIS link described.
- [ ] Run an **export** for the test user → audit rows come out.
- [ ] Run a **delete** for the test user → their rows go; others remain.

## 9. Security / permissions spot-checks

- [ ] Entry points (`sync.php`, `log.php`) enforce `require_login` → capability → sesskey (for writes).
- [ ] Output is escaped — try a member value containing `<b>` and confirm it renders as text, not markup.
- [ ] No secrets in the log even with capture on (re-confirm redaction on a real token).

## 10. Rollback plan (have it ready; don't test on production)

- [ ] Know how to disable fast: clear the WSDL (stops all SOAP), or uninstall.
- [ ] Confirm uninstall drops the log table cleanly.

---

## Build-specific gotchas

- The wire format (field casing, dates) is the **production-proven** one. If a value looks "wrong" next to the WSDL camelCase, that is expected — do not change it mid-test.
- Observers fire on **every** login. On a busy staging site that is a lot of calls; enable `capture_payloads` only briefly while debugging, then turn it off.
- `capture_payloads` stores full XML. Leave it **off** except when actively investigating a specific call.
