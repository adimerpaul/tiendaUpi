class Product {
  final int id;
  final String nombre;
  final double precio;
  final int cantidad;
  final String marca;
  final String? imagen;

  const Product({
    required this.id,
    required this.nombre,
    required this.precio,
    required this.cantidad,
    required this.marca,
    this.imagen,
  });

  factory Product.fromJson(Map<String, dynamic> json) {
    return Product(
      id: int.parse(json['id'].toString()),
      nombre: json['nombre']?.toString() ?? '',
      precio: double.parse(json['precio'].toString()),
      cantidad: int.parse(json['cantidad'].toString()),
      marca: json['marca']?.toString() ?? '',
      imagen: json['imagen']?.toString(),
    );
  }

  Map<String, dynamic> toJsonWithoutId() {
    return {
      'nombre': nombre,
      'precio': precio,
      'cantidad': cantidad,
      'marca': marca,
      'imagen': imagen,
    };
  }
}
