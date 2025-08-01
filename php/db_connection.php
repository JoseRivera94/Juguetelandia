<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "TOYS";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $database);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Establecer el conjunto de caracteres
$conn->set_charset("utf8mb4");

// Crear tablas si no existen
function initializeDatabase($conn) {
    $sql = "
    CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        direccion TEXT NOT NULL,
        telefono VARCHAR(20) NOT NULL,
        role ENUM('customer', 'admin') DEFAULT 'customer',
        fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    
    CREATE TABLE IF NOT EXISTS juguetes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        descripcion TEXT NOT NULL,
        edad VARCHAR(20) NOT NULL,
        precio DECIMAL(10, 2) NOT NULL,
        cantidad_inventario INT NOT NULL,
        imagen VARCHAR(255),
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    
    CREATE TABLE IF NOT EXISTS carrito (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        juguete_id INT NOT NULL,
        cantidad INT NOT NULL,
        monto_total DECIMAL(10, 2) NOT NULL,
        fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
        FOREIGN KEY (juguete_id) REFERENCES juguetes(id) ON DELETE CASCADE
    );
    
    CREATE TABLE IF NOT EXISTS pedidos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        fecha_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        estado ENUM('pendiente', 'procesando', 'enviado', 'entregado', 'cancelado') DEFAULT 'pendiente',
        total DECIMAL(10, 2) NOT NULL,
        direccion_envio TEXT NOT NULL,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    );
    
    CREATE TABLE IF NOT EXISTS detalles_pedido (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pedido_id INT NOT NULL,
        juguete_id INT NOT NULL,
        cantidad INT NOT NULL,
        precio_unitario DECIMAL(10, 2) NOT NULL,
        subtotal DECIMAL(10, 2) NOT NULL,
        FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
        FOREIGN KEY (juguete_id) REFERENCES juguetes(id)
    );
    ";
    
    if ($conn->multi_query($sql)) {
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());
    }
}

// Inicializar la base de datos al incluir este archivo
initializeDatabase($conn);
?>