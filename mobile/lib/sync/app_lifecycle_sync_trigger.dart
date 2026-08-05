import 'package:flutter/widgets.dart';

import 'sync_engine.dart';

/// Triggers a sync whenever the app returns to the foreground. Wraps the
/// whole authenticated shell (see `phien_dang_nhap.dart`) rather than being
/// mixed into individual screens, so it fires regardless of which screen
/// happens to be showing on resume.
class AppLifecycleSyncTrigger extends StatefulWidget {
  const AppLifecycleSyncTrigger({
    super.key,
    required this.syncEngine,
    required this.child,
  });

  final SyncEngine syncEngine;
  final Widget child;

  @override
  State<AppLifecycleSyncTrigger> createState() =>
      _AppLifecycleSyncTriggerState();
}

class _AppLifecycleSyncTriggerState extends State<AppLifecycleSyncTrigger>
    with WidgetsBindingObserver {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      widget.syncEngine.chaySync();
    }
  }

  @override
  Widget build(BuildContext context) => widget.child;
}
