# Security Policy

Thanks for helping keep **WP Simple SAML** and its users safe. Because this
plugin handles authentication (SAML SSO), security reports are especially
welcome and are taken seriously.

## Reporting a Vulnerability

Please report security vulnerabilities **privately**. Do **not** open a public
GitHub issue, discussion, or pull request for a security problem, as that would
disclose the issue before a fix is available.

Use one of the following channels, in order of preference:

1. **GitHub Private Vulnerability Reporting** (preferred): use the
   **"Report a vulnerability"** button on this repository's
   [Security tab](../../security/advisories/new). This opens a private advisory
   visible only to you and the maintainers.
   *Maintainers:* if that button is not visible, enable it under
   **Settings → Code security and analysis → Private vulnerability reporting**.

2. **Email**: if private reporting is unavailable, email
   `hello@humanmade.com` (and optionally the plugin author `shady@humanmade.com`).
   Please send a brief heads-up first and wait for a private channel before
   sharing full details or proof-of-concept code.

### What to include

To help us triage quickly, please include as much as you can:

- A description of the vulnerability and its impact (e.g. authentication
  bypass, assertion replay, XSS).
- Affected version(s) / commit, and the plugin configuration if relevant.
- The relevant source file(s) and line references (`file:line`).
- Step-by-step reproduction, and a proof-of-concept if available.
- Any suggested remediation.

Please **only** test against systems you own or are explicitly authorized to test.

## Response Process

- **Acknowledgement** within 48 hours.
- **Initial assessment** and severity triage within 5 business days.
- **Status updates** at least every 7 days until resolution.
- On fix, we coordinate a release and, with your consent, credit you in the
  advisory / release notes.

## Coordinated Disclosure

We follow coordinated disclosure. Please give us a reasonable window (target:
90 days) to release a fix before any public disclosure. When a fix ships, the
maintainers may publish a GitHub Security Advisory and, if appropriate, request
a CVE through GitHub's CNA.

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| latest (`master`) | :white_check_mark: |

Only the latest released version receives security updates. Please upgrade
before reporting an issue on an older version.
