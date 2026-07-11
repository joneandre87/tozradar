import 'package:flutter/material.dart';
import 'package:flutter_dotenv/flutter_dotenv.dart';
import 'package:provider/provider.dart';
import '../core/api_client.dart';
import '../core/auth_kernel.dart';
import '../core/dev_mode.dart';
import 'dev_console_screen.dart';

/// Account, backend connection status, and logout. Everything shown
/// here is real: the user block comes from the authenticated session,
/// and the backend status is a live stats.public call — not a
/// hardcoded "connected" label.
///
/// Hidden developer gate: tapping the title 7 times toggles DevMode,
/// which reveals the developer console entry. Normal users never see
/// it, and the console's backend data is additionally gated
/// server-side (system.health checks the account).
class SettingsScreen extends StatefulWidget {
  const SettingsScreen({super.key});

  @override
  State<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends State<SettingsScreen> {
  String _backendStatus = 'Sjekker...';
  int _titleTaps = 0;

  @override
  void initState() {
    super.initState();
    _checkBackend();
  }

  Future<void> _checkBackend() async {
    final api = context.read<ApiClient>();
    final r = await api.call('stats.public');
    if (!mounted) return;
    setState(() {
      _backendStatus = r['error'] != null
          ? 'Frakoblet — ${r['error']}'
          : 'Tilkoblet (${r['data']?['total_users'] ?? '?'} brukere)';
    });
  }

  Future<void> _onTitleTap() async {
    _titleTaps++;
    if (_titleTaps < 7) return;
    _titleTaps = 0;
    final devMode = context.read<DevMode>();
    await devMode.set(!devMode.enabled);
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(devMode.enabled ? 'Utviklermodus PÅ' : 'Utviklermodus AV'),
      duration: const Duration(seconds: 2),
    ));
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthKernel>();
    final devMode = context.watch<DevMode>();
    final user = auth.currentUser;
    return SafeArea(
      child: ListView(
        padding: const EdgeInsets.all(18),
        children: [
          GestureDetector(
            onTap: _onTitleTap,
            child: const Text('Innstillinger',
                style: TextStyle(fontSize: 26, fontWeight: FontWeight.bold)),
          ),
          const SizedBox(height: 16),
          if (user != null)
            Card(
              child: ListTile(
                leading: const Icon(Icons.person_outline),
                title: Text(user['name']?.toString() ?? ''),
                subtitle: Text(user['email']?.toString() ?? ''),
                trailing: Chip(
                  label: Text(
                    '${user['tier']} · ${user['tier'] == 'omega' ? '∞' : user['credits']} ✦',
                  ),
                ),
              ),
            ),
          Card(
            child: ListTile(
              leading: const Icon(Icons.cloud_outlined),
              title: const Text('Backend'),
              subtitle: Text(
                  '${dotenv.env['TOZA_API_BASE'] ?? '(ikke satt)'}\n$_backendStatus'),
              isThreeLine: true,
              trailing: IconButton(
                icon: const Icon(Icons.refresh),
                onPressed: () {
                  setState(() => _backendStatus = 'Sjekker...');
                  _checkBackend();
                },
              ),
            ),
          ),
          if (devMode.enabled)
            Card(
              child: ListTile(
                leading: Icon(Icons.terminal,
                    color: Theme.of(context).colorScheme.primary),
                title: const Text('Utviklerkonsoll'),
                subtitle: const Text('Systemhelse, telemetri, logger'),
                onTap: () => Navigator.of(context).push(
                  MaterialPageRoute(builder: (_) => const DevConsoleScreen()),
                ),
              ),
            ),
          const SizedBox(height: 16),
          FilledButton.tonalIcon(
            icon: const Icon(Icons.logout),
            label: const Text('Logg ut'),
            onPressed: () => auth.logout(),
          ),
        ],
      ),
    );
  }
}
