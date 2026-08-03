import 'package:flutter/material.dart';

import 'login_screen.dart';
import 'register_screen.dart';

// ---- Paleta de la landing (idéntica a Landing.vue) ----
const _bgBase = Color(0xFF08080A);
const _bgAlt = Color(0xFF0B0B0D);
const _bgCard = Color(0xFF0E0E11);
const _bgCard2 = Color(0xFF131317);
const _accent = Color(0xFFFF5F00);
const _accentSoft = Color(0xFFFF8A3D);
const _lime = Color(0xFFB6FF2E);
const _tp = Color(0xFFF2F2F0);
const _ts = Color(0xFFA8A8A3);
const _tm = Color(0xFF8F8F8B);
const _td = Color(0xFF7A7A76);
const _tdd = Color(0xFF6D6D69);
const _hair = Color(0x14FFFFFF);
const _bcard = Color(0x1AFFFFFF);

// ---- Contenido (textos ES de la landing) ----
const _sports = [
  ('🎮', 'FIFA / Consola'),
  ('⚽', 'Fútbol'),
  ('🥅', 'Futsal'),
  ('🏀', 'Básquet'),
  ('🏐', 'Vóley'),
  ('🎾', 'Tenis'),
  ('🏓', 'Pádel'),
  ('🏸', 'Pickleball'),
  ('🤾', 'Handball'),
  ('🏉', 'Rugby'),
];

const _steps = [
  ('Cargá a los participantes', 'Escribí quién juega: jugadores o equipos, dos personas o treinta y dos.'),
  ('Elegí el deporte y el formato', 'Cualquier deporte, en consola o cancha. Liga, grupos + eliminatorias o mata-mata directo.'),
  ('Anotá el marcador', 'Termina el partido, cargás el resultado (goles, puntos o sets) y la tabla se acomoda sola.'),
  ('Coroná al campeón', 'Bracket, líderes de cada deporte y palmarés listos para el chat del grupo.'),
];

const _features = [
  ('Fixture', 'Grupos armados en segundos', 'Sorteo balanceado, fechas ordenadas y nadie repite rival dos veces seguidas.'),
  ('Live', 'Tabla que se actualiza sola', 'Puntos, diferencia y desempates calculados al instante según el deporte, sin planillas.'),
  ('Playoffs', 'Eliminatorias automáticas', 'Los clasificados se cruzan solos: cuartos, semis y final sin dibujar nada.'),
  ('Stats', 'Líderes y récords', 'Máximo goleador, anotador o líder en sets, según el deporte de cada torneo.'),
  ('Share', 'Un link para todo el grupo', 'Mandás el link y todos ven la tabla desde el celular; ellos no necesitan cuenta.'),
  ('Mobile', 'Se carga desde el sillón', 'Pensado para usarlo con una mano mientras el otro elige equipo.'),
];

const _quotes = [
  ('Se terminaron las discusiones de quién iba puntero. Está escrito.', 'Torneo del barrio · 12 jugadores'),
  ('Lo armé en el entretiempo y ya estábamos jugando la fecha 1.', 'Liga de oficina · 8 jugadores'),
  ('El bracket salió solo y quedó mejor que el del Mundial.', 'Copa de vacaciones · 16 jugadores'),
];

const _faqs = [
  ('¿Hay que registrarse?', 'Solo el organizador: creás tu cuenta gratis y administrás el torneo. Los demás solo abren el link que compartís, sin registrarse.'),
  ('¿Sirve solo para FIFA?', 'No: funciona para cualquier deporte, individual o por equipos (fútbol, básquet, vóley, tenis, pádel, ping pong, handball, rugby…), en consola o en cancha.'),
  ('¿Cuántos participantes entran?', 'De 2 a 32 (o más con equipos), con o sin fase de grupos, partidos de ida y vuelta opcionales.'),
  ('¿Es gratis de verdad?', 'Sí. Sin límites de torneos ni funciones bloqueadas.'),
];

