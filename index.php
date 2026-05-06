<?php
session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controllers/ImageController.php';

$controller = new ImageController();

$action = $_GET['action'] ?? 'gallery';

switch ($action) {
    case 'list':
        echo json_encode($controller->index());
        break;

    case 'upload':
        $controller->upload();
        break;

    case 'update':
        $controller->update();
        break;

    case 'delete':
        $controller->delete();
        break;

    case 'status':
        echo json_encode($controller->getStatus());
        break;

    case 'toggleDebug':
        $data = json_decode(file_get_contents('php://input'), true);
        Config::setDebug($data['enabled'] ?? false);
        echo json_encode(['success' => true, 'debug_mode' => Config::isDebug()]);
        break;

    case 'initDb':
        $db = Database::getInstance();
        $result = $db->initSchema();
        echo json_encode(['success' => $result, 'error' => $db->getError()]);
        break;

    case 'gallery':
    default:
        include __DIR__ . '/views/pages/gallery.php';
        break;
}