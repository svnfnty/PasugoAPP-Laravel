# 📱 PasugoAPP Mobile (Android) Guide

This guide explains how to manage, sync, and generate the Android application for **PasugoAPP** using Capacitor.

---

## 🚀 Quick Commands

| Action | Command |
| :--- | :--- |
| **Sync Changes** | `npx cap sync` |
| **Open Android Studio** | `npx cap open android` |
| **Update Assets only** | `npx cap copy` |

---

## 🛠️ Step 1: Syncing Your Laravel Code to Android

Whenever you make changes to your **Blade views**, **CSS**, or **JavaScript** in Laravel, you need to sync them so the Android app sees them.

1.  Open your terminal in the project root.
2.  Run the sync command:
    ```bash
    npx cap sync
    ```
    *This command copies your `public` folder assets and updates any native plugins (like GPS).*

---

## 🏗️ Step 2: Generating the APK (Android Studio)

To create the actual file you can install on a phone:

1.  **Open the project:**
    ```bash
    npx cap open android
    ```
2.  **In Android Studio:**
    *   Wait for the "Gradle Sync" to finish (check the bottom progress bar).
    *   Go to the top menu: **Build** > **Build Bundle(s) / APK(s)** > **Build APK(s)**.
3.  **Locate your file:**
    *   Once finished, a popup will appear in the bottom right. Click **Locate**.
    *   Your file is usually at: `android/app/build/outputs/apk/debug/app-debug.apk`.

---

## 🌐 Local Testing (XAMPP / Localhost)

Since your Laravel app is currently running on XAMPP, you must tell the Android app where to look.

1.  Find your **Computer's Local IP** (Open CMD and type `ipconfig`). It looks like `192.168.1.XX`.
2.  Open `capacitor.config.json` and update the URL:
    ```json
    "server": {
      "url": "http://192.168.1.XX",
      "cleartext": true
    }
    ```
3.  Run `npx cap sync` and then run the app in Android Studio.

---

## 🛰️ Native Features Added
- **Geolocation:** The app uses `@capacitor/geolocation` for high-accuracy Rider tracking. 
- **Permissions:** The app is configured to request GPS permissions automatically on Android.

---

## 🔒 Biometric Authentication (Fingerprint Login)

I have implemented the backend support for Biometric/Persistent logins. To enable this in your Android app:

### 1. Install Capacitor Biometric Plugin
Run this in your terminal:
```bash
npm install @capgo/capacitor-native-biometric
npx cap sync
```

### 2. Implementation logic (Frontend JS)
When the user logs in for the first time with a password, you should:
1.  Call the new `/api/login` endpoint.
2.  Upon success, use `NativeBiometric.setCredentials` to store the Email/Password or the Sanctum Token in the device's secure vault.
3.  On next app open:
    *   Call `NativeBiometric.verifyIdentity`.
    *   If successful, retrieve the stored token and use it to authenticate.

### 3. Backend Endpoints
- **Login:** `POST /api/login` (Returns a long-lived Sanctum token)
- **Check Status:** `GET /api/user` (Requires Bearer Token)
- **Logout:** `POST /api/logout` (Requires Bearer Token)

---

## ⚠️ Important Note
**Do not delete the `android/` folder.** This folder contains your actual Android Studio project, including your app icons and splash screen configurations.
