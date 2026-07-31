import 'dart:convert';

import 'package:http/http.dart' as http;

import '../config.dart';

class ApiException implements Exception {
  final String message;
  final int? statusCode;
  final Map<String, dynamic>? errors;

  ApiException(this.message, {this.statusCode, this.errors});

  @override
  String toString() => message;
}

class ApiClient {
  String? _token;
  static final ApiClient instance = ApiClient._();

  ApiClient._();

  void setToken(String token) => _token = token;

  Map<String, String> _headers({bool json = true}) => {
        if (_token != null) 'Authorization': 'Bearer $_token',
        if (json) 'Accept': 'application/json',
        if (json) 'Content-Type': 'application/json',
      };

  Uri _uri(String path, [Map<String, String>? query]) =>
      Uri.parse('${AppConfig.apiBaseUrl}$path').replace(queryParameters: query);

  Future<Map<String, dynamic>> get(String path,
      {Map<String, String>? query}) async {
    final res = await http
        .get(_uri(path, query), headers: _headers())
        .timeout(const Duration(seconds: 15));
    return _decode(res);
  }

  Future<Map<String, dynamic>> post(String path,
      {Map<String, dynamic>? body, bool json = true}) async {
    final res = await http
        .post(_uri(path), headers: _headers(json: json), body: json ? jsonEncode(body ?? {}) : body)
        .timeout(const Duration(seconds: 20));
    return _decode(res);
  }

  Map<String, dynamic> _decode(http.Response res) {
    Map<String, dynamic> data = {};
    if (res.body.isNotEmpty) {
      try {
        data = jsonDecode(res.body) as Map<String, dynamic>;
      } catch (_) {
        data = {};
      }
    }
    if (res.statusCode >= 400) {
      final message = data['message'] as String? ??
          data['error'] as String? ??
          'Error del servidor (${res.statusCode})';
      final errors = data['errors'] as Map<String, dynamic>?;
      throw ApiException(message, statusCode: res.statusCode, errors: errors);
    }
    return data;
  }
}
