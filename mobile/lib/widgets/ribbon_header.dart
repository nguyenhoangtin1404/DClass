import 'package:flutter/material.dart';

import '../theme/dclass_colors.dart';

/// Section-title pill with a star badge peeking out of the top-right corner
/// - ported from `.ribbon-title-modern` on the web app, used there for every
/// major card heading ("Danh sách học sinh", "Lý do", "Lịch sử gần đây").
class RibbonHeader extends StatelessWidget {
  const RibbonHeader({super.key, required this.label, this.gradient});

  final String label;
  final Gradient? gradient;

  @override
  Widget build(BuildContext context) {
    return Stack(
      clipBehavior: Clip.none,
      children: [
        Container(
          padding: const EdgeInsets.fromLTRB(16, 8, 34, 8),
          decoration: BoxDecoration(
            gradient: gradient ?? DClassColors.ribbonGradient,
            borderRadius: BorderRadius.circular(16),
            boxShadow: const [
              BoxShadow(color: Color(0x14000000), blurRadius: 10, offset: Offset(0, 4)),
            ],
          ),
          child: Text(
            label,
            style: const TextStyle(
              color: Color(0xFF111111),
              fontWeight: FontWeight.w800,
              fontSize: 17,
            ),
          ),
        ),
        Positioned(
          right: -8,
          top: -10,
          child: Image.asset('assets/brand/star.png', width: 30, height: 30),
        ),
      ],
    );
  }
}
