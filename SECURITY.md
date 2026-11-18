# Security policy

## Supported versions

Security fixes are provided for the latest Awards 2.x release for Flarum 2.
Earlier releases should be upgraded before reporting an issue.

## Reporting a vulnerability

Please use [GitHub private vulnerability reporting](https://github.com/huseyinfiliz/awards/security/advisories/new)
to report a suspected vulnerability privately. Do not publish exploit details in
an issue or pull request before a fix is available.

Include the Awards, Flarum, PHP, and database versions; required permissions;
minimal reproduction steps using disposable data; and the expected and observed
behavior. Never include passwords, access tokens, or personal forum data.

Reports are reviewed by the maintainer. Remediation and disclosure timing are
coordinated with the reporter according to the demonstrated impact.

## Dependency maintenance

Dependabot checks Composer, Yarn, and GitHub Actions dependencies weekly. Update
pull requests require review and successful CI; they are not automatically merged.
Composer lockfiles are intentionally not distributed by this extension. Scheduled
dependency auditing supplements GitHub alerts for the resolved development tree;
forum owners must also audit their own installed dependencies and lockfile.
