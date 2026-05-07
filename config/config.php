<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

class Config {
    private static $config = [];

    public static function load() {
        self::$config = [
            'app_env' => $_ENV['APP_ENV'] ?? 'development',
            'debug_mode' => filter_var($_ENV['DEBUG_MODE'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'db_host' => $_ENV['DB_HOST'] ?? 'localhost',
            'db_port' => $_ENV['DB_PORT'] ?? '3306',
            'db_name' => $_ENV['DB_NAME'] ?? 'gallery_db',
            'db_user' => $_ENV['DB_USER'] ?? 'root',
            'db_password' => $_ENV['DB_PASSWORD'] ?? '',
            'aws_region' => $_ENV['AWS_REGION'] ?? 'us-east-1',
            'aws_s3_bucket' => $_ENV['AWS_S3_BUCKET'] ?? '',
            'aws_s3_folder' => $_ENV['AWS_S3_FOLDER'] ?? 'imagenes',
            'upload_dir' => __DIR__ . '/../' . ($_ENV['UPLOAD_DIR'] ?? 'uploads/'),
            'max_file_size' => (int)($_ENV['MAX_FILE_SIZE'] ?? 5242880),
            'allowed_types' => explode(',', $_ENV['ALLOWED_TYPES'] ?? 'image/jpeg,image/png,image/gif,image/webp'),
        ];
    }

    public static function get($key, $default = null) {
        return self::$config[$key] ?? $default;
    }

    public static function isDebug() {
        $sessionDebug = isset($_SESSION['debug_mode']) ? $_SESSION['debug_mode'] : null;
        if ($sessionDebug !== null) {
            return $sessionDebug;
        }
        return self::$config['debug_mode'] ?? false;
    }

    public static function setDebug($value) {
        $_SESSION['debug_mode'] = (bool)$value;
    }
}

Config::load();