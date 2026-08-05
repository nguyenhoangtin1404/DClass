# dclass_mobile

Offline-first companion app for teachers: fast in-class point add/redeem from
a phone. The web app (`public/`) stays the source of truth for admin,
reports, and setup (students, classes, reasons, gifts) - this app only needs
to read that data and push point transactions.

It talks to the existing JSON API (`api/hoc_sinh.php`, `api/ly_do.php`,
`api/qua_tang.php`, `api/diem.php`) using the Bearer token auth added in
[#81](../../../pull/81): generate a token from **Cấu hình > Tài khoản** on
the web app (shown once as text and a QR code), paste it into the app's
login screen along with the server URL.

## What's here

This folder currently has only the Dart side of a Flutter project
(`pubspec.yaml`, `lib/`, `test/`) - there's no Flutter SDK in this sandbox to
run `flutter create`, so the platform folders (`android/`, `ios/`, `web/`,
etc.) haven't been generated yet.

To finish scaffolding on a machine with Flutter installed:

```bash
cd mobile
flutter create --org com.dclass --project-name dclass_mobile .
```

`flutter create .` on a directory that already has a `pubspec.yaml`/`lib/`
fills in the missing platform folders; it may also rewrite
`pubspec.yaml`/`analysis_options.yaml`/`test/widget_test.dart` with its own
template. Run `git status`/`git diff` afterwards and re-apply anything from
this version worth keeping (dependencies, lint rule, the login-screen test)
before committing.

Then:

```bash
flutter pub get
flutter run
```

## Structure

- `lib/api_client.dart` - HTTP client wrapping the Bearer-token API, including
  `client_action_id` support for idempotent point transactions (safe to
  retry after a dropped connection).
- `lib/models/` - `HocSinh`, `LyDo`, `QuaTang` matching the JSON shapes those
  endpoints already return.
- `lib/screens/login_screen.dart` - enter server URL + token, verified with a
  real API call before saving (via `shared_preferences`).
- `lib/screens/students_screen.dart` - student list with a per-row "add
  points" action.

Not yet implemented: an offline outbox/replay queue (queuing actions while
offline and syncing them back with retried `client_action_id`s once
connectivity returns) - the backend supports it, but the app currently
requires a live connection for every action.
