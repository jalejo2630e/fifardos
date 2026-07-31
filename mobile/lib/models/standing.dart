class Standing {
  final int position;
  final int? competitorId;
  final String competitorName;
  final String? type;
  final int points;
  final int played;
  final int won;
  final int drawn;
  final int lost;
  final int goalsFor;
  final int goalsAgainst;
  final int goalDifference;
  final bool isChampion;

  Standing({
    required this.position,
    this.competitorId,
    required this.competitorName,
    this.type,
    required this.points,
    required this.played,
    required this.won,
    required this.drawn,
    required this.lost,
    required this.goalsFor,
    required this.goalsAgainst,
    required this.goalDifference,
    required this.isChampion,
  });

  factory Standing.fromJson(Map<String, dynamic> json) => Standing(
        position: (json['position'] as int?) ?? 0,
        competitorId: json['competitor_id'] as int?,
        competitorName: (json['competitor_name'] ?? json['player_name'] ?? json['team_name'])
                as String? ??
            '—',
        type: json['type'] as String?,
        points: (json['points'] as int?) ?? 0,
        played: (json['played'] as int?) ?? 0,
        won: (json['won'] as int?) ?? 0,
        drawn: (json['drawn'] as int?) ?? 0,
        lost: (json['lost'] as int?) ?? 0,
        goalsFor: (json['goals_for'] as int?) ?? 0,
        goalsAgainst: (json['goals_against'] as int?) ?? 0,
        goalDifference: (json['goal_difference'] as int?) ?? 0,
        isChampion: json['is_champion'] == true,
      );
}
