import 'package:flutter/material.dart';

import '../models/product.dart';
import '../services/product_api_service.dart';

class ProductViewModel extends ChangeNotifier {
  ProductViewModel({ProductApiService? apiService})
      : _apiService = apiService ?? ProductApiService();

  final ProductApiService _apiService;

  List<Product> products = [];
  bool isLoading = false;
  String? errorMessage;

  Future<void> loadProducts() async {
    isLoading = true;
    errorMessage = null;
    notifyListeners();

    try {
      products = await _apiService.fetchProducts();
    } catch (e) {
      errorMessage = e.toString().replaceFirst('Exception: ', '');
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }

  Future<void> createProduct({
    required String nombre,
    required double precio,
    required int cantidad,
    required String marca,
    String? imagePath,
  }) async {
    final newProduct = Product(
      id: 0,
      nombre: nombre,
      precio: precio,
      cantidad: cantidad,
      marca: marca,
    );
    await _apiService.createProduct(newProduct, imagePath: imagePath);
    await loadProducts();
  }

  Future<void> updateProduct({
    required int id,
    required String nombre,
    required double precio,
    required int cantidad,
    required String marca,
    String? imagePath,
  }) async {
    final editedProduct = Product(
      id: id,
      nombre: nombre,
      precio: precio,
      cantidad: cantidad,
      marca: marca,
    );
    await _apiService.updateProduct(id, editedProduct, imagePath: imagePath);
    await loadProducts();
  }

  Future<void> deleteProduct(int id) async {
    await _apiService.deleteProduct(id);
    await loadProducts();
  }
}
