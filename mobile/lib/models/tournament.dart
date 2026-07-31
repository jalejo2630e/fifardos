class TournamentLeader {
  final int? competitorId;
  final String? competitorName;
  final String? type;
  final int pts;

  TournamentLeader({
    this.competitorId,
    this.competitorName,
    this.type,
    required this.pts,
  });

  factory TournamentLeader.fromJson(Map<String, dynamic> json) =>
      TournamentLeader(
        competitorId: (json['competitor_id'] ?? json['player_id'] ?? json['team_id'])
            as int?,
        competitorName: (json['competitor_name'] ?? json['player_name'] ?? json['team_name'])
            as String?,
        type: json['type'] as String?,
        pts: (json['pts'] as int?) ?? 0,
      );
}

class Tournament {
  final int id;
  final String name;
  final String status;
  final String color;
  final String sport;
  final String sportName;
  final bool isTeam;
  final int? consolesCount;
  final int? maxPlayers;
  final int playersCount;
  final int teamsCount;
  final int totalMatches;
  final int playedMatches;
  final int progressPercent;
  final TournamentLeader? leader;

  Tournament({
    required this.id,
    required this.name,
    required this.status,
    required this.color,
    required this.sport,
    required this.sportName,
    required this.isTeam,
    this.consolesCount,
    this.maxPlayers,
    required this.playersCount,
    required this.teamsCount,
    required this.totalMatches,
    required this.playedMatches,
    required this.progressPercent,
    this.leader,
  });

  bool get isFinished => status == 'finished' || status == 'completed';
  int get competitorCount => isTeam ? teamsCount : playersCount;

  factory Tournament.fromJson(Map<String, dynamic> json) => Tournament(
        id: json['id'] as int,
        name: json['name'] as String,
        status: json['status'] as String,
        color: json['color'] as String? ?? '#ff5f00',
        sport: json['sport'] as String? ?? 'fifa',
        sportName: json['sport_name'] as String? ?? '',
        isTeam: json['is_team'] == true,
        consolesCount: json['consoles_count'] as int?,
        maxPlayers: json['max_players'] as int?,
        playersCount: json['players_count'] as int? ?? 0,
        teamsCount: json['teams_count'] as int? ?? 0,
        totalMatches: json['total_matches'] as int? ?? 0,
        playedMatches: json['played_matches'] as int? ?? 0,
        progressPercent: json['progress_percent'] as int? ?? 0,
        leader: json['leader'] == null
            ? null
            : TournamentLeader.fromJson(json['leader'] as Map<String, dynamic>),
      );
}
