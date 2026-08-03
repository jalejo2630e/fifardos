import 'package:flutter/material.dart';

import '../main.dart';
import '../models/catalog.dart';
import '../services/auth_service.dart';
import 'home_screen.dart';

class _TeamData {
  String name;
  List<String> players = [];
  _TeamData({required this.name});
}

class CreateTournamentScreen extends StatefulWidget {
  const CreateTournamentScreen({super.key});

  @override
  State<CreateTournamentScreen> createState() => _CreateTournamentScreenState();
}

class _CreateTournamentScreenState extends State<CreateTournamentScreen> {
  final TournamentService _service = TournamentService();

  SportCatalog? _catalog;
  bool _loadingCatalog = true;

  int _step = 1;

  // Form state
  String _name = '';
  String? _sportKey;
  String _mode = 'virtual';
  bool _modeTouched = false;
  int _consoles = 1;
  int _minutes = 6;
  String _format = 'groups_knockout';
  bool _homeAway = false;
  Map<String, String> _rules = {};

  final List<String> _players = [];
  final List<_TeamData> _teams = [];

  final TextEditingController _newPlayer = TextEditingController();
  final TextEditingController _newTeam = TextEditingController();
  final Map<int, TextEditingController> _newMember = {};

  bool _submitting = false;
  String? _submitError;

  @override
  void initState() {
    super.initState();
    _loadCatalog();
  }

