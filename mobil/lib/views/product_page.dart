import 'package:flutter/material.dart';

import '../models/product.dart';
import '../viewmodels/product_view_model.dart';

class ProductPage extends StatefulWidget {
  const ProductPage({super.key});

  @override
  State<ProductPage> createState() => _ProductPageState();
}

class _ProductPageState extends State<ProductPage> {
  final ProductViewModel _viewModel = ProductViewModel();

  final TextEditingController _idCtrl = TextEditingController();
  final TextEditingController _nombreCtrl = TextEditingController();
  final TextEditingController _precioCtrl = TextEditingController();
  final TextEditingController _cantidadCtrl = TextEditingController();
  final TextEditingController _marcaCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    _viewModel.loadProducts();
  }

  @override
  void dispose() {
    _idCtrl.dispose();
    _nombreCtrl.dispose();
    _precioCtrl.dispose();
    _cantidadCtrl.dispose();
    _marcaCtrl.dispose();
    _viewModel.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('CRUD Productos (MVVM)'),
        centerTitle: true,
      ),
      body: AnimatedBuilder(
        animation: _viewModel,
        builder: (context, _) {
          return RefreshIndicator(
            onRefresh: _viewModel.loadProducts,
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(12),
                    child: Column(
                      children: [
                        _buildField(_idCtrl, 'ID (para editar/eliminar)'),
                        _buildField(_nombreCtrl, 'Nombre'),
                        _buildField(
                          _precioCtrl,
                          'Precio',
                          keyboardType: const TextInputType.numberWithOptions(
                            decimal: true,
                          ),
                        ),
                        _buildField(
                          _cantidadCtrl,
                          'Cantidad',
                          keyboardType: TextInputType.number,
                        ),
                        _buildField(_marcaCtrl, 'Marca'),
                        const SizedBox(height: 8),
                        Wrap(
                          spacing: 8,
                          runSpacing: 8,
                          children: [
                            ElevatedButton.icon(
                              onPressed: _viewModel.isLoading ? null : _onCreate,
                              icon: const Icon(Icons.add),
                              label: const Text('Crear'),
                            ),
                            ElevatedButton.icon(
                              onPressed: _viewModel.isLoading ? null : _onUpdate,
                              icon: const Icon(Icons.edit),
                              label: const Text('Editar'),
                            ),
                            ElevatedButton.icon(
                              onPressed: _viewModel.isLoading ? null : _onDelete,
                              icon: const Icon(Icons.delete),
                              label: const Text('Eliminar'),
                            ),
                            OutlinedButton.icon(
                              onPressed:
                                  _viewModel.isLoading ? null : _viewModel.loadProducts,
                              icon: const Icon(Icons.refresh),
                              label: const Text('Refrescar'),
                            ),
                            TextButton(
                              onPressed: _clearForm,
                              child: const Text('Limpiar'),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
                if (_viewModel.errorMessage != null) ...[
                  const SizedBox(height: 8),
                  Text(
                    _viewModel.errorMessage!,
                    style: const TextStyle(color: Colors.red),
                  ),
                ],
                const SizedBox(height: 12),
                if (_viewModel.isLoading)
                  const Center(child: CircularProgressIndicator())
                else if (_viewModel.products.isEmpty)
                  const Center(child: Text('Sin productos'))
                else
                  ..._viewModel.products.map(_buildProductCard),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _buildField(
    TextEditingController controller,
    String label, {
    TextInputType keyboardType = TextInputType.text,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: TextField(
        controller: controller,
        keyboardType: keyboardType,
        decoration: InputDecoration(
          labelText: label,
          border: const OutlineInputBorder(),
          isDense: true,
        ),
      ),
    );
  }

  Widget _buildProductCard(Product product) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        title: Text('${product.nombre} - ${product.marca}'),
        subtitle: Text(
          'ID: ${product.id} | Precio: ${product.precio.toStringAsFixed(2)} | Cantidad: ${product.cantidad}',
        ),
        trailing: const Icon(Icons.chevron_right),
        onTap: () {
          _idCtrl.text = product.id.toString();
          _nombreCtrl.text = product.nombre;
          _precioCtrl.text = product.precio.toString();
          _cantidadCtrl.text = product.cantidad.toString();
          _marcaCtrl.text = product.marca;
        },
      ),
    );
  }

  Future<void> _onCreate() async {
    try {
      final data = _readFormWithoutId();
      if (data == null) return;

      await _viewModel.createProduct(
        nombre: data.nombre,
        precio: data.precio,
        cantidad: data.cantidad,
        marca: data.marca,
      );
      _showMessage('Producto creado');
      _clearForm();
    } catch (e) {
      _showMessage(e.toString().replaceFirst('Exception: ', ''), isError: true);
    }
  }

  Future<void> _onUpdate() async {
    try {
      final id = int.tryParse(_idCtrl.text.trim());
      if (id == null || id <= 0) {
        _showMessage('ID invalido para editar', isError: true);
        return;
      }
      final data = _readFormWithoutId();
      if (data == null) return;

      await _viewModel.updateProduct(
        id: id,
        nombre: data.nombre,
        precio: data.precio,
        cantidad: data.cantidad,
        marca: data.marca,
      );
      _showMessage('Producto actualizado');
    } catch (e) {
      _showMessage(e.toString().replaceFirst('Exception: ', ''), isError: true);
    }
  }

  Future<void> _onDelete() async {
    try {
      final id = int.tryParse(_idCtrl.text.trim());
      if (id == null || id <= 0) {
        _showMessage('ID invalido para eliminar', isError: true);
        return;
      }
      await _viewModel.deleteProduct(id);
      _showMessage('Producto eliminado');
      _clearForm();
    } catch (e) {
      _showMessage(e.toString().replaceFirst('Exception: ', ''), isError: true);
    }
  }

  ({String nombre, double precio, int cantidad, String marca})?
      _readFormWithoutId() {
    final nombre = _nombreCtrl.text.trim();
    final precio = double.tryParse(_precioCtrl.text.trim());
    final cantidad = int.tryParse(_cantidadCtrl.text.trim());
    final marca = _marcaCtrl.text.trim();

    if (nombre.isEmpty || marca.isEmpty || precio == null || cantidad == null) {
      _showMessage('Completa nombre, precio, cantidad y marca', isError: true);
      return null;
    }

    if (precio < 0 || cantidad < 0) {
      _showMessage('Precio y cantidad deben ser >= 0', isError: true);
      return null;
    }

    return (nombre: nombre, precio: precio, cantidad: cantidad, marca: marca);
  }

  void _clearForm() {
    _idCtrl.clear();
    _nombreCtrl.clear();
    _precioCtrl.clear();
    _cantidadCtrl.clear();
    _marcaCtrl.clear();
    setState(() {});
  }

  void _showMessage(String message, {bool isError = false}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: isError ? Colors.red : Colors.green,
      ),
    );
  }
}
