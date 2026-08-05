import 'package:flutter/material.dart';

import 'dashed_pill_border.dart';
import 'dclass_colors.dart';

/// App-wide `ThemeData`, ported from `public/vendor/theme.css` so the mobile
/// app and the web app read as one product: Nunito everywhere, pill buttons
/// with a dashed border instead of a filled/elevated look, a warm paper
/// background instead of stark white.
class DClassTheme {
  DClassTheme._();

  static ButtonStyle pillButtonStyle(Color color) {
    return FilledButton.styleFrom(
      backgroundColor: Colors.white,
      foregroundColor: color,
      disabledBackgroundColor: Colors.white,
      disabledForegroundColor: color.withValues(alpha: .35),
      shape: DashedPillBorder(color: color),
      minimumSize: const Size(64, 48),
      padding: const EdgeInsets.symmetric(horizontal: 22, vertical: 12),
      textStyle: const TextStyle(
        fontFamily: 'Nunito',
        fontWeight: FontWeight.w800,
        fontSize: 15,
      ),
      elevation: 0,
    ).copyWith(
      overlayColor: WidgetStatePropertyAll(color.withValues(alpha: .08)),
    );
  }

  static ThemeData get themeData {
    final colorScheme = ColorScheme.fromSeed(
      seedColor: DClassColors.primary,
      primary: DClassColors.primary,
      secondary: DClassColors.secondary,
      error: DClassColors.danger,
      surface: DClassColors.background,
    );

    return ThemeData(
      useMaterial3: true,
      fontFamily: 'Nunito',
      colorScheme: colorScheme,
      scaffoldBackgroundColor: DClassColors.background,
      textTheme: const TextTheme(
        titleLarge: TextStyle(fontWeight: FontWeight.w800, color: DClassColors.ink),
        titleMedium: TextStyle(fontWeight: FontWeight.w800, color: DClassColors.ink),
        bodyLarge: TextStyle(color: DClassColors.ink),
        bodyMedium: TextStyle(color: DClassColors.ink),
      ),
      appBarTheme: const AppBarTheme(
        backgroundColor: DClassColors.background,
        foregroundColor: DClassColors.ink,
        elevation: 0,
        scrolledUnderElevation: 0,
        centerTitle: false,
        titleTextStyle: TextStyle(
          fontFamily: 'Nunito',
          fontWeight: FontWeight.w800,
          fontSize: 20,
          color: DClassColors.ink,
        ),
        iconTheme: IconThemeData(color: DClassColors.primary),
        actionsIconTheme: IconThemeData(color: DClassColors.primary),
      ),
      filledButtonTheme: FilledButtonThemeData(style: pillButtonStyle(DClassColors.primary)),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: Colors.white,
        hintStyle: const TextStyle(color: DClassColors.muted, fontWeight: FontWeight.w600),
        labelStyle: const TextStyle(color: DClassColors.muted, fontWeight: FontWeight.w700),
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: const BorderSide(color: DClassColors.fieldBorder, width: 1.6),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: const BorderSide(color: DClassColors.fieldBorder, width: 1.6),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: const BorderSide(color: DClassColors.primary, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(14),
          borderSide: const BorderSide(color: DClassColors.danger, width: 1.6),
        ),
      ),
      listTileTheme: const ListTileThemeData(
        tileColor: DClassColors.listBg,
        textColor: DClassColors.ink,
        iconColor: DClassColors.primary,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.all(Radius.circular(14)),
          side: BorderSide(color: DClassColors.listBorder),
        ),
      ),
      switchTheme: SwitchThemeData(
        thumbColor: const WidgetStatePropertyAll(Colors.white),
        trackColor: WidgetStateProperty.resolveWith(
          (states) => states.contains(WidgetState.selected)
              ? DClassColors.primary
              : DClassColors.listBorder,
        ),
      ),
      dividerTheme: const DividerThemeData(color: DClassColors.cardBorder),
      cardTheme: CardThemeData(
        color: Colors.white.withValues(alpha: .94),
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: const BorderSide(color: DClassColors.cardBorder),
        ),
      ),
      snackBarTheme: const SnackBarThemeData(
        backgroundColor: DClassColors.ink,
        contentTextStyle: TextStyle(color: Colors.white, fontWeight: FontWeight.w600),
      ),
    );
  }
}