  @override
  void dispose() {
    _newPlayer.dispose();
    _newTeam.dispose();
    for (final c in _newMember.values) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _loadCatalog() async {
    setState(() => _loadingCatalog = true);
    try {
      final cat = await _service.catalog();
      setState(() {
        _catalog = cat;
        _loadingCatalog = false;
      });
    } catch (e) {
      setState(() {
        _loadingCatalog = false;
        _submitError = 'No se pudo cargar el catálogo de deportes.\n$e';
      });
    }
  }

  SportInfo? get _currentSport {
    if (_sportKey == null || _catalog == null) return null;
    for (final s in _catalog!.sports) {
      if (s.key == _sportKey) return s;
    }
    return null;
  }

  bool get _isTeam => _currentSport?.isTeam ?? false;

  void _selectSport(String key) {
    final cat = _catalog;
    if (cat == null) return;
    SportInfo? picked;
    for (final s in cat.sports) {
      if (s.key == key) picked = s;
    }
    if (picked == null) return;
    setState(() {
      _sportKey = key;
      _minutes = picked!.minutes;
      if (!_modeTouched) _mode = key == 'fifa' ? 'virtual' : 'physical';
      _players.clear();
      _teams.clear();
      final defaults = <String, String>{};
      for (final def in cat.rules[key] ?? []) {
        defaults[def.key] =
            def.defaultValue ?? (def.type == 'boolean' ? '0' : '');
      }
      _rules = defaults;
    });
  }

  List<RuleDef> get _rulesForSport {
    if (_sportKey == null || _catalog == null) return [];
    return _catalog!.rules[_sportKey] ?? [];
  }

  List<(String, List<RuleDef>)> get _rulesGroups {
    final out = <(String, List<RuleDef>)>[];
    for (final def in _rulesForSport) {
      final g = def.group.isEmpty ? 'general' : def.group;
      final idx = out.indexWhere((e) => e.$1 == g);
      if (idx >= 0) {
        out[idx] = (g, [...out[idx].$2, def]);
      } else {
        out.add((g, [def]));
      }
    }
    return out;
  }

  bool get _canNext {
    switch (_step) {
      case 1:
        return _sportKey != null;
      case 2:
        return _name.trim().isNotEmpty;
      case 3:
        return _consoles >= 1;
      default:
        return _isTeam ? _teams.length >= 2 : _players.length >= 2;
    }
  }

  int get _steps => 4;

  bool get _physical => _mode == 'physical';

  String get _tvSingular => _physical ? 'cancha' : 'consola';
  String get _tvPlural => _physical ? 'canchas' : 'consolas';

  Map<String, int>? get _estimate {
    final n = _isTeam ? _teams.length : _players.length;
    final tv = _consoles < 1 ? 1 : _consoles;
    final m = _minutes < 1 ? 1 : _minutes;
    if (n < 2) return null;
    int group = (n * (n - 1)) ~/ 2;
    if (_homeAway) group *= 2;
    int knockout = 0;
    if (_format == 'groups_knockout') {
      int top = n <= 4 ? 4 : (n <= 8 ? 8 : 16);
      if (top > n) top = n;
      // power of two floor
      var pw = 1;
      while (pw * 2 <= top) {
        pw *= 2;
      }
      top = pw;
      if (top < 2) top = 2;
      knockout = top >= 4 ? top : 1;
    }
    final total = group + knockout;
    final slots = (total / tv).ceil();
    return {'group': group, 'knockout': knockout, 'total': total, 'minutes': slots * m, 'tv': tv, 'm': m};
  }

  String _fmtDuration(int min) {
    if (min <= 0) return '—';
    final h = min ~/ 60;
    final mm = min % 60;
    if (h == 0) return '$mm min';
    if (mm == 0) return '$h h';
    return '$h h $mm min';
  }

  void _addPlayer() {
    final name = _newPlayer.text.trim();
    if (name.isEmpty || _players.contains(name)) return;
    setState(() {
      _players.add(name);
      _newPlayer.clear();
    });
  }

  void _addTeam() {
    final name = _newTeam.text.trim();
    if (name.isEmpty || _teams.any((t) => t.name == name)) return;
    setState(() {
      _teams.add(_TeamData(name: name));
      _newTeam.clear();
    });
  }

  void _addMember(int teamIndex) {
    final c = _newMember[teamIndex] ?? TextEditingController();
    final name = c.text.trim();
    final team = _teams[teamIndex];
    if (name.isEmpty || team.players.contains(name)) return;
    setState(() {
      team.players = [...team.players, name];
      c.clear();
    });
  }

  void _submit() async {
    final key = _sportKey!;
    setState(() {
      _submitting = true;
      _submitError = null;
    });
    try {
      await _service.createTournament(
        name: _name.trim(),
        sport: key,
        mode: _mode,
        consolesCount: _consoles,
        minutesPerMatch: _minutes,
        format: _format,
        homeAndAway: _homeAway,
        players: _players,
        teams: _teams.map((t) => {'name': t.name, 'players': t.players}).toList(),
        rules: _rules,
      );
      if (!mounted) return;
      Navigator.of(context).pushAndRemoveUntil(
        MaterialPageRoute(builder: (_) => const HomeScreen()),
        (route) => route.isFirst,
      );
    } catch (e) {
      setState(() {
        _submitError = e.toString();
        _submitting = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('NUEVO TORNEO', style: TextStyle(fontSize: 15, letterSpacing: 2, fontWeight: FontWeight.w800)),
      ),
      body: _body(),
      bottomNavigationBar: _bottomBar(),
    );
  }

  Widget _body() {
    if (_loadingCatalog) {
      return const Center(child: CircularProgressIndicator(color: kAccent));
    }
    if (_catalog == null) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(_submitError ?? 'No se pudo cargar el catálogo.', textAlign: TextAlign.center, style: const TextStyle(color: kTextDim)),
              const SizedBox(height: 16),
              OutlinedButton(onPressed: _loadCatalog, child: const Text('REINTENTAR')),
            ],
          ),
        ),
      );
    }
    return Column(
      children: [
        const SizedBox(height: 16),
        _StepIndicator(current: _step, total: _steps, canFinish: _canNext),
        const SizedBox(height: 12),
        Expanded(
          child: SingleChildScrollView(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 24),
            child: switch (_step) {
              1 => _buildSportStep(),
              2 => _buildNameStep(),
              3 => _buildFormatStep(),
              _ => _buildCompetitorsStep(),
            },
          ),
        ),
      ],
    );
  }

  Widget _bottomBar() {
    return SafeArea(
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
        decoration: const BoxDecoration(
          color: kSurface,
          border: Border(top: BorderSide(color: Color(0xFF2A2A2A))),
        ),
        child: Row(
          children: [
            if (_step > 1)
              OutlinedButton(
                onPressed: _submitting
                    ? null
                    : () => setState(() => _step -= 1),
                style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 14)),
                child: const Text('ANTERIOR', style: TextStyle(fontSize: 13)),
              ),
            if (_step == 1) const Spacer(),
            const Spacer(),
            if (_step < _steps)
              ElevatedButton(
                onPressed: _canNext ? () => setState(() => _step += 1) : null,
                style: ElevatedButton.styleFrom(padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14)),
                child: const Text('SIGUIENTE', style: TextStyle(fontSize: 13)),
              )
            else
              ElevatedButton(
                onPressed: (_canNext && !_submitting) ? _submit : null,
                style: ElevatedButton.styleFrom(padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14)),
                child: _submitting
                    ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Color(0xFF08080A)))
                    : const Text('INICIAR TORNEO', style: TextStyle(fontSize: 13)),
              ),
          ],
        ),
      ),
    );
  }

  // ---- Step 1: Deporte + modo ----
  Widget _buildSportStep() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        _sectionLabel('¿QUÉ DEPORTE?', 'Torneos individuales o por equipos'),
        const SizedBox(height: 12),
        GridView.count(
          crossAxisCount: 2,
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          mainAxisSpacing: 8,
          crossAxisSpacing: 8,
          childAspectRatio: 2.4,
          children: _catalog!.sports.map((s) {
            final selected = _sportKey == s.key;
            return _SelectableCard(
              selected: selected,
              onTap: () => _selectSport(s.key),
              child: Row(
                children: [
                  Text(s.icon, style: const TextStyle(fontSize: 26)),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(s.name, style: TextStyle(fontWeight: FontWeight.w700, color: selected ? kAccent : Colors.white, fontSize: 14), maxLines: 1, overflow: TextOverflow.ellipsis),
                        const SizedBox(height: 2),
                        Text(
                          '${s.isTeam ? 'Equipos' : 'Individual'} · ${s.playersPerSide}v${s.playersPerSide}',
                          style: const TextStyle(color: Color(0x66FFFFFF), fontSize: 11),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            );
          }).toList(),
        ),
        const SizedBox(height: 24),
        const Divider(color: Color(0x14FFFFFF)),
        const SizedBox(height: 20),
        _sectionLabel('¿CÓMO SE JUEGA?'),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: _ModeCard(
                icon: '🎮',
                label: 'Virtual',
                desc: 'Consolas y videojuegos',
                selected: _mode == 'virtual',
                onTap: () => setState(() {
                  _mode = 'virtual';
                  _modeTouched = true;
                }),
              ),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: _ModeCard(
                icon: '🏟️',
                label: 'Campo físico',
                desc: 'Canchas y espacios reales',
                selected: _mode == 'physical',
                onTap: () => setState(() {
                  _mode = 'physical';
                  _modeTouched = true;
                }),
              ),
            ),
          ],
        ),
        if (_sportKey == null) ...[
          const SizedBox(height: 16),
          const _HintText('Elegí un deporte para continuar'),
        ],
      ],
    );
  }

  // ---- Step 2: Nombre ----
  Widget _buildNameStep() {
    final sport = _currentSport;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        const SizedBox(height: 8),
        if (sport != null) ...[
          Text(sport.icon, textAlign: TextAlign.center, style: const TextStyle(fontSize: 40)),
          const SizedBox(height: 6),
          Text(sport.name.toUpperCase(),
              textAlign: TextAlign.center,
              style: const TextStyle(color: kAccent, letterSpacing: 1.5, fontSize: 12, fontWeight: FontWeight.w700)),
          const SizedBox(height: 24),
        ],
        _sectionLabel('NOMBRE DEL TORNEO'),
        const SizedBox(height: 10),
        TextField(
          controller: _nameController,
          maxLength: 40,
          textAlign: TextAlign.center,
          style: const TextStyle(fontSize: 20, letterSpacing: 1, fontWeight: FontWeight.w600),
          decoration: const InputDecoration(
            hintText: 'Ej: Copa del Barrio 2026',
            hintStyle: TextStyle(color: Color(0x55FFFFFF)),
            counterText: '',
          ),
          onChanged: (v) => setState(() => _name = v),
        ),
        const SizedBox(height: 10),
        const _HintText('Elíge un nombre épico para tu torneo'),
      ],
    );
  }

  final TextEditingController _nameController = TextEditingController();

  // ---- Step 3: Formato + reglas + espacios + minutos ----
  Widget _buildFormatStep() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        _sectionLabel('FORMATO DEL TORNEO'),
        const SizedBox(height: 12),
        _FormatCard(
          title: 'Grupos + eliminatorias',
          desc: 'Todos contra todos y luego llaves hasta la final.',
          selected: _format == 'groups_knockout',
          onTap: () => setState(() => _format = 'groups_knockout'),
        ),
        const SizedBox(height: 8),
        _FormatCard(
          title: 'Liga · todos contra todos',
          desc: 'Una sola tabla; campeón = primero al final.',
          selected: _format == 'league',
          onTap: () => setState(() => _format = 'league'),
        ),
        const SizedBox(height: 8),
        _CheckRow(
          title: 'IDA Y VUELTA',
          desc: 'Cada cruce se juega dos veces (local y visitante).',
          value: _homeAway,
          onChanged: (v) => setState(() => _homeAway = v),
        ),
        if (_rulesForSport.isNotEmpty) ...[
          const SizedBox(height: 20),
          const Divider(color: Color(0x14FFFFFF)),
          const SizedBox(height: 18),
          _sectionLabel('REGLAS DEL TORNEO'),
          const _HintText('Cada deporte tiene sus propias reglas. Ajustalas a tu torneo.'),
          const SizedBox(height: 8),
          for (final (group, defs) in _rulesGroups) ...[
            Padding(
              padding: const EdgeInsets.only(top: 12, bottom: 6),
              child: Text(
                (ruleGroupLabels[group] ?? group).toUpperCase(),
                style: const TextStyle(color: kAccent, fontSize: 11, letterSpacing: 0.1, fontWeight: FontWeight.w700),
              ),
            ),
            for (final def in defs) _RuleRow(def: def, value: _rules[def.key] ?? '', onChanged: (v) => setState(() => _rules[def.key] = v)),
          ],
        ],
        const SizedBox(height: 20),
        const Divider(color: Color(0x14FFFFFF)),
        const SizedBox(height: 18),
        Text(
          _physical ? 'CANCHAS DISPONIBLES' : 'CONSOLAS DISPONIBLES',
          style: const TextStyle(color: Color(0x66FFFFFF), fontSize: 11, letterSpacing: 0.1, fontWeight: FontWeight.w700),
        ),
        const SizedBox(height: 4),
        _HintText(_physical ? 'Canchas disponibles en paralelo' : 'Consolas disponibles en paralelo'),
        const SizedBox(height: 12),
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            IconButton(
              onPressed: () => setState(() => _consoles = _consoles > 1 ? _consoles - 1 : 1),
              icon: const Icon(Icons.remove),
              style: IconButton.styleFrom(backgroundColor: const Color(0x1AFFFFFF), foregroundColor: Colors.white),
            ),
            Container(
              width: 70, height: 60,
              margin: const EdgeInsets.symmetric(horizontal: 12),
              alignment: Alignment.center,
              decoration: BoxDecoration(color: kAccent.withValues(alpha: 0.1), border: Border.all(color: kAccent.withValues(alpha: 0.25)), borderRadius: BorderRadius.circular(12)),
              child: Text('$_consoles', style: const TextStyle(fontSize: 30, fontWeight: FontWeight.w900, color: kAccent)),
            ),
            IconButton(
              onPressed: () => setState(() => _consoles = _consoles < 20 ? _consoles + 1 : 20),
              icon: const Icon(Icons.add),
              style: IconButton.styleFrom(backgroundColor: const Color(0x1AFFFFFF), foregroundColor: Colors.white),
            ),
          ],
        ),
        const SizedBox(height: 12),
        Wrap(
          alignment: WrapAlignment.center,
          spacing: 6,
          runSpacing: 6,
          children: [
            for (var i = 1; i <= (_consoles > 12 ? 12 : _consoles); i++)
              Container(
                width: 32, height: 32,
                alignment: Alignment.center,
                decoration: BoxDecoration(border: Border.all(color: kAccent.withValues(alpha: 0.25)), color: const Color(0x1A414348), borderRadius: BorderRadius.circular(6)),
                child: Text('$i', style: const TextStyle(fontWeight: FontWeight.w700, color: kAccent, fontSize: 12)),
              ),
            if (_consoles > 12)
              Container(
                width: 32, height: 32,
                alignment: Alignment.center,
                decoration: BoxDecoration(color: const Color(0x0FFFFFFF), borderRadius: BorderRadius.circular(6)),
                child: Text('+${_consoles - 12}', style: const TextStyle(fontSize: 11, color: Color(0x4DFFFFFF))),
              ),
          ],
        ),
        const SizedBox(height: 20),
        const Divider(color: Color(0x14FFFFFF)),
        const SizedBox(height: 18),
        _sectionLabel('MINUTOS POR PARTIDO'),
        const SizedBox(height: 12),
        Wrap(
          alignment: WrapAlignment.center,
          spacing: 8,
          runSpacing: 8,
          children: [5, 10, 15, 20, 30, 40, 60, 90].map((m) {
            final selected = _minutes == m;
            return ChoiceChip(
              label: Text('$m min'),
              selected: selected,
              onSelected: (_) => setState(() => _minutes = m),
              selectedColor: kAccent.withValues(alpha: 0.2),
              backgroundColor: const Color(0x0FFFFFFF),
              labelStyle: TextStyle(color: selected ? kAccent : Colors.white54, fontWeight: FontWeight.w600),
              side: BorderSide(color: selected ? kAccent : Colors.white12),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            );
          }).toList(),
        ),
      ],
    );
  }

  // ---- Step 4: Competidores ----
  Widget _buildCompetitorsStep() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(_isTeam ? 'EQUIPOS' : 'JUGADORES', style: const TextStyle(color: Color(0x66FFFFFF), fontSize: 11, letterSpacing: 0.1, fontWeight: FontWeight.w700)),
        const SizedBox(height: 4),
        Text(_isTeam ? 'Añade los equipos y sus integrantes (opcional)' : 'Añade a los participantes del torneo',
            style: const TextStyle(color: Colors.white38, fontSize: 13)),
        const SizedBox(height: 14),
        if (!_isTeam) ..._buildPlayersEditor() else ..._buildTeamsEditor(),
        const SizedBox(height: 18),
        if (_estimate != null) _buildEstimate(),
        if (_submitError != null) ...[
          const SizedBox(height: 16),
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(color: const Color(0x33F44336), borderRadius: BorderRadius.circular(8)),
            child: Text(_submitError!, style: const TextStyle(color: Color(0xFFFF8A80), fontSize: 13)),
          ),
        ],
      ],
    );
  }

  List<Widget> _buildPlayersEditor() {
    return [
      Row(
        children: [
          Expanded(
            child: TextField(
              controller: _newPlayer,
              style: const TextStyle(color: Colors.white),
              decoration: const InputDecoration(hintText: 'Nombre del jugador', hintStyle: TextStyle(color: kTextDim)),
              onSubmitted: (_) => _addPlayer(),
            ),
          ),
          const SizedBox(width: 10),
          ElevatedButton(
            onPressed: _addPlayer,
            style: ElevatedButton.styleFrom(padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 16)),
            child: const Text('+ AÑADIR', style: TextStyle(fontSize: 12)),
          ),
        ],
      ),
      const SizedBox(height: 12),
      Center(
        child: Text(
          _players.isEmpty ? 'SIN JUGADORES' : '${_players.length} ${_players.length == 1 ? 'jugador' : 'jugadores'}',
          style: TextStyle(color: _players.length >= 2 ? kAccent : Colors.white24, fontSize: 12, letterSpacing: 0.5, fontWeight: FontWeight.w600),
        ),
      ),
      const SizedBox(height: 12),
      if (_players.isNotEmpty)
        Wrap(
          spacing: 8,
          runSpacing: 8,
          children: [
            for (var i = 0; i < _players.length; i++)
              Chip(
                avatar: CircleAvatar(
                  radius: 12,
                  backgroundColor: kAccent.withValues(alpha: 0.12),
                  child: Text('${i + 1}', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: kAccent)),
                ),
                label: Text(_players[i], style: const TextStyle(color: Colors.white70)),
                backgroundColor: const Color(0x14FFFFFF),
                deleteIcon: const Icon(Icons.close, size: 16, color: Colors.white38),
                onDeleted: () => setState(() => _players.removeAt(i)),
              ),
          ],
        )
      else
        const _HintText('No hay jugadores aún. ¡Añade al menos 2!'),
    ];
  }

  List<Widget> _buildTeamsEditor() {
    return [
      Row(
        children: [
          Expanded(
            child: TextField(
              controller: _newTeam,
              style: const TextStyle(color: Colors.white),
              decoration: const InputDecoration(hintText: 'Nombre del equipo', hintStyle: TextStyle(color: kTextDim)),
              onSubmitted: (_) => _addTeam(),
            ),
          ),
          const SizedBox(width: 10),
          ElevatedButton(
            onPressed: _addTeam,
            style: ElevatedButton.styleFrom(padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 16)),
            child: const Text('+ AÑADIR EQUIPO', style: TextStyle(fontSize: 12)),
          ),
        ],
      ),
      const SizedBox(height: 12),
      Center(
        child: Text(
          _teams.isEmpty ? 'SIN EQUIPOS' : '${_teams.length} ${_teams.length == 1 ? 'equipo' : 'equipos'}',
          style: TextStyle(color: _teams.length >= 2 ? const Color(0xFFFFD700) : Colors.white54, fontSize: 12, letterSpacing: 0.5, fontWeight: FontWeight.w600),
        ),
      ),
      const SizedBox(height: 12),
      if (_teams.isNotEmpty)
        for (var ti = 0; ti < _teams.length; ti++) _buildTeamCard(ti)
      else
        const _HintText('No hay equipos aún. ¡Añade al menos 2!'),
      const SizedBox(height: 10),
      const _HintText('Los integrantes se registran pero la tabla y los partidos son por equipo.'),
    ];
  }

  Widget _buildTeamCard(int ti) {
    final team = _teams[ti];
    final memberCtrl = _newMember[ti] ?? TextEditingController();
    _newMember[ti] = memberCtrl;
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(color: const Color(0x14FFFFFF), borderRadius: BorderRadius.circular(12), border: Border.all(color: const Color(0x22FFFFFF))),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              CircleAvatar(
                radius: 12,
                backgroundColor: kAccent.withValues(alpha: 0.12),
                child: Text('${ti + 1}', style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: kAccent)),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: TextField(
                  controller: TextEditingController(text: team.name),
                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600),
                  decoration: const InputDecoration(hintText: 'Nombre del equipo', hintStyle: TextStyle(color: kTextDim)),
                  onChanged: (v) => setState(() => team.name = v),
                ),
              ),
              IconButton(
                onPressed: () => setState(() => _teams.removeAt(ti)),
                icon: const Icon(Icons.close, size: 18, color: Colors.white38),
              ),
            ],
          ),
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: memberCtrl,
                  style: const TextStyle(color: Colors.white),
                  decoration: const InputDecoration(hintText: 'Jugador del equipo', hintStyle: TextStyle(color: kTextDim)),
                  onSubmitted: (_) => _addMember(ti),
                ),
              ),
              const SizedBox(width: 8),
              ElevatedButton(
                onPressed: () => _addMember(ti),
                style: ElevatedButton.styleFrom(padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 16)),
                child: const Text('+', style: TextStyle(fontSize: 16)),
              ),
            ],
          ),
          if (team.players.isNotEmpty) ...[
            const SizedBox(height: 8),
            Wrap(
              spacing: 6,
              runSpacing: 6,
              children: [
                for (var mi = 0; mi < team.players.length; mi++)
                  InputChip(
                    label: Text(team.players[mi], style: const TextStyle(color: Colors.white70, fontSize: 12)),
                    backgroundColor: const Color(0x14FFFFFF),
                    deleteIcon: const Icon(Icons.close, size: 14, color: Colors.white38),
                    onDeleted: () => setState(() => team.players = List<String>.from(team.players)..removeAt(mi)),
                  ),
              ],
            ),
            const SizedBox(height: 6),
            Text('${team.players.length} integrantes', style: const TextStyle(color: Colors.white24, fontSize: 11)),
          ],
        ],
      ),
    );
  }

  Widget _buildEstimate() {
    final e = _estimate!;
    final text = _format == 'league'
        ? '${e['total']} partidos de liga${_homeAway ? ' (ida y vuelta)' : ''}'
        : '${e['total']} partidos (${e['group']} de grupos${_homeAway ? ' ida y vuelta' : ''} + ${e['knockout']} de eliminatorias)';
    final tvLabel = e['tv'] == 1 ? _tvSingular : _tvPlural;
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(border: Border.all(color: kAccent.withValues(alpha: 0.25)), color: kAccent.withValues(alpha: 0.06), borderRadius: BorderRadius.circular(16)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.schedule, color: kAccent, size: 20),
              const SizedBox(width: 8),
              const Text('DURACIÓN ESTIMADA', style: TextStyle(color: Colors.white, letterSpacing: 0.1, fontSize: 13, fontWeight: FontWeight.w700)),
              const Spacer(),
              Text('≈ ${_fmtDuration(e['minutes']!)}',
                  style: const TextStyle(color: kAccent, fontSize: 24, fontWeight: FontWeight.w900)),
            ],
          ),
          const SizedBox(height: 10),
          Text('$text · ${e['tv']} $tvLabel en paralelo · ${e['m']} min por partido.',
              style: const TextStyle(color: Colors.white38, fontSize: 12, height: 1.5)),
          const SizedBox(height: 4),
          const Text('Estimado aproximado, sin contar descansos entre partidos.',
              style: TextStyle(color: Colors.white24, fontSize: 11)),
        ],
      ),
    );
  }
}

