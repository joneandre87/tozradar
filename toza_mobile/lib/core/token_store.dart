import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Encrypted, persistent storage for the session token. This is the
/// first concrete piece of what the roadmap calls SecurityKernel —
/// everything that needs auth reads the token from here instead of
/// holding it in memory or widget state, so it survives app restarts
/// and never touches plain SharedPreferences.
class TokenStore {
  static const _key = 'toza_session_token';
  final FlutterSecureStorage _storage;

  TokenStore({FlutterSecureStorage? storage}) : _storage = storage ?? const FlutterSecureStorage();

  Future<String?> read() => _storage.read(key: _key);
  Future<void> save(String token) => _storage.write(key: _key, value: token);
  Future<void> clear() => _storage.delete(key: _key);
}
