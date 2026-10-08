# Replay regression tests

Run `python3 tests/replay/run.py` with PHP (7.1 or newer), Python 3, the
committed vendor libraries, and a disposable local MySQL server. PHP needs
mysqli, DOM, and OpenSSL. The database user needs CREATE and DROP DATABASE.

Connection settings: `SAML_DB_HOST`, `SAML_DB_PORT`, `SAML_DB_USER`,
`SAML_DB_PASSWORD`, and optionally `SAML_DB_SOCKET`. Defaults are localhost,
3306, root, and an empty password. For example:

```sh
SAML_DB_SOCKET=/tmp/replay-test.sock python3 tests/replay/run.py
```

The runner creates a random `saml_replay_test_*` database and drops it in a
finally block. It generates short-lived signed fixtures in a temporary directory;
no private key is stored. Use a disposable local server, not a production server.
GitHub Actions runs the same tests on PHP 7.1 and 8.3 with MySQL 8.0.

These tests execute the actual plugin response validation, bundled SAML library,
replay claim and user/cookie flow. WordPress functions, users and wpdb's API are
minimal adapters. SQL, the options-table unique index, and concurrent PHP
processes are real. The encoding cases set the assertion ID directly on a real
Auth object; they do not test XML validity. Storage-failure and thrown-exception
cases deliberately inject faults; the missing-table case is a real SQL failure.

Coverage includes first use, repeat use, freshly signed responses with the same
assertion ID, response issuer changes, invalid signatures, missing IDs, expired
assertion reuse with strict validation unchanged, both stored key formats,
configured IdP namespaces, delimiter ambiguity, database errors, error suppression,
non-autoloaded permanent records, and twelve concurrent submissions. Concurrent
losers may report either replay or storage failure (for example, a deadlock); both
must fail closed. A second concurrency case mixes the old single-key protocol
with the new dual-key protocol.

The new encoding uses byte-length-prefixed fields and a versioned key. Both the
legacy and new key are inserted in one statement, with acceptance requiring two
inserted rows. This keeps old records effective and protects overlapping older
workers or a rollback. Duplicate or partial claims remain stored and fail closed.
It costs two permanent rows per assertion. The legacy NUL encoding is retained
only for compatibility; replacing existing hashes without this guard would
re-enable assertions already used by the earlier branch version. No deployment
of that branch is assumed.

Network tests exercise shared main-site storage through WordPress adapters,
including a network whose main site is not site 1. Full WordPress multisite,
browser redirects, provisioning, and cross-site login integration are still
outstanding; passing this harness does not establish those behaviors end to end.
