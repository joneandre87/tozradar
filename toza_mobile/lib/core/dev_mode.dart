import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Hidden developer-mode gate. Enabled by tapping the Settings title 7
/// times (see SettingsScreen); persisted so it survives restarts.
/// Normal users never see the console entry point — and the backend's
/// system.health action is additionally gated server-side, so hiding
/// the UI is not the only line of defense.
class DevMode extends ChangeNotifier {
  static const _key = 'toza_dev_mode';
  final FlutterSecureStorage _storage;
  bool _enabled = false;

  DevMode({FlutterSecureStorage? storage})
      : _storage = storage ?? const FlutterSecureStorage();

  bool get enabled => _enabled;

  Future<void> load() async {
    try {
      _enabled = (await _storage.read(key: _key)) == '1';
    } catch (_) {
      _enabled = false;
    }
    notifyListeners();
  }

  Future<void> set(bool value) async {
    _enabled = value;
    notifyListeners();
    try {
      await _storage.write(key: _key, value: value ? '1' : '0');
    } catch (_) {
      // Persistence failing (e.g. web without storage) still leaves
      // dev mode active for this session.
    }
  }
}
