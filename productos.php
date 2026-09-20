<?php
session_start();

// Proteger vista: requiere inicio de sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'conexion.php';

$busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

try {
    if (!empty($busqueda)) {
        // Marcadores independientes (:buscar1 y :buscar2)
        $sql = "SELECT p.*, c.nombre AS categoria_nombre 
                FROM productos p 
                LEFT JOIN categoria c ON p.id_categoria = c.id_categoria 
                WHERE (p.codigo LIKE :buscar1 OR p.nombre LIKE :buscar2) 
                  AND p.estado = 1 
                ORDER BY p.nombre ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':buscar1' => '%' . $busqueda . '%',
            ':buscar2' => '%' . $busqueda . '%'
        ]);
    } else {
        $sql = "SELECT p.*, c.nombre AS categoria_nombre 
                FROM productos p 
                LEFT JOIN categoria c ON p.id_categoria = c.id_categoria 
                WHERE p.estado = 1 
                ORDER BY p.nombre ASC";
        $stmt = $pdo->query($sql);
    }
    
    $productos = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error en la consulta: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TecnoStock - Catálogo de Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar-brand { font-weight: bold; font-size: 1.5rem; }
        .table-dark-header th { background-color: #212529; color: #ffffff; }
    </style>
</head>
<body class="bg-light">

<!-- Barra de navegación superior -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark px-4 mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="productos.php">TecnoStock</a>
        <div class="d-flex align-items-center">
            <span class="text-white me-3">
                Hola, <strong><?php echo htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Administrador TecnoStock'); ?></strong>
            </span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Cerrar Sesión</a>
        </div>
    </div>
</nav>

<div class="container pb-5">

    <!-- Encabezado y botones de acción -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-secondary">Catálogo de Productos</h2>
        <div>
            <a href="productos_inactivos.php" class="btn btn-outline-secondary me-2">Ver Desactivados</a>
            <a href="agregar_producto.php" class="btn btn-success fw-semibold">+ Nuevo Producto</a>
        </div>
    </div>

    <!-- Barra de búsqueda -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form action="productos.php" method="GET" class="row g-2">
                <div class="col">
                    <input type="text" name="buscar" class="form-control" 
                           placeholder="Buscar por código o nombre..." 
                           value="<?php echo htmlspecialchars($busqueda); ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary px-4">Buscar</button>
                    <?php if (!empty($busqueda)): ?>
                        <a href="productos.php" class="btn btn-outline-secondary">Limpiar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de productos -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark-header">
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th class="text-center">Stock Actual</th>
                            <th class="text-center">Stock Mín.</th>
                            <th class="text-center">Estado Stock</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($productos)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    No se encontraron productos registrados.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($productos as $prod): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($prod['codigo']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($prod['nombre']); ?></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo htmlspecialchars($prod['categoria_nombre'] ?? 'Sin Categoría'); ?>
                                        </span>
                                    </td>
                                    <td>$<?php echo number_format($prod['precio'], 0, ',', '.'); ?></td>
                                    <td class="text-center fw-bold"><?php echo $prod['stock_actual']; ?></td>
                                    <td class="text-center text-muted"><?php echo $prod['stock_minimo']; ?></td>
                                    <td class="text-center">
                                        <?php if ($prod['stock_actual'] < $prod['stock_minimo']): ?>
                                            <span class="badge bg-danger px-3 py-2">⚠️ Stock Bajo</span>
                                        <?php else: ?>
                                            <span class="badge bg-success px-3 py-2">Normal</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="editar_producto.php?id=<?php echo $prod['id_producto']; ?>" 
                                           class="btn btn-sm btn-warning fw-semibold text-dark mb-1">
                                            Editar
                                        </a>
                                        <a href="movimiento.php?id=<?php echo $prod['id_producto']; ?>" 
                                           class="btn btn-sm btn-info text-white fw-semibold mb-1">
                                            Stock +/-
                                        </a>
                                        <a href="desactivar_producto.php?id=<?php echo $prod['id_producto']; ?>" 
                                           class="btn btn-sm btn-danger fw-semibold mb-1"
                                           onclick="return confirm('¿Está seguro de que desea desactivar este producto?');">
                                            Desactivar
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
