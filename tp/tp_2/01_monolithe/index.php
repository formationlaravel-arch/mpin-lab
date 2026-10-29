<?php
header("Content-Type: application/json");

// Connexion à l'unique base de données MySQL
$host = getenv('DB_HOST') ?: 'orders-db';
$db   = getenv('DB_NAME') ?: 'monolith_db';
$user = getenv('DB_USER') ?: 'mpin_user';
$pass = getenv('DB_PASS') ?: 'mpin_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erreur de connexion : " . $e->getMessage()]);
    exit;
}

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Route unique : POST /orders
if ($requestMethod === 'POST' && strpos($requestUri, '/orders') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
    $productId = $input['product_id'] ?? null;
    $quantity = $input['quantity'] ?? null;

    if (!$productId || !$quantity || $quantity <= 0) {
        http_response_code(400);
        echo json_encode(["error" => "Données d'entrée invalides"]);
        exit;
    }

    $pdo->beginTransaction();
    try {
        // 1. Vérification et verrouillage du stock (Logique Inventaire interne)
        $stmt = $pdo->prepare("SELECT stock FROM stocks WHERE product_id = :id FOR UPDATE");
        $stmt->execute(['id' => $productId]);
        $currentStock = $stmt->fetchColumn();

        if ($currentStock === false) {
            http_response_code(404);
            echo json_encode(["error" => "Produit introuvable"]);
            $pdo->rollBack();
            exit;
        }

        if ($currentStock < $quantity) {
            http_response_code(400);
            echo json_encode(["error" => "Stock insuffisant"]);
            $pdo->rollBack();
            exit;
        }

        // 2. Déduction du stock
        $newStock = $currentStock - $quantity;
        $updateStock = $pdo->prepare("UPDATE stocks SET stock = :new_stock WHERE product_id = :id");
        $updateStock->execute(['new_stock' => $newStock, 'id' => $productId]);

        // 3. Création de la commande (Logique Commande interne)
        $orderId = "ORD-" . uniqid();
        $insertOrder = $pdo->prepare("INSERT INTO orders (order_id, product_id, quantity, status) VALUES (:order_id, :product_id, :quantity, 'VALIDATED')");
        $insertOrder->execute([
            'order_id' => $orderId,
            'product_id' => $productId,
            'quantity' => $quantity
        ]);

        $pdo->commit();
        echo json_encode([
            "order_id" => $orderId,
            "status" => "VALIDATED",
            "remaining_stock" => $newStock
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["error" => "Erreur système : " . $e->getMessage()]);
    }
    exit;
}

http_response_code(404);
echo json_encode(["error" => "Route introuvable"]);
