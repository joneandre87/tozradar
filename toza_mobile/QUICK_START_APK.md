# 🚀 Quick Start: APK Installation

**Current Status:** ✅ APK built and ready | ⏳ Waiting for device

---

## Option 1️⃣ : Use Physical Android Device (Fastest)

### Step 1: Enable USB Debugging on Your Phone
```
Settings → About Phone → Tap Build Number 7 times
Settings → Developer Options → Enable USB Debugging
```

### Step 2: Connect Phone via USB Cable
```powershell
adb devices -l
# You should see: <device-id>    device
```

### Step 3: Install APK
```powershell
adb install -r toza_mobile/build/app/outputs/flutter-apk/app-debug.apk
# Wait for: Success
```

### Step 4: Launch App
```powershell
adb shell am start -n com.example.toza_mobile/com.example.toza_mobile.MainActivity
```

---

## Option 2️⃣ : Enable BIOS Virtualization for Emulator

### Step 1: Restart and Enter BIOS
- **Restart your PC**
- Press `F2`, `F10`, `Del`, or `Esc` during startup (varies by manufacturer)

### Step 2: Enable Virtualization
- Look for: **"Virtualization"** / **"VT-x"** / **"Intel VT"** / **"AMD-V"** / **"SVM"**
- **Enable** it and **Save**
- **Restart PC**

### Step 3: Enable Windows Hypervisor
```powershell
# Run as Administrator:
Enable-WindowsOptionalFeature -Online -FeatureName VirtualMachinePlatform -NoRestart
Enable-WindowsOptionalFeature -Online -FeatureName HypervisorPlatform -NoRestart
# Restart when done
```

### Step 4: Relaunch Emulator
```bash
emulator -avd toza_android_emulator_clean -gpu auto -accel auto
# Wait 30 seconds for full boot
adb devices -l
# Should show: emulator-5554 device
```

### Step 5: Install APK
```powershell
adb install -r toza_mobile/build/app/outputs/flutter-apk/app-debug.apk
adb shell am start -n com.example.toza_mobile/com.example.toza_mobile.MainActivity
```

---

## Troubleshooting

### ADB shows "offline"
- **Emulator:** Virtualization disabled in BIOS (see Option 2️⃣  above)
- **Physical device:** Unplug USB, wait 5s, replug + authorize host fingerprint

### Device not found
```powershell
adb kill-server
adb start-server
adb devices -l
```

### APK installation fails
```powershell
# Clear app cache first:
adb shell pm clear com.example.toza_mobile
# Then retry:
adb install -r toza_mobile/build/app/outputs/flutter-apk/app-debug.apk
```

---

## Check Out the Built APK

📂 **Location:** `toza_mobile/build/app/outputs/flutter-apk/app-debug.apk`  
💾 **Size:** ~166 MB  
✅ **Status:** Ready to install

---

## Documentation

📄 Full session summary: [`toza_mobile/ANDROID_RECOVERY_SUMMARY.md`](./ANDROID_RECOVERY_SUMMARY.md)  
🔄 CI workflow: [`.github/workflows/android-build.yml`](../.github/workflows/android-build.yml)  
📊 Build report: [`toza_mobile/BUILD_REPORT.md`](./BUILD_REPORT.md)  

---

**Next Step:** Choose Option 1️⃣  or 2️⃣  above and let me know when you're ready! 🚀
