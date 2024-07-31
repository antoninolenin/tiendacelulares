<?php
require_once "configServer.php";
require_once "consulSQL.php";

function addToCart($userId, $productId, $quantity) {
    $con = ejecutarSQL::conectar();
    
    // Actualizar la sesión
    if(!isset($_SESSION['carro'])){
        $_SESSION['carro'] = array();
    }
    
    $found = false;
    foreach($_SESSION['carro'] as &$item) {
        if($item['producto'] == $productId) {
            $item['cantidad'] += $quantity;
            $found = true;
            break;
        }
    }
    
    if(!$found) {
        $_SESSION['carro'][] = array('producto' => $productId, 'cantidad' => $quantity);
    }
    
    // Actualizar la base de datos
    $stmt = $con->prepare("INSERT INTO carrito (user_id, product_id, quantity) 
                           VALUES (?, ?, ?) 
                           ON DUPLICATE KEY UPDATE quantity = quantity + ?");
    $stmt->bind_param("isii", $userId, $productId, $quantity, $quantity);
    $stmt->execute();
    
    mysqli_close($con);
}

function loadCart($userId) {
    $con = ejecutarSQL::conectar();
    
    $_SESSION['carro'] = array();
    
    $stmt = $con->prepare("SELECT product_id, quantity FROM carrito WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while($row = $result->fetch_assoc()) {
        $_SESSION['carro'][] = array('producto' => $row['product_id'], 'cantidad' => $row['quantity']);
    }
    
    mysqli_close($con);
}

function removeFromCart($userId, $productId) {
    $con = ejecutarSQL::conectar();
    
    // Eliminar de la sesión
    foreach($_SESSION['carro'] as $key => $item) {
        if($item['producto'] == $productId) {
            unset($_SESSION['carro'][$key]);
            break;
        }
    }
    
    // Eliminar de la base de datos
    $stmt = $con->prepare("DELETE FROM carrito WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("is", $userId, $productId);
    $stmt->execute();
    
    mysqli_close($con);
}

function clearCart($userId) {
    $con = ejecutarSQL::conectar();
    
    // Vaciar la sesión
    $_SESSION['carro'] = array();
    
    // Vaciar la base de datos
    $stmt = $con->prepare("DELETE FROM carrito WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    mysqli_close($con);
}