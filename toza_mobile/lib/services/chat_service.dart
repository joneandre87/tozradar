import 'package:flutter/foundation.dart';
import '../core/ai_kernel.dart';
import '../core/memory_kernel.dart';
import '../models/chat_message.dart';
import '../models/memory_item.dart';

/// Chat screen state + orchestration. All AI calls go through
/// [AIKernel] and all memory writes through [MemoryKernel] — this class
/// never builds an HTTP request or touches SQLite itself, so swapping
/// backends or storage never touches this file.
class ChatService extends ChangeNotifier {
  final AIKernel ai;
  final MemoryKernel memory;
  final List<ChatMessage> messages = [];
  bool loading = false;
  String? error;
  int? _sessionId;

  ChatService(this.ai, this.memory);

  Future<void> send(String text) async {
    final clean = text.trim();
    if (clean.isEmpty) return;

    messages.add(ChatMessage(role: 'user', content: clean));
    notifyListeners();

    if (clean.toLowerCase().startsWith('husk ')) {
      final value = clean.substring(5).trim();
      // Key must be unique per memory: both stores upsert by key, so a
      // fixed key would make every «husk» overwrite the previous one.
      final key = 'husk_${DateTime.now().millisecondsSinceEpoch}';
      // remember() writes the local cache AND pushes to the server, so
      // the memory survives reinstall and shows up on other devices.
      await memory.remember(MemoryItem(key: key, value: value, category: 'user'));
      messages.add(ChatMessage(role: 'assistant', content: 'Lagret i Tøza-minnet: $value'));
      notifyListeners();
      return;
    }

    loading = true;
    error = null;
    notifyListeners();

    final result = await ai.chat(message: clean, sessionId: _sessionId);
    if (result.isSuccess) {
      _sessionId = result.sessionId ?? _sessionId;
      messages.add(ChatMessage(role: 'assistant', content: result.reply ?? 'Ingen respons.'));
    } else {
      error = result.error;
      messages.add(ChatMessage(role: 'assistant', content: 'Jeg fikk ikke kontakt med Tøza-serveren akkurat nå.'));
    }
    loading = false;
    notifyListeners();
  }
}
