import 'dart:convert';
import 'package:flutter_dotenv/flutter_dotenv.dart';
import 'package:http/http.dart' as http;
import 'dev_telemetry.dart';
import 'token_store.dart';

/// Single HTTP gateway to the Tøza backend (tozastarter/api/index.php).
/// No service or widget should call `http` directly — every request
/// goes through here so the base URL, auth header and error shape stay
/// consistent in exactly one place. `TOZA_API_BASE` should point at
/// `https://toza.tozradar.com/api/index.php` (or the local equivalent).
class ApiClient {
  final TokenStore tokenStore;
  ApiClient(this.tokenStore);

  String get _base => dotenv.env['TOZA_API_BASE'] ?? '';

  /// Calls [action] on the API and always returns a decoded JSON map —
  /// network/parse failures are folded into `{'error': ...}` so callers
  /// never need a try/catch of their own. Every round trip is timed
  /// and recorded in DevTelemetry for the developer console.
  Future<Map<String, dynamic>> call(String action, [Map<String, dynamic> body = const {}]) async {
    final token = await tokenStore.read();
    final sw = Stopwatch()..start();
    Map<String, dynamic> result;
    try {
      final response = await http
          .post(
            Uri.parse('$_base?action=$action'),
            headers: {
              'Content-Type': 'application/json',
              if (token != null) 'Authorization': 'Bearer $token',
            },
            body: jsonEncode(body),
          )
          .timeout(const Duration(seconds: 30));
      final decoded = jsonDecode(response.body);
      result = decoded is Map<String, dynamic>
          ? decoded
          : {'error': 'Uventet svarformat fra serveren.'};
    } catch (e) {
      result = {'error': 'Nettverksfeil: $e'};
    }
    sw.stop();
    DevTelemetry.instance
        .recordApiCall(action, sw.elapsedMilliseconds, result['error'] == null);
    return result;
  }
}
