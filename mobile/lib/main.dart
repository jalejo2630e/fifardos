import 'package:flutter/material.dart';

import 'screens/home_screen.dart';
import 'screens/landing_screen.dart';
import 'services/api_client.dart';
import 'services/storage_service.dart';

const kBg = Color(0xFF131313);
const kSurface = Color(0xFF1C1B1B);
const kSurfaceLow = Color(0xFF0E0E0E);
const kAccent = Color(0xFFFF5F00);
const kAccentSoft = Color(0xFFFFB599);
const kTextDim = Color(0xFF9E9E9E);

ThemeData buildFifardosTheme() {
  final base = ThemeData.dark(useMaterial3: true);
  return base.copyWith(
    scaffoldBackgroundColor: kBg,
    colorScheme: base.colorScheme.copyWith(
      primary: kAccent,
      secondary: kAccent,
      surface: kSurface,
      onSurface: Colors.white,
      onPrimary: const Color(0xFF08080A),
    ),
    appBarTheme: const AppBarTheme(
      backgroundColor: kBg,
      foregroundColor: Colors.white,
      elevation: 0,
      centerTitle: false,
    ),
    textTheme: base.textTheme.apply(bodyColor: Colors.white, displayColor: Colors.white),
    elevatedButtonTheme: ElevatedButtonThemeData(
      style: ElevatedButton.styleFrom(
        backgroundColor: kAccent,
        foregroundColor: const Color(0xFF08080A),
        textStyle: const TextStyle(fontWeight: FontWeight.w700, letterSpacing: 1.1),
        padding: const EdgeInsets.symmetric(vertical: 16),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6)),
      ),
    ),
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        foregroundColor: Colors.white,
        side: const BorderSide(color: kAccent),
        textStyle: const TextStyle(fontWeight: FontWeight.w600),
        padding: const EdgeInsets.symmetric(vertical: 16),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(6)),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: kSurfaceLow,
      hintStyle: const TextStyle(color: kTextDim),
      labelStyle: const TextStyle(color: kAccentSoft),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(6),
        borderSide: BorderSide.none,
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(6),
        borderSide: const BorderSide(color: kAccent, width: 1.5),
      ),
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
    ),
    cardTheme: CardThemeData(
      color: kSurface,
      elevation: 0,
      margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
    ),
    dividerTheme: const DividerThemeData(color: Color(0xFF2A2A2A)),
    tabBarTheme: const TabBarThemeData(
      labelColor: kAccent,
      unselectedLabelColor: kTextDim,
      indicatorColor: kAccent,
      dividerColor: Color(0xFF2A2A2A),
    ),
  );
}

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  final token = await StorageService.getToken();
  if (token != null) {
    ApiClient.instance.setToken(token);
  }
  runApp(FifardosApp(loggedIn: token != null));
}

class FifardosApp extends StatelessWidget {
  final bool loggedIn;
  const FifardosApp({super.key, required this.loggedIn});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'FIFARDOS',
      debugShowCheckedModeBanner: false,
      theme: buildFifardosTheme(),
      home: loggedIn ? const HomeScreen() : const LandingScreen(),
    );
  }
}
