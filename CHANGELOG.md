# Changelog

All notable changes to the iMIS Bridge plugin (`local_imisbridge`) are documented
in this file. The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.4.0] - 2026-09-21

Open-source release preparation. All changes are backward-compatible: existing
installations keep their stored configuration and their current on-the-wire
behaviour.

### Added
- **Configurable iMIS contact ID source** — a new *iMIS contact ID source*
  setting (`imis_id_source`) lets sites choose which Moodle user field holds the
  iMIS contact ID: username, ID number, or email. Defaults to username, so
  existing installs are unaffected. Observers resolve the ID through the new
  `util::get_imis_id()` helper.
- **Configuration guard** — `util::is_configured()` short-circuits every
  automatic write (the login/completion/quiz observers, the three nightly tasks,
  and the queued adhoc tasks) when no WSDL URL is set, so a fresh install is a
  clean no-op instead of raising repeated cron errors.
- Unit tests for the ID-source resolver, the configuration guard, and the
  observer paths that use them.

### Changed
- The shipped **iMIS WSDL URL** default is now blank instead of an
  organisation-specific ATS host, so a fresh install never points at another
  site's endpoint. Existing installs keep their stored URL.
- Generalised the privacy metadata string, `README.md`, and `docs/TESTING.md` to
  describe the configurable identifier rather than assuming the username.

[1.4.0]: https://github.com/verzog/imis_bridge/releases/tag/v1.4.0
