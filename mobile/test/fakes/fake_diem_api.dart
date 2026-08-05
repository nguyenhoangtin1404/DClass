import 'package:dclass_mobile/api_client.dart';
import 'package:dclass_mobile/models/hoc_sinh.dart';
import 'package:dclass_mobile/models/ly_do.dart';
import 'package:dclass_mobile/models/qua_tang.dart';

/// Hand-rolled fake for [DiemApi] - lets tests script exactly what each call
/// returns or throws, and records call order (for asserting the sync engine
/// replays the outbox sequentially and in order).
class FakeDiemApi implements DiemApi {
  List<HocSinh> hocSinhTraVe = [];
  List<LyDo> lyDoTraVe = [];
  List<QuaTang> quaTangTraVe = [];

  /// Set to make the corresponding read throw instead of returning data -
  /// simulates being offline.
  Object? loiHocSinh;
  Object? loiLyDo;
  Object? loiQuaTang;

  /// Queue of results `congDiem`/`quyDoiQuaTang` return, one per call, in
  /// order. Each entry is either a `Map<String, dynamic>` (success payload)
  /// or any other [Object], which is thrown as-is.
  final List<Object> hangDoiKetQua = [];

  /// `'congDiem:<clientActionId>'` / `'quyDoiQuaTang:<clientActionId>'` for
  /// every write call made so far, in call order.
  final List<String> lenhDaGoi = [];

  @override
  Future<List<HocSinh>> danhSachHocSinh({
    int? lopHocId,
    String tuKhoa = '',
  }) async {
    if (loiHocSinh != null) throw loiHocSinh!;
    return hocSinhTraVe;
  }

  @override
  Future<List<LyDo>> danhSachLyDo() async {
    if (loiLyDo != null) throw loiLyDo!;
    return lyDoTraVe;
  }

  @override
  Future<List<QuaTang>> danhSachQuaTang() async {
    if (loiQuaTang != null) throw loiQuaTang!;
    return quaTangTraVe;
  }

  Object _layKetQuaTiepTheo(String tenLenh, String? clientActionId) {
    lenhDaGoi.add('$tenLenh:${clientActionId ?? ''}');
    if (hangDoiKetQua.isEmpty) {
      throw StateError('FakeDiemApi: no scripted result left for $tenLenh');
    }
    return hangDoiKetQua.removeAt(0);
  }

  @override
  Future<Map<String, dynamic>> congDiem({
    required int hocSinhId,
    required int lyDoId,
    String ghiChu = '',
    String? clientActionId,
  }) async {
    final ketQua = _layKetQuaTiepTheo('congDiem', clientActionId);
    if (ketQua is Map<String, dynamic>) return ketQua;
    throw ketQua;
  }

  @override
  Future<Map<String, dynamic>> quyDoiQuaTang({
    required int hocSinhId,
    required int quaTangId,
    String ghiChu = '',
    String? clientActionId,
  }) async {
    final ketQua = _layKetQuaTiepTheo('quyDoiQuaTang', clientActionId);
    if (ketQua is Map<String, dynamic>) return ketQua;
    throw ketQua;
  }
}
