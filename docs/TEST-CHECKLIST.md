# iMIS Bridge — Pre-Testing Checklist

Work top-down: each section gates the next. There is no point testing sync
before connectivity is green, or connectivity before the plugin installs.

This plugin **writes to a live iMIS instance** over SOAP, and the ATS bridge
sends callbacks to **whatever Moodle it is configured to talk to** — which may
be your *production* Moodle, not the staging site making the call. A disposable
iMIS contact does **not** isolate those callbacks. So:

- **Read-only** checks (the "Test connection" button) are safe against the
  production WSDL.
- **Write** checks (§§4–5: login sync, completion/quiz pushes, manual sync) must
  run against a **staging bridge or a bridge repointed at staging Moodle** —
  never the production bridge.

Take a **DB + `moodledata` backup** before you begin.

---

## 0. Before you start

- [ ] Take a **DB + `moodledata` backup** of the target site.
- [ ] Confirm the target is a **staging/test Moodle on Moodle 5.1–5.3 LTS**, not production.
- [ ] For the write tests (§§4–5): confirm you have a **staging bridge, or a bridge whose callback Moodle is repointed at this staging site**. If you only have the production bridge/WSDL, do §3 (read-only connection test) and stop there.
- [ ] Have ready: the **WSDL URL**, the **ATS API AuthToken** (`MO-…`), and a **known test member's iMIS ID**.
- [ ] Decide which iMIS records are *safe to touch* (ideally a disposable test contact in iMIS).

## 1. Install / upgrade

- [ ] Fresh install: place the plugin in `local/imisbridge`, run the upgrade — **no errors**; it appears under *Site admin → Plugins → Local plugins*.
- [ ] Upgrade path: install an older version first, then upgrade — confirm `db/upgrade.php` creates **`local_imisbridge_log`** and the savepoint lands (version → `2026101400` / release `1.5.0`).
- [ ] Verify the `local_imisbridge_log` table exists in the database.

## 2. Configuration

- [ ] Settings page loads.
- [ ] On a **fresh install**, the **WSDL default is blank** (no hard-coded URL). *(On an upgrade this does not apply — see the upgrade check below.)*
- [ ] On an **upgrade** from an install that had a WSDL set, the settings page **retains the existing WSDL endpoint** (configuration is preserved, not reset to blank).
- [ ] Set the **WSDL URL** (staging/repointed bridge for the write tests).
- [ ] Set the **ATS API AuthToken** (`MO-…`). This is **required** for the completion/quiz pushes and for the contact-lookup part of the connection test — they call `MoodleUpdate` / `MoodleGetUserProfile`, which fail with `noauthtoken` if it is blank. *(Login enrolment/cancellation/group sync does not use the AuthToken.)*
- [ ] Set **iMIS ID source** to match your SAML2 reality (`username` for this deployment).
- [ ] (If testing completion/quiz) set the **course credit field** and **credit type** as needed.
- [ ] Audit-log settings visible: `log_enabled`, `log_retention_days` (default 90), `capture_payloads` (default **off**), and the **Open log** button.

## 3. Connectivity (read-only — safe against production)

- [ ] Confirm the site can reach the WSDL host (network policy allows it).
- [ ] **Test connection** with **no contact ID** → runs `GetBridgeSettings` (unauthenticated) and succeeds, proving reachability.
- [ ] **Test connection** with the **test member's contact ID** → additionally runs `MoodleGetUserProfile` (uses the AuthToken), proving credentials. This should produce a **success** row in the audit log.
- [ ] Force a **genuine SOAP fault** to exercise error logging: with a **reachable** WSDL, set the AuthToken to a present-but-**invalid** value, then run Test connection with a contact ID → `MoodleGetUserProfile` faults server-side **inside** the logged call → an **error** row appears, with the token redacted. *(Note: an unreachable/bad WSDL instead throws in the client constructor **before** any call is logged, so it produces no audit row — the request just fails.)*

## 4. Observer-driven sync (core — staging/repointed bridge only)

Observers **queue adhoc tasks**; the SOAP calls happen when **cron** runs, not
at the moment of the event. So after each trigger, **run cron** (or run the
pending adhoc tasks) before checking the audit log.

Before triggering completion/quiz, **set the test course's ID number to the
intended safe iMIS product code** — the observer sends `course.idnumber`
(falling back to `shortname`) as `productID`, so a blank/arbitrary value can hit
the wrong activity or fail on mapping rather than on the observer.

Verify both the iMIS-side effect and an audit-log row for each:

