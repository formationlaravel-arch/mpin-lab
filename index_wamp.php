<?php
header("Content-Type: application/json");

// 1. Connexion au MySQL natif de WampServer
$dsn = "mysql:host=localhost;dbname=wamp_monolith_db;charset=utf8";
try {
    $pdo = new PDO($dsn, "root", "", [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erreur Wamp MySQL : " . $e->getMessage()]);
    exit;
}

// 2. Filtrage strict de la route d'API
$isPost = ($_SERVER['REQUEST_METHOD'] === 'POST');
$isOrdersRoute = (strpos($_SERVER['REQUEST_URI'], '/orders') !== false);

if (!$isPost || !$isOrdersRoute) {
    http_response_code(404);
    echo json_encode(["error" => "Route introuvable. Utilisez POST sur /orders"]);
    exit;
}

// 3. Récupération et validation du Payload JSON
$input = json_decode(file_get_contents('php://input'), true);
$productId = $input['product_id'] ?? null;
$qty = $input['quantity'] ?? null;

if (!$productId || !$qty || $qty <= 0) {
    http_response_code(400);
    echo json_encode(["error" => "Payload JSON invalide ou manquant"]);
    exit;
}

// 4. Exécution de la transaction métier
$pdo->beginTransaction();
try {
    // A. Vérification et verrouillage de la ligne de stock
    $stmt = $pdo->prepare("SELECT stock_qty FROM global_ecommerce WHERE item_id = :id FOR UPDATE");
    $stmt->execute(['id' => $productId]);
    $currentStock = $stmt->fetchColumn();

    if ($currentStock === false || $currentStock < $qty) {
        http_response_code(400);
        echo json_encode(["error" => "Stock insuffisant ou produit inconnu"]);
        $pdo->rollBack();
        exit;
    }

    // B. Déduction du stock et mise à jour
    $newStock = $currentStock - $qty;
    $update = $pdo->prepare("UPDATE global_ecommerce SET stock_qty = :new_stock, order_count = order_count + 1 WHERE item_id = :id");
    $update->execute(['new_stock' => $newStock, 'id' => $productId]);

    $pdo->commit();
    
    // C. Réponse de succès
    echo json_encode([
        "status" => "SUCCESS",
        "message" => "Commande validee avec succes sur le Monolithe Wamp",
        "stock_restant" => (int)$newStock
    ]);
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["error" => "Erreur transactionnelle : " . $e->getMessage()]);
    exit;
}
