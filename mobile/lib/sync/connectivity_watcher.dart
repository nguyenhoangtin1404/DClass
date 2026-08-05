import 'package:connectivity_plus/connectivity_plus.dart';

/// Collapses connectivity_plus's per-interface result list down to the
/// single boolean the sync engine cares about: "is there any connection at
/// all right now".
class ConnectivityWatcher {
  ConnectivityWatcher() : _connectivity = Connectivity();
  final Connectivity _connectivity;

  /// Emits whenever overall connectivity changes (regained or lost).
  Stream<bool> get thayDoi {
    return _connectivity.onConnectivityChanged.map(_coKetNoi).distinct();
  }

  Future<bool> coKetNoiHienTai() async {
    return _coKetNoi(await _connectivity.checkConnectivity());
  }

  bool _coKetNoi(List<ConnectivityResult> ds) {
    return ds.any((r) => r != ConnectivityResult.none);
  }
}
