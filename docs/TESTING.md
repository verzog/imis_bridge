# Testing iMIS Bridge

This guide covers configuring and testing `local_imisbridge` against an iMIS
environment, including how to test as safely as possible against a **production**
iMIS when no staging iMIS is available.

## Requirements

- Moodle 5.0 or later, PHP 8.2 or later.
- The PHP **SOAP extension** (`ext-soap`) enabled — the client instantiates
  `\SoapClient`; without it every path fails with a missing-class error.
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
| Admin iMIS ID | A valid iMIS contact ID for service-level calls. **Currently unused by this endpoint:** its only consumer, `get_service_token()`, calls `getToken`, which `wsmoodle.asmx` does not advertise and which will fault. No sync or connection-test path uses it today, so it is safe to leave blank. |
| Web service timeout | Seconds to wait on iMIS before giving up (default 30). |
| Default credit type | Credit type recorded on activity records (default `CEU`). |
| Credit value course field | Short name of the course custom field holding the credit value (blank sends 0). |

Keep the WSDL URL and AuthToken from the **same iMIS environment** together —
never pair a staging token with a production WSDL, or vice versa. Testing against
production iMIS from an isolated Moodle (§5) is expected to use production
credentials; the isolation comes from the separate Moodle instance and the
disabled write toggles, not from mixing environments.

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

## 3. Understand what writes, and where

| Operation | Trigger | Effect |
| --- | --- | --- |
| `GetBridgeSettings`, `MoodleGetUserProfile`, `getActivityByIDAndType`, `getIQARows` | Connection test / API | Read only |
| Completion push (`MoodleUpdate`) | Course completion | Writes status, score, credit value and the start/completion/grant dates to the iMIS activity record |
| Quiz push (`MoodleUpdate`) | Graded quiz submission | Writes score, pass/fail status, credit value and the start/completion/grant dates to the iMIS activity record |
| Enrolment sync (`SendNewOrdersToMoodle`) | Login, nightly task, manual | Creates iMIS activity records **and** pushes an enrol notice to the bridge's configured Moodle |
| Cancellation sync (`SendCancelledOrdersToMoodle`) | Login, nightly task, manual | Marks iMIS activity records cancelled **and** pushes an unenrol notice to the bridge's configured Moodle |
| Group sync (`UpdateMoodleGroups`) | Login, nightly task, manual | Pushes group changes to the bridge's configured Moodle |

Writes fire automatically from four sources: the **login observer** (any user
login), **course-completion** events, **graded-quiz submissions**, and the
**nightly scheduled tasks**. A blank contact ID means **all users** — never run
that against production.

**Direction matters.** The completion and quiz pushes write an iMIS activity
record directly. The order and group operations do two things: they create or
modify records in iMIS **and** tell the ATS bridge to send a subscription/group
notice back to Moodle. The bridge sends that notice to **whichever Moodle it is
configured to call back** (via `SetupBridge` / `GetBridgeSettings`) — not
necessarily the Moodle that made the request. So calling these against a
production bridge can enrol or modify users on the **production** Moodle even
when you triggered them from a test site.

## 4. Write controls (kill-switches)

Under _iMIS Bridge_ settings, every automatic write has an enable/disable
checkbox (all default on):

- **Automatic sync controls:** sync enrolments / cancellations / groups on
  login; push course completions; push quiz scores.
- **Scheduled tasks:** the nightly enrolment / cancellation / group sync tasks.

The login toggles and the scheduled-task toggles are **independent**: disabling
_Sync enrolments on login_ does **not** disable the nightly enrolment task, and
vice versa. To stop an operation entirely, disable **both** its login toggle and
its task toggle. The completion and quiz push toggles each gate their operation
everywhere it runs (the observer and the queued adhoc task). The manual sync
buttons are deliberate admin actions and are **not** gated by any toggle.

## 5. Testing against a production iMIS

Without a staging iMIS, only the **read-only connection test is safe** against
production. Every sync and push writes to production iMIS, and the order/group
operations additionally make the bridge call back its configured (production)
Moodle — see §3 — so they are **not** made safe by running them from a test
Moodle.

1. Use a **fresh, isolated test Moodle** — never your live Moodle — with only
   your own test account on it.
2. In _iMIS Bridge_ settings, **disable every automatic toggle** (all login
   toggles, both push toggles, and all scheduled-task toggles) so nothing fires
   on login, completion, quiz, or cron.
3. Run the **connection test** (§2). This is read-only and safe; fix
   connectivity and credentials here.
4. **Do not run a sync or push against a production bridge as a "safe" test.**
   None of them are isolated by your Moodle instance:
   - `MoodleUpdate` / `createActivity` write directly to production iMIS records.
   - `SendNewOrdersToMoodle` / `SendCancelledOrdersToMoodle` /
     `UpdateMoodleGroups` write to production iMIS **and** push notices to the
     bridge's configured (production) Moodle.
5. To exercise writes safely, ask ATS for **either** a staging bridge configured
   to call back **your** test Moodle, **or** a scoped test AuthToken / test
   product that confines writes to a throwaway iMIS contact and product. Only
   then run a manual sync scoped to that single contact ID — never blank — verify
   the result in iMIS, and delete the test records afterwards. Know your rollback
   before you start.

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
