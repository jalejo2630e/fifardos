import 'dart:async';

import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../main.dart';
import '../models/tournament.dart';
import '../services/auth_service.dart';
import '../services/storage_service.dart';
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
  Timer? _syncTimer;
  DateTime? _lastSync;
  bool _loggingOut = false;
  bool _isAdmin = false;
  String _userName = '';

  @override
  void initState() {
    super.initState();
    _future = _service.tournaments();
    _loadUser();
    _syncTimer = Timer.periodic(const Duration(seconds: 12), (_) {
      if (mounted) _refresh(silent: true);
    });
  }

  @override
  void dispose() {
    _syncTimer?.cancel();
    super.dispose();
  }

  Future<void> _loadUser() async {
    final isAdmin = await AuthService.isAdmin();
    final user = await StorageService.getUser();
    if (mounted) {
      setState(() {
        _isAdmin = isAdmin;
        _userName = (user?['name'] as String? ?? '').trim();
      });
    }
  }

  Future<void> _refresh({bool silent = false}) async {
    if (!silent) {
      setState(() => _future = _service.tournaments());
    } else {
      _future = _service.tournaments();
    }
    try {
      await _future;
      if (mounted) setState(() => _lastSync = DateTime.now());
    } catch (_) {}
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

  Future<void> _openUrl(String url) async {
    final uri = Uri.parse(url);
    try {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('No se pudo abrir el enlace.')),
        );
      }
    }
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
        title: Row(
          children: [
            const Text(
              'FIFARDOS',
              style: TextStyle(fontWeight: FontWeight.w800, letterSpacing: 3),
            ),
            if (_isAdmin) ...[
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(
                  color: kAccent.withValues(alpha: 0.18),
                  borderRadius: BorderRadius.circular(4),
                ),
                child: const Text(
                  'ADMIN',
                  style: TextStyle(color: kAccent, fontSize: 10, fontWeight: FontWeight.w800, letterSpacing: 1),
                ),
              ),
            ],
          ],
        ),
        actions: [
          IconButton(
            tooltip: 'Explorar en la web',
            onPressed: () => _openUrl('https://www.fifardos.com'),
            icon: const Icon(Icons.public),
          ),
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
            return ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              children: [
                if (_isAdmin) ...[
                  _AdminPanel(onOpen: _openUrl),
                  const SizedBox(height: 20),
                ],
                if (!_isAdmin) ...[
                  _UserWelcome(name: _userName),
                  const SizedBox(height: 14),
                ],
                if (!_isAdmin) ...[
                  _JoinTournament(onOpen: _openUrl),
                  const SizedBox(height: 20),
                ],
                Row(
                  children: [
                    const Expanded(
                      child: Text(
                        'MIS TORNEOS',
                        style: TextStyle(color: kTextDim, fontSize: 12, letterSpacing: 1.5, fontWeight: FontWeight.w700),
                      ),
                    ),
                    if (_lastSync != null)
                      Text(
                        'SINCRONIZADO ${_fmtTime(_lastSync!)}',
                        style: const TextStyle(color: Color(0xFF10B981), fontSize: 11),
                      ),
                  ],
                ),
                const SizedBox(height: 10),
                if (tournaments.isEmpty)
                  _EmptyTournaments(isAdmin: _isAdmin)
                else
                  ...tournaments.map(
                    (t) => Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: _TournamentCard(
                        tournament: t,
                        statusLabel: _statusLabel(t.status),
                        statusColor: _statusColor(t.status),
                        onTap: () async {
                          await Navigator.of(context).push(
                            MaterialPageRoute(
                              builder: (_) => TournamentDetailScreen(
                                tournamentId: t.id,
                                tournamentName: t.name,
                                tournamentColor: t.color,
                              ),
                            ),
                          );
                          _refresh(silent: true);
                        },
                      ),
                    ),
                  ),
              ],
            );
          },
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: () async {
          await Navigator.of(context).push(
            MaterialPageRoute(builder: (_) => const CreateTournamentScreen()),
          );
          _refresh(silent: true);
        },
        backgroundColor: kAccent,
        foregroundColor: const Color(0xFF08080A),
        icon: const Icon(Icons.add),
        label: const Text('NUEVO TORNEO', style: TextStyle(fontWeight: FontWeight.w700, letterSpacing: 1)),
      ),
    );
  }

  static String _fmtTime(DateTime t) {
    final h = t.hour.toString().padLeft(2, '0');
    final m = t.minute.toString().padLeft(2, '0');
    final s = t.second.toString().padLeft(2, '0');
    return '$h:$m:$s';
  }
}

class _AdminPanel extends StatelessWidget {
  final void Function(String url) onOpen;

  const _AdminPanel({required this.onOpen});

