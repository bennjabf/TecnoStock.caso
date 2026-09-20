<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'conexion.php';

try {
    // Consultar solo los productos inactivos (estado = 0)
    $sql = "SELECT p.*, c.nombre AS categoria_nombre 
            FROM productos p 
            LEFT JOIN categoria c ON p.id_categoria = c.id_categoria 
            WHERE p.estado = 0 
            ORDER BY p.nombre ASC";
    $stmt = $pdo->query($sql);
    $productos_inactivos = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error al consultar productos inactivos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TecnoStock - Productos Desactivados</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Productos Desactivados</h2>
        <a href="productos.php" class="btn btn-secondary">← Volver al Catálogo</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Stock Actual</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($productos_inactivos)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    No hay productos desactivados en el sistema.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($productos_inactivos as $prod): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($prod['codigo']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($prod['nombre']); ?></td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($prod['categoria_nombre'] ?? 'Sin Categoría'); ?></span></td>
                                    <td>$<?php echo number_format($prod['precio'], 0, ',', '.'); ?></td>
                                    <td><?php echo $prod['stock_actual']; ?></td>
                                    <td>
                                        <a href="reactivar_producto.php?id=<?php echo $prod['id_producto']; ?>" 
                                           class="btn btn-sm btn-success"
                                           onclick="return confirm('¿Desea reactivar este producto para que vuelva a estar disponible en el catálogo?');">
                                            Reactivar
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