- [ ] **Login** (`user_loggedin`) → queues the per-user sync; after cron, the **enabled** login operations run: `SendNewOrdersToMoodle`, `SendCancelledOrdersToMoodle`, `UpdateMoodleGroups` (whichever login toggles are on). *(Not `MoodleUpdate`.)*
- [ ] **Course completion** (`course_completed`, with `push_completions` on) → after cron, `MoodleUpdate` fires with the course's ID number as the product code and the AuthToken.
- [ ] **Quiz submit** (`quiz_attempt_submitted`, with `push_quiz_scores` on) → after cron, `MoodleUpdate` fires with the score/status. *(Preview attempts and ungraded quizzes are skipped by design.)*
- [ ] The iMIS ID used matches the configured **source** (not blindly `$user->username` if you changed it).
- [ ] A user **with no iMIS ID** (blank username/idnumber/email) is handled gracefully — no task queued, no bogus call.

## 5. Manual sync (`admin/sync.php` — staging/repointed bridge only)

- [ ] Page requires `moodle/site:config` (an account lacking it → **denied**).
- [ ] Submitting shows the **confirmation step** (`$OUTPUT->confirm()`) *before* any write.
- [ ] Per-user sync: enter the test member → confirm → the sync operations run → audit rows recorded.
- [ ] All-users sync with a **blank contactid** → the **all-users warning** appears on the confirm screen.
- [ ] Sesskey check holds (stale/absent sesskey → rejected).

## 6. Audit log (new in 1.5.0 — test hard)

- [ ] `admin/log.php` loads; an account **lacking `moodle/site:config` → denied**.
- [ ] After the §§3–4 tests (and cron), rows appear: time, user link, operation, iMIS ID, product, status badge, detail.
- [ ] **Filters** work — by operation and by status (success/error).
- [ ] **Pagination** works with >50 rows.
- [ ] **Default (capture off):** request/response are **empty** — only metadata stored.
- [ ] Turn **`capture_payloads` on**, trigger an event + cron → payloads appear in the collapsible detail, **redacted**: no `MO-…` tokens, no `<AuthToken>` / `<Token>` / `<getTokenResult>` values.
- [ ] Error row from the §3 fault test (reachable WSDL + invalid AuthToken) shows status = error with the token **redacted** (`MO-[redacted]`).
- [ ] Ambiguous iMIS ID (two users share the configured source value) → row stored with **userid 0** (unattributed), not mis-attributed.

## 7. Scheduled tasks

- [ ] `cleanup_log_task` is listed under *Site admin → Server → Scheduled tasks* (daily, ~03:45).
- [ ] Run it manually with retention = 90 → old rows pruned, recent kept (`mtrace` reports the count).
- [ ] Set `log_retention_days` = **0** → task reports "retention disabled; keeping all rows," nothing deleted.
- [ ] The nightly sync scheduled tasks run clean.

## 8. Privacy

- [ ] *Site admin → Privacy → Plugin privacy registry* shows `local_imisbridge` with the log-table fields **and** the external iMIS link described.
- [ ] Run an **export** for the test user → audit rows come out.
- [ ] Run a **delete** for the test user → their rows go; others remain.

## 9. Security / permissions spot-checks

- [ ] Entry points (`sync.php`, `log.php`) enforce `require_login` → `require_capability('moodle/site:config')` → sesskey (for writes). Test denial with an account that **lacks** `moodle/site:config` (don't assume "non-admin" — a role granted that capability is *meant* to get in).
- [ ] Output is escaped — try a member value containing `<b>` and confirm it renders as text, not markup.
- [ ] No secrets in the log even with capture on (re-confirm redaction on a real token).

## 10. Rollback plan (have it ready; don't test on production)

- [ ] Know how to disable fast: clear the WSDL (stops all SOAP), or uninstall.
- [ ] Confirm uninstall drops the log table cleanly.

---

## Build-specific gotchas

- The wire format (field casing, dates) is the **production-proven** one. If a value looks "wrong" next to the WSDL camelCase, that is expected — do not change it mid-test.
- **Observers queue adhoc tasks**; nothing hits iMIS until cron runs. If an audit row doesn't appear, run cron before concluding anything is broken.
- A **bad WSDL** fails in the client constructor (the adhoc task errors in cron, the user's request is unaffected) — it does **not** create an audit row. To see an *error* row, use a reachable WSDL with a faulting operation.
- Observers fire on **every** login. On a busy staging site that is a lot of queued tasks; enable `capture_payloads` only briefly while debugging, then turn it off.
- `capture_payloads` stores full XML. Leave it **off** except when actively investigating a specific call.
