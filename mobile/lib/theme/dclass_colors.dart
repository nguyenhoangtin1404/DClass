import 'package:flutter/material.dart';

/// Color tokens lifted straight from `public/vendor/theme.css` so the mobile
/// app reads as the same product as the web app, not a separate skin.
abstract final class DClassColors {
  static const primary = Color(0xFF2151D1);
  static const secondary = Color(0xFFD62872);
  static const success = Color(0xFF0D9A4A);
  static const successSolid = Color(0xFF16A34A);
  static const danger = Color(0xFFD6254F);
  static const warning = Color(0xFFD08700);
  static const warningSolid = Color(0xFFFFC107);
  static const info = Color(0xFF009FBF);

  static const background = Color(0xFFF9F6F1);
  static const cardBorder = Color(0xFFE7E0D6);
  static const ink = Color(0xFF0A1A2F);
  static const muted = Color(0xFF5C6A85);
  static const fieldBorder = Color(0xFF9BB6E0);
  static const listBg = Color(0xFFF5F7FB);
  static const listBorder = Color(0xFFC9D5EB);

  static const ribbonGradient = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [Color(0xFFFFE4F3), Color(0xFFFFF3C9)],
  );

  /// Gradient behind the two "gate" screens (connect / unlock), matching
  /// `body.login-bg` on the web login page.
  static const gateGradient = RadialGradient(
    center: Alignment(-0.5, -0.6),
    radius: 1.1,
    colors: [
      Color(0xFF3B82F6),
      Color(0xFF2563EB),
      Color(0xFF111827),
      Color(0xFF0B1220)
    ],
    stops: [0.0, 0.28, 0.65, 1.0],
  );
}
