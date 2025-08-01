<?php
require_once 'db_connection.php';
require_once 'session_control.php';

header('Content-Type: application/json');

if (!isAuthenticated()) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para realizar un pedido']);
    exit;
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener dirección de envío (podría venir del formulario o de la base de datos)
    $stmt = $conn->prepare("SELECT direccion FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit;
    }
    
    $user = $result->fetch_assoc();
    $shippingAddress = $user['direccion'];
    $stmt->close();
    
    // Iniciar transacción
    $conn->begin_transaction();
    
    try {
        // 1. Obtener items del carrito
        $stmt = $conn->prepare("
            SELECT c.juguete_id, c.cantidad, c.monto_total, j.precio, j.cantidad_inventario as stock
            FROM carrito c
            JOIN juguetes j ON c.juguete_id = j.id
            WHERE c.usuario_id = ?
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $cartItems = [];
        $orderTotal = 0;
        
        while ($row = $result->fetch_assoc()) {
            // Verificar stock antes de procesar
            if ($row['stock'] < $row['cantidad']) {
                throw new Exception("No hay suficiente stock para el producto ID: " . $row['juguete_id']);
            }
            
            $cartItems[] = $row;
            $orderTotal += $row['monto_total'];
        }
        
        $stmt->close();
        
        if (empty($cartItems)) {
            throw new Exception("El carrito está vacío");
        }
        
        // 2. Crear el pedido
        $stmt = $conn->prepare("INSERT INTO pedidos (usuario_id, total, direccion_envio) VALUES (?, ?, ?)");
        $stmt->bind_param("ids", $userId, $orderTotal, $shippingAddress);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al crear el pedido");
        }
        
        $orderId = $stmt->insert_id;
        $stmt->close();
        
        // 3. Agregar detalles del pedido y actualizar inventario
        foreach ($cartItems as $item) {
            // Agregar detalle
            $stmt = $conn->prepare("
                INSERT INTO detalles_pedido (pedido_id, juguete_id, cantidad, precio_unitario, subtotal)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("iiidd", $orderId, $item['juguete_id'], $item['cantidad'], $item['precio'], $item['monto_total']);
            
            if (!$stmt->execute()) {
                throw new Exception("Error al agregar detalles del pedido");
            }
            
            $stmt->close();
            
            // Actualizar inventario
            $stmt = $conn->prepare("
                UPDATE juguetes 
                SET cantidad_inventario = cantidad_inventario - ? 
                WHERE id = ?
            ");
            $stmt->bind_param("ii", $item['cantidad'], $item['juguete_id']);
            
            if (!$stmt->execute()) {
                throw new Exception("Error al actualizar el inventario");
            }
            
            $stmt->close();
        }
        
        // 4. Vaciar carrito
        $stmt = $conn->prepare("DELETE FROM carrito WHERE usuario_id = ?");
        $stmt->bind_param("i", $userId);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al vaciar el carrito");
        }
        
        $stmt->close();
        
        // Confirmar transacción
        $conn->commit();
        
        // Limpiar carrito de sesión
        $_SESSION['cart'] = [];
        
        echo json_encode([
            'success' => true,
            'message' => 'Pedido realizado con éxito',
            'orderId' => $orderId
        ]);
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conn->rollback();
        
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

$conn->close();
?>