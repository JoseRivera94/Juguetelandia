<?php
session_start();

// Verificar autenticación
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

// Obtener información del usuario actual
function getCurrentUser() {
    if (isAuthenticated()) {
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'role' => $_SESSION['user_role'] ?? 'customer'
        ];
    }
    return null;
}

// Verificar rol de administrador
function isAdmin() {
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}

// Inicializar carrito si no existe
if (isAuthenticated() && !isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Función para agregar items al carrito
function addToCart($toyId, $quantity = 1) {
    if (!isAuthenticated()) {
        return false;
    }
    
    if (isset($_SESSION['cart'][$toyId])) {
        $_SESSION['cart'][$toyId] += $quantity;
    } else {
        $_SESSION['cart'][$toyId] = $quantity;
    }
    
    return true;
}

// Función para actualizar cantidad en el carrito
function updateCartItem($toyId, $quantity) {
    if (!isAuthenticated()) {
        return false;
    }
    
    if ($quantity <= 0) {
        unset($_SESSION['cart'][$toyId]);
    } else {
        $_SESSION['cart'][$toyId] = $quantity;
    }
    
    return true;
}

// Función para eliminar item del carrito
function removeFromCart($toyId) {
    if (!isAuthenticated()) {
        return false;
    }
    
    if (isset($_SESSION['cart'][$toyId])) {
        unset($_SESSION['cart'][$toyId]);
        return true;
    }
    
    return false;
}

// Función para obtener el conteo de items en el carrito
function getCartCount() {
    if (!isAuthenticated() || !isset($_SESSION['cart'])) {
        return 0;
    }
    
    return array_sum($_SESSION['cart']);
}

// Función para obtener el contenido del carrito
function getCartContents() {
    if (!isAuthenticated() || empty($_SESSION['cart'])) {
        return [];
    }
    
    return $_SESSION['cart'];
}
?>