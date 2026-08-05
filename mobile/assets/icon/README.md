# App icon

Drop a **1024x1024 PNG** logo here as `icon.png`, then run:

```bash
cd mobile
dart run flutter_launcher_icons
```

This generates every Android (`mipmap-*`) and iOS (`Assets.xcassets/AppIcon.appiconset`)
icon size from that one source image - see the `flutter_launcher_icons:`
config block in `pubspec.yaml`.

No source image is committed here yet - a real logo/icon needs actual
design input (colors, mark, etc.), not something to invent in code. Until
one exists, the app uses Flutter's default launcher icon. The display name
("DClass") is already set independently in
`android/app/src/main/AndroidManifest.xml` (`android:label`) and
`ios/Runner/Info.plist` (`CFBundleDisplayName`) - that part didn't need an
image and is done.
