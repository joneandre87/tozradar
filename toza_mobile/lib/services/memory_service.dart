import 'package:flutter/foundation.dart';
import 'package:path/path.dart';
import 'package:sqflite/sqflite.dart';
import '../models/memory_item.dart';

class MemoryService {
  Database? _db;

  /// True when the local cache is usable. False on web (sqflite has no
  /// web implementation) or if the database failed to open — the app
  /// keeps running either way; memory features degrade gracefully
  /// instead of bricking startup.
  bool get isAvailable => _db != null;

  Future<void> init() async {
    try {
      await _open();
    } catch (e) {
      debugPrint('MemoryService: local database unavailable: $e');
    }
  }

  Future<void> _open() async {
    final path = join(await getDatabasesPath(), 'toza_memory.db');
    _db = await openDatabase(
      path,
      version: 2,
      onCreate: (db, version) async {
        await db.execute('''
          CREATE TABLE memories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            key TEXT NOT NULL,
            value TEXT NOT NULL,
            category TEXT NOT NULL,
            created_at TEXT NOT NULL
          )
        ''');
        await db.execute('CREATE UNIQUE INDEX idx_memories_cat_key ON memories(category, key)');
        await db.execute('''
          CREATE TABLE commands (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            phrase TEXT NOT NULL,
            action TEXT NOT NULL,
            payload TEXT,
            created_at TEXT NOT NULL
          )
        ''');
      },
      onUpgrade: (db, oldVersion, newVersion) async {
        if (oldVersion < 2) {
          // MemoryKernel.sync() re-saves rows pulled from the server on
          // every app start; without this uniqueness constraint that
          // would insert a fresh duplicate row each time instead of
          // updating the existing one.
          await db.execute('CREATE UNIQUE INDEX IF NOT EXISTS idx_memories_cat_key ON memories(category, key)');
        }
      },
    );
  }

  /// Upserts by (category, key) — see the unique index created in
  /// [_open]. `ConflictAlgorithm.replace` deletes and re-inserts the
  /// conflicting row, so `id` is not stable across saves; nothing in
  /// this app currently depends on a memory's id staying fixed.
  Future<void> saveMemory(MemoryItem item) async {
    final db = _db;
    if (db == null) return;
    await db.insert('memories', item.toMap(), conflictAlgorithm: ConflictAlgorithm.replace);
  }

  Future<List<MemoryItem>> getMemories({String? category}) async {
    final db = _db;
    if (db == null) return [];
    final rows = await db.query(
      'memories',
      where: category == null ? null : 'category = ?',
      whereArgs: category == null ? null : [category],
      orderBy: 'created_at DESC',
      limit: 50,
    );
    return rows.map((r) => MemoryItem(
      id: r['id'] as int?,
      key: r['key'] as String,
      value: r['value'] as String,
      category: r['category'] as String,
      createdAt: DateTime.tryParse(r['created_at'] as String),
    )).toList();
  }

  Future<String> compactContext() async {
    final items = await getMemories();
    if (items.isEmpty) return 'Ingen lagrede minner enda.';
    return items.map((m) => '- ${m.category}: ${m.key} = ${m.value}').join('\n');
  }

  // ── Developer-console introspection (real queries, null = unavailable) ──

  Future<int?> totalCount() async {
    final db = _db;
    if (db == null) return null;
    return Sqflite.firstIntValue(await db.rawQuery('SELECT COUNT(*) FROM memories'));
  }

  Future<Map<String, int>> categoryCounts() async {
    final db = _db;
    if (db == null) return {};
    final rows = await db.rawQuery(
        'SELECT category, COUNT(*) AS c FROM memories GROUP BY category ORDER BY c DESC');
    return {for (final r in rows) r['category'] as String: r['c'] as int};
  }

  /// Rows sharing the same (category, key). The unique index makes this
  /// structurally impossible, so a non-zero result means the invariant
  /// broke — that is exactly what a dev console should catch.
  Future<int?> duplicateCount() async {
    final db = _db;
    if (db == null) return null;
    final rows = await db.rawQuery(
        'SELECT COUNT(*) AS c FROM (SELECT category, key FROM memories GROUP BY category, key HAVING COUNT(*) > 1)');
    return rows.isEmpty ? 0 : rows.first['c'] as int;
  }

  /// Real database file size via SQLite pragmas — no dart:io, so this
  /// compiles on web too (where it simply returns null).
  Future<int?> cacheSizeBytes() async {
    final db = _db;
    if (db == null) return null;
    final pageCount = Sqflite.firstIntValue(await db.rawQuery('PRAGMA page_count'));
    final pageSize = Sqflite.firstIntValue(await db.rawQuery('PRAGMA page_size'));
    if (pageCount == null || pageSize == null) return null;
    return pageCount * pageSize;
  }
}
