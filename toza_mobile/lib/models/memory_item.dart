class MemoryItem {
  final int? id;
  final String key;
  final String value;
  final String category;
  final DateTime createdAt;

  MemoryItem({
    this.id,
    required this.key,
    required this.value,
    this.category = 'general',
    DateTime? createdAt,
  }) : createdAt = createdAt ?? DateTime.now();

  Map<String, dynamic> toMap() => {
        'id': id,
        'key': key,
        'value': value,
        'category': category,
        'created_at': createdAt.toIso8601String(),
      };
}
