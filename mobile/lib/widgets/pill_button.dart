import 'package:flutter/material.dart';

import '../theme/dashed_pill_border.dart';
import '../theme/dclass_colors.dart';

/// The rounded, dashed-border "chip button" used everywhere on the web app
/// for reasons, gift prices, class filters and small icon actions
/// (`.btn-outline-*` in `public/vendor/theme.css`). Kept as a standalone
/// widget - rather than a real `FilledButton`/`OutlinedButton` - for the
/// many call sites (reason picker, class filter, retry/delete actions) that
/// have no test coupling to a specific Flutter button type.
class PillButton extends StatelessWidget {
  const PillButton({
    super.key,
    required this.label,
    required this.color,
    this.onTap,
    this.selected = false,
    this.dense = false,
    this.leading,
  }) : _iconOnly = false;

  /// A round icon-only pill (e.g. retry/delete/back actions).
  const PillButton.icon({
    super.key,
    required Widget icon,
    required this.color,
    this.onTap,
    this.selected = false,
  })  : label = '',
        dense = true,
        leading = icon,
        _iconOnly = true;

  final String label;
  final Color color;
  final VoidCallback? onTap;
  final bool selected;
  final bool dense;
  final Widget? leading;
  final bool _iconOnly;

  @override
  Widget build(BuildContext context) {
    final fg = selected ? Colors.white : color;
    final bg = selected ? color : Colors.white;
    final shape = selected
        ? StadiumBorder(side: BorderSide(color: color, width: 2))
        : DashedPillBorder(color: color);

    final textStyle = TextStyle(
      color: fg,
      fontWeight: FontWeight.w800,
      fontSize: dense ? 12.5 : 14,
    );

    final child = _iconOnly
        ? Padding(
            padding: const EdgeInsets.all(8),
            child: IconTheme(data: IconThemeData(color: fg, size: 18), child: leading!),
          )
        : Padding(
            padding: EdgeInsets.symmetric(horizontal: dense ? 13 : 18, vertical: dense ? 8 : 11),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                if (leading != null) ...[
                  IconTheme(data: IconThemeData(color: fg, size: 16), child: leading!),
                  const SizedBox(width: 6),
                ],
                Text(label, style: textStyle),
              ],
            ),
          );

    return Material(
      color: bg,
      shape: shape,
      child: InkWell(
        customBorder: shape,
        onTap: onTap,
        child: child,
      ),
    );
  }
}

/// Solid pill badge, matching `.badge.bg-success` / `.badge.bg-warning`
/// on the web app - used for point deltas and sync-status labels.
class StatusBadge extends StatelessWidget {
  const StatusBadge({
    super.key,
    required this.label,
    this.background = DClassColors.successSolid,
    this.foreground = Colors.white,
  });

  const StatusBadge.warning({super.key, required this.label})
      : background = DClassColors.warningSolid,
        foreground = const Color(0xFF212529);

  const StatusBadge.danger({super.key, required this.label})
      : background = DClassColors.danger,
        foreground = Colors.white;

  final String label;
  final Color background;
  final Color foreground;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(color: background, borderRadius: BorderRadius.circular(999)),
      child: Text(
        label,
        style: TextStyle(color: foreground, fontWeight: FontWeight.w800, fontSize: 12),
      ),
    );
  }
}
