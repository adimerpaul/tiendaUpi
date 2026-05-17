import 'dart:convert';

import 'package:http/http.dart' as http;

import '../models/product.dart';

class ProductApiService {
  static const String _baseUrl =
      'https://6309-2800-cd0-af7c-2e00-6424-fe15-3920-65e8.ngrok-free.app/tienda/api/productos';

  Future<List<Product>> fetchProducts() async {
    final response = await http.get(Uri.parse(_baseUrl));
    final body = _decodeBody(response.body);

    if (response.statusCode != 200) {
      throw Exception(_extractError(body, 'No se pudieron cargar productos'));
    }

    if (body is! List) {
      return [];
    }

    return body
        .whereType<Map<String, dynamic>>()
        .map(Product.fromJson)
        .toList();
  }

  Future<Product> createProduct(Product product) async {
    final response = await http.post(
      Uri.parse(_baseUrl),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode(product.toJsonWithoutId()),
    );
    final body = _decodeBody(response.body);

    if (response.statusCode != 201) {
      throw Exception(_extractError(body, 'No se pudo crear el producto'));
    }

    return Product.fromJson(body as Map<String, dynamic>);
  }

  Future<Product> updateProduct(int id, Product product) async {
    final response = await http.put(
      Uri.parse('$_baseUrl/$id'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode(product.toJsonWithoutId()),
    );
    final body = _decodeBody(response.body);

    if (response.statusCode != 200) {
      throw Exception(_extractError(body, 'No se pudo actualizar el producto'));
    }

    return Product.fromJson(body as Map<String, dynamic>);
  }

  Future<void> deleteProduct(int id) async {
    final response = await http.delete(Uri.parse('$_baseUrl/$id'));
    final body = _decodeBody(response.body);

    if (response.statusCode != 200) {
      throw Exception(_extractError(body, 'No se pudo eliminar el producto'));
    }
  }

  dynamic _decodeBody(String body) {
    try {
      return jsonDecode(body);
    } catch (_) {
      return null;
    }
  }

  String _extractError(dynamic body, String fallback) {
    if (body is Map<String, dynamic> && body['error'] != null) {
      return body['error'].toString();
    }
    return fallback;
  }
}
