import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:fifardos_mobile/main.dart';

void main() {
  testWidgets('App renders landing when not logged in', (tester) async {
    await tester.pumpWidget(const FifardosApp(loggedIn: false));

    expect(find.byType(Image), findsWidgets);
    expect(find.text('ARMAR MI TORNEO'), findsOneWidget);
    expect(find.text('INICIAR SESIÓN'), findsOneWidget);
  });
}
