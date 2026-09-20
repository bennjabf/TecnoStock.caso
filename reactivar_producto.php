<?php
session_start();
require_once 'conexion.php';

$id_producto = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_producto > 0) {
    try {
        // Volvemos el estado a 1 (Activo)
        $sql = "UPDATE productos SET estado = 1 WHERE id_producto = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id_producto]);
    } catch (PDOException $e) {
        die("Error al reactivar el producto: " . $e->getMessage());
    }
}

// Redirigir al catálogo de productos activos
header('Location: productos.php');
exit;
