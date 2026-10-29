<?php
header("Content-Type: application/json");

// Connexion à PostgreSQL au sein du réseau Docker
$host = getenv('DB_HOST') ?: 'inventory-db';
$db   = getenv('DB_NAME') ?: 'inventory_db';
$user = getenv('DB_USER') ?: 'mpin_user';
$pass = getenv('DB_PASS') ?: 'mpin_password';

$dsn = "pgsql:host=$host;port=5432;dbname=$db;";
try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erreur Postgres Docker : " . $e->getMessage()]);
    exit;
}

$requestUri = $_SERVER['REQUEST_URI'];
preg_match('/\/inventory\/([A-Z0-9-]+)\/deduct/', $requestUri, $matches);
$productId = $matches[1] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $productId) {
    $input = json_decode(file_get_contents('php://input'), true);
    $qty = $input['quantity'] ?? 0;

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT stock FROM stocks WHERE product_id = :id FOR UPDATE");
        $stmt->execute(['id' => $productId]);
        $currentStock = $stmt->fetchColumn();

        if ($currentStock === false || $currentStock < $qty) {
            http_response_code(400);
            echo json_encode(["error" => "Stock insuffisant dans Docker"]);
            $pdo->rollBack();
            exit;
        }

        $newStock = $currentStock - $qty;
        $update = $pdo->prepare("UPDATE stocks SET stock = :new_stock WHERE product_id = :id");
        $update->execute(['new_stock' => $newStock, 'id' => $productId]);

        $pdo->commit();
        echo json_encode(["product_id" => $productId, "updated_stock" => $newStock]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
    exit;
}