// ---- Reusable widgets ----
class _StepIndicator extends StatelessWidget {
  final int current;
  final int total;
  final bool canFinish;
  const _StepIndicator({required this.current, required this.total, required this.canFinish});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16),
      child: Row(
        children: [
          for (var s = 1; s <= total; s++) ...[
            if (s > 1) Expanded(child: Container(height: 1, color: s <= current ? kAccent : const Color(0x22FFFFFF))),
            _StepDot(
              label: s,
              active: s == current,
              done: s < current || (s == total && canFinish),
            ),
          ],
        ],
      ),
    );
  }
}

class _StepDot extends StatelessWidget {
  final int label;
  final bool active;
  final bool done;
  const _StepDot({required this.label, required this.active, required this.done});

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 30, height: 30,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: active ? kAccent : (done ? kAccent : kSurfaceLow),
            border: Border.all(color: active || done ? kAccent : const Color(0x33FFFFFF)),
            shape: BoxShape.circle,
          ),
          child: done
              ? const Icon(Icons.check, color: Color(0xFF08080A), size: 16)
              : Text('$label', style: TextStyle(color: active ? const Color(0xFF08080A) : Colors.white38, fontWeight: FontWeight.w800)),
        ),
      ],
    );
  }
}

Widget _sectionLabel(String text, [String? hint]) => Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          text,
          style: const TextStyle(color: Color(0x66FFFFFF), fontSize: 11, letterSpacing: 0.15, fontWeight: FontWeight.w700),
        ),
        if (hint != null) ...[
          const SizedBox(height: 4),
          Text(hint, style: const TextStyle(color: Colors.white38, fontSize: 13)),
        ],
      ],
    );

