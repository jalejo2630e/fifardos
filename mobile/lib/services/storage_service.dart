import 'dart:convert';

import 'package:shared_preferences/shared_preferences.dart';

class StorageService {
  static const _tokenKey = 'auth_token';
  static const _userKey = 'auth_user';

  static Future<void> saveToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
  }

  static Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_tokenKey);
  }

  static Future<void> saveUser(Map<String, dynamic> user) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_userKey, jsonEncode(user));
  }

  static Future<Map<String, dynamic>?> getUser() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_userKey);
    if (raw == null) return null;
    try {
      return Map<String, dynamic>.from(jsonDecode(raw) as Map);
    } catch (_) {
      try {
        return Map<String, dynamic>.from(_parseJson(raw));
      } catch (_) {
        return null;
      }
    }
  }

  static Map<String, dynamic> _parseJson(String raw) {
    final cleaned = raw
        .replaceAll('{', '')
        .replaceAll('}', '')
        .split(',')
        .where((e) => e.contains(':'))
        .map((e) {
          final parts = e.split(':');
          return [parts[0].trim(), parts.sublist(1).join(':').trim()];
        })
        .toList();
    return {for (final p in cleaned) p[0]: p[1]};
  }

  static Future<void> clear() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
    await prefs.remove(_userKey);
  }
}
