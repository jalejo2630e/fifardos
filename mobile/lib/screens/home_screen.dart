import 'package:flutter/material.dart';

import '../main.dart';
import '../models/tournament.dart';
import '../services/auth_service.dart';
import 'create_tournament_screen.dart';
import 'login_screen.dart';
import 'tournament_detail_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  final TournamentService _service = TournamentService();
  late Future<List<Tournament>> _future;
  bool _loggingOut = false;

  @override
  void initState() {
    super.initState();
    _future = _service.tournaments();
  }

  Future<void> _refresh() async {
    setState(() => _future = _service.tournaments());
    await _future;
  }

  Future<void> _logout() async {
    setState(() => _loggingOut = true);
    await AuthService().logout();
    if (!mounted) return;
    Navigator.of(context).pushAndRemoveUntil(
      MaterialPageRoute(builder: (_) => const LoginScreen()),
      (route) => false,
    );
  }

  String _statusLabel(String status) {
    switch (status) {
      case 'setup':
        return 'EN PREPARACIÓN';
      case 'in_progress':
        return 'EN CURSO';
      case 'completed':
      case 'finished':
        return 'FINALIZADO';
      default:
        return status.toUpperCase();
    }
  }

  Color _statusColor(String status) {
    switch (status) {
      case 'in_progress':
        return kAccent;
      case 'completed':
      case 'finished':
        return const Color(0xFF10B981);
      default:
        return kTextDim;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text(
          'FIFARDOS',
          style: TextStyle(fontWeight: FontWeight.w800, letterSpacing: 3),
        ),
        actions: [
          IconButton(
            tooltip: 'Salir',
            onPressed: _loggingOut ? null : _logout,
            icon: const Icon(Icons.logout),
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _refresh,
        color: kAccent,
        child: FutureBuilder<List<Tournament>>(
          future: _future,
          builder: (context, snapshot) {
            if (snapshot.connectionState == ConnectionState.waiting) {
              return const Center(child: CircularProgressIndicator(color: kAccent));
            }
            if (snapshot.hasError) {
              return _ErrorView(
                message: 'No se pudieron cargar los torneos.\n${snapshot.error}',
                onRetry: _refresh,
              );
            }
            final tournaments = snapshot.data ?? [];
            if (tournaments.isEmpty) {
              return ListView(
                children: const [
                  SizedBox(height: 160),
                  Icon(Icons.emoji_events_outlined, color: kTextDim, size: 64),
                  SizedBox(height: 16),
                  Text(
                    'Todavía no tenés torneos.',
                    textAlign: TextAlign.center,
                    style: TextStyle(color: kTextDim),
                  ),
                  SizedBox(height: 8),
                  Text(
                    'Creá uno desde la web en fifardos.com',
                    textAlign: TextAlign.center,
                    style: TextStyle(color: kTextDim, fontSize: 13),
                  ),
                ],
              );
            }
            return ListView.separated(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              itemCount: tournaments.length,
              separatorBuilder: (_, _) => const SizedBox(height: 12),
              itemBuilder: (context, i) => _TournamentCard(
                tournament: tournaments[i],
                statusLabel: _statusLabel(tournaments[i].status),
                statusColor: _statusColor(tournaments[i].status),
                onTap: () async {
                  await Navigator.of(context).push(
                    MaterialPageRoute(
                      builder: (_) => TournamentDetailScreen(
                        tournamentId: tournaments[i].id,
                        tournamentName: tournaments[i].name,
                        tournamentColor: tournaments[i].color,
                      ),
                    ),
                  );
                  _refresh();
                },
              ),
            );
          },
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () async {
          await Navigator.of(context).push(
            MaterialPageRoute(builder: (_) => const CreateTournamentScreen()),
          );
          _refresh();
        },
        backgroundColor: kAccent,
        foregroundColor: const Color(0xFF08080A),
        icon: const Icon(Icons.add),
        label: const Text('NUEVO TORNEO', style: TextStyle(fontWeight: FontWeight.w700, letterSpacing: 1)),
      ),
    );
  }
}

class _TournamentCard extends StatelessWidget {
  final Tournament tournament;
  final String statusLabel;
  final Color statusColor;
  final VoidCallback onTap;

  const _TournamentCard({
    required this.tournament,
    required this.statusLabel,
    required this.statusColor,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final progress = tournament.progressPercent.clamp(0, 100);
    final color = _parseHex(tournament.color);
    final modeLabel = tournament.mode == 'physical' ? 'Físico' : 'Virtual';
    return Card(
      child: InkWell(
        borderRadius: BorderRadius.circular(8),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(color: color, shape: BoxShape.circle),
                    alignment: Alignment.center,
                    child: Text(
                      tournament.sportIcon,
                      style: const TextStyle(fontSize: 22),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          tournament.name,
                          style: const TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.w700,
                            color: Colors.white,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        const SizedBox(height: 6),
                        Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                              decoration: BoxDecoration(
                                color: color.withValues(alpha: 0.18),
                                borderRadius: BorderRadius.circular(20),
                              ),
                              child: Text(
                                tournament.sportName.isEmpty ? tournament.sport : tournament.sportName,
                                style: TextStyle(color: color, fontSize: 11, fontWeight: FontWeight.w700),
                              ),
                            ),
                            const SizedBox(width: 8),
                            const Icon(Icons.videogame_asset, color: kTextDim, size: 13),
                            const SizedBox(width: 4),
                            Text(modeLabel, style: const TextStyle(color: kTextDim, fontSize: 12)),
                          ],
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Container(
                            width: 8,
                            height: 8,
                            decoration: BoxDecoration(color: statusColor, shape: BoxShape.circle),
                          ),
                          const SizedBox(width: 6),
                          Text(
                            statusLabel,
                            style: TextStyle(
                              color: statusColor,
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                              letterSpacing: 1.2,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 4),
                      Text(
                        '${tournament.competitorCount} ${tournament.isTeam ? 'equipos' : 'jug.'}',
                        style: const TextStyle(color: kTextDim, fontSize: 12),
                      ),
                    ],
                  ),
                ],
              ),
              const SizedBox(height: 14),
              Row(
                children: [
                  Expanded(
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(4),
                      child: LinearProgressIndicator(
                        value: progress / 100,
                        minHeight: 6,
                        backgroundColor: kSurfaceLow,
                        color: kAccent,
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Text(
                    '${tournament.playedMatches}/${tournament.totalMatches}',
                    style: const TextStyle(color: kTextDim, fontSize: 12),
                  ),
                ],
              ),
              if (tournament.leader != null) ...[
                const SizedBox(height: 12),
                Row(
                  children: [
                    const Icon(Icons.emoji_events, color: kAccent, size: 16),
                    const SizedBox(width: 6),
                    Expanded(
                      child: Text(
                        'Líder: ${tournament.leader!.competitorName ?? '—'} · ${tournament.leader!.pts} pts',
                        style: const TextStyle(color: kAccentSoft, fontSize: 13),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  ],
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

Color _parseHex(String hex) {
  var value = hex.replaceAll('#', '');
  if (value.length == 6) value = 'FF$value';
  final parsed = int.tryParse(value, radix: 16) ?? 0xFFFF5F00;
  return Color(parsed);
}

class _ErrorView extends StatelessWidget {
  final String message;
  final Future<void> Function() onRetry;

  const _ErrorView({required this.message, required this.onRetry});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.cloud_off, color: kTextDim, size: 48),
            const SizedBox(height: 12),
            Text(
              message,
              textAlign: TextAlign.center,
              style: const TextStyle(color: kTextDim),
            ),
            const SizedBox(height: 16),
            OutlinedButton(
              onPressed: () => onRetry(),
              child: const Text('REINTENTAR'),
            ),
          ],
        ),
      ),
    );
  }
}
