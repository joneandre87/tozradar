Next milestone and recommended actions

1) Add `platform-tools` to PATH for convenient installs
   - Add `C:\Users\Joni\AppData\Local\Android\Sdk\platform-tools` to system/user PATH.
   - Verify with `adb devices` and then `flutter install`.

2) CI pipeline for reproducible builds
   - Create a GitHub Actions workflow that installs the Android SDK (cmdline-tools, platform-tools), accepts licenses, checks out Flutter (matching channel), runs `flutter analyze`, `flutter test`, and `flutter build apk --debug` and stores the produced APK as an artifact.

3) Release signing and artifact management
   - Add a `key.properties`/keystore for release builds and document secure storage for the keystore in CI.

4) Backend DNS verification (optional)
   - If you want the app to use `toza.tozradar.com`, provision DNS + TLS; otherwise keep `.env` pointing to `https://tozradar.com/tozastarter/api/index.php`.

5) Gradle/Android upgrades
   - Address Gradle deprecation warnings proactively before upgrading to Gradle 10.
