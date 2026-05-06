-- Galería de Imágenes - Database Setup

CREATE DATABASE IF NOT EXISTS gallery_db;
USE gallery_db;

CREATE TABLE IF NOT EXISTS images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    file_path VARCHAR(500) NOT NULL,
    s3_url VARCHAR(500) NULL,
    thumbnail_path VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Para inicializar desde la app, visita: index.php?action=initDb