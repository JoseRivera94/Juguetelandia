<?php
require_once 'db_connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    
    // Validaciones básicas
    $errors = [];
    
    if (empty($name)) {
        $errors['name'] = 'El nombre es requerido';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'El correo electrónico no es válido';
    } else {
        // Verificar si el email ya existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $errors['email'] = 'Este correo electrónico ya está registrado';
        }
        
        $stmt->close();
    }
    
    if (strlen($password) < 8) {
        $errors['password'] = 'La contraseña debe tener al menos 8 caracteres';
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors['password'] = 'La contraseña debe incluir mayúsculas, minúsculas y números';
    }
    
    if ($password !== $confirmPassword) {
        $errors['confirmPassword'] = 'Las contraseñas no coinciden';
    }
    
    if (empty($address)) {
        $errors['address'] = 'La dirección es requerida';
    }
    
    if (empty($phone)) {
        $errors['phone'] = 'El teléfono es requerido';
    }
    
    if (empty($errors)) {
        // Hash de la contraseña
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insertar usuario en la base de datos
        $stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password, direccion, telefono) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $hashedPassword, $address, $phone);
        
        if ($stmt->execute()) {
            // Iniciar sesión automáticamente después del registro
            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            
            $response = [
                'success' => true,
                'message' => 'Registro exitoso. Serás redirigido a la página principal.'
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Error al registrar el usuario. Por favor intenta nuevamente.'
            ];
        }
        
        $stmt->close();
    } else {
        $response = [
            'success' => false,
            'message' => 'Por favor corrige los errores en el formulario',
            'errors' => $errors
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$conn->close();
?>