# Donation Campaigns — phpBB extension

A phpBB 3.3 extension that attaches a fundraising campaign to a topic and shows
its progress — target, amount collected and a progress bar — above the first
post. Donations are **confirmed receipts recorded by an administrator**; the
extension processes no payments and stores no payment data.

> **Status:** `1.0.0-rc1` — feature complete, release-candidate. Not yet
> submitted to the phpBB Extension Database.

## The extension lives here

This is the development repository. The extension itself — the only thing you
install on a board — is under
[`ext/uflagmey/donationcampaigns/`](ext/uflagmey/donationcampaigns/), and its
own README is the canonical documentation:

- **[Extension README](ext/uflagmey/donationcampaigns/README.md)** — what it
  does and does not do, requirements, installation, permissions, configuration
  and the campaign/donation workflows.
- [Administrator guide](ext/uflagmey/donationcampaigns/docs/ADMIN_GUIDE.md) —
  the day-to-day workflow.
- [Privacy and data handling](ext/uflagmey/donationcampaigns/docs/PRIVACY.md) —
  what is stored, and what publishing a donor name means.
- [Developer notes](ext/uflagmey/donationcampaigns/docs/DEVELOPERS.md) —
  architecture, contracts and tests.

Installation in one line: copy the package to
`ext/uflagmey/donationcampaigns/` on your board and enable it in **ACP →
Customise → Extensions**. See the extension README for requirements and the
full steps.

## Repository layout

```
ext/uflagmey/donationcampaigns/   The extension (this is what ships)
.github/workflows/                Continuous integration
phpcs.xml, phpunit.xml.dist       Development toolchain configuration
tests/                            Test bootstrap for the toolchain
composer.json                     Dev toolchain only — NOT the shipped manifest
```

The shipped package manifest is
[`ext/uflagmey/donationcampaigns/composer.json`](ext/uflagmey/donationcampaigns/composer.json);
the root `composer.json` pulls in PHPUnit and PHP_CodeSniffer for development
and is never distributed.

## Development

```
composer install
vendor/bin/phpcs           # phpBB / PSR-12 coding standard
vendor/bin/phpunit         # unit and functional tests
```

The functional tests need a phpBB source tree; see the
[developer notes](ext/uflagmey/donationcampaigns/docs/DEVELOPERS.md).

## Licence

GPL-2.0-only. See [LICENSE](LICENSE).

phpBB is a trademark of phpBB Limited. This extension is not affiliated with or
endorsed by phpBB Limited.