const _tableRows = [
  (1, 'Nico', 'NI', 'Real Madrid', 4, '+7', 10),
  (2, 'Maru', 'MA', 'Inter', 4, '+4', 9),
  (3, 'El Chino', 'CH', 'Liverpool', 4, '+1', 6),
  (4, 'Juanma', 'JU', 'Boca', 4, '-3', 3),
  (5, 'Tincho', 'TI', 'Newcastle', 4, '-9', 1),
];

const _bracket = {
  'quarters': [('Nico', 3), ('Juanma', 1), ('Maru', 2), ('El Chino', 0)],
  'semis': [('Nico', 2), ('Maru', 1)],
  'champion': 'Nico',
};

// Tipografías aproximadas (Anton → w900 uppercase; Barlow → w700)
TextStyle _display(double size, {Color color = _tp, double ls = -1}) =>
    TextStyle(fontSize: size, fontWeight: FontWeight.w900, letterSpacing: ls, height: 0.95, color: color);

TextStyle _barlow(double size, {Color color = _tp, double ls = 0.04, double? height}) =>
    TextStyle(fontSize: size, fontWeight: FontWeight.w700, letterSpacing: ls, height: height, color: color);

class LandingScreen extends StatelessWidget {
  const LandingScreen({super.key});

  void _goLogin(BuildContext context) {
    Navigator.of(context).push(MaterialPageRoute(builder: (_) => const LoginScreen()));
  }

  void _goRegister(BuildContext context) {
    Navigator.of(context).push(MaterialPageRoute(builder: (_) => const RegisterScreen()));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _bgBase,
      body: SafeArea(
        bottom: false,
        child: ListView(
          padding: EdgeInsets.zero,
          children: [
            const _Header(),
            _Hero(onLogin: () => _goLogin(context), onRegister: () => _goRegister(context)),
            const _SportsSection(),
            const _HowSection(),
            const _FeaturesSection(),
            _BracketSection(onRegister: () => _goRegister(context)),
            const _QuotesSection(),
            const _FaqSection(),
            _CtaSection(onRegister: () => _goRegister(context)),
            const _Footer(),
          ],
        ),
      ),
    );
  }
}

// ---- Header ----
class _Header extends StatelessWidget {
  const _Header();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
      decoration: const BoxDecoration(
        color: Color(0xD108080A),
        border: Border(bottom: BorderSide(color: _hair)),
      ),
      child: Row(
        children: [
          _BarlowText('FIFARDOS', 20, color: Colors.white, ls: 0.14),
          const Spacer(),
        ],
      ),
    );
  }
}

// ---- Botones con esquina cortada (clip-path polygon) ----
class _CutCornerClipper extends CustomClipper<Path> {
  final double cut;
  const _CutCornerClipper(this.cut);
  @override
  Path getClip(Size size) {
    return Path()
      ..moveTo(0, 0)
      ..lineTo(size.width, 0)
      ..lineTo(size.width, size.height - cut)
      ..lineTo(size.width - cut, size.height)
      ..lineTo(0, size.height)
      ..close();
  }

  @override
  bool shouldReclip(_CutCornerClipper oldClipper) => oldClipper.cut != cut;
}

class _SolidButton extends StatelessWidget {
  final String label;
  final double cut;
  final VoidCallback onTap;
  final TextStyle? textStyle;
  final Widget? trailing;

