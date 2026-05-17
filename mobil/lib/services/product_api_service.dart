import 'dart:convert';

import 'package:http/http.dart' as http;

import '../models/product.dart';

class ProductApiService {
  static const String _siteBaseUrl =
      'https://6309-2800-cd0-af7c-2e00-6424-fe15-3920-65e8.ngrok-free.app/tienda';
  static const String _baseUrl = '$_siteBaseUrl/api/productos';

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
        .map(_parseProduct)
        .toList();
  }

  Future<Product> createProduct(Product product, {String? imagePath}) async {
    final request = http.MultipartRequest('POST', Uri.parse(_baseUrl))
      ..fields['nombre'] = product.nombre
      ..fields['precio'] = product.precio.toString()
      ..fields['cantidad'] = product.cantidad.toString()
      ..fields['marca'] = product.marca;

    if (imagePath != null && imagePath.isNotEmpty) {
      request.files.add(await http.MultipartFile.fromPath('imagen', imagePath));
    }

    final streamedResponse = await request.send();
    final response = await http.Response.fromStream(streamedResponse);
    final body = _decodeBody(response.body);

    if (response.statusCode != 201) {
      throw Exception(_extractError(body, 'No se pudo crear el producto'));
    }

    return _parseProduct(body as Map<String, dynamic>);
  }

  Future<Product> updateProduct(int id, Product product, {String? imagePath}) async {
    final request = http.MultipartRequest('POST', Uri.parse('$_baseUrl/$id'))
      ..fields['nombre'] = product.nombre
      ..fields['precio'] = product.precio.toString()
      ..fields['cantidad'] = product.cantidad.toString()
      ..fields['marca'] = product.marca;

    if (imagePath != null && imagePath.isNotEmpty) {
      request.files.add(await http.MultipartFile.fromPath('imagen', imagePath));
    }

    final streamedResponse = await request.send();
    final response = await http.Response.fromStream(streamedResponse);
    final body = _decodeBody(response.body);

    if (response.statusCode != 200) {
      throw Exception(_extractError(body, 'No se pudo actualizar el producto'));
    }

    return _parseProduct(body as Map<String, dynamic>);
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

  Product _parseProduct(Map<String, dynamic> json) {
    final rawImage = json['imagen']?.toString();
    if (rawImage != null && rawImage.isNotEmpty && !rawImage.startsWith('http')) {
      final cleanPath = rawImage.startsWith('/') ? rawImage.substring(1) : rawImage;
      json = Map<String, dynamic>.from(json)
        ..['imagen'] = '$_siteBaseUrl/$cleanPath';
    }
    return Product.fromJson(json);
  }
}
