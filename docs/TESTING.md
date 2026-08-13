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
- **Username = iMIS contact ID.** Every event-driven operation — login sync,
  course-completion push, and quiz push — sends the Moodle username directly as
  the iMIS contact ID (`UserID` for `MoodleUpdate`). This is normally set by
  SAML2. For manual testing, create Moodle test users whose usernames are valid
  iMIS contact IDs. A username that coincidentally matches an unrelated iMIS ID
  will update the **wrong** contact.

## 1. Lock the plugin down before connecting it

Do this **first**, before entering any endpoint or token. On a fresh install the
WSDL setting already defaults to the **production** ATS endpoint, every write
toggle defaults to enabled (an unset toggle is treated as enabled too), and the
enrolment/cancellation/group syncs do not use the AuthToken — so a cron run or a
single user login can trigger an all-user **production** sync before you have
configured anything.

1. On the test Moodle, **pause Moodle cron** and avoid user logins during setup.
2. Install the plugin (`git clone` into `local/imisbridge`, then _Site
   administration > Notifications_).
3. Immediately go to _Site administration > Plugins > Local plugins > iMIS
   Bridge_ and **disable every automatic toggle** (all three login toggles, both
   push toggles, and all three scheduled-task toggles) — see §4.

Only once writes are disabled should you configure the endpoint (§2).

## 2. Configure the plugin

Under _Site administration > Plugins > Local plugins > iMIS Bridge_:

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

**Course ID number = iMIS product code.** The completion and quiz pushes send the
course's **ID number** (Course settings), falling back to its **short name**, as
the iMIS `productID`. The credit-field setting above does not establish this
mapping. For a completion/quiz test to target the right iMIS product, set the
test course's ID number to the intended iMIS product code; a blank ID number or
an arbitrary short name will target the wrong product or fail.

## 3. Verify connectivity (read-only, safe anywhere)

The connection test calls only non-mutating operations, so it is safe to run
against production as a first check.

```bash
# Connectivity + WSDL only (does not exercise the AuthToken):
php local/imisbridge/cli/test_connection.php

# Also verify credentials via an authenticated MoodleGetUserProfile call:
php local/imisbridge/cli/test_connection.php --imisid=12345
```

Admin page: _Open sync admin_ from the plugin settings, then use the **Test
connection** button. Enter a contact ID first to exercise the AuthToken as well.

Note: `GetBridgeSettings` is unauthenticated. Without a contact ID the test
confirms connectivity and the WSDL only — it does **not** prove the AuthToken is
valid. Supply an iMIS contact ID to run an authenticated call.

## 4. Understand what writes, and where

| Operation | Trigger | Sends AuthToken? | Effect |
| --- | --- | --- | --- |
| `GetBridgeSettings`, `MoodleGetUserProfile`, `getActivityByIDAndType`, `getIQARows` | Connection test / API | Read: some yes | Read only |
| Completion push (`MoodleUpdate`) | Course completion | Yes | Writes status, score, credit value and the start/completion/grant dates to the iMIS activity record |
| Quiz push (`MoodleUpdate`) | Graded quiz submission | Yes | Writes score, pass/fail status, credit value and the start/completion/grant dates to the iMIS activity record |
| Enrolment sync (`SendNewOrdersToMoodle`) | Login, nightly task, manual | No | Creates iMIS activity records **and** pushes an enrol notice to the bridge's configured Moodle |
| Cancellation sync (`SendCancelledOrdersToMoodle`) | Login, nightly task, manual | No | Marks iMIS activity records cancelled **and** pushes an unenrol notice to the bridge's configured Moodle |
| Group sync (`UpdateMoodleGroups`) | Login, nightly task, manual | No | Pushes group changes to the bridge's configured Moodle. Does **not** write iMIS records |

Writes fire automatically from four sources: the **login observer** (any user
login), **course-completion** events, **graded-quiz submissions**, and the
**nightly scheduled tasks**. A blank contact ID means **all users** — never run
that against production.

**Direction matters.** The completion and quiz pushes write an iMIS activity
record directly (these carry the AuthToken). The order and group operations
instead tell the ATS bridge to act: they send a notice back to **whichever Moodle
the bridge is configured to call back** (via `SetupBridge` / `GetBridgeSettings`)
— not necessarily the Moodle that made the request — and the two order operations
additionally create/modify iMIS activity records. So calling these against a
production bridge can enrol, unenrol or re-group users on the **production**
Moodle even when you triggered them from a test site.

## 5. Write controls (kill-switches)

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

## 6. Testing against a production iMIS

Follow the lock-down in §1 first. Without a staging iMIS, only the **read-only
connection test is safe** against production. Every sync and push either writes
to production iMIS or drives the production bridge's callback — none is made safe
merely by running it from a test Moodle.

1. Use a **fresh, isolated test Moodle** — never your live Moodle — with only
   your own test account, cron paused, and all write toggles disabled (§1).
2. Run the **connection test** (§3). This is read-only and safe; fix
   connectivity and credentials here.
3. **Do not run a sync or push against a production bridge as a "safe" test.**
   None of them are isolated by your Moodle instance:
   - Completion/quiz pushes (`MoodleUpdate`) write directly to production iMIS
     activity records (these carry the AuthToken).
   - Enrolment/cancellation syncs write iMIS activity records **and** push
     enrol/unenrol notices to the bridge's configured (production) Moodle.
   - Group sync pushes group changes to the bridge's configured (production)
     Moodle (no iMIS record write, but it still modifies that Moodle).
4. Choose the confinement that actually applies to what you want to test —
   these are not interchangeable:
   - **Completion/quiz pushes** are token-bearing, so a **scoped test AuthToken
     / test product** confines them to a throwaway iMIS contact and product.
     Scope the test to that single contact ID (via the observers or a scoped
     manual action), verify in iMIS, and delete the test records afterwards.
   - **Enrolment / cancellation / group syncs carry no AuthToken**, so a scoped
     token does **not** constrain them. The only safe way to exercise these is a
     **staging or repointed bridge configured to call back your test Moodle**.
     Confirm the bridge's callback target with ATS before running any of them.
5. Whatever you run, scope manual syncs to a single contact ID — never blank —
   and know your rollback (how to delete test activity records in iMIS) before
   you start.

## 7. Scheduled tasks

Three nightly tasks run via Moodle cron (times are UTC):

- 02:00 — enrolment sync (all users)
- 02:15 — cancellation sync (all users)
- 02:30 — group sync (incremental)

The group sync only requests changes since its own last successful run, tracked
independently of Moodle's task scheduler so a disabled interval does not skip
changes. Tasks can be disabled from the _iMIS Bridge_ settings (§5) or from _Site
administration > Server > Scheduled tasks_. View run output under _Site
administration > Server > Task logs_.

## Scope

The bridge writes learning records (completions, scores, credit) back to iMIS as
**activity records**. Creating iMIS orders or **event registrations** from a
Moodle-side purchase is not implemented and is not planned; the ATS
`wsmoodle.asmx` service exposes no order/registration write operation.
