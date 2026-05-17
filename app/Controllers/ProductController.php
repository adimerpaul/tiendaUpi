<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Product;

class ProductController
{
    private Product $productModel;

    public function __construct()
    {
        $this->productModel = new Product();
    }

    public function index(): void
    {
        http_response_code(200);
        echo json_encode($this->productModel->all(), JSON_UNESCAPED_UNICODE);
    }

    public function show(string $id): void
    {
        $productId = $this->parseId($id);
        if ($productId === null) {
            return;
        }

        $product = $this->productModel->find($productId);
        if ($product === null) {
            $this->jsonError('Producto no encontrado', 404);
            return;
        }

        http_response_code(200);
        echo json_encode($product, JSON_UNESCAPED_UNICODE);
    }

    public function store(): void
    {
        $data = $this->requestData();
        if ($data === null) {
            return;
        }

        $errors = $this->validate($data);
        if (!empty($errors)) {
            $this->jsonError('Datos inválidos', 422, ['detalles' => $errors]);
            return;
        }

        $newId = $this->productModel->create($data);
        $product = $this->productModel->find($newId);

        http_response_code(201);
        echo json_encode($product, JSON_UNESCAPED_UNICODE);
    }

    public function update(string $id): void
    {
        $productId = $this->parseId($id);
        if ($productId === null) {
            return;
        }

        if ($this->productModel->find($productId) === null) {
            $this->jsonError('Producto no encontrado', 404);
            return;
        }

        $data = $this->requestData();
        if ($data === null) {
            return;
        }

        $errors = $this->validate($data);
        if (!empty($errors)) {
            $this->jsonError('Datos inválidos', 422, ['detalles' => $errors]);
            return;
        }

        $this->productModel->update($productId, $data);
        $product = $this->productModel->find($productId);

        http_response_code(200);
        echo json_encode($product, JSON_UNESCAPED_UNICODE);
    }

    public function destroy(string $id): void
    {
        $productId = $this->parseId($id);
        if ($productId === null) {
            return;
        }

        if (!$this->productModel->delete($productId)) {
            $this->jsonError('Producto no encontrado', 404);
            return;
        }

        http_response_code(200);
        echo json_encode(['mensaje' => 'Producto eliminado'], JSON_UNESCAPED_UNICODE);
    }

    private function parseId(string $id): ?int
    {
        if (!ctype_digit($id) || (int) $id <= 0) {
            $this->jsonError('El id debe ser un entero positivo', 422);
            return null;
        }

        return (int) $id;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function requestData(): ?array
    {
        $body = file_get_contents('php://input');
        $data = json_decode($body ?: '', true);

        if (!is_array($data)) {
            $this->jsonError('El cuerpo debe ser JSON válido', 400);
            return null;
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, string>
     */
    private function validate(array $data): array
    {
        $errors = [];

        if (!isset($data['nombre']) || !is_string($data['nombre']) || trim($data['nombre']) === '') {
            $errors[] = 'nombre es obligatorio y debe ser texto';
        }

        if (!isset($data['marca']) || !is_string($data['marca']) || trim($data['marca']) === '') {
            $errors[] = 'marca es obligatoria y debe ser texto';
        }

        if (!isset($data['precio']) || !is_numeric($data['precio']) || (float) $data['precio'] < 0) {
            $errors[] = 'precio es obligatorio y debe ser un número mayor o igual a 0';
        }

        if (!isset($data['cantidad']) || filter_var($data['cantidad'], FILTER_VALIDATE_INT) === false || (int) $data['cantidad'] < 0) {
            $errors[] = 'cantidad es obligatoria y debe ser un entero mayor o igual a 0';
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function jsonError(string $message, int $statusCode, array $extra = []): void
    {
        http_response_code($statusCode);
        echo json_encode(array_merge(['error' => $message], $extra), JSON_UNESCAPED_UNICODE);
    }
}
