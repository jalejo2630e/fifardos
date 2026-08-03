class TournamentRuleValue {
  final String key;
  final String label;
  final String type;
  final List<String> options;
  final int? min;
  final int? max;
  final String? unit;
  final String? value;

  TournamentRuleValue({
    required this.key,
    required this.label,
    required this.type,
    this.options = const [],
    this.min,
    this.max,
    this.unit,
    this.value,
  });

  factory TournamentRuleValue.fromJson(Map<String, dynamic> json) =>
      TournamentRuleValue(
        key: json['key'] as String,
        label: json['label'] as String? ?? '',
        type: json['type'] as String? ?? 'boolean',
        options: (json['options'] as List?)?.cast<String>() ?? [],
        min: json['min'] as int?,
        max: json['max'] as int?,
        unit: json['unit'] as String?,
        value: json['value']?.toString(),
      );
}

class BracketMatch {
  final int id;
  final int? tvNumber;
  final String phase;
  final String? bracketPosition;
  final String status;
  final String competitor1;
  final String competitor2;
  final int? score1;
  final int? score2;
  final List<List<int>> sets;

  BracketMatch({
    required this.id,
    this.tvNumber,
    required this.phase,
    this.bracketPosition,
    required this.status,
    required this.competitor1,
    required this.competitor2,
    this.score1,
    this.score2,
    this.sets = const [],
  });

  bool get isFinished => status == 'finished';
  bool get isDraw => isFinished && (score1 ?? 0) == (score2 ?? 0);

  factory BracketMatch.fromJson(Map<String, dynamic> json) => BracketMatch(
        id: json['id'] as int,
        tvNumber: json['tv_number'] as int?,
        phase: json['phase'] as String? ?? '',
        bracketPosition: json['bracket_position'] as String?,
        status: json['status'] as String? ?? 'pending',
        competitor1: (json['competitor1'] as Map<String, dynamic>?)?['name'] as String? ?? '—',
        competitor2: (json['competitor2'] as Map<String, dynamic>?)?['name'] as String? ?? '—',
        score1: json['score1'] as int?,
        score2: json['score2'] as int?,
        sets: ((json['sets'] as List?) ?? []).map((s) {
          final m = s as Map<String, dynamic>;
          return [m['a'] as int? ?? 0, m['b'] as int? ?? 0];
        }).toList(),
      );

  bool isWinner(String competitorName) {
    if (!isFinished || isDraw) return false;
    if (score1! > score2!) return competitor1 == competitorName;
    return competitor2 == competitorName;
  }
}

class TournamentRound {
  final int round;
  final String phase;
  final List<BracketMatch> matches;

  TournamentRound({
    required this.round,
    required this.phase,
    required this.matches,
  });

  bool get isKnockout => phase != 'groups' && phase != 'league';

  factory TournamentRound.fromJson(Map<String, dynamic> json) =>
      TournamentRound(
        round: json['round'] as int? ?? 0,
        phase: json['phase'] as String? ?? '',
        matches: ((json['matches'] as List?) ?? [])
            .map((e) => BracketMatch.fromJson(e as Map<String, dynamic>))
            .toList(),
      );
}

class TournamentDetail {
  final int id;
  final String name;
  final String sport;
  final String sportName;
  final String sportIcon;
  final bool isTeam;
  final String status;
  final String mode;
  final String format;
  final bool homeAndAway;
  final String color;
  final int? consolesCount;
  final int? minutesPerMatch;
  final int playersCount;
  final int teamsCount;
  final int totalMatches;
  final int playedMatches;
  final List<TournamentRuleValue> rules;
  final List<TournamentRound> rounds;

  TournamentDetail({
    required this.id,
    required this.name,
    required this.sport,
    required this.sportName,
    required this.sportIcon,
    required this.isTeam,
    required this.status,
    required this.mode,
    required this.format,
    required this.homeAndAway,
    required this.color,
    this.consolesCount,
    this.minutesPerMatch,
    required this.playersCount,
    required this.teamsCount,
    required this.totalMatches,
    required this.playedMatches,
    this.rules = const [],
    this.rounds = const [],
  });

  bool get isFinished => status == 'finished' || status == 'completed';
  int get competitorCount => isTeam ? teamsCount : playersCount;

  factory TournamentDetail.fromJson(Map<String, dynamic> json) =>
      TournamentDetail(
        id: json['id'] as int,
        name: json['name'] as String,
        sport: json['sport'] as String? ?? 'fifa',
        sportName: json['sport_name'] as String? ?? '',
        sportIcon: json['sport_icon'] as String? ?? '⚽',
        isTeam: json['is_team'] == true,
        status: json['status'] as String? ?? 'pending',
        mode: json['mode'] as String? ?? 'virtual',
        format: json['format'] as String? ?? 'groups_knockout',
        homeAndAway: json['home_and_away'] == true,
        color: json['color'] as String? ?? '#ff5f00',
        consolesCount: json['consoles_count'] as int?,
        minutesPerMatch: json['minutes_per_match'] as int?,
        playersCount: json['players_count'] as int? ?? 0,
        teamsCount: json['teams_count'] as int? ?? 0,
        totalMatches: json['total_matches'] as int? ?? 0,
        playedMatches: json['played_matches'] as int? ?? 0,
        rules: ((json['rules'] as List?) ?? [])
            .map((e) => TournamentRuleValue.fromJson(e as Map<String, dynamic>))
            .toList(),
        rounds: ((json['rounds'] as List?) ?? [])
            .map((e) => TournamentRound.fromJson(e as Map<String, dynamic>))
            .toList(),
      );
}
