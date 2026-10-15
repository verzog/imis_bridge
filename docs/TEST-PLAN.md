# iMIS Bridge — test plan (living document)

The master, continuously-updated test plan for `local_imisbridge`. Every change
is validated on a **test/staging environment before production**. This file is
updated on each PR that adds or changes behaviour — see the revision log at the
bottom.

- Environment setup, prerequisites and the **staging-iMIS safety rules** live in
  [`TEST-CHECKLIST.md`](TEST-CHECKLIST.md); read that first.
- **Golden rule:** the write/sync and provisioning paths mutate the iMIS behind
  the WSDL. Run them **only against a staging iMIS/bridge**, never production,
  until signed off here.

**Status key:** ☐ not tested · 🔄 in progress · ✅ pass · ❌ fail · ⛔ blocked · n/a

| Env | Moodle version | iMIS/bridge | Tester | Date |
|-----|----------------|-------------|--------|------|
| _(fill in per run)_ | | staging / prod | | |

---

## A. Install / upgrade

| ID | Test | Expected | Status | Notes |
|----|------|----------|--------|-------|
| A1 | Fresh install into `local/imisbridge` | Installs with no errors; appears under Local plugins | ☐ | Folder must be `imisbridge`, not `imis_bridge` |
| A2 | Upgrade from a prior version | `local_imisbridge_log` table created; version advances; stored settings retained | ☐ | |
| A3 | Install from the release zip | Correct `imisbridge/` root folder; installs cleanly | ☐ | |
| A4 | Uninstall | Plugin removed; `local_imisbridge_log` dropped | ☐ | |

## B. Configuration & connectivity (read-only — safe against production)

