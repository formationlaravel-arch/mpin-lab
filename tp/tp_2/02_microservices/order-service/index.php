<?php
header("Content-Type: application/json");

$host = getenv('DB_HOST') ?: 'orders-db';
$db   = getenv('DB_NAME') ?: 'orders_db';
$user = getenv('DB_USER') ?: 'mpin_user';
$pass = getenv('DB_PASS') ?: 'mpin_password';
$inventoryUrl = getenv('INVENTORY_SERVICE_URL') ?: 'http://inventory-service:8000';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erreur de connexion Orders : " . $e->getMessage()]);
    exit;
}

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

if ($requestMethod === 'POST' && strpos($requestUri, '/orders') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
    $productId = $input['product_id'] ?? null;
    $quantity = $input['quantity'] ?? null;

    if (!$productId || !$quantity || $quantity <= 0) {
        http_response_code(400);
        echo json_encode(["error" => "Données invalides"]);
        exit;
    }

    // 1. Appel HTTP vers le Microservice Inventory (Communication Synchrone)
    $url = $inventoryUrl . "/inventory/" . $productId . "/deduct";
    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => json_encode(['quantity' => $quantity]),
            'ignore_errors' => true
        ]
    ];
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    // Analyse du code de réponse HTTP
    preg_match('{HTTP/\S*\s(\d+)}', $http_response_header[0], $matches);
    $status_code = intval($matches[1]);

    if ($status_code !== 200) {
        http_response_code($status_code ?: 400);
        echo $result; // Transmet l'erreur renvoyée par l'inventaire (ex: Stock insuffisant)
        exit;
    }

    // 2. Si l'inventaire a validé, persister la commande localement dans MySQL
    $orderId = "ORD-" . uniqid();
    $stmt = $pdo->prepare("INSERT INTO orders (order_id, product_id, quantity, status) VALUES (:order_id, :product_id, :quantity, 'VALIDATED')");
    $stmt->execute([
        'order_id' => $orderId,
        'product_id' => $productId,
        'quantity' => $quantity
    ]);

    $inventoryResponse = json_decode($result, true);

    echo json_encode([
        "order_id" => $orderId,
        "status" => "VALIDATED",
        "remaining_stock" => $inventoryResponse['updated_stock']
    ]);
    exit;
}

http_response_code(404);
echo json_encode(["error" => "Route introuvable"]);
