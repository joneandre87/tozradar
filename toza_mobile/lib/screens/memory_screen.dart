import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/memory_service.dart';

class MemoryScreen extends StatelessWidget {
  const MemoryScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final memory = context.read<MemoryService>();
    return SafeArea(
      child: FutureBuilder(
        future: memory.getMemories(),
        builder: (context, snapshot) {
          final items = snapshot.data ?? [];
          return ListView(
            padding: const EdgeInsets.all(18),
            children: [
              const Text('Tøza-minne', style: TextStyle(fontSize: 26, fontWeight: FontWeight.bold)),
              const SizedBox(height: 14),
              if (items.isEmpty) const Text('Ingen minner enda. Gå til chat og skriv: husk ...'),
              for (final item in items)
                Card(
                  child: ListTile(
                    title: Text(item.key),
                    subtitle: Text(item.value),
                    trailing: Text(item.category),
                  ),
                ),
            ],
          );
        },
      ),
    );
  }
}