  const _SolidButton({
    required this.label,
    required this.cut,
    required this.onTap,
    this.textStyle,
    this.trailing,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: ClipPath(
        clipper: _CutCornerClipper(cut),
        child: Container(
          color: _accent,
          padding: const EdgeInsets.symmetric(horizontal: 26, vertical: 15),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(label, style: textStyle ?? _barlow(18, color: _bgBase, ls: 0.1)),
              if (trailing != null) ...[
                const SizedBox(width: 9),
                trailing!,
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _OutlineButton extends StatelessWidget {
  final String label;
  final double cut;
  final VoidCallback onTap;

  const _OutlineButton({required this.label, required this.cut, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: ClipPath(
        clipper: _CutCornerClipper(cut),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 26, vertical: 15),
          decoration: BoxDecoration(
            border: Border.all(color: const Color(0x2EFFFFFF)),
            borderRadius: BorderRadius.circular(0),
          ),
          child: Text(label, style: _barlow(18, color: _tp, ls: 0.1)),
        ),
      ),
    );
  }
}

class _BarlowText extends StatelessWidget {
  final String text;
  final double size;
  final Color color;
  final double ls;
  const _BarlowText(this.text, this.size, {this.color = _tp, this.ls = 0.04});
  @override
  Widget build(BuildContext context) => Text(text, style: _barlow(size, color: color, ls: ls));
}

// ---- HERO ----
class _Hero extends StatelessWidget {
  final VoidCallback onLogin;
  final VoidCallback onRegister;
  const _Hero({required this.onLogin, required this.onRegister});

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [Color(0xFF0D0D10), _bgBase],
        ),
      ),
      padding: const EdgeInsets.fromLTRB(20, 40, 20, 36),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Badge
          Align(
            alignment: Alignment.centerLeft,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 13, vertical: 7),
              decoration: BoxDecoration(
                border: Border.all(color: const Color(0x73FF5F00)),
                borderRadius: BorderRadius.circular(2),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const _PulseDot(_accent),
                  const SizedBox(width: 8),
                  Text('GRATIS · SIN INSTALAR NADA',
                      style: TextStyle(fontSize: 12, letterSpacing: 0.18, fontWeight: FontWeight.w700, color: _accentSoft)),
                ],
              ),
            ),
          ),
          const SizedBox(height: 26),
          Text('Se armó\n', style: _display(46)),
          const Text.rich(
            TextSpan(
              children: [
                TextSpan(text: 'el torneo', style: TextStyle(fontSize: 46, fontWeight: FontWeight.w900, letterSpacing: -1.5, height: 0.95, color: _accent)),
                TextSpan(text: '\nde la casa.', style: TextStyle(fontSize: 46, fontWeight: FontWeight.w900, letterSpacing: -1.5, height: 0.95, color: _tp)),
              ],
            ),
          ),
          const SizedBox(height: 22),
          const Text(
            'Fútbol, básquet, vóley, tenis, ping pong y más. Grupos, resultados, tabla en vivo y eliminatorias automáticas para cualquier deporte, en consola o en cancha. Vos jugás, nosotros llevamos las cuentas.',
            style: TextStyle(color: _ts, fontSize: 16, height: 1.55),
          ),
          const SizedBox(height: 30),
          Wrap(
            spacing: 14,
            runSpacing: 14,
            children: [
              _SolidButton(
                label: 'ARMAR MI TORNEO',
                cut: 14,
                onTap: onRegister,
                trailing: const Text('→', style: TextStyle(color: _bgBase, fontWeight: FontWeight.w900)),
              ),
              _OutlineButton(label: 'INICIAR SESIÓN', cut: 14, onTap: onLogin),
            ],
          ),
          const SizedBox(height: 38),
          const Divider(color: _hair, height: 1),
          const SizedBox(height: 26),
          const Row(
            children: [
              Expanded(child: _Stat('40 s', 'en armar el fixture')),
              Expanded(child: _Stat('2–32', 'jugadores por torneo')),
              Expanded(child: _Stat('\$0', 'para siempre')),
            ],
          ),
          const SizedBox(height: 34),
          const _LiveCard(),
        ],
      ),
    );
  }
}

class _PulseDot extends StatefulWidget {
  final Color color;
  const _PulseDot(this.color);
  @override
  State<_PulseDot> createState() => _PulseDotState();
}

