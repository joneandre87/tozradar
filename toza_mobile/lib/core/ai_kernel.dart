import 'api_client.dart';

/// Result of an AIKernel request. Never thrown as an exception — a
/// failed call is just a result with [error] set, so UI code can
/// render it directly without a try/catch.
class AIKernelResult {
  final String? reply;
  final int? sessionId;
  final String? error;
  const AIKernelResult({this.reply, this.sessionId, this.error});
  bool get isSuccess => error == null;
}

/// The only door into AI functionality on the client. ChatService,
/// and any future voice/vision feature, must call through here
/// instead of hitting the backend directly. Provider choice, quota
/// enforcement and prompt/tool selection all live server-side behind
/// this one call so they can change without touching the UI layer.
class AIKernel {
  final ApiClient api;
  AIKernel(this.api);

  Future<AIKernelResult> chat({
    required String message,
    String tool = 'intel',
    int? sessionId,
  }) async {
    final res = await api.call('ai.chat', {
      'message': message,
      'tool': tool,
      'session_id': sessionId,
    });
    if (res['error'] != null) {
      return AIKernelResult(error: res['error'].toString());
    }
    final data = res['data'];
    if (data is! Map) return const AIKernelResult(error: 'Tomt svar fra serveren.');
    return AIKernelResult(
      reply: data['reply'] as String?,
      sessionId: data['session_id'] as int?,
    );
  }
}
