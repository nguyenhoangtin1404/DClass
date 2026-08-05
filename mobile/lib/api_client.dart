import 'dart:convert';
import 'package:http/http.dart' as http;

import 'models/hoc_sinh.dart';
import 'models/ly_do.dart';
import 'models/qua_tang.dart';

/// Thrown when the DClass API responds with `ok: false`, or with a
/// non-2xx HTTP status.
class ApiException implements Exception {
  final String thongBao;
  ApiException(this.thongBao);

  @override
  String toString() => thongBao;
}

/// Contract for the DClass teacher-facing JSON API, extracted so tests (the
/// sync engine and repositories) can depend on this instead of [ApiClient]
/// directly and substitute a fake with scripted responses/exceptions.
abstract class DiemApi {
  Future<List<HocSinh>> danhSachHocSinh({int? lopHocId, String tuKhoa = ''});
  Future<List<LyDo>> danhSachLyDo();
  Future<List<QuaTang>> danhSachQuaTang();

  Future<Map<String, dynamic>> congDiem({
    required int hocSinhId,
    required int lyDoId,
    String ghiChu = '',
    String? clientActionId,
  });

  Future<Map<String, dynamic>> quyDoiQuaTang({
    required int hocSinhId,
    required int quaTangId,
    String ghiChu = '',
    String? clientActionId,
  });
}

/// Thin client for the DClass teacher-facing JSON API (api/*.php), using the
/// Bearer token auth added for the mobile app (see api/dang_nhap.php and
/// lib/tro_giup.php::thu_xac_thuc_bang_token).
class ApiClient implements DiemApi {
  final String baseUrl;
  final String token;

  ApiClient({required this.baseUrl, required this.token});

  Uri _uri(String path, [Map<String, String>? query]) {
    return Uri.parse('$baseUrl$path').replace(queryParameters: query);
  }

  Map<String, String> get _headers => {
        'Authorization': 'Bearer $token',
        'Content-Type': 'application/json',
      };

  dynamic _giaiMa(http.Response res) {
    if (res.statusCode < 200 || res.statusCode >= 300) {
      throw ApiException('loi_http_${res.statusCode}');
    }
    final body = jsonDecode(res.body) as Map<String, dynamic>;
    if (body['ok'] != true) {
      throw ApiException(
        (body['thong_bao'] as String?) ?? 'loi_khong_xac_dinh',
      );
    }
    return body['du_lieu'];
  }

  Future<List<HocSinh>> danhSachHocSinh({
    int? lopHocId,
    String tuKhoa = '',
  }) async {
    final query = <String, String>{};
    if (lopHocId != null) query['lop_hoc_id'] = '$lopHocId';
    if (tuKhoa.isNotEmpty) query['tu_khoa'] = tuKhoa;
    final res = await http.get(
      _uri('/api/hoc_sinh.php', query),
      headers: _headers,
    );
    final data = _giaiMa(res) as List<dynamic>;
    return data
        .map((e) => HocSinh.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<List<LyDo>> danhSachLyDo() async {
    final res = await http.get(_uri('/api/ly_do.php'), headers: _headers);
    final data = _giaiMa(res) as List<dynamic>;
    return data.map((e) => LyDo.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<List<QuaTang>> danhSachQuaTang() async {
    final res = await http.get(_uri('/api/qua_tang.php'), headers: _headers);
    final data = _giaiMa(res) as List<dynamic>;
    return data
        .map((e) => QuaTang.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  /// [clientActionId] should be a stable id (e.g. a uuid generated once per
  /// offline action) so a retried sync after a dropped connection doesn't
  /// double-apply the same transaction. See so_cai_diem.client_action_id.
  Future<Map<String, dynamic>> congDiem({
    required int hocSinhId,
    required int lyDoId,
    String ghiChu = '',
    String? clientActionId,
  }) async {
    final res = await http.post(
      _uri('/api/diem.php', {'hanh_dong': 'cong'}),
      headers: _headers,
      body: jsonEncode({
        'hoc_sinh_id': hocSinhId,
        'ly_do_id': lyDoId,
        'ghi_chu': ghiChu,
        if (clientActionId != null) 'client_action_id': clientActionId,
      }),
    );
    return _giaiMa(res) as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> quyDoiQuaTang({
    required int hocSinhId,
    required int quaTangId,
    String ghiChu = '',
    String? clientActionId,
  }) async {
    final res = await http.post(
      _uri('/api/diem.php', {'hanh_dong': 'quy_doi'}),
      headers: _headers,
      body: jsonEncode({
        'hoc_sinh_id': hocSinhId,
        'qua_tang_id': quaTangId,
        'ghi_chu': ghiChu,
        if (clientActionId != null) 'client_action_id': clientActionId,
      }),
    );
    return _giaiMa(res) as Map<String, dynamic>;
  }
}