class _PulseDotState extends State<_PulseDot> with SingleTickerProviderStateMixin {
  late final AnimationController _c = AnimationController(vsync: this, duration: const Duration(milliseconds: 1600))..repeat();
  @override
  void dispose() {
    _c.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return FadeTransition(
      opacity: Tween(begin: 1.0, end: 0.25).animate(CurvedAnimation(parent: _c, curve: Curves.easeInOut)),
      child: Container(width: 7, height: 7, decoration: BoxDecoration(color: widget.color, shape: BoxShape.circle)),
    );
  }
}

class _Stat extends StatelessWidget {
  final String num;
  final String label;
  const _Stat(this.num, this.label);
  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(num, style: _display(28, color: _accent)),
        const SizedBox(height: 5),
        Text(label.toUpperCase(),
            style: const TextStyle(fontSize: 11, letterSpacing: 0.12, color: _td, fontWeight: FontWeight.w600)),
      ],
    );
  }
}

// ---- Card tabla en vivo ----
class _LiveCard extends StatelessWidget {
  const _LiveCard();
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: _bgCard,
        border: Border.all(color: _bcard),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.6), blurRadius: 40, offset: const Offset(0, 30))],
      ),
      child: Column(
        children: [
          Row(
            children: [
              const _PulseDot(_lime),
              const SizedBox(width: 8),
              const Text('TABLA EN VIVO', style: TextStyle(color: _lime, fontSize: 11, letterSpacing: 0.2, fontWeight: FontWeight.w700)),
              const Spacer(),
              Text('GRUPO A · J4', style: TextStyle(color: _tdd, fontSize: 12)),
            ],
          ),
          const SizedBox(height: 14),
          Row(
            children: [
              _th('', 26), _th('JUGADOR', 0), const Spacer(), _th('PJ', 34), _th('DG', 34), _th('PTS', 40, right: true),
            ],
          ),
          const SizedBox(height: 4),
          ..._tableRows.map((r) => _LiveRow(pos: r.$1, name: r.$2, ini: r.$3, team: r.$4, pj: r.$5, dg: r.$6, pts: r.$7)),
          const SizedBox(height: 12),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 13),
            decoration: const BoxDecoration(
              color: Color(0x17FF5F00),
              border: Border(left: BorderSide(color: _accent, width: 3)),
            ),
            child: const Row(
              children: [
                Text('LÍDER', style: TextStyle(fontSize: 13, letterSpacing: 0.14, color: _accentSoft, fontWeight: FontWeight.w900)),
                Spacer(),
                Text('Nico — 11 goles', style: TextStyle(fontWeight: FontWeight.w700, color: _tp)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

Widget _th(String label, double width, {bool right = false}) => SizedBox(
      width: width == 0 ? null : width,
      child: Text(label.toUpperCase(),
          textAlign: right ? TextAlign.right : TextAlign.center,
          style: const TextStyle(fontSize: 11, letterSpacing: 0.14, color: _tdd)),
    );

class _LiveRow extends StatelessWidget {
  final int pos;
  final String name;
  final String ini;
  final String team;
  final int pj;
  final String dg;
  final int pts;
  const _LiveRow({required this.pos, required this.name, required this.ini, required this.team, required this.pj, required this.dg, required this.pts});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 11),
      decoration: const BoxDecoration(border: Border(top: BorderSide(color: Color(0x0FFFFFFF)))),
      child: Row(
        children: [
          SizedBox(width: 26, child: Text('$pos', style: _display(16, color: _accent))),
          Expanded(
            child: Row(
              children: [
                Container(
                  width: 24, height: 24,
                  alignment: Alignment.center,
                  color: const Color(0x12FFFFFF),
                  child: Text(ini, style: const TextStyle(fontSize: 11, color: Color(0xFFCFCFCA), fontWeight: FontWeight.w900)),
                ),
                const SizedBox(width: 9),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(name, style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600, overflow: TextOverflow.ellipsis)),
                      Text(team.toUpperCase(), style: const TextStyle(fontSize: 11, letterSpacing: 0.1, color: _tdd)),
                    ],
                  ),
                ),
              ],
            ),
          ),
          SizedBox(width: 34, child: Text('$pj', textAlign: TextAlign.center, style: const TextStyle(color: Color(0xFF9A9A96), fontSize: 14))),
          SizedBox(width: 34, child: Text(dg, textAlign: TextAlign.center, style: const TextStyle(color: Color(0xFF9A9A96), fontSize: 14))),
          SizedBox(width: 40, child: Text('$pts', textAlign: TextAlign.right, style: _display(17, color: _tp))),
        ],
      ),
    );
  }
}

