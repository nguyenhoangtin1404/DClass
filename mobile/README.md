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
  cache, network-first with an offline fallback. A dead/revoked token
  (`loi_phan_loai.dart`'s `laLoiPhienHetHan`) is rethrown instead of masked
  by the cache fallback - see `lib/session.dart`.
- `lib/screens/` - login, student list (search/filter, add points, redeem
  gift, point history), failed actions review.
- `lib/screens/history_screen.dart` - reads `api/diem.php?hanh_dong=lich_su`
  directly (network-only, no offline cache - it's a look-back, not a
  mid-class necessity the way the student list is).
- `lib/secure_token_storage.dart` - the Bearer token lives in
  `flutter_secure_storage` (Keystore/Keychain), not plaintext prefs.
- `lib/session.dart` - `dangXuat()`: the one place that clears the saved
  token + server URL and returns to the login screen, used both by the
  explicit logout action and by the dead-token handling above.

## Setup

Platform folders (`android/`, `web/`, `windows/`) aren't committed here -
generate them once on your own machine with a real Flutter SDK:

```bash
cd mobile
flutter create --platforms=android --org com.dclass --project-name dclass_mobile .
```

`flutter create .` on a directory that already has `pubspec.yaml`/`lib/`
only fills in the missing platform folders - it won't touch existing
tracked files. After generating `android/`, apply these two manual fixes
(stock `flutter create` doesn't add them, and the app won't work without
the first one):

1. **`android/app/src/main/AndroidManifest.xml`** needs
   `<uses-permission android:name="android.permission.INTERNET" />` and
   `android.permission.ACCESS_NETWORK_STATE` (used by `connectivity_plus`) -
   Flutter's template no longer adds these by default.
2. If the server a teacher points the app at doesn't run HTTPS (common for
   small self-hosted/intranet deployments), Android blocks cleartext HTTP by
   default since API 28. Add a `network_security_config.xml` under
   `res/xml/` permitting cleartext, and reference it via
   `android:networkSecurityConfig="@xml/network_security_config"` on
   `<application>`.

Then:

```bash
flutter pub get
flutter test      # 37 tests, all against real in-memory SQLite + fakes
flutter analyze
flutter build apk --debug
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
- No real app icon yet - `flutter_launcher_icons` is wired up in
  `pubspec.yaml`, just needs a real 1024x1024 logo dropped at
  `assets/icon/icon.png` (see `assets/icon/README.md`); the display name
  ("DClass") is already set independently and doesn't need an image.
- No release signing config - `flutter build apk --debug` only so far.
- No app-level lock screen (PIN/biometric) - the token is Keychain/Keystore-
  protected, but the app itself doesn't re-prompt for auth on resume.
- No real device testing yet - verified via `flutter test`/`flutter
  analyze`/`flutter build web --release` and the real PHP backend over
  `curl`, not an actual phone.
