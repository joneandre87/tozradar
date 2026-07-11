import 'package:flutter/foundation.dart';
import 'api_client.dart';
import 'dev_telemetry.dart';
import 'token_store.dart';

/// Owns login/register/logout and the current user snapshot
/// (id/name/email/tier/credits, mirroring the backend's users table).
/// Any authenticated feature (chat, memory sync) checks
/// [currentUser] before running.
class AuthKernel extends ChangeNotifier {
  final ApiClient api;
  final TokenStore tokenStore;
  Map<String, dynamic>? currentUser;

  AuthKernel(this.api, this.tokenStore);

  /// Tries to resume a session from a previously stored token.
  /// Returns true if a user is now signed in.
  Future<bool> restoreSession() async {
    final res = await api.call('auth.me');
    if (res['success'] == true) {
      currentUser = res['user'] as Map<String, dynamic>?;
      DevTelemetry.instance.log('auth', 'Sesjon gjenopprettet');
      notifyListeners();
      return true;
    }
    return false;
  }

  /// Returns an error message on failure, or null on success.
  Future<String?> login(String email, String password) async {
    final res = await api.call('auth.login', {'email': email, 'password': password});
    return _handleAuthResponse(res);
  }

  /// Returns an error message on failure, or null on success.
  Future<String?> register(String name, String email, String password) async {
    final res = await api.call('auth.register', {'name': name, 'email': email, 'password': password});
    return _handleAuthResponse(res);
  }

  Future<void> logout() async {
    await api.call('auth.logout');
    await tokenStore.clear();
    currentUser = null;
    DevTelemetry.instance.log('auth', 'Logget ut');
    notifyListeners();
  }

  Future<String?> _handleAuthResponse(Map<String, dynamic> res) async {
    if (res['error'] != null) return res['error'].toString();
    final token = res['token'] as String?;
    if (token != null) await tokenStore.save(token);
    currentUser = res['user'] as Map<String, dynamic>?;
    DevTelemetry.instance.log('auth', 'Innlogget: ${currentUser?['email']}');
    notifyListeners();
    return null;
  }
}