class _HintText extends StatelessWidget {
  final String text;
  const _HintText(this.text);
  @override
  Widget build(BuildContext context) => Text(
        text,
        textAlign: TextAlign.center,
        style: const TextStyle(color: Colors.white24, fontSize: 13),
      );
}

class _SelectableCard extends StatelessWidget {
  final bool selected;
  final VoidCallback onTap;
  final Widget child;
  const _SelectableCard({required this.selected, required this.onTap, required this.child});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: selected ? kAccent.withValues(alpha: 0.12) : const Color(0x14FFFFFF),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: selected ? kAccent.withValues(alpha: 0.5) : const Color(0x14FFFFFF)),
        ),
        child: child,
      ),
    );
  }
}

class _ModeCard extends StatelessWidget {
  final String icon;
  final String label;
  final String desc;
  final bool selected;
  final VoidCallback onTap;
  const _ModeCard({required this.icon, required this.label, required this.desc, required this.selected, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 12),
        decoration: BoxDecoration(
          color: selected ? kAccent.withValues(alpha: 0.12) : const Color(0x14FFFFFF),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: selected ? kAccent.withValues(alpha: 0.5) : const Color(0x14FFFFFF)),
        ),
        child: Column(
          children: [
            Text(icon, style: const TextStyle(fontSize: 28)),
            const SizedBox(height: 6),
            Text(label, style: TextStyle(fontWeight: FontWeight.w700, color: selected ? kAccent : Colors.white70)),
            const SizedBox(height: 2),
            Text(desc, textAlign: TextAlign.center, style: const TextStyle(fontSize: 11, color: Colors.white38)),
          ],
        ),
      ),
    );
  }
}

