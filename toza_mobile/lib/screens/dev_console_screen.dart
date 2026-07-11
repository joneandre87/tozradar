import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_dotenv/flutter_dotenv.dart';
import 'package:provider/provider.dart';

import '../core/api_client.dart';
import '../core/dev_telemetry.dart';
import '../services/memory_service.dart';
import '../widgets/dev_panel_widgets.dart';

/// Internal developer console. Reached only through the hidden gate in
/// Settings (7 taps on the title) — and the backend's system.health
/// action is gated server-side on top, so this screen leaking would
/// still not expose server internals to a normal account.
///
/// Ground rules, enforced throughout: every value shown is either a
/// real measurement/real query result, or an explicit "Not Available"
/// for services that don't exist yet. No mock data anywhere.
class DevConsoleScreen extends StatefulWidget {
  const DevConsoleScreen({super.key});

  @override
  State<DevConsoleScreen> createState() => _DevConsoleScreenState();
}

class _DevConsoleScreenState extends State<DevConsoleScreen> {
  Map<String, dynamic>? _health;
  String? _healthError;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _refresh();
  }

  Future<void> _refresh() async {
    setState(() => _loading = true);
    final res = await context.read<ApiClient>().call('system.health');
    if (!mounted) return;
    setState(() {
      _loading = false;
      if (res['error'] != null) {
        _health = null;
        _healthError = res['error'].toString();
      } else {
        _health = res['data'] as Map<String, dynamic>?;
        _healthError = null;
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final memory = context.read<MemoryService>();
    return Scaffold(
      appBar: AppBar(
        title: const Text('Utviklerkonsoll'),
        actions: [
          if (_loading)
            const Padding(
              padding: EdgeInsets.all(14),
              child: SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2)),
            )
          else
            IconButton(icon: const Icon(Icons.refresh), onPressed: _refresh),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _refresh,
        child: ListView(
          padding: const EdgeInsets.all(10),
          children: [
            SystemHealthPanel(health: _health, healthError: _healthError, memory: memory),
            AIPanel(health: _health),
            MemoryPanel(memory: memory),
            BackendPanel(health: _health),
            const FlutterPanel(),
            DatabasePanel(health: _health),
            const PluginsPanel(),
            const LogsPanel(),
            const EventBusPanel(),
            const PerformancePanel(),
            const DebugPanel(),
            ProfilerPanel(health: _health),
          ],
        ),
      ),
    );
  }
}

// ─── Panels — each an independent widget ────────────────────────────

class SystemHealthPanel extends StatelessWidget {
  final Map<String, dynamic>? health;
  final String? healthError;
  final MemoryService memory;
  const SystemHealthPanel({super.key, this.health, this.healthError, required this.memory});

  @override
  Widget build(BuildContext context) {
    final db = health?['db'] as Map<String, dynamic>?;
    final ai = health?['ai'] as Map<String, dynamic>?;
    final providers = ai?['providers'] as Map<String, dynamic>?;
    final anyProvider = providers?.values.any((p) => p is Map && p['configured'] == true);
    final t = DevTelemetry.instance;
    return DevPanel(
      title: 'Systemhelse',
      icon: Icons.monitor_heart_outlined,
      initiallyExpanded: true,
      children: [
        StatusRow('API', health != null, detail: health != null ? 'OK' : (healthError ?? 'utilgjengelig')),
        StatusRow('Database', db == null ? null : db['status'] == 'ok',
            detail: db == null ? null : '${db['status']} (${db['latency_ms']} ms)'),
        StatusRow('AI-leverandør', anyProvider),
        StatusRow('Lokalt minne (SQLite)', memory.isAvailable),
        StatusRow('Autentisering', t.logs.any((l) => l.type == 'auth' && l.message.contains('Innlogget') || l.message.contains('gjenopprettet'))
            ? true
            : null,
            detail: 'se logg-panelet'),
        StatusRow('Nettverk', t.lastSuccessfulRequest != null,
            detail: t.lastSuccessfulRequest != null ? 'sist OK ${_ago(t.lastSuccessfulRequest!)}' : 'ingen vellykkede kall ennå'),
        const NotAvailable('Lagringsforbruk', reason: 'krever plattform-API'),
        const NotAvailable('Kø', reason: 'ingen kø-infrastruktur (EVENT_SYSTEM fase)'),
        const NotAvailable('Bakgrunnsjobber', reason: 'ikke implementert'),
      ],
    );
  }
}

class AIPanel extends StatelessWidget {
  final Map<String, dynamic>? health;
  const AIPanel({super.key, this.health});

  @override
  Widget build(BuildContext context) {
    final ai = health?['ai'] as Map<String, dynamic>?;
    final providers = ai?['providers'] as Map<String, dynamic>? ?? {};
    final defaultProvider = ai?['default_provider']?.toString();
    final t = DevTelemetry.instance;
    final fallbacks = providers.entries
        .where((e) => e.key != defaultProvider && e.value is Map && e.value['configured'] == true)
        .map((e) => e.key)
        .join(', ');
    return DevPanel(
      title: 'AI',
      icon: Icons.psychology_outlined,
      children: [
        KV('Aktiv leverandør', defaultProvider ?? '—'),
        KV('Modell', providers[defaultProvider]?['model']?.toString() ?? '—'),
        KV('Fallback', fallbacks.isEmpty ? 'ingen konfigurert' : fallbacks),
        KV('Responstid (snitt)', t.avgApiLatencyMs != null ? '${t.avgApiLatencyMs!.toStringAsFixed(0)} ms' : '—'),
        KV('Antall kall (denne økten)', '${t.requestCount}'),
        KV('Feil (denne økten)', '${t.errorCount}'),
        KV('Siste vellykkede kall', t.lastSuccessfulRequest != null ? _ago(t.lastSuccessfulRequest!) : '—'),
        const NotAvailable('Token-forbruk', reason: 'backend logger ikke usage ennå'),
      ],
    );
  }
}

class MemoryPanel extends StatelessWidget {
  final MemoryService memory;
  const MemoryPanel({super.key, required this.memory});

  @override
  Widget build(BuildContext context) {
    final t = DevTelemetry.instance;
    return DevPanel(
      title: 'Minne',
      icon: Icons.memory,
      children: [
        FutureBuilder(
          future: Future.wait([
            memory.totalCount(),
            memory.duplicateCount(),
            memory.cacheSizeBytes(),
          ]),
          builder: (context, AsyncSnapshot<List<int?>> snap) {
            final counts = snap.data;
            return Column(children: [
              KV('Antall minner (lokalt)', counts?[0]?.toString() ?? (memory.isAvailable ? '…' : 'Not Available — SQLite utilgjengelig')),
              KV('Duplikater', counts?[1] == null ? '—' : (counts![1] == 0 ? '0 (invariant holder)' : '${counts[1]} ⚠ BRUDD'),
                  valueColor: (counts?[1] ?? 0) > 0 ? Colors.redAccent : null),
              KV('Cache-størrelse', counts?[2] != null ? '${((counts![2]!) / 1024).toStringAsFixed(1)} kB' : '—'),
            ]);
          },
        ),
        FutureBuilder<Map<String, int>>(
          future: memory.categoryCounts(),
          builder: (context, snap) {
            final cats = snap.data ?? {};
            return KV('Kategorier',
                cats.isEmpty ? 'ingen' : cats.entries.map((e) => '${e.key}: ${e.value}').join(', '));
          },
        ),
        KV('Sync-status', t.lastSyncOk == null ? 'ikke kjørt ennå' : (t.lastSyncOk! ? 'OK' : 'FEILET'),
            valueColor: t.lastSyncOk == false ? Colors.redAccent : null),
        KV('Siste sync', t.lastSyncAt != null ? _ago(t.lastSyncAt!) : '—'),
        KV('Mislykkede syncs', '${t.failedSyncCount}'),
      ],
    );
  }
}

class BackendPanel extends StatelessWidget {
  final Map<String, dynamic>? health;
  const BackendPanel({super.key, this.health});

  @override
  Widget build(BuildContext context) {
    final db = health?['db'] as Map<String, dynamic>?;
    final endpoints = (health?['endpoints'] as List?)?.cast<String>() ?? [];
    final lastHealthCall = DevTelemetry.instance.apiCalls
        .where((c) => c.action == 'system.health')
        .toList();
    return DevPanel(
      title: 'Backend',
      icon: Icons.dns_outlined,
      children: [
        KV('PHP-versjon', health?['php_version']?.toString() ?? '—'),
        KV('Servertid', health?['server_time']?.toString() ?? '—'),
        KV('Aktive sesjoner', db?['active_sessions']?.toString() ?? '—'),
        KV('DB-latens (server)', db?['latency_ms'] != null ? '${db!['latency_ms']} ms' : '—'),
        KV('API-latens (klient)',
            lastHealthCall.isNotEmpty ? '${lastHealthCall.last.durationMs} ms' : '—'),
        KV('REST-endepunkter (${endpoints.length})', endpoints.isEmpty ? '—' : endpoints.join(', ')),
      ],
    );
  }
}

class FlutterPanel extends StatelessWidget {
  const FlutterPanel({super.key});

  @override
  Widget build(BuildContext context) {
    final mode = kReleaseMode ? 'release' : (kProfileMode ? 'profile' : 'debug');
    return DevPanel(
      title: 'Flutter',
      icon: Icons.flutter_dash,
      children: [
        KV('Byggmodus', mode),
        KV('Plattform', kIsWeb ? 'web' : defaultTargetPlatform.name),
        KV('TOZA_MODE', dotenv.env['TOZA_MODE'] ?? '(ikke satt)'),
        const NotAvailable('Pakkeversjoner', reason: 'kjør `flutter pub deps` (ingen runtime-API uten package_info_plus)'),
        const NotAvailable('Enhetsinfo', reason: 'device_info_plus ikke installert'),
      ],
    );
  }
}

class DatabasePanel extends StatelessWidget {
  final Map<String, dynamic>? health;
  const DatabasePanel({super.key, this.health});

  @override
  Widget build(BuildContext context) {
    final tables = (health?['db'] as Map<String, dynamic>?)?['tables'] as Map<String, dynamic>?;
    return DevPanel(
      title: 'Database',
      icon: Icons.storage_outlined,
      children: [
        if (tables == null)
          const KV('Tabeller', 'utilgjengelig — se Systemhelse')
        else
          ...tables.entries.map((e) => KV(e.key, '${e.value} rader')),
        const NotAvailable('Migrasjonsversjon', reason: 'ingen migrasjonstabell (bruker _setup.php-mønsteret)'),
        const NotAvailable('Trege spørringer', reason: 'ingen slow-query-logg på delt hosting'),
        const NotAvailable('Connection pool', reason: 'PHP åpner per forespørsel'),
      ],
    );
  }
}

class PluginsPanel extends StatelessWidget {
  const PluginsPanel({super.key});

  @override
  Widget build(BuildContext context) {
    return const DevPanel(
      title: 'Plugins',
      icon: Icons.extension_outlined,
      children: [
        NotAvailable('Installerte plugins', reason: 'PluginKernel ikke implementert (fase 4)'),
      ],
    );
  }
}

class LogsPanel extends StatelessWidget {
  const LogsPanel({super.key});

  @override
  Widget build(BuildContext context) {
    return DevPanel(
      title: 'Logg',
      icon: Icons.receipt_long_outlined,
      children: [
        ListenableBuilder(
          listenable: DevTelemetry.instance,
          builder: (context, _) {
            final logs = DevTelemetry.instance.logs.reversed.take(50).toList();
            if (logs.isEmpty) return const KV('Hendelser', 'ingen ennå denne økten');
            return Column(
              children: logs
                  .map((l) => KV(
                        '${_time(l.at)} [${l.type}]',
                        l.message,
                        valueColor: l.type == 'error' ? Colors.redAccent : null,
                      ))
                  .toList(),
            );
          },
        ),
        const NotAvailable('Serverlogg', reason: 'error_log er kun tilgjengelig på serveren'),
      ],
    );
  }
}

class EventBusPanel extends StatelessWidget {
  const EventBusPanel({super.key});

  @override
  Widget build(BuildContext context) {
    return const DevPanel(
      title: 'Event Bus',
      icon: Icons.hub_outlined,
      children: [
        NotAvailable('Events/sek, kølengde, feilede events', reason: 'Event Bus ikke implementert (EVENT_SYSTEM.md er design)'),
      ],
    );
  }
}

class PerformancePanel extends StatelessWidget {
  const PerformancePanel({super.key});

  @override
  Widget build(BuildContext context) {
    final t = DevTelemetry.instance;
    return DevPanel(
      title: 'Ytelse',
      icon: Icons.speed_outlined,
      children: [
        KV('Frame build (snitt/verst)',
            t.avgFrameBuildMs != null
                ? '${t.avgFrameBuildMs!.toStringAsFixed(1)} / ${t.worstFrameBuildMs!.toStringAsFixed(1)} ms'
                : 'ingen målinger ennå'),
        KV('Frame raster (snitt/verst)',
            t.avgFrameRasterMs != null
                ? '${t.avgFrameRasterMs!.toStringAsFixed(1)} / ${t.worstFrameRasterMs!.toStringAsFixed(1)} ms'
                : 'ingen målinger ennå'),
        KV('API-latens (snitt)', t.avgApiLatencyMs != null ? '${t.avgApiLatencyMs!.toStringAsFixed(0)} ms' : '—'),
        const NotAvailable('CPU / RAM', reason: 'krever plattformkanaler'),
      ],
    );
  }
}

class DebugPanel extends StatelessWidget {
  const DebugPanel({super.key});

  @override
  Widget build(BuildContext context) {
    return DevPanel(
      title: 'Debug',
      icon: Icons.bug_report_outlined,
      children: [
        // Safe values only: the .env holds no secrets by design, but we
        // still allowlist explicitly rather than dumping dotenv.env.
        KV('TOZA_API_BASE', dotenv.env['TOZA_API_BASE'] ?? '(ikke satt)'),
        KV('TOZA_DEVICE_NAME', dotenv.env['TOZA_DEVICE_NAME'] ?? '(ikke satt)'),
        KV('TOZA_MODE', dotenv.env['TOZA_MODE'] ?? '(ikke satt)'),
        KV('kDebugMode', '$kDebugMode'),
        const NotAvailable('Feature flags', reason: 'ingen flagg definert'),
        const NotAvailable('Release-kanal', reason: 'ingen kanalstyring ennå'),
      ],
    );
  }
}

class ProfilerPanel extends StatelessWidget {
  final Map<String, dynamic>? health;
  const ProfilerPanel({super.key, this.health});

  @override
  Widget build(BuildContext context) {
    final calls = DevTelemetry.instance.apiCalls.reversed.take(20).toList();
    final dbLatency = (health?['db'] as Map<String, dynamic>?)?['latency_ms'];
    return DevPanel(
      title: 'Profiler',
      icon: Icons.timer_outlined,
      children: [
        KV('DB-timing (server)', dbLatency != null ? '$dbLatency ms' : '—'),
        if (calls.isEmpty)
          const KV('API-timing', 'ingen kall ennå')
        else
          ...calls.map((c) => KV(
                '${_time(c.at)} ${c.action}',
                '${c.durationMs} ms ${c.ok ? '' : '✖'}',
                valueColor: c.ok ? null : Colors.redAccent,
              )),
        const NotAvailable('Widget rebuilds', reason: 'bruk Flutter DevTools'),
        const NotAvailable('Minneallokeringer', reason: 'bruk Flutter DevTools'),
      ],
    );
  }
}

// ─── Small formatting helpers ───────────────────────────────────────

String _ago(DateTime t) {
  final d = DateTime.now().difference(t);
  if (d.inSeconds < 60) return 'for ${d.inSeconds}s siden';
  if (d.inMinutes < 60) return 'for ${d.inMinutes}m siden';
  return 'for ${d.inHours}t siden';
}

String _time(DateTime t) =>
    '${t.hour.toString().padLeft(2, '0')}:${t.minute.toString().padLeft(2, '0')}:${t.second.toString().padLeft(2, '0')}';
