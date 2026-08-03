import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../services/auth_service.dart';
import 'home_screen.dart';
import 'register_screen.dart';

const _bg = Color(0xFF08080A);
const _card = Color(0xFF0E0E11);
const _card2 = Color(0xFF131317);
const _accent = Color(0xFFFF5F00);
const _accentHover = Color(0xFFFF7A26);
const _tp = Color(0xFFF2F2F0);
const _tm = Color(0xFF8F8F8B);
const _tdd = Color(0xFF6D6D69);
const _hair = Color(0x1AFFFFFF);

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _email = TextEditingController();
  final _password = TextEditingController();
  bool _remember = false;
  bool _loading = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _restoreEmail();
  }

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _restoreEmail() async {
    final prefs = await SharedPreferences.getInstance();
    final saved = prefs.getString('remember_email');
    if (saved != null && mounted) {
      _email.text = saved;
      _remember = true;
    }
  }

  Future<void> _login() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      await AuthService().login(_email.text.trim(), _password.text);
      final prefs = await SharedPreferences.getInstance();
      if (_remember) {
        await prefs.setString('remember_email', _email.text.trim());
      } else {
        await prefs.remove('remember_email');
      }
      if (!mounted) return;
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => const HomeScreen()),
      );
    } catch (e) {
      if (mounted) setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _bg,
      body: Stack(
        children: [
          const Positioned(
            top: -160,
            left: 0,
            right: 0,
            height: 500,
            child: DecoratedBox(
              decoration: BoxDecoration(
                gradient: RadialGradient(
                  colors: [Color(0x29FF5F00), Color(0x00FF5F00)],
                ),
              ),
            ),
          ),
          SafeArea(
            child: Center(
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(20),
                child: ConstrainedBox(
                  constraints: const BoxConstraints(maxWidth: 420),
                  child: Container(
                    decoration: BoxDecoration(
                      color: _card,
                      border: Border.all(color: _hair),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Container(
                          height: 4,
                          decoration: const BoxDecoration(
                            gradient: LinearGradient(
                              colors: [_accent, Color(0x00FF5F00)],
                            ),
                          ),
                        ),
                        Padding(
                          padding: const EdgeInsets.fromLTRB(32, 28, 32, 28),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              Align(
                                alignment: Alignment.centerLeft,
                                child: Image.asset(
                                  'assets/logo-horizontal.png',
                                  height: 42,
                                ),
                              ),
                              const SizedBox(height: 24),
                              const Text(
                                'INICIÁ SESIÓN',
                                style: TextStyle(
                                  fontSize: 34,
                                  height: 0.95,
                                  fontWeight: FontWeight.w800,
                                  letterSpacing: -0.5,
                                  color: _tp,
                                ),
                              ),
                              const SizedBox(height: 8),
                              const Text(
                                'Entrá a tu cuenta y seguí el torneo.',
                                style: TextStyle(color: _tm, fontSize: 15),
                              ),
                              if (_error != null) ...[
                                const SizedBox(height: 16),
                                Container(
                                  padding:
                                      const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                                  decoration: BoxDecoration(
                                    color: const Color(0x1AFF5A5A),
                                    border: Border.all(color: const Color(0x4DFF5A5A)),
                                  ),
                                  child: Text(
                                    _error!,
                                    style: const TextStyle(color: Color(0xFFFF7A7A), fontSize: 13),
                                  ),
                                ),
                              ],
                              const SizedBox(height: 20),
                              const _FieldLabel('CORREO'),
                              const SizedBox(height: 6),
                              _AuthField(
                                controller: _email,
                                hint: 'vos@correo.com',
                                keyboardType: TextInputType.emailAddress,
                                autocorrect: false,
                              ),
                              const SizedBox(height: 16),
                              const _FieldLabel('CONTRASEÑA'),
                              const SizedBox(height: 6),
                              _AuthField(
                                controller: _password,
                                hint: '••••••••',
                                obscure: true,
                                onSubmitted: (_) => _login(),
                              ),
                              const SizedBox(height: 20),
                              Row(
                                children: [
                                  GestureDetector(
                                    onTap: () => setState(() => _remember = !_remember),
                                    child: Row(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        Container(
                                          width: 16,
                                          height: 16,
                                          decoration: BoxDecoration(
                                            border: Border.all(color: _accent, width: 1.5),
                                            color: _remember ? _accent : Colors.transparent,
                                          ),
                                          child: _remember
                                              ? const Icon(Icons.check, size: 12, color: _bg)
                                              : null,
                                        ),
                                        const SizedBox(width: 8),
                                        const Text(
                                          'Recordarme',
                                          style: TextStyle(color: Color(0xFFA8A8A3), fontSize: 14),
                                        ),
                                      ],
                                    ),
                                  ),
                                  const Spacer(),
                                  GestureDetector(
                                    onTap: () => ScaffoldMessenger.of(context).showSnackBar(
                                      const SnackBar(
                                        content: Text(
                                          'Recuperá tu contraseña desde www.fifardos.com',
                                        ),
                                      ),
                                    ),
                                    child: const Text(
                                      '¿Olvidaste tu contraseña?',
                                      style: TextStyle(color: Color(0xFFFF8A3D), fontSize: 13),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 20),
                              _AuthButton(
                                label: _loading ? 'ENTRANDO…' : 'INGRESAR',
                                loading: _loading,
                                onTap: _loading ? null : _login,
                              ),
                              const SizedBox(height: 22),
                              GestureDetector(
                                onTap: () {
                                  Navigator.of(context).push(
                                    MaterialPageRoute(
                                      builder: (_) => const RegisterScreen(),
                                    ),
                                  );
                                },
                                child: const Row(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: [
                                    Text('¿No tenés cuenta? ',
                                        style: TextStyle(color: _tm, fontSize: 14)),
                                    Text(
                                      'Registrate',
                                      style: TextStyle(
                                        color: Color(0xFFFF8A3D),
                                        fontSize: 14,
                                        fontWeight: FontWeight.w600,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _FieldLabel extends StatelessWidget {
  final String text;
  const _FieldLabel(this.text);

  @override
  Widget build(BuildContext context) {
    return Text(
      text,
      style: const TextStyle(
        color: _tm,
        fontSize: 13,
        fontWeight: FontWeight.w600,
        letterSpacing: 1.5,
      ),
    );
  }
}

class _AuthField extends StatelessWidget {
  final TextEditingController controller;
  final String hint;
  final bool obscure;
  final TextInputType? keyboardType;
  final bool autocorrect;
  final ValueChanged<String>? onSubmitted;

  const _AuthField({
    required this.controller,
    required this.hint,
    this.obscure = false,
    this.keyboardType,
    this.autocorrect = true,
    this.onSubmitted,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      color: _card2,
      child: TextField(
        controller: controller,
        obscureText: obscure,
        keyboardType: keyboardType,
        autocorrect: autocorrect,
        onSubmitted: onSubmitted,
        style: const TextStyle(color: _tp, fontSize: 15),
        cursorColor: _accent,
        decoration: InputDecoration(
          hintText: hint,
          hintStyle: const TextStyle(color: _tdd),
          contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
          enabledBorder: OutlineInputBorder(
            borderSide: BorderSide(color: _hair),
            borderRadius: BorderRadius.zero,
          ),
          focusedBorder: OutlineInputBorder(
            borderSide: BorderSide(color: _accent, width: 1.2),
            borderRadius: BorderRadius.zero,
          ),
          border: InputBorder.none,
        ),
      ),
    );
  }
}

class _AuthButton extends StatelessWidget {
  final String label;
  final bool loading;
  final VoidCallback? onTap;

  const _AuthButton({required this.label, required this.loading, this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: ClipPath(
        clipper: _CutCornerClipper(14),
        child: Container(
          color: loading ? _accentHover : _accent,
          padding: const EdgeInsets.symmetric(vertical: 15),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              if (loading)
                const SizedBox(
                  width: 18,
                  height: 18,
                  child: CircularProgressIndicator(strokeWidth: 2.5, color: _bg),
                )
              else
                const SizedBox.shrink(),
              if (loading) const SizedBox(width: 9),
              Text(
                label,
                style: const TextStyle(
                  color: _bg,
                  fontSize: 19,
                  fontWeight: FontWeight.w700,
                  letterSpacing: 1.5,
                ),
              ),
              if (!loading) ...[
                const SizedBox(width: 9),
                const Text('→', style: TextStyle(color: _bg, fontSize: 19)),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _CutCornerClipper extends CustomClipper<Path> {
  final double cut;
  const _CutCornerClipper(this.cut);

  @override
  Path getClip(Size size) {
    final path = Path()
      ..moveTo(cut, 0)
      ..lineTo(size.width, 0)
      ..lineTo(size.width, size.height - cut)
      ..lineTo(size.width - cut, size.height)
      ..lineTo(0, size.height)
      ..lineTo(0, cut)
      ..close();
    return path;
  }

  @override
  bool shouldReclip(_CutCornerClipper oldClipper) => oldClipper.cut != cut;
}
