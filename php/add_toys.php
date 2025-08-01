<?php
require_once 'db_connection.php';
session_start();

// Verificar si el usuario es administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    exit('Acceso denegado');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['toyName']);
    $description = trim($_POST['toyDescription']);
    $age = $_POST['toyAge'];
    $price = floatval($_POST['toyPrice']);
    $quantity = intval($_POST['toyQuantity']);
    
    // Procesar la imagen
    $imagePath = '';
    if (isset($_FILES['toyImage']) && $_FILES['toyImage']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../images/toys/';
        $uploadFile = $uploadDir . basename($_FILES['toyImage']['name']);
        
        // Validar tipo de archivo
        $imageFileType = strtolower(pathinfo($uploadFile, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($imageFileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES['toyImage']['tmp_name'], $uploadFile)) {
                $imagePath = 'images/toys/' . basename($_FILES['toyImage']['name']);
            }
        }
    }
    
    // Insertar juguete en la base de datos
    $stmt = $conn->prepare("INSERT INTO juguetes (nombre, descripcion, edad, precio, cantidad_inventario, imagen) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdds", $name, $description, $age, $price, $quantity, $imagePath);
    
    if ($stmt->execute()) {
        $response = [
            'success' => true,
            'message' => 'Juguete agregado exitosamente'
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'Error al agregar el juguete'
        ];
    }
    
    $stmt->close();
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$conn->close();
?>