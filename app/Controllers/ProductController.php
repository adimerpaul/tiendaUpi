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

        $dataWithImage = $this->attachImageToData($data);
        if ($dataWithImage === null) {
            return;
        }

        $errors = $this->validate($dataWithImage);
        if (!empty($errors)) {
            $this->jsonError('Datos inválidos', 422, ['detalles' => $errors]);
            return;
        }

        $newId = $this->productModel->create($dataWithImage);
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

        $existingProduct = $this->productModel->find($productId);
        if ($existingProduct === null) {
            $this->jsonError('Producto no encontrado', 404);
            return;
        }

        $data = $this->requestData();
        if ($data === null) {
            return;
        }

        $dataWithImage = $this->attachImageToData($data, $existingProduct['imagen'] ?? null);
        if ($dataWithImage === null) {
            return;
        }

        $errors = $this->validate($dataWithImage);
        if (!empty($errors)) {
            $this->jsonError('Datos inválidos', 422, ['detalles' => $errors]);
            return;
        }

        $this->productModel->update($productId, $dataWithImage);
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
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (stripos($contentType, 'multipart/form-data') !== false) {
            return $_POST;
        }

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

        if (isset($data['imagen']) && $data['imagen'] !== null && !is_string($data['imagen'])) {
            $errors[] = 'imagen debe ser texto con la ruta del archivo';
        }

        return $errors;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    private function attachImageToData(array $data, ?string $currentImage = null): ?array
    {
        $data['imagen'] = $currentImage;

        if (
            !isset($_FILES['imagen']) ||
            !is_array($_FILES['imagen']) ||
            !isset($_FILES['imagen']['error'])
        ) {
            return $data;
        }

        if ((int) $_FILES['imagen']['error'] === UPLOAD_ERR_NO_FILE) {
            return $data;
        }

        $imagePath = $this->saveImage($_FILES['imagen']);
        if ($imagePath === null) {
            return null;
        }

        $data['imagen'] = $imagePath;
        return $data;
    }

    /**
     * @param array<string, mixed> $file
     */
    private function saveImage(array $file): ?string
    {
        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorCode !== UPLOAD_ERR_OK) {
            $this->jsonError('No se pudo subir la imagen', 400);
            return null;
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            $this->jsonError('Archivo de imagen inválido', 400);
            return null;
        }

        if (!function_exists('imagewebp')) {
            $this->jsonError('El servidor no tiene soporte para convertir a WebP', 500);
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo ? finfo_file($finfo, $tmpName) : false;
        if ($finfo) {
            finfo_close($finfo);
        }

        if (!is_string($mimeType)) {
            $this->jsonError('Formato de imagen no permitido', 422);
            return null;
        }

        $imageResource = match ($mimeType) {
            'image/jpeg' => imagecreatefromjpeg($tmpName),
            'image/png' => imagecreatefrompng($tmpName),
            'image/gif' => imagecreatefromgif($tmpName),
            'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($tmpName) : false,
            default => false,
        };

        if ($imageResource === false) {
            $this->jsonError('Formato de imagen no permitido o archivo corrupto', 422);
            return null;
        }

        if (function_exists('imagepalettetotruecolor')) {
            imagepalettetotruecolor($imageResource);
        }
        imagealphablending($imageResource, false);
        imagesavealpha($imageResource, true);

        $uploadDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'upload';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
            imagedestroy($imageResource);
            $this->jsonError('No se pudo crear la carpeta de uploads', 500);
            return null;
        }

        $fileName = 'prod_' . str_replace('.', '', uniqid('', true)) . '.webp';
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

        if (!imagewebp($imageResource, $targetPath, 75)) {
            imagedestroy($imageResource);
            $this->jsonError('No se pudo guardar la imagen', 500);
            return null;
        }

        imagedestroy($imageResource);
        return 'upload/' . $fileName;
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
