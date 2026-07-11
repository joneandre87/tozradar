import 'package:flutter_test/flutter_test.dart';
import 'package:toza_mobile/core/ai_kernel.dart';
import 'package:toza_mobile/models/chat_message.dart';
import 'package:toza_mobile/models/memory_item.dart';

void main() {
  group('ChatMessage', () {
    test('serializes role, content and timestamp', () {
      final m = ChatMessage(role: 'user', content: 'hei');
      final json = m.toJson();
      expect(json['role'], 'user');
      expect(json['content'], 'hei');
      expect(DateTime.parse(json['created_at'] as String), isA<DateTime>());
    });
  });

  group('MemoryItem', () {
    test('defaults category to general and serializes', () {
      final item = MemoryItem(key: 'k', value: 'v');
      final map = item.toMap();
      expect(map['key'], 'k');
      expect(map['value'], 'v');
      expect(map['category'], 'general');
      expect(map['id'], isNull);
    });

    test('preserves explicit category and id', () {
      final item = MemoryItem(id: 7, key: 'k', value: 'v', category: 'project');
      final map = item.toMap();
      expect(map['id'], 7);
      expect(map['category'], 'project');
    });
  });

  group('AIKernelResult', () {
    test('isSuccess is true only when error is null', () {
      expect(const AIKernelResult(reply: 'ok', sessionId: 1).isSuccess, isTrue);
      expect(const AIKernelResult(error: 'nede').isSuccess, isFalse);
    });
  });
}
