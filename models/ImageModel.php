<?php
require_once __DIR__ . '/../config/database.php';

class ImageModel {
    private $db;
    private $table = 'images';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create($title, $description, $filePath, $s3Url = null, $thumbnailPath = null) {
        if (!$this->db->isConnected()) {
            return false;
        }

        $sql = "INSERT INTO {$this->table} (title, description, file_path, s3_url, thumbnail_path) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->getConnection()->prepare($sql);
        
        return $stmt->execute([$title, $description, $filePath, $s3Url, $thumbnailPath]);
    }

    public function getAll() {
        if (!$this->db->isConnected()) {
            return [];
        }

        $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        $stmt = $this->db->getConnection()->query($sql);
        
        return $stmt->fetchAll();
    }

    public function getById($id) {
        if (!$this->db->isConnected()) {
            return null;
        }

        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        $stmt->execute([$id]);
        
        return $stmt->fetch();
    }

    public function update($id, $title, $description) {
        if (!$this->db->isConnected()) {
            return false;
        }

        $sql = "UPDATE {$this->table} SET title = ?, description = ? WHERE id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        
        return $stmt->execute([$title, $description, $id]);
    }

    public function delete($id) {
        if (!$this->db->isConnected()) {
            return false;
        }

        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->getConnection()->prepare($sql);
        
        return $stmt->execute([$id]);
    }
}