  @override
  Widget build(BuildContext context) {
    const items = [
      ('USUARIOS', 'https://www.fifardos.com/admin/usuarios', Icons.group_outlined),
      ('REPORTES', 'https://www.fifardos.com/admin/reportes', Icons.insights_outlined),
      ('CHAT IA', 'https://www.fifardos.com/admin/chat-config', Icons.smart_toy_outlined),
      ('CÓMO USAR', 'https://www.fifardos.com/admin/como-usar', Icons.help_outline),
    ];
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'PANEL DE ADMINISTRADOR',
          style: const TextStyle(color: kTextDim, fontSize: 12, letterSpacing: 1.5, fontWeight: FontWeight.w700),
        ),
        const SizedBox(height: 10),
        GridView.count(
          crossAxisCount: 4,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          mainAxisSpacing: 8,
          crossAxisSpacing: 8,
          childAspectRatio: 0.9,
          children: [
            for (final (label, url, icon) in items)
              InkWell(
                onTap: () => onOpen(url),
                borderRadius: BorderRadius.circular(8),
                child: Container(
                  decoration: BoxDecoration(
                    color: kSurface,
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: kSurfaceLow),
                  ),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(icon, color: kAccent, size: 24),
                      const SizedBox(height: 6),
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 4),
                        child: Text(
                          label,
                          textAlign: TextAlign.center,
                          style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.w700, letterSpacing: 0.5),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
          ],
        ),
      ],
    );
  }
}

class _UserWelcome extends StatelessWidget {
  final String name;

  const _UserWelcome({required this.name});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: kSurface,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: kSurfaceLow),
      ),
      child: Row(
        children: [
          Container(
            width: 44,
            height: 44,
            decoration: const BoxDecoration(color: kAccent, shape: BoxShape.circle),
            alignment: Alignment.center,
            child: Text(
              name.isEmpty ? 'U' : name.characters.first.toUpperCase(),
              style: const TextStyle(color: Color(0xFF08080A), fontSize: 20, fontWeight: FontWeight.w800),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  name.isEmpty ? '¡Hola!' : '¡Hola, $name!',
                  style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.w700),
                ),
                const SizedBox(height: 4),
                const Text(
                  'Seguí tus torneos y el tablero en vivo.',
                  style: TextStyle(color: kTextDim, fontSize: 13),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _JoinTournament extends StatefulWidget {
  final void Function(String url) onOpen;

  const _JoinTournament({required this.onOpen});

  @override
  State<_JoinTournament> createState() => _JoinTournamentState();
}

class _JoinTournamentState extends State<_JoinTournament> {
  final _link = TextEditingController();

  @override
  void dispose() {
    _link.dispose();
    super.dispose();
  }

  void _open() {
    var value = _link.text.trim();
    if (value.isEmpty) return;
    final uri = Uri.tryParse(value);
    if (uri != null && uri.hasScheme) {
      widget.onOpen(value);
    } else {
      widget.onOpen('https://www.fifardos.com/torneos/${_slugify(value)}/bracket');
    }
  }

  static String _slugify(String s) =>
      s.trim().toLowerCase().replaceAll(RegExp(r'[^a-z0-9]+'), '-').replaceAll(RegExp(r'^-+|-+$'), '');

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: kSurface,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: kSurfaceLow),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Row(
            children: [
              Icon(Icons.link, color: kAccent, size: 20),
              SizedBox(width: 8),
              Text(
                '¿TE COMPARTIERON UN TORNEO?',
                style: TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.w700),
              ),
            ],
          ),
          const SizedBox(height: 10),
          const Text(
            'Pegá el link del torneo o su nombre para ver el bracket, la tabla y los resultados en vivo.',
            style: TextStyle(color: kTextDim, fontSize: 13),
          ),
          const SizedBox(height: 12),
          TextField(
            controller: _link,
            style: const TextStyle(color: Colors.white, fontSize: 14),
            decoration: InputDecoration(
              hintText: 'fifardos.com/torneos/mi-torneo o "mi torneo"',
              hintStyle: const TextStyle(color: kTextDim, fontSize: 13),
              filled: true,
              fillColor: kSurfaceLow,
              isDense: true,
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(6),
                borderSide: BorderSide.none,
              ),
            ),
          ),
          const SizedBox(height: 10),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: _open,
              style: ElevatedButton.styleFrom(
                backgroundColor: kAccent,
                foregroundColor: const Color(0xFF08080A),
              ),
              child: const Text('ABRIR TORNEO', style: TextStyle(fontWeight: FontWeight.w700, letterSpacing: 1)),
            ),
          ),
        ],
      ),
    );
  }
}

class _EmptyTournaments extends StatelessWidget {
  final bool isAdmin;

  const _EmptyTournaments({required this.isAdmin});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        const SizedBox(height: 60),
        const Icon(Icons.emoji_events_outlined, color: kTextDim, size: 64),
        const SizedBox(height: 16),
        const Text(
          'Todavía no tenés torneos.',
          textAlign: TextAlign.center,
          style: TextStyle(color: kTextDim),
        ),
        const SizedBox(height: 8),
        Text(
          isAdmin
              ? 'Creá uno desde el botón NUEVO TORNEO o desde la web en fifardos.com'
              : 'Creá el tuyo con NUEVO TORNEO, o abrí el que te compartieron con el campo de arriba.',
          textAlign: TextAlign.center,
          style: const TextStyle(color: kTextDim, fontSize: 13),
        ),
      ],
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