| ID | Test | Expected | Status | Notes |
|----|------|----------|--------|-------|
| B1 | Settings page loads; WSDL blank on fresh install | No hard-coded endpoint | ☐ | |
| B2 | Set WSDL + ATS API AuthToken + iMIS ID source | Saved | ☐ | Use staging WSDL for write tests |
| B3 | **Test connection**, no contact ID → `GetBridgeSettings` | Succeeds; audit success row | ☐ | Now sends AuthToken when configured (#17) |
| B4 | **Test connection** with a contact ID → `MoodleGetUserProfile` | Returns the contact; audit success row | ☐ | Exercises the AuthToken |
| B5 | `SetupBridge` (if surfaced) returns log lines | Log strings returned | ☐ | Method added in #17; not yet in UI |
| B6 | Bad/unreachable WSDL | Caught; task fails cleanly; **no** audit row (constructor throws before logging) | ☐ | |

## C. Outbound write path — SOAP contract (⚠ CRITICAL, staging iMIS only)

Validates the #17 WSDL alignment. **A single live call per row is enough.**

| ID | Test | Expected | Status | Notes |
|----|------|----------|--------|-------|
| C1 | **One live `MoodleUpdate`** for a test contact/course | iMIS activity record created/updated for the **correct contact and product** | ☐ | Proves `userId`/`productId` casing binds; was `UserID`/`productID` |
| C2 | Inspect the `MoodleUpdate` response | `ResultsBase.ResultCode`/`ResultMessage` readable | ☐ | Confirm the success `ResultCode` value with ATS → feeds failure detection |
| C3 | `SendNewOrdersToMoodle` (per-contact) | Succeeds with AuthToken; `OrdersSent`/`NumOrders` returned | ☐ | AuthToken now sent (#17) |
| C4 | `SendCancelledOrdersToMoodle` (per-contact) | Succeeds with AuthToken | ☐ | |
| C5 | `UpdateMoodleGroups` (per-contact) | Succeeds; `CourseId`/`LastUpdated` accepted; `ResultsBase` returned | ☐ | Casing fixed (#17) |
| C6 | **ProductID source** matches iMIS expectation | Record lands under the same product key the vendor used | ☐ | **Pending decision** — numeric course id vs idnumber (see Pending) |

## D. Observer-driven push (staging iMIS only)

| ID | Test | Expected | Status | Notes |
|----|------|----------|--------|-------|
| D1 | Login, then run cron | Per-user sync tasks run (`SendNewOrders`/`SendCancelled`/`UpdateGroups` per enabled toggles) | ☐ | Observers queue adhoc tasks |
| D2 | Course completion (toggle on) + cron | `MoodleUpdate` fires with course product + AuthToken; audit row | ☐ | |
| D3 | Quiz submit (graded, non-preview) + cron | `MoodleUpdate` fires with score/status; audit row | ☐ | Preview/ungraded skipped |
| D4 | User with no iMIS ID | No task queued; no bogus call | ☐ | |
| D5 | Transient SOAP failure on a push | Adhoc task retries (no data loss) | ☐ | Our advantage vs vendor fire-and-forget |

## E. Manual sync admin page (staging iMIS only)

| ID | Test | Expected | Status | Notes |
|----|------|----------|--------|-------|
| E1 | Access requires `moodle/site:config` | Denied without it | ☐ | |
| E2 | Submit shows confirmation before any write | Confirm step appears | ☐ | |
| E3 | Per-user sync | Runs; audit rows | ☐ | |
| E4 | All-users (blank contact ID) | All-users warning on confirm | ☐ | |
| E5 | Sesskey enforced | Stale/absent sesskey rejected | ☐ | |
| E6 | Single heading (no duplicate title) | One heading renders | ☐ | Fixed in #12 |

## F. Nightly scheduled tasks (staging iMIS only)

| ID | Test | Expected | Status | Notes |
|----|------|----------|--------|-------|
| F1 | Tasks listed under Server → Scheduled tasks | enrolment/cancellation/group + cleanup present | ☐ | |
| F2 | Nightly enrolment/cancellation/group sync run | Complete cleanly (all-user writes!) | ☐ | Keep disabled on prod-backed bridge |

## G. Audit log & privacy

| ID | Test | Expected | Status | Notes |
|----|------|----------|--------|-------|
| G1 | `admin/log.php` loads; site-admin only | Denied without `moodle/site:config` | ☐ | |
| G2 | Rows appear after calls; filters + paging work | Correct data, filterable | ☐ | |
| G3 | Capture off (default) | request/response empty | ☐ | |
| G4 | Capture on → redaction | No `MO-…`/`<AuthToken>`/`<Token>` in stored payloads | ☐ | |
| G5 | Error row from a fault (capture on) | token redacted (`MO-[redacted]`) | ☐ | |
| G6 | Ambiguous iMIS ID | row stored userid 0 | ☐ | |
| G7 | Privacy registry shows log fields + external iMIS link | Present | ☐ | |
| G8 | Export / delete for a test user | Rows exported / deleted | ☐ | |

## H. Bulk completions/grades pull web service (`get_completions_since`)

| ID | Test | Expected | Status | Notes |
|----|------|----------|--------|-------|
| H1 | Enable WS + REST; issue a token to a WS user with the capability | Token works; others rejected | ☐ | Cap at **system context**; service is restricted-users |
| H2 | Call with `since` | Returns changed completions/grades + `nextafterid`/`hasmore` | ☐ | |
| H3 | Paging across >1 page | Stable, no overlap/gaps | ☐ | id cursor |
| H4 | `imisid` resolves per configured source | Matches username/idnumber/email | ☐ | |
| H5 | Deleted/suspended users excluded | Not returned | ☐ | |
| H6 | Missing capability | Rejected | ☐ | |

## I. Cleanup task / retention

| ID | Test | Expected | Status | Notes |
|----|------|----------|--------|-------|
| I1 | `cleanup_log_task` listed (daily) | Present | ☐ | |
| I2 | Run with retention 90 | Old rows pruned, recent kept | ☐ | |
| I3 | Retention 0 | Nothing deleted; "disabled" message | ☐ | |

## J. Security spot-checks

| ID | Test | Expected | Status | Notes |
|----|------|----------|--------|-------|
| J1 | Entry points enforce login → capability → sesskey | Verified | ☐ | |
| J2 | Output escaped (try `<b>` in a value) | Rendered as text | ☐ | |
| J3 | No secret in logs even with capture on | Confirmed | ☐ | |

## K. Rollback

| ID | Test | Expected | Status | Notes |
|----|------|----------|--------|-------|
| K1 | Clear WSDL stops all SOAP | No calls made | ☐ | |
| K2 | Uninstall drops the log table | Clean | ☐ | |

---

## Pending decisions (resolve before the relevant tests)

- **ProductID source (C6):** confirm whether iMIS keys activity records on the
  **numeric Moodle course id** (as the vendor sent) or the course **idnumber**.
  Drives the planned configurable `productid_source` default.
- **`MoodleUpdate` success `ResultCode` (C2):** get the success value from ATS so
  we can wire `ResultCode`/`Exception`-based failure detection and logging.

## Not yet built (tests to be added when these land — see IMPROVEMENT-PLAN.md)

- Inbound create/update/enrol/unenrol web service (Phase 2).
- Both-direction logging + admin status page + "last heard from" (Phase 3).

## Plan revision log

| Date | Change |
|------|--------|
| 2026-10-15 | Initial plan: install/upgrade, config/connectivity, SOAP write-path contract (#17), observers, manual sync, nightly tasks, audit log/privacy, pull WS, cleanup, security, rollback. |
