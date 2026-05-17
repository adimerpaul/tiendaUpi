import 'package:flutter_test/flutter_test.dart';
import 'package:mobil/main.dart';

void main() {
  testWidgets('Carga pantalla de productos', (WidgetTester tester) async {
    await tester.pumpWidget(const ProductApp());
    expect(find.text('CRUD Productos (MVVM)'), findsOneWidget);
  });
}