class _FormatCard extends StatelessWidget {
  final String title;
  final String desc;
  final bool selected;
  final VoidCallback onTap;
  const _FormatCard({required this.title, required this.desc, required this.selected, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: selected ? kAccent.withValues(alpha: 0.12) : const Color(0x14FFFFFF),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: selected ? kAccent.withValues(alpha: 0.5) : const Color(0x14FFFFFF)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title, style: TextStyle(fontWeight: FontWeight.w700, color: selected ? kAccent : Colors.white70)),
            const SizedBox(height: 4),
            Text(desc, style: const TextStyle(fontSize: 12, color: Colors.white38)),
          ],
        ),
      ),
    );
  }
}

class _CheckRow extends StatelessWidget {
  final String title;
  final String desc;
  final bool value;
  final ValueChanged<bool> onChanged;
  const _CheckRow({required this.title, required this.desc, required this.value, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () => onChanged(!value),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(color: const Color(0x14FFFFFF), borderRadius: BorderRadius.circular(12), border: Border.all(color: const Color(0x14FFFFFF))),
        child: Row(
          children: [
            Checkbox(value: value, onChanged: (v) => onChanged(v ?? false), activeColor: kAccent),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title, style: const TextStyle(color: Colors.white70, fontSize: 14, fontWeight: FontWeight.w500)),
                  const SizedBox(height: 2),
                  Text(desc, style: const TextStyle(color: Colors.white38, fontSize: 11)),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _RuleRow extends StatelessWidget {
  final RuleDef def;
  final String value;
  final ValueChanged<String> onChanged;
  const _RuleRow({required this.def, required this.value, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(color: const Color(0x14FFFFFF), borderRadius: BorderRadius.circular(12), border: Border.all(color: const Color(0x14FFFFFF))),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(def.label, style: const TextStyle(color: Colors.white70, fontSize: 13)),
                if (def.note != null) ...[
                  const SizedBox(height: 2),
                  Text(def.note!, style: const TextStyle(color: Colors.white38, fontSize: 11)),
                ],
              ],
            ),
          ),
          const SizedBox(width: 12),
          switch (def.type) {
            'boolean' => _BooleanToggle(value: value, onChanged: onChanged),
            'number' => _NumberInput(value: value, def: def, onChanged: onChanged),
            _ => _SelectInput(value: value, def: def, onChanged: onChanged),
          },
        ],
      ),
    );
  }
}

