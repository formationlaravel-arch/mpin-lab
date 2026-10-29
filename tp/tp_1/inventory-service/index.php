<?php
header("Content-Type: application/json");

// Connexion au MySQL natif de WampServer
$dsn = "mysql:host=localhost;dbname=wamp_monolith_db;charset=utf8";
try {
    $pdo = new PDO($dsn, "root", "", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erreur Wamp MySQL : " . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['REQUEST_URI'] === '/monolith/index.php/orders') {
    $input = json_decode(file_get_contents('php://input'), true);
    $productId = $input['product_id'] ?? null;
    $qty = $input['quantity'] ?? null;

    if (!$productId || !$qty || $qty <= 0) {
        http_response_code(400);
        echo json_encode(["error" => "Payload invalide"]);
        exit;
    }

    // Début de la transaction unique (Avantage du Monolithe)
    $pdo->beginTransaction();
    try {
        // 1. Vérifier et verrouiller le stock
        $stmt = $pdo->prepare("SELECT stock_qty FROM global_ecommerce WHERE item_id = :id FOR UPDATE");
        $stmt->execute(['id' => $productId]);
        $currentStock = $stmt->fetchColumn();

        if ($currentStock === false || $currentStock < $qty) {
            http_response_code(400);
            echo json_encode(["error" => "Stock insuffisant ou produit inconnu"]);
            $pdo->rollBack();
            exit;
        }

        // 2. Mettre à jour le stock et incrémenter les commandes (Couplage fort)
        $newStock = $currentStock - $qty;
        $update = $pdo->prepare("UPDATE global_ecommerce SET stock_qty = :new_stock, order_count = order_count + 1 WHERE item_id = :id");
        $update->execute(['new_stock' => $newStock, 'id' => $productId]);

        $pdo->commit();
        echo json_encode(["status" => "SUCCESS", "message" => "Commande validée en local sous Wamp", "stock_restant" => $newStock]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
    exit;
}
