<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'conexion.php';

$id_producto = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_producto > 0) {
    try {
        // Borrado lógico: cambiamos el estado a 0
        $sql = "UPDATE productos SET estado = 0 WHERE id_producto = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id_producto]);
    } catch (PDOException $e) {
        die("Error al desactivar el producto: " . $e->getMessage());
    }
}

header('Location: productos.php');
exit;
