# Testing iMIS Bridge

This guide covers configuring and testing `local_imisbridge` against an iMIS
environment, including how to test safely against a **production** iMIS when no
staging iMIS is available.

## Requirements

- Moodle 5.0 or later, PHP 8.2 or later.
- Network access from the Moodle server to the ATS bridge WSDL endpoint.
- An ATS-issued API AuthToken (format `MO-xxxxxx`) for the secured methods.
- For login-driven sync, Moodle usernames must equal iMIS contact IDs (normally
  set by SAML2). For manual testing you can instead create Moodle test users
  whose usernames are valid iMIS contact IDs.

## 1. Configure the plugin

Install the plugin (`git clone` into `local/imisbridge`, then _Site
administration > Notifications_), then set the values under _Site administration
> Plugins > Local plugins > iMIS Bridge_:

| Setting | Value |
| --- | --- |
| iMIS WSDL URL | The ATS `wsmoodle.asmx?WSDL` endpoint (production points at `scca.atsservices.net`; ask ATS for the staging URL, which is a different ATS host, **not** the imiscloud site). |
| ATS API AuthToken | The `MO-xxxxxx` token issued by ATS. |
| Admin iMIS ID | A valid iMIS contact ID used for service-level calls. |
| Web service timeout | Seconds to wait on iMIS before giving up (default 30). |
| Default credit type | Credit type recorded on activity records (default `CEU`). |
| Credit value course field | Short name of the course custom field holding the credit value (blank sends 0). |

Only ever put **staging** credentials on a staging Moodle and **production**
credentials on production — never mix them.

## 2. Verify connectivity (read-only, safe anywhere)

The connection test calls only non-mutating operations, so it is safe to run
against production as a first check.

CLI:

```bash
# Connectivity + WSDL only (does not exercise the AuthToken):
php local/imisbridge/cli/test_connection.php

# Also verify credentials via an authenticated MoodleGetUserProfile call:
php local/imisbridge/cli/test_connection.php --imisid=12345
```

Admin page: _Site administration > Plugins > Local plugins > iMIS Bridge > Open
sync admin_, then use the **Test connection** button. Enter a contact ID first
to exercise the AuthToken as well.

Note: `GetBridgeSettings` is unauthenticated. Without a contact ID the test
confirms connectivity and the WSDL only — it does **not** prove the AuthToken is
valid. Supply an iMIS contact ID to run an authenticated call.

## 3. Understand what writes to iMIS

| Operation | Trigger | Writes to iMIS? |
| --- | --- | --- |
| `GetBridgeSettings`, `MoodleGetUserProfile`, `getActivityByIDAndType`, `getIQARows` | Connection test / API | No (read only) |
| Group sync (`UpdateMoodleGroups`) | Login, nightly task, manual | No — writes Moodle groups only |
| Enrolment sync (`SendNewOrdersToMoodle`) | Login, nightly task, manual | **Yes** — creates activity records |
| Cancellation sync (`SendCancelledOrdersToMoodle`) | Login, nightly task, manual | **Yes** — marks activity records cancelled |
| Completion push (`MoodleUpdate`) | Course completion | **Yes** — writes status/score/CEU |
| Quiz push (`MoodleUpdate`) | Graded quiz submission | **Yes** — writes score |

Two things fire writes automatically: the login observer (on any user login) and
the nightly scheduled tasks. A blank contact ID means **all users** — never run
that against production.

## 4. Write controls (kill-switches)

Under _iMIS Bridge_ settings, every automatic write has an enable/disable
checkbox (all default on):

- **Automatic sync controls:** sync enrolments / cancellations / groups on
  login; push course completions; push quiz scores.
- **Scheduled tasks:** the nightly enrolment / cancellation / group sync tasks.

Disabling a toggle takes effect everywhere the operation runs — including work
already queued but not yet processed by cron. Turning all writes off gives you an
effectively read-only bridge. The manual sync buttons on the sync admin page are
deliberate admin actions and are **not** gated by these toggles.

## 5. Safe production-testing sequence

When you must test against a live iMIS:

1. Use a **fresh, isolated test Moodle** — never your live Moodle — with only
   your own test account on it.
2. In _iMIS Bridge_ settings, **disable all the automatic sync and scheduled
   task toggles** so nothing fires on login or via cron.
3. Run the **connection test** (§2). Fix connectivity/credentials before going
   further.
4. Try the **Groups manual sync** — it writes only to your test Moodle, not to
   iMIS.
5. For an iMIS write test, use **one dedicated throwaway iMIS contact and one
   product/course**, and always scope the manual sync to that **contact ID** —
   never blank. Verify the created activity record in iMIS, then delete it.
6. Know your rollback before you start (how to delete the test activity records
   in iMIS). Ask ATS for a scoped test AuthToken / test product if available.

## 6. Scheduled tasks

Three nightly tasks run via Moodle cron (times are UTC):

- 02:00 — enrolment sync (all users)
- 02:15 — cancellation sync (all users)
- 02:30 — group sync (incremental)

The group sync only requests changes since its own last successful run, tracked
independently of Moodle's task scheduler so a disabled interval does not skip
changes. Tasks can be disabled from the _iMIS Bridge_ settings (§4) or from _Site
administration > Server > Scheduled tasks_. View run output under _Site
administration > Server > Task logs_.

## Scope

The bridge writes learning records (completions, scores, credit) back to iMIS as
**activity records**. Creating iMIS orders or **event registrations** from a
Moodle-side purchase is not implemented and is not planned; the ATS
`wsmoodle.asmx` service exposes no order/registration write operation.