// ---- Secciones ----
class _SectionHead extends StatelessWidget {
  final String h1;
  final String h2Accent;
  final String? note;
  const _SectionHead({required this.h1, required this.h2Accent, this.note});
  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text.rich(TextSpan(
          children: [
            TextSpan(text: '$h1\n', style: _display(34)),
            TextSpan(text: h2Accent, style: _display(34, color: _accent)),
          ],
        )),
        if (note != null) ...[
          const SizedBox(height: 14),
          Text(note!, style: const TextStyle(color: _tm, fontSize: 15, height: 1.5)),
        ],
        const SizedBox(height: 30),
      ],
    );
  }
}

class _SportsSection extends StatelessWidget {
  const _SportsSection();
  @override
  Widget build(BuildContext context) {
    return Container(
      color: _bgAlt,
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 60),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const _SectionHead(h1: 'Cualquier deporte,', h2Accent: 'una sola plataforma.',
              note: 'De deportes individuales a los de equipos, en consola o en campo físico. Marcadores por goles, puntos o sets, con reglas configurables por torneo.'),
          Wrap(
            spacing: 10,
            runSpacing: 10,
            children: _sports.map((s) => _SportChip(icon: s.$1, name: s.$2)).toList(),
          ),
        ],
      ),
    );
  }
}

class _SportChip extends StatelessWidget {
  final String icon;
  final String name;
  const _SportChip({required this.icon, required this.name});
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(color: _bgCard, border: Border.all(color: _bcard), borderRadius: BorderRadius.circular(999)),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(icon, style: const TextStyle(fontSize: 20)),
          const SizedBox(width: 10),
          Text(name.toUpperCase(), style: _barlow(13, ls: 0.04)),
        ],
      ),
    );
  }
}

class _HowSection extends StatelessWidget {
  const _HowSection();
  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 60),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const _SectionHead(h1: 'Cuatro pasos', h2Accent: 'y a jugar',
              note: 'Una cuenta gratis para organizar cualquier deporte, sin app ni planilla de Excel del primo que se ofende.'),
          ..._steps.mapIndexed((i, s) => _StepCard(n: '0${i + 1}', t: s.$1, d: s.$2)),
        ],
      ),
    );
  }
}

class _StepCard extends StatelessWidget {
  final String n;
  final String t;
  final String d;
  const _StepCard({required this.n, required this.t, required this.d});
  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(color: _bgCard, border: Border.all(color: _bcard)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(n, style: _display(42, color: const Color(0x21FFFFFF))),
          const SizedBox(height: 12),
          _BarlowText(t.toUpperCase(), 22),
          const SizedBox(height: 8),
          Text(d, style: const TextStyle(color: _tm, fontSize: 15, height: 1.5)),
        ],
      ),
    );
  }
}

class _FeaturesSection extends StatelessWidget {
  const _FeaturesSection();
  @override
  Widget build(BuildContext context) {
    return Container(
      color: _bgAlt,
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 60),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const _SectionHead(h1: 'Todo lo que', h2Accent: 'el grupo pide'),
          ..._features.map((f) => _FeatureCard(tag: f.$1, t: f.$2, d: f.$3)),
        ],
      ),
    );
  }
}

