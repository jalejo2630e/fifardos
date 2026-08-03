import '../models/catalog.dart';
import '../models/tournament.dart';
import '../models/tournament_detail.dart';
import '../models/tournament_match.dart';
import '../models/standing.dart';
import '../models/user.dart';
import 'api_client.dart';
import 'storage_service.dart';

class AuthService {
  final ApiClient _api = ApiClient.instance;

  static Future<bool> isAdmin() async {
    final user = await StorageService.getUser();
    final value = user?['is_admin'];
    return value == true || value == 'true';
  }

  Future<({User user, String token})> login(
      String email, String password) async {
    final res = await _api.post('/api/auth/login', body: {
      'email': email,
      'password': password,
      'device_name': 'fifardos-mobile',
    });
    final token = res['token'] as String;
    final user = User.fromJson(res['user'] as Map<String, dynamic>);
    _api.setToken(token);
    await StorageService.saveToken(token);
    await StorageService.saveUser({
      'name': user.name,
      'email': user.email,
      'is_admin': user.isAdmin,
    });
    return (user: user, token: token);
  }

  Future<({User user, String token})> register(
      String name, String email, String password) async {
    final res = await _api.post('/api/auth/register', body: {
      'name': name,
      'email': email,
      'password': password,
      'password_confirmation': password,
      'device_name': 'fifardos-mobile',
    });
    final token = res['token'] as String;
    final user = User.fromJson(res['user'] as Map<String, dynamic>);
    _api.setToken(token);
    await StorageService.saveToken(token);
    await StorageService.saveUser({
      'name': user.name,
      'email': user.email,
      'is_admin': user.isAdmin,
    });
    return (user: user, token: token);
  }

  Future<void> logout() async {
    try {
      await _api.post('/api/auth/logout');
    } catch (_) {}
    await StorageService.clear();
  }
}

class TournamentService {
  final ApiClient _api = ApiClient.instance;

  Future<SportCatalog> catalog() async {
    final res = await _api.get('/api/agent/catalog');
    return SportCatalog.fromJson(res);
  }

  Future<Map<String, dynamic>> createTournament({
    required String name,
    required String sport,
    required String mode,
    required int consolesCount,
    required int minutesPerMatch,
    required String format,
    required bool homeAndAway,
    required List<String> players,
    required List<Map<String, dynamic>> teams,
    Map<String, dynamic>? rules,
  }) async {
    return _api.post('/api/agent/tournaments', body: {
      'name': name,
      'sport': sport,
      'mode': mode,
      'consoles_count': consolesCount,
      'minutes_per_match': minutesPerMatch,
      'format': format,
      'home_and_away': homeAndAway,
      'players': players,
      'teams': teams,
      'rules': rules ?? {},
    });
  }

  Future<List<Tournament>> tournaments() async {
    final res = await _api.get('/api/agent/tournaments');
    final list = res['data'] as List? ?? [];
    return list
        .map((e) => Tournament.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<TournamentDetail> tournamentDetail(int tournamentId) async {
    final res = await _api.get('/api/agent/tournaments/$tournamentId');
    return TournamentDetail.fromJson(res['data'] as Map<String, dynamic>);
  }

  Future<List<TournamentMatch>> matches(int tournamentId,
      {String? status}) async {
    final res = await _api.get('/api/agent/tournaments/$tournamentId/matches',
        query: status == null ? null : {'status': status});
    final list = res['data'] as List? ?? [];
    return list
        .map((e) => TournamentMatch.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<List<Standing>> standings(int tournamentId) async {
    final res = await _api.get('/api/agent/tournaments/$tournamentId/standings');
    final list = res['standings'] as List? ?? [];
    return list
        .map((e) => Standing.fromJson(e as Map<String, dynamic>))
        .toList();
  }

  Future<Map<String, dynamic>> topScorer(int tournamentId) async {
    final res = await _api.get('/api/agent/tournaments/$tournamentId/top-scorer');
    return res['data'] as Map<String, dynamic>? ?? {};
  }

  Future<Map<String, dynamic>> recordScore(
    int tournamentId,
    int matchId, {
    int? score1,
    int? score2,
    int? penalties1,
    int? penalties2,
    List<Map<String, dynamic>>? sets,
    List<Map<String, dynamic>>? goalScorers,
  }) async {
    return _api.post(
      '/api/agent/tournaments/$tournamentId/matches/$matchId/score',
      body: {
        'score1': ?score1,
        'score2': ?score2,
        'penalties1': ?penalties1,
        'penalties2': ?penalties2,
        'sets': ?sets,
        'goal_scorers': ?goalScorers,
      },
    );
  }
}
