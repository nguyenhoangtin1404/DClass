import 'package:flutter/material.dart';

/// A fully-rounded (stadium) border painted as a dash pattern instead of a
/// solid line - the signature button treatment on the web app
/// (`public/vendor/theme.css` sets `border-style: dashed !important` on
/// every `.btn`). Flutter's [BorderSide] has no dash support, so this paints
/// the dashes itself along the stadium outline.
class DashedPillBorder extends OutlinedBorder {
  const DashedPillBorder({
    required this.color,
    this.strokeWidth = 2,
    this.dashWidth = 5,
    this.dashGap = 3.5,
    super.side,
  });

  final Color color;
  final double strokeWidth;
  final double dashWidth;
  final double dashGap;

  @override
  DashedPillBorder copyWith(
      {BorderSide? side, Color? color, double? strokeWidth}) {
    return DashedPillBorder(
      color: color ?? this.color,
      strokeWidth: strokeWidth ?? this.strokeWidth,
      dashWidth: dashWidth,
      dashGap: dashGap,
      side: side ?? this.side,
    );
  }

  @override
  EdgeInsetsGeometry get dimensions => EdgeInsets.all(strokeWidth);

  RRect _rrect(Rect rect) => RRect.fromRectAndRadius(
        rect,
        Radius.circular(rect.shortestSide / 2),
      );

  @override
  Path getInnerPath(Rect rect, {TextDirection? textDirection}) {
    return Path()..addRRect(_rrect(rect.deflate(strokeWidth)));
  }

  @override
  Path getOuterPath(Rect rect, {TextDirection? textDirection}) {
    return Path()..addRRect(_rrect(rect));
  }

  @override
  void paint(Canvas canvas, Rect rect, {TextDirection? textDirection}) {
    final path = Path()..addRRect(_rrect(rect.deflate(strokeWidth / 2)));
    final paint = Paint()
      ..color = color
      ..style = PaintingStyle.stroke
      ..strokeWidth = strokeWidth
      ..strokeCap = StrokeCap.round;
    for (final metric in path.computeMetrics()) {
      var distance = 0.0;
      while (distance < metric.length) {
        final end = (distance + dashWidth).clamp(0.0, metric.length);
        canvas.drawPath(metric.extractPath(distance, end), paint);
        distance = end + dashGap;
      }
    }
  }

  @override
  ShapeBorder scale(double t) => DashedPillBorder(
        color: color,
        strokeWidth: strokeWidth * t,
        dashWidth: dashWidth,
        dashGap: dashGap,
        side: side.scale(t),
      );
}
