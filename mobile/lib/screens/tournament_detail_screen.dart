import 'dart:async';

import 'package:flutter/material.dart';

import '../main.dart';
import '../models/catalog.dart' show ruleOptLabels;
import '../models/standing.dart';
import '../models/tournament_detail.dart';
import '../models/tournament_match.dart';
import '../services/auth_service.dart';
import 'match_score_screen.dart';

class TournamentDetailScreen extends StatefulWidget {
  final int tournamentId;
  final String tournamentName;
  final String tournamentColor;

  const TournamentDetailScreen({
    super.key,
    required this.tournamentId,
    required this.tournamentName,
    required this.tournamentColor,
  });

  @override
  State<TournamentDetailScreen> createState() => _TournamentDetailScreenState();
}

class _TournamentDetailScreenState extends State<TournamentDetailScreen>
    with SingleTickerProviderStateMixin {
  final TournamentService _service = TournamentService();
  late TabController _tabController;

  late Future<List<TournamentMatch>> _matchesFuture;
  late Future<List<Standing>> _standingsFuture;
  late Future<Map<String, dynamic>> _topScorerFuture;
  late Future<TournamentDetail> _detailFuture;
  Timer? _syncTimer;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 5, vsync: this);
    _matchesFuture = _service.matches(widget.tournamentId);
    _standingsFuture = _service.standings(widget.tournamentId);
    _topScorerFuture = _service.topScorer(widget.tournamentId);
    _detailFuture = _service.tournamentDetail(widget.tournamentId);
    _syncTimer = Timer.periodic(const Duration(seconds: 12), (_) {
      if (mounted) _reload();
    });
  }

  @override
  void dispose() {
    _syncTimer?.cancel();
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _reload() async {
    setState(() {
      _matchesFuture = _service.matches(widget.tournamentId);
      _standingsFuture = _service.standings(widget.tournamentId);
      _topScorerFuture = _service.topScorer(widget.tournamentId);
      _detailFuture = _service.tournamentDetail(widget.tournamentId);
    });
    await Future.wait(
        [_matchesFuture, _standingsFuture, _topScorerFuture, _detailFuture]);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          widget.tournamentName,
          style: const TextStyle(fontWeight: FontWeight.w700),
        ),
        bottom: TabBar(
          controller: _tabController,
          tabs: const [
            Tab(text: 'PARTIDOS'),
            Tab(text: 'POSICIONES'),
            Tab(text: 'BRACKET'),
            Tab(text: 'REGLAS'),
            Tab(text: 'GOLEADOR'),
          ],
        ),
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          RefreshIndicator(
            onRefresh: _reload,
            color: kAccent,
            child: FutureBuilder<List<TournamentMatch>>(
              future: _matchesFuture,
              builder: (context, snapshot) {
                if (snapshot.connectionState == ConnectionState.waiting) {
                  return const Center(child: CircularProgressIndicator(color: kAccent));
                }
                if (snapshot.hasError) {
                  return _SimpleError(message: '${snapshot.error}', onRetry: _reload);
                }
                final matches = snapshot.data ?? [];
                if (matches.isEmpty) {
                  return const Center(
                    child: Text('Sin partidos todavía.', style: TextStyle(color: kTextDim)),
                  );
                }
                return ListView.separated(
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding: const EdgeInsets.all(12),
                  itemCount: matches.length,
                  separatorBuilder: (_, _) => const SizedBox(height: 8),
                  itemBuilder: (context, i) => _MatchCard(
                    match: matches[i],
                    onTap: matches[i].isFinished
                        ? null
                        : () async {
                            await Navigator.of(context).push(
                              MaterialPageRoute(
                                builder: (_) => MatchScoreScreen(
                                  tournamentId: widget.tournamentId,
                                  match: matches[i],
                                ),
                              ),
                            );
                            _reload();
                          },
                  ),
                );
              },
            ),
          ),
          RefreshIndicator(
            onRefresh: _reload,
            color: kAccent,
            child: FutureBuilder<List<Standing>>(
              future: _standingsFuture,
              builder: (context, snapshot) {
                if (snapshot.connectionState == ConnectionState.waiting) {
                  return const Center(child: CircularProgressIndicator(color: kAccent));
                }
                if (snapshot.hasError) {
                  return _SimpleError(message: '${snapshot.error}', onRetry: _reload);
                }
                final rows = snapshot.data ?? [];
                if (rows.isEmpty) {
                  return const Center(
                    child: Text('Sin posiciones todavía.', style: TextStyle(color: kTextDim)),
                  );
                }
                return SingleChildScrollView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding: const EdgeInsets.all(12),
                  child: _StandingsTable(rows: rows),
                );
              },
            ),
          ),
          RefreshIndicator(
            onRefresh: _reload,
            color: kAccent,
            child: FutureBuilder<TournamentDetail>(
              future: _detailFuture,
              builder: (context, snapshot) {
                if (snapshot.connectionState == ConnectionState.waiting) {
                  return const Center(child: CircularProgressIndicator(color: kAccent));
                }
                if (snapshot.hasError) {
                  return _SimpleError(message: '${snapshot.error}', onRetry: _reload);
                }
                final detail = snapshot.data!;
                final knockout = detail.rounds.where((r) => r.isKnockout).toList();
                if (knockout.isEmpty) {
                  return const Center(
                    child: Padding(
                      padding: EdgeInsets.all(24),
                      child: Text(
                        'El bracket de eliminatorias se arma cuando termina la fase de grupos.',
                        textAlign: TextAlign.center,
                        style: TextStyle(color: kTextDim),
                      ),
                    ),
                  );
                }
                return ListView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding: const EdgeInsets.all(12),
                  children: [
                    for (final round in knockout) ...[
                      _BracketSection(round: round),
                      const SizedBox(height: 12),
                    ],
                  ],
                );
              },
            ),
          ),
          RefreshIndicator(
            onRefresh: _reload,
            color: kAccent,
            child: FutureBuilder<TournamentDetail>(
              future: _detailFuture,
              builder: (context, snapshot) {
                if (snapshot.connectionState == ConnectionState.waiting) {
                  return const Center(child: CircularProgressIndicator(color: kAccent));
                }
                if (snapshot.hasError) {
                  return _SimpleError(message: '${snapshot.error}', onRetry: _reload);
                }
                final detail = snapshot.data!;
                if (detail.rules.isEmpty) {
                  return const Center(
                    child: Text('Este torneo no tiene reglas configuradas.',
                        style: TextStyle(color: kTextDim)),
                  );
                }
                return ListView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding: const EdgeInsets.all(12),
                  children: [
                    _MetaCard(detail: detail),
                    const SizedBox(height: 12),
                    for (final rule in detail.rules) ...[
                      _RuleCard(rule: rule),
                      const SizedBox(height: 8),
                    ],
                  ],
                );
              },
            ),
          ),
          RefreshIndicator(
            onRefresh: _reload,
            color: kAccent,
            child: FutureBuilder<Map<String, dynamic>>(
              future: _topScorerFuture,
              builder: (context, snapshot) {
                if (snapshot.connectionState == ConnectionState.waiting) {
                  return const Center(child: CircularProgressIndicator(color: kAccent));
                }
                if (snapshot.hasError) {
                  return _SimpleError(message: '${snapshot.error}', onRetry: _reload);
                }
                final data = snapshot.data ?? {};
                if (data.isEmpty || (data['total_goals'] ?? 0) == 0) {
                  return const Center(
                    child: Text('Todavía no hay goles.', style: TextStyle(color: kTextDim)),
                  );
                }
                return ListView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding: const EdgeInsets.all(24),
                  children: [
                    const SizedBox(height: 12),
                    const Icon(Icons.emoji_events, color: kAccent, size: 64),
                    const SizedBox(height: 16),
                    Text(
                      '${data['player_name'] ?? data['competitor_name']}',
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        fontSize: 26,
                        fontWeight: FontWeight.w800,
                        color: Colors.white,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      '${data['total_goals']} goles',
                      textAlign: TextAlign.center,
                      style: const TextStyle(fontSize: 40, fontWeight: FontWeight.w800, color: kAccent),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      '${data['matches_played']} partidos · ${data['goals_per_match']} goles/partido',
                      textAlign: TextAlign.center,
                      style: const TextStyle(color: kTextDim),
                    ),
                  ],
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

class _MatchCard extends StatelessWidget {
  final TournamentMatch match;
  final VoidCallback? onTap;

  const _MatchCard({required this.match, this.onTap});

  @override
  Widget build(BuildContext context) {
    final p1 = match.competitor1Name ?? '—';
    final p2 = match.competitor2Name ?? '—';
    final isFinished = match.isFinished;

    Widget scoreSide(String name, int? score, bool isWinner) => Expanded(
          child: Text(
            name,
            style: TextStyle(
              color: isWinner ? Colors.white : kTextDim,
              fontWeight: isWinner ? FontWeight.w700 : FontWeight.w400,
              fontSize: 14,
            ),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        );

    return Card(
      child: InkWell(
        borderRadius: BorderRadius.circular(8),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(12),
          child: Column(
            children: [
              Row(
                children: [
                  Text(
                    'R${match.round} · TV${match.tvNumber}',
                    style: const TextStyle(color: kTextDim, fontSize: 11, letterSpacing: 1),
                  ),
                  const Spacer(),
                  if (!isFinished)
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                      decoration: BoxDecoration(
                        color: kAccent.withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(4),
                      ),
                      child: const Text(
                        'PENDIENTE',
                        style: TextStyle(color: kAccent, fontSize: 10, fontWeight: FontWeight.w700),
                      ),
                    ),
                ],
              ),
              const SizedBox(height: 10),
              Row(
                children: [
                  scoreSide(p1, match.score1, isFinished && (match.score1 ?? 0) > (match.score2 ?? 0)),
                  Container(
                    margin: const EdgeInsets.symmetric(horizontal: 8),
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                    decoration: BoxDecoration(
                      color: isFinished ? kAccent : kSurfaceLow,
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: FittedBox(
                      fit: BoxFit.scaleDown,
                      child: Text(
                        isFinished && match.isSets && match.sets.isNotEmpty
                            ? match.sets.map((s) => '${s.a}-${s.b}').join(' ')
                            : isFinished
                                ? '${match.score1} - ${match.score2}'
                                : 'VS',
                        style: TextStyle(
                          color: isFinished ? const Color(0xFF08080A) : kTextDim,
                          fontWeight: FontWeight.w800,
                          fontSize: isFinished && match.isSets ? 15 : 16,
                        ),
                      ),
                    ),
                  ),
                  scoreSide(p2, match.score2, isFinished && (match.score2 ?? 0) > (match.score1 ?? 0)),
                ],
              ),
              if (onTap != null) ...[
                const SizedBox(height: 8),
                const Text(
                  'TOCÁ PARA CARGAR RESULTADO',
                  style: TextStyle(color: kAccent, fontSize: 10, letterSpacing: 1.2),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _StandingsTable extends StatelessWidget {
  final List<Standing> rows;

  const _StandingsTable({required this.rows});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 8),
          decoration: BoxDecoration(
            color: kSurfaceLow,
            borderRadius: BorderRadius.circular(6),
          ),
          child: const Row(
            children: [
              SizedBox(width: 28, child: Text('#', style: TextStyle(color: kTextDim, fontSize: 12))),
              Expanded(child: Text('COMPETIDOR', style: TextStyle(color: kTextDim, fontSize: 12))),
              SizedBox(width: 28, child: Text('PJ', textAlign: TextAlign.center, style: TextStyle(color: kTextDim, fontSize: 12))),
              SizedBox(width: 28, child: Text('G', textAlign: TextAlign.center, style: TextStyle(color: kTextDim, fontSize: 12))),
              SizedBox(width: 28, child: Text('E', textAlign: TextAlign.center, style: TextStyle(color: kTextDim, fontSize: 12))),
              SizedBox(width: 28, child: Text('P', textAlign: TextAlign.center, style: TextStyle(color: kTextDim, fontSize: 12))),
              SizedBox(width: 36, child: Text('PTS', textAlign: TextAlign.center, style: TextStyle(color: kAccent, fontSize: 12))),
            ],
          ),
        ),
        const SizedBox(height: 6),
        ...rows.map(
          (s) => Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 10),
            decoration: BoxDecoration(
              color: s.isChampion ? kAccent.withValues(alpha: 0.12) : kSurface,
              borderRadius: BorderRadius.circular(6),
              border: s.isChampion
                  ? Border.all(color: kAccent)
                  : Border.all(color: Colors.transparent),
            ),
            child: Row(
              children: [
                SizedBox(
                  width: 28,
                  child: s.isChampion
                      ? const Icon(Icons.emoji_events, color: kAccent, size: 16)
                      : Text('${s.position}', style: const TextStyle(color: kTextDim, fontSize: 12)),
                ),
                Expanded(
                  child: Text(
                    s.competitorName,
                    style: TextStyle(
                      color: Colors.white,
                      fontWeight: s.isChampion ? FontWeight.w800 : FontWeight.w500,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                SizedBox(width: 28, child: Text('${s.played}', textAlign: TextAlign.center, style: const TextStyle(color: Colors.white, fontSize: 12))),
                SizedBox(width: 28, child: Text('${s.won}', textAlign: TextAlign.center, style: const TextStyle(color: Colors.white, fontSize: 12))),
                SizedBox(width: 28, child: Text('${s.drawn}', textAlign: TextAlign.center, style: const TextStyle(color: Colors.white, fontSize: 12))),
                SizedBox(width: 28, child: Text('${s.lost}', textAlign: TextAlign.center, style: const TextStyle(color: Colors.white, fontSize: 12))),
                SizedBox(
                  width: 36,
                  child: Text(
                    '${s.points}',
                    textAlign: TextAlign.center,
                    style: const TextStyle(color: kAccent, fontWeight: FontWeight.w800),
                  ),
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: 8),
      ],
    );
  }
}

class _SimpleError extends StatelessWidget {
  final String message;
  final Future<void> Function() onRetry;

  const _SimpleError({required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.cloud_off, color: kTextDim, size: 40),
            const SizedBox(height: 12),
            Text(message, textAlign: TextAlign.center, style: const TextStyle(color: kTextDim)),
            const SizedBox(height: 12),
            OutlinedButton(onPressed: () => onRetry(), child: const Text('REINTENTAR')),
          ],
        ),
      ),
    );
  }
}

String _phaseLabel(String phase) => switch (phase) {
      'groups' => 'FASE DE GRUPOS',
      'league' => 'FASE DE LIGA',
      'round_of_32' => '32AVOS DE FINAL',
      'round_of_16' => 'OCTAVOS DE FINAL',
      'quarterfinal' => 'CUARTOS DE FINAL',
      'semifinal' => 'SEMIFINAL',
      'final' => 'FINAL',
      'third_place' => 'TERCER PUESTO',
      _ => phase.toUpperCase(),
    };

String _ruleValueLabel(TournamentRuleValue rule) {
  final value = rule.value;
  if (value == null || value.isEmpty) return 'Sin configurar';
  switch (rule.type) {
    case 'boolean':
      return value == '1' ? 'Sí' : 'No';
    case 'select':
      return ruleOptLabels[value] ?? value;
    case 'number':
      return rule.unit != null ? '$value ${rule.unit}' : value;
    default:
      return value;
  }
}

class _BracketSection extends StatelessWidget {
  final TournamentRound round;

  const _BracketSection({required this.round});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(left: 4, bottom: 6),
          child: Text(
            _phaseLabel(round.phase),
            style: const TextStyle(
              color: kAccent,
              fontSize: 12,
              fontWeight: FontWeight.w800,
              letterSpacing: 1.5,
            ),
          ),
        ),
        for (final match in round.matches) ...[
          _BracketMatchCard(match: match),
          const SizedBox(height: 8),
        ],
      ],
    );
  }
}

class _BracketMatchCard extends StatelessWidget {
  final BracketMatch match;

  const _BracketMatchCard({required this.match});

  @override
  Widget build(BuildContext context) {
    final finished = match.isFinished;
    return Container(
      decoration: BoxDecoration(
        color: kSurface,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: kSurfaceLow),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        child: Column(
          children: [
            Row(
              children: [
                Text(
                  'TV${match.tvNumber ?? '?'}',
                  style: const TextStyle(color: kTextDim, fontSize: 10, letterSpacing: 1),
                ),
                const Spacer(),
                if (finished)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                    decoration: BoxDecoration(
                      color: kAccent.withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(4),
                    ),
                    child: const Text('FINALIZADO',
                        style: TextStyle(color: kAccent, fontSize: 9, fontWeight: FontWeight.w700)),
                  ),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Expanded(
                  child: Text(
                    match.competitor1,
                    style: TextStyle(
                      color: match.isWinner(match.competitor1) ? Colors.white : kTextDim,
                      fontWeight: match.isWinner(match.competitor1) ? FontWeight.w700 : FontWeight.w400,
                      fontSize: 13,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                if (finished)
                  Text(
                    match.isDraw ? '${match.score1} - ${match.score2}' : '${match.score1}',
                    style: const TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.w800),
                  ),
              ],
            ),
            const SizedBox(height: 4),
            Row(
              children: [
                Expanded(
                  child: Text(
                    match.competitor2,
                    style: TextStyle(
                      color: match.isWinner(match.competitor2) ? Colors.white : kTextDim,
                      fontWeight: match.isWinner(match.competitor2) ? FontWeight.w700 : FontWeight.w400,
                      fontSize: 13,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                if (finished)
                  Text(
                    match.isDraw ? '${match.score2} - ${match.score1}' : '${match.score2}',
                    style: const TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.w800),
                  ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _MetaCard extends StatelessWidget {
  final TournamentDetail detail;

  const _MetaCard({required this.detail});

  @override
  Widget build(BuildContext context) {
    final formatLabel = detail.format == 'league'
        ? 'Liga (todos contra todos)'
        : 'Grupos + Eliminatorias';
    final modeLabel = detail.mode == 'physical' ? 'Campo físico' : 'Virtual';
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: kSurface,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: kSurfaceLow),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Text(detail.sportIcon, style: const TextStyle(fontSize: 20)),
              const SizedBox(width: 8),
              Text(
                detail.sportName.isEmpty ? detail.sport : detail.sportName,
                style: const TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.w700),
              ),
              const Spacer(),
              Text(
                '${detail.playedMatches}/${detail.totalMatches}',
                style: const TextStyle(color: kTextDim, fontSize: 12),
              ),
            ],
          ),
          const SizedBox(height: 12),
          _metaRow(Icons.category_outlined, 'Formato', formatLabel),
          _metaRow(Icons.videogame_asset_outlined, 'Modo', modeLabel),
          _metaRow(Icons.swap_horiz, 'Ida y vuelta', detail.homeAndAway ? 'Sí' : 'No'),
          _metaRow(Icons.timer_outlined, 'Duración', detail.minutesPerMatch != null ? '${detail.minutesPerMatch} min por partido' : '—'),
          _metaRow(Icons.sports_esports_outlined, 'Consolas', '${detail.consolesCount ?? 1}'),
          _metaRow(
            detail.isTeam ? Icons.groups_outlined : Icons.person_outline,
            'Competidores',
            '${detail.competitorCount} ${detail.isTeam ? 'equipos' : 'jugadores'}',
          ),
        ],
      ),
    );
  }

  Widget _metaRow(IconData icon, String label, String value) => Padding(
        padding: const EdgeInsets.only(top: 8),
        child: Row(
          children: [
            Icon(icon, color: kAccent, size: 16),
            const SizedBox(width: 8),
            Text(label, style: const TextStyle(color: kTextDim, fontSize: 13)),
            const Spacer(),
            Text(
              value,
              style: const TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.w600),
            ),
          ],
        ),
      );
}

class _RuleCard extends StatelessWidget {
  final TournamentRuleValue rule;

  const _RuleCard({required this.rule});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: kSurface,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: kSurfaceLow),
      ),
      child: Row(
        children: [
          const Icon(Icons.check_circle_outline, color: kAccent, size: 18),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              rule.label,
              style: const TextStyle(color: Colors.white, fontSize: 14),
            ),
          ),
          const SizedBox(width: 10),
          Text(
            _ruleValueLabel(rule),
            style: const TextStyle(color: kAccent, fontSize: 14, fontWeight: FontWeight.w700),
          ),
        ],
      ),
    );
  }
}
