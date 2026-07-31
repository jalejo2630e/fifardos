import 'package:flutter/material.dart';

import '../main.dart';
import '../models/tournament.dart';
import '../services/auth_service.dart';
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
                    width: 10,
                    height: 10,
                    decoration: BoxDecoration(
                      color: statusColor,
                      shape: BoxShape.circle,
                    ),
                  ),
                  const SizedBox(width: 8),
                  Text(
                    statusLabel,
                    style: TextStyle(
                      color: statusColor,
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                      letterSpacing: 1.5,
                    ),
                  ),
                  const Spacer(),
                  Text(
                    '${tournament.competitorCount} ${tournament.isTeam ? 'equipos' : 'jug.'}',
                    style: const TextStyle(color: kTextDim, fontSize: 12),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              Text(
                tournament.name,
                style: const TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.w700,
                  color: Colors.white,
                ),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
              ),
              const SizedBox(height: 12),
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
