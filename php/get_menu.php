<?php
header('Content-Type: application/json');
require_once __DIR__ . '/config.php';

try{
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id, name, description, price, category, image, stock, status FROM menu_items WHERE status = 'active' ORDER BY category, name");
    $stmt->execute();
    $res = $stmt->get_result();
    $items = [];
    while($row = $res->fetch_assoc()){
        // Normalize image path - remove ../ prefix if present
        if (!empty($row['image']) && strpos($row['image'], '../') === 0) {
            $row['image'] = substr($row['image'], 3);
        }
        $items[] = $row;
    }
    $stmt->close();
    echo json_encode($items);
} catch(Exception $e){
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

?>
