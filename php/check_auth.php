<?php
require_once 'session_control.php';

header('Content-Type: application/json');

if (isAuthenticated()) {
    echo json_encode([
        'authenticated' => true,
        'username' => $_SESSION['user_name'],
        'cartCount' => getCartCount()
    ]);
} else {
    echo json_encode([
        'authenticated' => false
    ]);
}
?>