# dclass_mobile

Offline-first companion app for teachers: fast in-class point add/redeem from
a phone, even with no connection. The web app (`public/`) stays the source of
truth for admin, reports, and setup (students, classes, reasons, gifts) -
this app only reads that data and pushes point transactions.

It talks to the existing JSON API (`api/hoc_sinh.php`, `api/ly_do.php`,
`api/qua_tang.php`, `api/diem.php`) using the Bearer token auth added in
[#81](../../../pull/81): generate a token from **Cấu hình > Tài khoản** on
the web app (shown once as text and a QR code), paste it into the app's
login screen along with the server URL.

## Structure

- `lib/api_client.dart` - HTTP client wrapping the Bearer-token API
  (`DiemApi` interface for testability), including `client_action_id`
  support for idempotent point transactions.
- `lib/db/app_database.dart` - local sqflite schema: read-model caches
  (students/reasons/gifts) plus the outbox table for queued actions.
- `lib/outbox/`, `lib/repositories/diem_repository.dart` - the offline
  queue: point actions try the network first, and only queue when the
  attempt fails transiently.
- `lib/sync/sync_engine.dart` - replays the queue in strict order once
  connectivity returns (or on app resume / pull-to-refresh / manual sync).
  Foreground-only by design - no background service.
- `lib/repositories/danh_muc_repository.dart` - students/reasons/gifts
  cache, network-first with an offline fallback.
- `lib/screens/` - login, student list (add points / redeem gift), failed
  actions review.
- `lib/secure_token_storage.dart` - the Bearer token lives in
  `flutter_secure_storage` (Keystore/Keychain), not plaintext prefs.

## Setup

`android/` and `ios/` are committed (generated via `flutter create
--platforms=android,ios --org com.dclass --project-name dclass_mobile .` on
a real Flutter SDK). `web/`/`windows/` are not - they're not this app's
target platforms, only used ad hoc for local verification when no
Android/iOS device is handy.

Both platforms needed manual fixes on top of the stock `flutter create`
template - the app won't function without the first one on either platform:

**Android** (`android/app/src/main/AndroidManifest.xml`):
1. Add `<uses-permission android:name="android.permission.INTERNET" />` and
   `android.permission.ACCESS_NETWORK_STATE` (used by `connectivity_plus`) -
   Flutter's template no longer adds these by default.
2. If the server a teacher points the app at doesn't run HTTPS (common for
   small self-hosted/intranet deployments), Android blocks cleartext HTTP by
   default since API 28. `res/xml/network_security_config.xml` permits it,
   referenced via `android:networkSecurityConfig` on `<application>`.

**iOS** (`ios/Runner/Info.plist`):
1. App Transport Security blocks plain HTTP the same way - `Info.plist` sets
   `NSAppTransportSecurity` / `NSAllowsArbitraryLoads` for the same reason as
   the Android config above.

Neither cleartext exception touches the Bearer token's own storage, which
stays in `flutter_secure_storage` (Keystore/Keychain) regardless of what
protocol the server runs - see `lib/secure_token_storage.dart`.

If regenerating either folder from scratch, `flutter create .` on a
directory that already has `pubspec.yaml`/`lib/` only fills in missing
platform folders - it won't touch existing tracked files, so re-running it
is safe. It does have one quirk worth knowing: generating one platform at a
time overwrites (rather than appends to) `.metadata`'s migration platform
list - if you add `ios` after `android` already exists, check
`.metadata`'s `migration.platforms` still lists both afterward.

Then:

```bash
flutter pub get
flutter test      # 33 tests, all against real in-memory SQLite + fakes
flutter analyze
flutter build apk --debug   # Android; iOS needs an actual Mac + Xcode
```

### A Gradle build quirk you might hit

On some Windows machines/sandboxes, `flutter build apk` fails with:

```
java.io.IOException: Unable to establish loopback connection
Caused by: java.net.SocketException: Invalid argument: connect
  at java.base/sun.nio.ch.UnixDomainSockets.connect0
```

This is Gradle's daemon-connection handshake hitting a JDK NIO code path
that tries a Unix-domain-socket loopback pipe on Windows; if the environment
blocks `AF_UNIX` sockets (some sandboxes do, while regular TCP loopback
still works fine), the build fails before it ever touches this app's code.
`org.gradle.daemon=false` and JDK/JVM-arg overrides did not resolve it in
that environment - it needed a machine without that restriction. Worth
knowing before assuming a build failure is this app's fault.

## Not yet implemented

- No background sync (deliberate scope decision - foreground triggers
  only; see `lib/sync/sync_engine.dart`'s doc comment).
- No app icon/branding beyond Flutter's defaults.
- No release signing config - `flutter build apk --debug` only so far.
