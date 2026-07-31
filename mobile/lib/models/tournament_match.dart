class MatchCompetitor {
  final int? id;
  final String name;

  MatchCompetitor({this.id, required this.name});

  factory MatchCompetitor.fromJson(Map<String, dynamic> json) =>
      MatchCompetitor(
        id: json['id'] as int?,
        name: (json['name'] as String?) ?? '—',
      );
}

class MatchSet {
  final int a;
  final int b;

  MatchSet({required this.a, required this.b});

  bool get isTied => a == b;

  factory MatchSet.fromJson(Map<String, dynamic> json) => MatchSet(
        a: (json['a'] as int?) ?? 0,
        b: (json['b'] as int?) ?? 0,
      );
}

class TournamentMatch {
  final int id;
  final int round;
  final int tvNumber;
  final String status;
  final String? phase;
  final String? sport;
  final bool isSets;
  final int maxSets;
  final MatchCompetitor? competitor1;
  final MatchCompetitor? competitor2;
  final int? score1;
  final int? score2;
  final List<MatchSet> sets;
  final int? penalties1;
  final int? penalties2;
  final String? playedAt;
  final int? winnerId;
  final bool isDraw;

  TournamentMatch({
    required this.id,
    required this.round,
    required this.tvNumber,
    required this.status,
    this.phase,
    this.sport,
    this.isSets = false,
    this.maxSets = 3,
    this.competitor1,
    this.competitor2,
    this.score1,
    this.score2,
    this.sets = const [],
    this.penalties1,
    this.penalties2,
    this.playedAt,
    this.winnerId,
    required this.isDraw,
  });

  bool get isFinished => status == 'finished';

  String? get competitor1Name => competitor1?.name;
  String? get competitor2Name => competitor2?.name;

  factory TournamentMatch.fromJson(Map<String, dynamic> json) =>
      TournamentMatch(
        id: json['id'] as int,
        round: (json['round'] as int?) ?? 0,
        tvNumber: (json['tv_number'] as int?) ?? 0,
        status: json['status'] as String,
        phase: json['phase'] as String?,
        sport: json['sport'] as String?,
        isSets: json['is_sets'] == true,
        maxSets: (json['max_sets'] as int?) ?? 3,
        competitor1: json['competitor1'] == null
            ? null
            : MatchCompetitor.fromJson(json['competitor1'] as Map<String, dynamic>),
        competitor2: json['competitor2'] == null
            ? null
            : MatchCompetitor.fromJson(json['competitor2'] as Map<String, dynamic>),
        score1: json['score1'] as int?,
        score2: json['score2'] as int?,
        sets: (json['sets'] as List<dynamic>? ?? [])
            .map((s) => MatchSet.fromJson(s as Map<String, dynamic>))
            .toList(),
        penalties1: json['penalties1'] as int?,
        penalties2: json['penalties2'] as int?,
        playedAt: json['played_at'] as String?,
        winnerId: json['winner_id'] as int?,
        isDraw: json['is_draw'] == true,
      );
}