class _FeatureCard extends StatelessWidget {
  final String tag;
  final String t;
  final String d;
  const _FeatureCard({required this.tag, required this.t, required this.d});
  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 14),
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(color: _bgBase, border: Border.all(color: _bcard)),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(tag.toUpperCase(), style: TextStyle(fontSize: 12, letterSpacing: 0.2, color: _accent, fontWeight: FontWeight.w900)),
          const SizedBox(height: 14),
          _BarlowText(t.toUpperCase(), 25),
          const SizedBox(height: 8),
          Text(d, style: const TextStyle(color: _tm, fontSize: 15, height: 1.5)),
        ],
      ),
    );
  }
}

// ---- Bracket ----
class _BracketSection extends StatelessWidget {
  final VoidCallback onRegister;
  const _BracketSection({required this.onRegister});
  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 60),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('ELIMINATORIAS', style: TextStyle(fontSize: 12, letterSpacing: 0.2, color: _lime, fontWeight: FontWeight.w900)),
          const SizedBox(height: 12),
          Text.rich(TextSpan(
            children: [
              TextSpan(text: 'El bracket se\n', style: _display(34)),
              TextSpan(text: 'arma solo.', style: _display(34, color: _accent)),
            ],
          )),
          const SizedBox(height: 16),
          const Text('Cuando termina la fase de grupos, FIFARDOS cruza los clasificados y genera las llaves. Cargás el resultado y el ganador avanza al toque.',
              style: TextStyle(color: _tm, fontSize: 16, height: 1.55)),
          const SizedBox(height: 22),
          GestureDetector(
            onTap: onRegister,
            child: const Text('PROBARLO AHORA', style: TextStyle(fontSize: 18, letterSpacing: 0.1, fontWeight: FontWeight.w700, color: _tp)),
          ),
          const SizedBox(height: 10),
          Container(
            height: 3, width: 80, color: _accent,
          ),
          const SizedBox(height: 28),
          const _BracketCard(),
        ],
      ),
    );
  }
}

class _BracketCard extends StatelessWidget {
  const _BracketCard();
  @override
  Widget build(BuildContext context) {
    final quarters = _bracket['quarters']! as List<(String, int)>;
    final semis = _bracket['semis']! as List<(String, int)>;
    final champion = _bracket['champion']! as String;
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(color: _bgCard, border: Border.all(color: _bcard)),
      child: SingleChildScrollView(
        scrollDirection: Axis.horizontal,
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            Column(
              children: quarters.map((m) => Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: _BracketMatch(name: m.$1, score: m.$2),
                  )).toList(),
            ),
            const SizedBox(width: 18),
            Column(
              children: semis.map((m) => Padding(
                    padding: const EdgeInsets.only(bottom: 34),
                    child: _BracketMatch(name: m.$1, score: m.$2, semi: true),
                  )).toList(),
            ),
            const SizedBox(width: 18),
            Container(
              width: 150,
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                border: Border.all(color: _accent),
                color: const Color(0x1AFF5F00),
              ),
              child: Column(
                children: [
                  const Text('CAMPEÓN', style: TextStyle(fontSize: 10, letterSpacing: 0.2, color: _accentSoft, fontWeight: FontWeight.w700)),
                  const SizedBox(height: 6),
                  Text(champion.toUpperCase(), style: _display(20)),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _BracketMatch extends StatelessWidget {
  final String name;
  final int score;
  final bool semi;
  const _BracketMatch({required this.name, required this.score, this.semi = false});
  @override
  Widget build(BuildContext context) {
    return Container(
      width: 150,
      padding: const EdgeInsets.all(11),
      decoration: BoxDecoration(
        color: _bgCard2,
        border: Border.all(color: semi ? const Color(0x66FF5F00) : _bcard),
      ),
      child: Row(
        children: [
          Expanded(child: Text(name, style: TextStyle(fontSize: 14, fontWeight: semi ? FontWeight.w700 : FontWeight.w600), overflow: TextOverflow.ellipsis)),
          Text('$score', style: _display(15, color: _accent)),
        ],
      ),
    );
  }
}

// ---- Quotes ----
class _QuotesSection extends StatelessWidget {
  const _QuotesSection();
  @override
  Widget build(BuildContext context) {
    return Container(
      color: _bgAlt,
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 60),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: _quotes.map((q) => Container(
              padding: const EdgeInsets.only(left: 18, top: 20, bottom: 20, right: 4),
              decoration: const BoxDecoration(border: Border(left: BorderSide(color: _accent, width: 2))),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('“${q.$1}”', style: const TextStyle(fontSize: 17, height: 1.5, fontWeight: FontWeight.w600)),
                  const SizedBox(height: 12),
                  Text(q.$2.toUpperCase(), style: const TextStyle(fontSize: 11, letterSpacing: 0.16, color: _td, fontWeight: FontWeight.w600)),
                ],
              ),
            )).toList(),
      ),
    );
  }
}

