import 'package:flutter/foundation.dart';
import 'package:flutter/scheduler.dart';

/// One recorded API round trip. Real measurement — created by
/// [ApiClient.call] around the actual HTTP request.
class ApiCallRecord {
  final String action;
  final int durationMs;
  final bool ok;
  final DateTime at;
  ApiCallRecord(this.action, this.durationMs, this.ok) : at = DateTime.now();
}

/// One log entry. Written by kernels at the moment the event happens.
class DevLogEntry {
  final String type; // 'error' | 'warn' | 'api' | 'auth' | 'memory'
  final String message;
  final DateTime at;
  DevLogEntry(this.type, this.message) : at = DateTime.now();
}

/// In-session developer telemetry. Everything in here is a real
/// measurement taken during this app run — nothing is estimated or
/// invented. Data lives in memory only (ring buffers) and costs
/// nothing when the developer console is closed.
class DevTelemetry extends ChangeNotifier {
  DevTelemetry._();
  static final DevTelemetry instance = DevTelemetry._();

  static const _maxRecords = 100;

  final List<ApiCallRecord> apiCalls = [];
  final List<DevLogEntry> logs = [];

  int requestCount = 0;
  int errorCount = 0;
  DateTime? lastSuccessfulRequest;

  // Memory sync tracking — written by MemoryKernel.
  DateTime? lastSyncAt;
  bool? lastSyncOk;
  int failedSyncCount = 0;

  // Frame timings — fed by SchedulerBinding, real render measurements.
  final List<int> _frameBuildMicros = [];
  final List<int> _frameRasterMicros = [];
  bool _frameHookInstalled = false;

  void recordApiCall(String action, int durationMs, bool ok) {
    requestCount++;
    if (!ok) errorCount++;
    if (ok) lastSuccessfulRequest = DateTime.now();
    apiCalls.add(ApiCallRecord(action, durationMs, ok));
    if (apiCalls.length > _maxRecords) apiCalls.removeAt(0);
    log(ok ? 'api' : 'error', '$action (${durationMs}ms)${ok ? '' : ' FEILET'}');
  }

  void recordSync(bool ok) {
    lastSyncAt = DateTime.now();
    lastSyncOk = ok;
    if (!ok) failedSyncCount++;
    log('memory', ok ? 'Sync fullført' : 'Sync feilet');
  }

  void log(String type, String message) {
    logs.add(DevLogEntry(type, message));
    if (logs.length > _maxRecords) logs.removeAt(0);
    notifyListeners();
  }

  /// Installs the real frame-timing hook. Idempotent; called once at
  /// startup. Timings arrive only for frames actually rendered.
  void installFrameHook() {
    if (_frameHookInstalled) return;
    _frameHookInstalled = true;
    SchedulerBinding.instance.addTimingsCallback((timings) {
      for (final t in timings) {
        _frameBuildMicros.add(t.buildDuration.inMicroseconds);
        _frameRasterMicros.add(t.rasterDuration.inMicroseconds);
      }
      while (_frameBuildMicros.length > 200) {
        _frameBuildMicros.removeAt(0);
      }
      while (_frameRasterMicros.length > 200) {
        _frameRasterMicros.removeAt(0);
      }
    });
  }

  double? get avgFrameBuildMs => _avgMs(_frameBuildMicros);
  double? get worstFrameBuildMs => _worstMs(_frameBuildMicros);
  double? get avgFrameRasterMs => _avgMs(_frameRasterMicros);
  double? get worstFrameRasterMs => _worstMs(_frameRasterMicros);

  double? get avgApiLatencyMs {
    if (apiCalls.isEmpty) return null;
    return apiCalls.map((c) => c.durationMs).reduce((a, b) => a + b) /
        apiCalls.length;
  }

  double? _avgMs(List<int> micros) {
    if (micros.isEmpty) return null;
    return micros.reduce((a, b) => a + b) / micros.length / 1000;
  }

  double? _worstMs(List<int> micros) {
    if (micros.isEmpty) return null;
    return micros.reduce((a, b) => a > b ? a : b) / 1000;
  }
}
