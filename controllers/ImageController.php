<?php
require_once __DIR__ . '/../models/ImageModel.php';
require_once __DIR__ . '/../aws/S3Service.php';
require_once __DIR__ . '/../config/config.php';

class ImageController {
    private $model;
    private $s3;

    public function __construct() {
        $this->model = new ImageModel();
        $this->s3 = new S3Service();
    }

    public function index() {
        return $this->model->getAll();
    }

    public function getById($id) {
        return $this->model->getById($id);
    }

    public function upload() {
        header('Content-Type: application/json');

        if (!isset($_FILES['image'])) {
            echo json_encode(['success' => false, 'error' => 'No se recibió archivo']);
            return;
        }

        $file = $_FILES['image'];
        $title = $_POST['title'] ?? 'Sin título';
        $description = $_POST['description'] ?? '';

        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'Error en la carga del archivo']);
            return;
        }

        $maxSize = Config::get('max_file_size');
        if ($file['size'] > $maxSize) {
            echo json_encode(['success' => false, 'error' => 'Archivo muy grande']);
            return;
        }

        $allowedTypes = Config::get('allowed_types');
        $validExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($mimeType, $allowedTypes)) {
            if (!in_array($extension, $validExtensions)) {
                echo json_encode(['success' => false, 'error' => 'Tipo de archivo no permitido']);
                return;
            }
        }

        $fileName = uniqid() . '_' . time() . '.' . $extension;

        $uploadDir = Config::get('upload_dir');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $localPath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $localPath)) {
            $s3Url = null;

            if ($this->s3->isConfigured()) {
                $s3Url = $this->s3->upload($localPath, $fileName);
            }

            $result = $this->model->create($title, $description, $localPath, $s3Url);

            if ($result) {
                $lastId = Database::getInstance()->getConnection()->lastInsertId();
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'id' => $lastId,
                        'title' => $title,
                        'file_path' => $localPath,
                        's3_url' => $s3Url,
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Error al guardar en BD']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al mover archivo']);
        }
    }

    public function update() {
        header('Content-Type: application/json');

        $id = $_POST['id'] ?? null;
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';

        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID requerido']);
            return;
        }

        $result = $this->model->update($id, $title, $description);

        echo json_encode(['success' => $result]);
    }

    public function delete() {
        header('Content-Type: application/json');

        $id = $_POST['id'] ?? null;

        if (!$id) {
            echo json_encode(['success' => false, 'error' => 'ID requerido']);
            return;
        }

        $image = $this->model->getById($id);

        if ($image && !empty($image['file_path']) && file_exists($image['file_path'])) {
            unlink($image['file_path']);
        }

        if ($image && !empty($image['s3_url']) && $this->s3->isConfigured()) {
            $fileName = basename($image['file_path']);
            $this->s3->delete($fileName);
        }

        $result = $this->model->delete($id);

        echo json_encode(['success' => $result]);
    }

    public function getStatus() {
        return [
            'db_connected' => Database::getInstance()->isConnected(),
            'db_error' => Database::getInstance()->getError(),
            's3_configured' => $this->s3->isConfigured(),
            'debug_mode' => Config::isDebug(),
            'memory_usage' => round(memory_get_usage() / 1024, 2),
            'php_version' => PHP_VERSION,
        ];
    }
}