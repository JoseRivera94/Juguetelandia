<?php
require_once 'db_connection.php';
require_once 'session_control.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Buscar usuario en la base de datos
    $stmt = $conn->prepare("SELECT id, nombre, email, password, role FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verificar contraseña
        if (password_verify($password, $user['password'])) {
            // Iniciar sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nombre'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            $response = [
                'success' => true,
                'message' => 'Inicio de sesión exitoso'
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Credenciales incorrectas'
            ];
        }
    } else {
        $response = [
            'success' => false,
            'message' => 'Credenciales incorrectas'
        ];
    }
    
    $stmt->close();
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$conn->close();
?>