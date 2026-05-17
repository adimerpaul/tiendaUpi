class Product {
  final int id;
  final String nombre;
  final double precio;
  final int cantidad;
  final String marca;

  const Product({
    required this.id,
    required this.nombre,
    required this.precio,
    required this.cantidad,
    required this.marca,
  });

  factory Product.fromJson(Map<String, dynamic> json) {
    return Product(
      id: int.parse(json['id'].toString()),
      nombre: json['nombre']?.toString() ?? '',
      precio: double.parse(json['precio'].toString()),
      cantidad: int.parse(json['cantidad'].toString()),
      marca: json['marca']?.toString() ?? '',
    );
  }

  Map<String, dynamic> toJsonWithoutId() {
    return {
      'nombre': nombre,
      'precio': precio,
      'cantidad': cantidad,
      'marca': marca,
    };
  }
}
