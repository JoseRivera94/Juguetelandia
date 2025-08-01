<?php
require_once 'db_connection.php';
require_once 'session_control.php';

header('Content-Type: application/json');

if (!isAuthenticated()) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para acceder al carrito']);
    exit;
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $toyId = intval($_POST['toyId'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    
    switch ($action) {
        case 'add':
            // Verificar si el juguete existe y hay suficiente stock
            $stmt = $conn->prepare("SELECT id, precio, cantidad_inventario FROM juguetes WHERE id = ?");
            $stmt->bind_param("i", $toyId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Juguete no encontrado']);
                exit;
            }
            
            $toy = $result->fetch_assoc();
            $stmt->close();
            
            // Verificar stock
            if ($toy['cantidad_inventario'] < $quantity) {
                echo json_encode(['success' => false, 'message' => 'No hay suficiente stock disponible']);
                exit;
            }
            
            // Verificar si ya está en el carrito de la base de datos
            $stmt = $conn->prepare("SELECT id, cantidad FROM carrito WHERE usuario_id = ? AND juguete_id = ?");
            $stmt->bind_param("ii", $userId, $toyId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Actualizar cantidad
                $cartItem = $result->fetch_assoc();
                $newQuantity = $cartItem['cantidad'] + $quantity;
                $total = $newQuantity * $toy['precio'];
                
                $stmt = $conn->prepare("UPDATE carrito SET cantidad = ?, monto_total = ? WHERE id = ?");
                $stmt->bind_param("idi", $newQuantity, $total, $cartItem['id']);
            } else {
                // Agregar nuevo item
                $total = $quantity * $toy['precio'];
                
                $stmt = $conn->prepare("INSERT INTO carrito (usuario_id, juguete_id, cantidad, monto_total) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiid", $userId, $toyId, $quantity, $total);
            }
            
            if ($stmt->execute()) {
                // Actualizar carrito en sesión
                addToCart($toyId, $quantity);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Juguete agregado al carrito',
                    'cartCount' => getCartCount()
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar el carrito']);
            }
            
            $stmt->close();
            break;
            
        case 'update':
            // Verificar cantidad válida
            if ($quantity <= 0) {
                echo json_encode(['success' => false, 'message' => 'Cantidad inválida']);
                exit;
            }
            
            // Verificar stock
            $stmt = $conn->prepare("SELECT cantidad_inventario, precio FROM juguetes WHERE id = ?");
            $stmt->bind_param("i", $toyId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                echo json_encode(['success' => false, 'message' => 'Juguete no encontrado']);
                exit;
            }
            
            $toy = $result->fetch_assoc();
            $stmt->close();
            
            if ($toy['cantidad_inventario'] < $quantity) {
                echo json_encode(['success' => false, 'message' => 'No hay suficiente stock disponible']);
                exit;
            }
            
            // Actualizar en base de datos
            $total = $quantity * $toy['precio'];
            
            $stmt = $conn->prepare("UPDATE carrito SET cantidad = ?, monto_total = ? WHERE usuario_id = ? AND juguete_id = ?");
            $stmt->bind_param("idii", $quantity, $total, $userId, $toyId);
            
            if ($stmt->execute()) {
                // Actualizar carrito en sesión
                updateCartItem($toyId, $quantity);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Cantidad actualizada',
                    'cartCount' => getCartCount(),
                    'subtotal' => $total
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar la cantidad']);
            }
            
            $stmt->close();
            break;
            
        case 'remove':
            // Eliminar de la base de datos
            $stmt = $conn->prepare("DELETE FROM carrito WHERE usuario_id = ? AND juguete_id = ?");
            $stmt->bind_param("ii", $userId, $toyId);
            
            if ($stmt->execute()) {
                // Actualizar carrito en sesión
                removeFromCart($toyId);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Juguete eliminado del carrito',
                    'cartCount' => getCartCount()
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar el juguete']);
            }
            
            $stmt->close();
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Obtener contenido del carrito
    $stmt = $conn->prepare("
        SELECT c.id, c.juguete_id, c.cantidad, c.monto_total, 
               j.nombre, j.precio, j.imagen, j.cantidad_inventario as stock
        FROM carrito c
        JOIN juguetes j ON c.juguete_id = j.id
        WHERE c.usuario_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cartItems = [];
    $total = 0;
    
    while ($row = $result->fetch_assoc()) {
        $cartItems[] = $row;
        $total += $row['monto_total'];
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'items' => $cartItems,
        'total' => $total,
        'cartCount' => getCartCount()
    ]);
}

$conn->close();
?>