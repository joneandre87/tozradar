import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../core/auth_kernel.dart';
import '../models/memory_item.dart';
import '../services/memory_service.dart';
import 'chat_screen.dart';
import 'memory_screen.dart';
import 'settings_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int index = 0;

  void goToTab(int i) => setState(() => index = i);

  final screens = const [
    DashboardView(),
    ChatScreen(),
    MemoryScreen(),
    SettingsScreen(),
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: screens[index],
      bottomNavigationBar: NavigationBar(
        selectedIndex: index,
        onDestinationSelected: (i) => setState(() => index = i),
        destinations: const [
          NavigationDestination(icon: Icon(Icons.auto_awesome), label: 'Tøza'),
          NavigationDestination(icon: Icon(Icons.chat_bubble_outline), label: 'Chat'),
          NavigationDestination(icon: Icon(Icons.memory), label: 'Minne'),
          NavigationDestination(icon: Icon(Icons.settings_outlined), label: 'Innstillinger'),
        ],
      ),
    );
  }
}

/// Dashboard backed by real session and database state: the greeting,
/// tier and credits come from the authenticated user; the memory card
/// reads the actual local SQLite cache. No hardcoded numbers.
class DashboardView extends StatelessWidget {
  const DashboardView({super.key});

  void _goToTab(BuildContext context, int tab) {
    context.findAncestorStateOfType<_HomeScreenState>()?.goToTab(tab);
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthKernel>();
    final user = auth.currentUser;
    final memory = context.read<MemoryService>();
    return SafeArea(
      child: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Text(
            user != null ? 'Hei, ${user['name']}' : 'Tøza AI',
            style: const TextStyle(fontSize: 34, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 8),
          if (user != null)
            Text(
              'Plan: ${user['tier']} · Kreditter: ${user['tier'] == 'omega' ? '∞' : user['credits']}',
              style: Theme.of(context).textTheme.titleMedium,
            ),
          const SizedBox(height: 24),
          Card(
            child: ListTile(
              leading: Icon(Icons.psychology_outlined,
                  color: Theme.of(context).colorScheme.primary),
              title: const Text('Snakk med Tøza'),
              subtitle: const Text('AI-chat via AIKernel — GPT-4o på norsk'),
              onTap: () => _goToTab(context, 1),
            ),
          ),
          FutureBuilder<List<MemoryItem>>(
            future: memory.getMemories(),
            builder: (context, snapshot) {
              final items = snapshot.data ?? [];
              return Card(
                child: ListTile(
                  leading: Icon(Icons.memory,
                      color: Theme.of(context).colorScheme.primary),
                  title: const Text('Tøza-minne'),
                  subtitle: Text(items.isEmpty
                      ? 'Ingen minner ennå — skriv «husk …» i chatten'
                      : '${items.length} minner · siste: ${items.first.value}'),
                  onTap: () => _goToTab(context, 2),
                ),
              );
            },
          ),
        ],
      ),
    );
  }
}
