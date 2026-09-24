# Phase 8 Hardening Review

## Localization

- Tenant requests now apply the selected business locale and timezone.
- Supported locales are English (`en`) and Kiswahili (`sw`).
- Locale and timezone changes are restored after each response, protecting long-running workers from cross-tenant leakage.
- Complete system navigation, the fourteen-report workspace, dashboard, and settings entry points are available in both languages.
- The dashboard applies locale/timezone safely while retaining its unselected workspace state.
- Approval totals and links are hidden from dashboard users without approval-view permission.
- Page-level Kiswahili copy outside these shared surfaces remains incremental.

## Responsive POS

- The sales workspace uses the available dynamic viewport height rather than a fixed desktop-only height.
- Checkout totals and actions remain sticky and reachable on narrow screens.
- Critical Hold and Create Sale actions use larger touch targets.

## Browser and session security

- Responses include `X-Content-Type-Options`, `X-Frame-Options`, a strict referrer policy and a restrictive browser permissions policy.
- New installations default to encrypted session payloads. Production HTTPS deployments must set `SESSION_SECURE_COOKIE=true`.
- Existing authentication throttling, signed verification URLs, CSRF protection, tenant middleware and permission checks remain covered by the regression suite.

## Dependency review

- `npm audit --omit=dev` reported zero production vulnerabilities on 2026-09-10.
- The Composer advisory audit could not reach Packagist because DNS resolution failed. This is an unresolved environmental check, not a clean result and not a reported vulnerability.
- Run `composer audit --locked` again from a network with working Packagist DNS before production release.

## Verification

- Full automated suite: 101 tests, 411 assertions.
- Blade template compilation succeeds.
- Vite production build succeeds.