class _BooleanToggle extends StatelessWidget {
  final String value;
  final ValueChanged<String> onChanged;
  const _BooleanToggle({required this.value, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    final on = value == '1';
    return GestureDetector(
      onTap: () => onChanged(on ? '0' : '1'),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 150),
        width: 44, height: 24,
        padding: const EdgeInsets.all(2),
        decoration: BoxDecoration(
          color: on ? kAccent.withValues(alpha: 0.3) : const Color(0x1AFFFFFF),
          borderRadius: BorderRadius.circular(999),
          border: Border.all(color: on ? kAccent.withValues(alpha: 0.6) : const Color(0x1AFFFFFF)),
        ),
        child: AnimatedAlign(
          duration: const Duration(milliseconds: 150),
          alignment: on ? Alignment.centerRight : Alignment.centerLeft,
          child: Container(width: 18, height: 18, decoration: BoxDecoration(color: on ? kAccent : Colors.white38, shape: BoxShape.circle)),
        ),
      ),
    );
  }
}

class _NumberInput extends StatelessWidget {
  final String value;
  final RuleDef def;
  final ValueChanged<String> onChanged;
  const _NumberInput({required this.value, required this.def, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 72,
      child: TextField(
        controller: TextEditingController(text: value),
        keyboardType: TextInputType.number,
        textAlign: TextAlign.center,
        style: const TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.w600),
        decoration: const InputDecoration(contentPadding: EdgeInsets.symmetric(horizontal: 8, vertical: 10), isDense: true),
        onChanged: (v) {
          final parsed = int.tryParse(v);
          if (parsed == null) {
            onChanged('');
            return;
          }
          final clamped = (def.min != null && parsed < def.min!) ? def.min! : (def.max != null && parsed > def.max!) ? def.max! : parsed;
          onChanged('$clamped');
        },
      ),
    );
  }
}

class _SelectInput extends StatelessWidget {
  final String value;
  final RuleDef def;
  final ValueChanged<String> onChanged;
  const _SelectInput({required this.value, required this.def, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    return DropdownButton<String>(
      value: def.options.contains(value) ? value : def.options.firstOrNull,
      dropdownColor: kSurface,
      style: const TextStyle(color: Colors.white, fontSize: 14),
      underline: const SizedBox.shrink(),
      icon: const Icon(Icons.arrow_drop_down, color: kAccent),
      items: def.options.map((opt) {
        return DropdownMenuItem(value: opt, child: Text(ruleOptLabels[opt] ?? opt));
      }).toList(),
      onChanged: (v) => onChanged(v ?? ''),
    );
  }
}
