import 'package:flutter/material.dart';

import '../main.dart';
import '../models/tournament_match.dart';
import '../services/auth_service.dart';

class MatchScoreScreen extends StatefulWidget {
  final int tournamentId;
  final TournamentMatch match;

  const MatchScoreScreen({
    super.key,
    required this.tournamentId,
    required this.match,
  });

  @override
  State<MatchScoreScreen> createState() => _MatchScoreScreenState();
}

class _MatchScoreScreenState extends State<MatchScoreScreen> {
  final TournamentService _service = TournamentService();
  final _p1Score = TextEditingController();
  final _p2Score = TextEditingController();
  final _pen1Score = TextEditingController();
  final _pen2Score = TextEditingController();
  late List<Map<String, TextEditingController>> _sets;
  bool _usePenalties = false;
  bool _loading = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    final initialSets = widget.match.sets.isNotEmpty
        ? widget.match.sets
        : [MatchSet(a: 0, b: 0)];
    _sets = initialSets
        .map((s) => {
              'a': TextEditingController(text: '${s.a}'),
              'b': TextEditingController(text: '${s.b}'),
            })
        .toList();
  }

  @override
  void dispose() {
    _p1Score.dispose();
    _p2Score.dispose();
    _pen1Score.dispose();
    _pen2Score.dispose();
    for (final s in _sets) {
      s['a']!.dispose();
      s['b']!.dispose();
    }
    super.dispose();
  }

  Future<void> _save() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      if (widget.match.isSets) {
        final sets = _sets
            .map((s) => {
                  'a': int.tryParse(s['a']!.text) ?? 0,
                  'b': int.tryParse(s['b']!.text) ?? 0,
                })
            .toList();
        if (sets.isEmpty || sets.any((s) => (s['a'] as int) == (s['b'] as int))) {
          throw Exception('Un set no puede terminar empatado.');
        }
        await _service.recordScore(
          widget.tournamentId,
          widget.match.id,
          sets: sets,
        );
      } else {
        final s1 = int.tryParse(_p1Score.text);
        final s2 = int.tryParse(_p2Score.text);
        if (s1 == null || s2 == null || s1 < 0 || s2 < 0) {
          throw Exception('Ingresá un marcador válido (números enteros).');
        }
        int? pen1, pen2;
        if (_usePenalties) {
          pen1 = int.tryParse(_pen1Score.text);
          pen2 = int.tryParse(_pen2Score.text);
          if (pen1 == null || pen2 == null) {
            throw Exception('Ingresá los penales de ambos equipos.');
          }
        }
        await _service.recordScore(
          widget.tournamentId,
          widget.match.id,
          score1: s1,
          score2: s2,
          penalties1: pen1,
          penalties2: pen2,
        );
      }
      if (!mounted) return;
      Navigator.of(context).pop(true);
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final p1 = widget.match.competitor1Name ?? '—';
    final p2 = widget.match.competitor2Name ?? '—';
    final isSets = widget.match.isSets;

    Widget teamScore(String name, TextEditingController controller, Color color) => Expanded(
          child: Column(
            children: [
              Text(
                name,
                textAlign: TextAlign.center,
                style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16, color: Colors.white),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
              const SizedBox(height: 12),
              TextField(
                controller: controller,
                keyboardType: TextInputType.number,
                textAlign: TextAlign.center,
                style: const TextStyle(fontSize: 36, fontWeight: FontWeight.w800, color: Colors.white),
                decoration: InputDecoration(
                  hintText: '0',
                  filled: true,
                  fillColor: kSurfaceLow,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(8),
                    borderSide: BorderSide.none,
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(8),
                    borderSide: BorderSide(color: color, width: 2),
                  ),
                ),
              ),
            ],
          ),
        );

    Widget setRow(Map<String, TextEditingController> s, int idx) => Row(
          children: [
            Text(
              'SET ${idx + 1}',
              style: const TextStyle(color: kTextDim, fontSize: 12, letterSpacing: 1),
            ),
            const Spacer(),
            SizedBox(
              width: 60,
              child: TextField(
                controller: s['a']!,
                keyboardType: TextInputType.number,
                textAlign: TextAlign.center,
                style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: Colors.white),
                decoration: InputDecoration(
                  filled: true,
                  fillColor: kSurfaceLow,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(6),
                    borderSide: BorderSide.none,
                  ),
                ),
              ),
            ),
            const Padding(
              padding: EdgeInsets.symmetric(horizontal: 8),
              child: Text(':', style: TextStyle(color: kTextDim, fontSize: 18, fontWeight: FontWeight.w800)),
            ),
            SizedBox(
              width: 60,
              child: TextField(
                controller: s['b']!,
                keyboardType: TextInputType.number,
                textAlign: TextAlign.center,
                style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: Colors.white),
                decoration: InputDecoration(
                  filled: true,
                  fillColor: kSurfaceLow,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(6),
                    borderSide: BorderSide.none,
                  ),
                ),
              ),
            ),
            const SizedBox(width: 8),
            IconButton(
              onPressed: _sets.length > 1
                  ? () => setState(() => _sets.removeAt(idx))
                  : null,
              icon: const Icon(Icons.remove_circle_outline, size: 18, color: kTextDim),
            ),
          ],
        );

    return Scaffold(
      appBar: AppBar(title: Text(isSets ? 'Cargar sets' : 'Cargar resultado')),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(
                'Ronda ${widget.match.round} · TV${widget.match.tvNumber}',
                textAlign: TextAlign.center,
                style: const TextStyle(color: kTextDim, letterSpacing: 1),
              ),
              const SizedBox(height: 20),
              if (!isSets)
                Row(
                  children: [
                    teamScore(p1, _p1Score, kAccent),
                    const Padding(
                      padding: EdgeInsets.symmetric(horizontal: 12),
                      child: Text(
                        ':',
                        style: TextStyle(fontSize: 32, fontWeight: FontWeight.w800, color: kTextDim),
                      ),
                    ),
                    teamScore(p2, _p2Score, const Color(0xFF10B981)),
                  ],
                )
              else ...[
                Text(
                  '$p1 vs $p2',
                  textAlign: TextAlign.center,
                  style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16, color: Colors.white),
                ),
                const SizedBox(height: 16),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: kSurfaceLow,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Column(
                    children: [
                      ..._sets.asMap().entries.map((e) => setRow(e.value, e.key)),
                      const SizedBox(height: 4),
                      TextButton.icon(
                        onPressed: _sets.length < widget.match.maxSets
                            ? () => setState(() => _sets.add({
                                  'a': TextEditingController(text: '0'),
                                  'b': TextEditingController(text: '0'),
                                }))
                            : null,
                        icon: const Icon(Icons.add, size: 16),
                        label: Text('AGREGAR SET'),
                      ),
                    ],
                  ),
                ),
              ],
              if (!isSets) ...[
                const SizedBox(height: 24),
                SwitchListTile(
                  value: _usePenalties,
                  onChanged: (v) => setState(() => _usePenalties = v),
                  activeTrackColor: kAccent,
                  activeThumbColor: const Color(0xFF08080A),
                  title: const Text('Fue por penales'),
                  subtitle: const Text('Marcador empatado y definieron desde los 12 pasos'),
                  contentPadding: EdgeInsets.zero,
                ),
                if (_usePenalties) ...[
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      Expanded(
                        child: TextField(
                          controller: _pen1Score,
                          keyboardType: TextInputType.number,
                          textAlign: TextAlign.center,
                          decoration: InputDecoration(
                            labelText: 'Penales $p1',
                            labelStyle: const TextStyle(color: kAccent, fontSize: 12),
                          ),
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: TextField(
                          controller: _pen2Score,
                          keyboardType: TextInputType.number,
                          textAlign: TextAlign.center,
                          decoration: InputDecoration(
                            labelText: 'Penales $p2',
                            labelStyle: const TextStyle(color: Color(0xFF10B981), fontSize: 12),
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ],
              if (_error != null) ...[
                const SizedBox(height: 16),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: kAccent.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(6),
                    border: Border.all(color: kAccent.withValues(alpha: 0.4)),
                  ),
                  child: Text(_error!, style: const TextStyle(color: kAccentSoft, fontSize: 13)),
                ),
              ],
              const SizedBox(height: 28),
              ElevatedButton(
                onPressed: _loading ? null : _save,
                child: _loading
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(strokeWidth: 2, color: Color(0xFF08080A)),
                      )
                    : Text(isSets ? 'GUARDAR SETS' : 'GUARDAR RESULTADO'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
