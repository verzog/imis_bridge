# iMIS Bridge #

Bridges Moodle with the iMIS association management system over the ATS
`wsmoodle.asmx` SOAP service. It keeps enrolments, cancellations and group
memberships in sync, and pushes course-completion and quiz results back to iMIS
as activity records.

Key behaviours:

* On login, a per-user enrolment/cancellation/group sync is queued as an adhoc
  task so iMIS round-trips never block the login request.
* Course completion and (graded, non-preview) quiz submissions queue an adhoc
  task that records the result in iMIS.
* Nightly scheduled tasks perform a full enrolment and cancellation sync, plus
  an incremental group sync.
* An admin page (Site administration > Plugins > Local plugins > iMIS Bridge)
  provides manual sync controls, optionally filtered by iMIS contact ID.

## Requirements ##

* Moodle 5.0 or later.
* PHP 8.2 or later.
* Network access from the Moodle server to the configured iMIS WSDL endpoint.
* An ATS-issued API AuthToken (format `MO-xxxxxx`) for the secured methods.

## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/local/imisbridge

Afterwards, log in to your Moodle site as an admin and go to _Site administration
> Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## Configuration ##

After installation, configure the plugin under _Site administration > Plugins >
Local plugins > iMIS Bridge_:

* **iMIS WSDL URL** — the `wsmoodle.asmx?WSDL` endpoint.
* **ATS API AuthToken** — the `MO-xxxxxx` token issued by ATS.
* **Admin iMIS ID** — used to generate a service-level SSO session token.
* **Web service timeout** — how long to wait on iMIS before giving up.
* **Default credit type** and **Credit value course field** — control the credit
  hours recorded against iMIS activity records.

## License ##

2024 Vernon Spain

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program. If not, see <https://www.gnu.org/licenses/>.