// ---- FAQ ----
class _FaqSection extends StatelessWidget {
  const _FaqSection();
  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 60),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const _SectionHead(h1: 'Preguntas', h2Accent: 'rápidas'),
          ..._faqs.map((f) => Container(
                padding: const EdgeInsets.symmetric(vertical: 18),
                decoration: const BoxDecoration(border: Border(top: BorderSide(color: _bcard))),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _BarlowText(f.$1.toUpperCase(), 20),
                    const SizedBox(height: 8),
                    Text(f.$2, style: const TextStyle(color: _tm, fontSize: 15, height: 1.55)),
                  ],
                ),
              )),
          const Divider(color: _bcard, height: 1),
        ],
      ),
    );
  }
}

// ---- CTA ----
class _CtaSection extends StatelessWidget {
  final VoidCallback onRegister;
  const _CtaSection({required this.onRegister});
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 80),
      decoration: BoxDecoration(
        gradient: RadialGradient(
          radius: 1.2,
          colors: [const Color(0x38FF5F00), _bgBase],
          stops: const [0.0, 0.7],
        ),
      ),
      child: Column(
        children: [
          Text.rich(TextSpan(
            children: [
              TextSpan(text: 'Dejá de discutir.\n', style: _display(38)),
              TextSpan(text: 'Jugá el torneo.', style: _display(38, color: _accent)),
            ],
          ), textAlign: TextAlign.center),
          const SizedBox(height: 20),
          const Text('Creá el torneo de tu deporte, mandá el link al grupo y que gane el mejor (o el que agarre al Madrid).',
              textAlign: TextAlign.center, style: TextStyle(color: _ts, fontSize: 16, height: 1.55)),
          const SizedBox(height: 30),
          _SolidButton(label: 'CREAR TORNEO GRATIS', cut: 16, onTap: onRegister, textStyle: _barlow(20, color: _bgBase, ls: 0.1)),
          const SizedBox(height: 18),
          const Text('CUENTA GRATIS · SIN DESCARGAS · EN CONSOLA O EN CANCHA',
              textAlign: TextAlign.center, style: TextStyle(fontSize: 11, letterSpacing: 0.16, color: _tdd, fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }
}

// ---- Footer ----
class _Footer extends StatelessWidget {
  const _Footer();
  @override
  Widget build(BuildContext context) {
    return Container(
      color: _bgAlt,
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 26),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _BarlowText('FIFARDOS', 16, color: Colors.white, ls: 0.14),
          const SizedBox(height: 10),
          const Text('Hecho por fanáticos, no por EA. FIFA y EA Sports FC son marcas de sus dueños.',
              style: TextStyle(color: _tdd, fontSize: 13)),
          const SizedBox(height: 16),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
            decoration: BoxDecoration(border: Border.all(color: const Color(0x59FF5F00))),
            child: const Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                _PulseDot(_accent),
                SizedBox(width: 7),
                Text('INTEGRACIÓN MCP', style: TextStyle(fontSize: 11, letterSpacing: 0.14, color: _accentSoft, fontWeight: FontWeight.w600)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

extension _IndexedList<T> on List<T> {
  List<R> mapIndexed<R>(R Function(int, T) f) => [for (var i = 0; i < length; i++) f(i, this[i])];
}
