import 'package:flutter/material.dart';
import 'package:flutter_dotenv/flutter_dotenv.dart';
import 'package:provider/provider.dart';

import 'core/ai_kernel.dart';
import 'core/api_client.dart';
import 'core/auth_kernel.dart';
import 'core/dev_mode.dart';
import 'core/dev_telemetry.dart';
import 'core/memory_kernel.dart';
import 'core/token_store.dart';
import 'screens/splash_screen.dart';
import 'services/chat_service.dart';
import 'services/memory_service.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  try {
    await dotenv.load(fileName: '.env');
  } catch (_) {
    debugPrint('Fant ingen .env — kopier .env.example til .env og legg inn dine verdier.');
    dotenv.loadFromString(envString: '');
  }

  final localMemory = MemoryService();
  final tokenStore = TokenStore();
  final apiClient = ApiClient(tokenStore);
  final authKernel = AuthKernel(apiClient, tokenStore);
  final aiKernel = AIKernel(apiClient);
  final memoryKernel = MemoryKernel(localMemory, apiClient);
  final devMode = DevMode();

  DevTelemetry.instance.installFrameHook();
  // Fire-and-forget: dev mode state arrives before anyone can reach
  // Settings, and defaults to off if the read fails.
  devMode.load();

  // No awaits here beyond dotenv: session restore and memory sync run
  // behind the splash screen (SplashGate) so the app window appears
  // immediately instead of blocking on network/database I/O.
  runApp(TozaApp(
    localMemory: localMemory,
    authKernel: authKernel,
    aiKernel: aiKernel,
    memoryKernel: memoryKernel,
    devMode: devMode,
  ));
}

class TozaApp extends StatelessWidget {
  final MemoryService localMemory;
  final AuthKernel authKernel;
  final AIKernel aiKernel;
  final MemoryKernel memoryKernel;
  final DevMode devMode;

  const TozaApp({
    super.key,
    required this.localMemory,
    required this.authKernel,
    required this.aiKernel,
    required this.memoryKernel,
    required this.devMode,
  });

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        Provider<ApiClient>.value(value: aiKernel.api),
        Provider<MemoryService>.value(value: localMemory),
        Provider<AIKernel>.value(value: aiKernel),
        Provider<MemoryKernel>.value(value: memoryKernel),
        ChangeNotifierProvider<AuthKernel>.value(value: authKernel),
        ChangeNotifierProvider<DevMode>.value(value: devMode),
        ChangeNotifierProvider(create: (_) => ChatService(aiKernel, memoryKernel)),
      ],
      child: MaterialApp(
        debugShowCheckedModeBanner: false,
        title: 'Tøza AI',
        theme: ThemeData(
          brightness: Brightness.dark,
          colorScheme: ColorScheme.fromSeed(
            seedColor: const Color(0xFFC6A87A),
            brightness: Brightness.dark,
          ),
          useMaterial3: true,
        ),
        home: const SplashGate(),
      ),
    );
  }
}
