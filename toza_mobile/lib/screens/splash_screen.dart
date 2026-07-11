import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../core/auth_kernel.dart';
import '../core/memory_kernel.dart';
import '../services/memory_service.dart';
import 'auth_screen.dart';

/// Shown while the app initializes: opens the local SQLite database,
/// tries to resume a stored session, and syncs memory if signed in.
/// This work used to block `runApp` in main() — moving it here means
/// the window appears instantly and the user watches a branded splash
/// instead of a blank screen.
class SplashGate extends StatefulWidget {
  const SplashGate({super.key});

  @override
  State<SplashGate> createState() => _SplashGateState();
}

class _SplashGateState extends State<SplashGate> {
  bool _ready = false;

  @override
  void initState() {
    super.initState();
    _init();
  }

  Future<void> _init() async {
    final memory = context.read<MemoryService>();
    final auth = context.read<AuthKernel>();
    final memoryKernel = context.read<MemoryKernel>();

    // Whatever fails here (no network, no local database, corrupt
    // token), the app must still reach the login screen — startup
    // never hangs on the splash.
    try {
      await memory.init();
      final signedIn = await auth.restoreSession();
      if (signedIn) await memoryKernel.sync();
    } catch (e) {
      debugPrint('SplashGate: init degraded: $e');
    }

    if (mounted) setState(() => _ready = true);
  }

  @override
  Widget build(BuildContext context) {
    if (_ready) return const AuthGate();
    return Scaffold(
      body: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              '✦',
              style: TextStyle(
                fontSize: 56,
                color: Theme.of(context).colorScheme.primary,
              ),
            ),
            const SizedBox(height: 16),
            Text('TØZA', style: Theme.of(context).textTheme.headlineMedium),
            const SizedBox(height: 4),
            Text(
              'AI Operating System',
              style: Theme.of(context).textTheme.bodySmall,
            ),
            const SizedBox(height: 32),
            const SizedBox(
              width: 22,
              height: 22,
              child: CircularProgressIndicator(strokeWidth: 2),
            ),
          ],
        ),
      ),
    );
  }
}
