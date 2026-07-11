import '../models/memory_item.dart';
import '../services/memory_service.dart';
import 'api_client.dart';
import 'dev_telemetry.dart';

/// Bridges the on-device SQLite cache ([MemoryService]) with the
/// server-side long-term memory (the memory.* actions in
/// tozastarter/api/index.php). Reads prefer the fast local cache so the
/// app stays responsive offline; writes go to both so the phone and the
/// account stay in sync once connectivity returns.
class MemoryKernel {
  final MemoryService local;
  final ApiClient api;
  MemoryKernel(this.local, this.api);

  Future<void> remember(MemoryItem item) async {
    await local.saveMemory(item);
    await api.call('memory.remember', {
      'category': item.category,
      'key': item.key,
      'value': item.value,
    });
  }

  Future<List<MemoryItem>> list({String? category}) => local.getMemories(category: category);

  /// Pulls server memories into the local cache. Safe to call on app
  /// start and after reconnect — local rows are unique on
  /// (category, key), so re-syncing overwrites the existing row instead
  /// of duplicating it.
  Future<void> sync() async {
    final res = await api.call('memory.list');
    final rows = res['data'];
    if (rows is! List) {
      DevTelemetry.instance.recordSync(false);
      return;
    }
    for (final row in rows) {
      if (row is! Map) continue;
      await local.saveMemory(MemoryItem(
        key: row['mem_key'] as String? ?? '',
        value: row['value'] as String? ?? '',
        category: row['category'] as String? ?? 'general',
      ));
    }
    DevTelemetry.instance.recordSync(true);
  }
}
