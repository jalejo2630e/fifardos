import 'package:flutter/material.dart';

import '../main.dart';
import '../models/standing.dart';
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

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _matchesFuture = _service.matches(widget.tournamentId);
    _standingsFuture = _service.standings(widget.tournamentId);
    _topScorerFuture = _service.topScorer(widget.tournamentId);
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _reload() async {
    setState(() {
      _matchesFuture = _service.matches(widget.tournamentId);
      _standingsFuture = _service.standings(widget.tournamentId);
      _topScorerFuture = _service.topScorer(widget.tournamentId);
    });
    await Future.wait([_matchesFuture, _standingsFuture, _topScorerFuture]);
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
