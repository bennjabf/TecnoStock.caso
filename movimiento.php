<?php
session_start();

// Proteger vista: requiere inicio de sesión
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'conexion.php';

$error = '';
$id_producto = $_GET['id'] ?? null;

if (!$id_producto) {
    header('Location: productos.php');
    exit;
}

// Obtener datos del producto seleccionado
try {
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE id_producto = :id AND estado = 1");
    $stmt->execute([':id' => $id_producto]);
    $producto = $stmt->fetch();

    if (!$producto) {
        header('Location: productos.php');
        exit;
    }
} catch (PDOException $e) {
    die("Error al consultar el producto: " . $e->getMessage());
}

// Procesar el movimiento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo        = $_POST['tipo'] ?? '';
    $cantidad    = intval($_POST['cantidad'] ?? 0);
    $observacion = trim($_POST['observacion'] ?? '');
    $id_usuario  = $_SESSION['usuario_id'];

    // Validaciones en servidor
    if (!in_array($tipo, ['entrada', 'salida'])) {
        $error = 'El tipo de movimiento debe ser Entrada o Salida.';
    } elseif ($cantidad <= 0) {
        $error = 'La cantidad debe ser un número entero mayor a cero.';
    } elseif ($tipo === 'salida' && $cantidad > $producto['stock_actual']) {
        // Regla de negocio: Una salida no puede dejar el stock bajo cero
        $error = "Movimiento denegado: No hay suficiente stock disponible. Stock actual: {$producto['stock_actual']}.";
    } else {
        try {
            // Iniciar Transacción PDO
            $pdo->beginTransaction();

            // 1. Insertar el movimiento en la tabla movimientos
            $sql_mov = "INSERT INTO movimientos (tipo, cantidad, observacion, id_producto, id_usuario) 
                        VALUES (:tipo, :cantidad, :observacion, :id_producto, :id_usuario)";
            $stmt_mov = $pdo->prepare($sql_mov);
            $stmt_mov->execute([
                ':tipo'        => $tipo,
                ':cantidad'    => $cantidad,
                ':observacion' => $observacion,
                ':id_producto' => $id_producto,
                ':id_usuario'  => $id_usuario
            ]);

            // 2. Calcular nuevo stock
            $nuevo_stock = ($tipo === 'entrada') 
                ? $producto['stock_actual'] + $cantidad 
                : $producto['stock_actual'] - $cantidad;

            // 3. Actualizar la tabla productos
            $sql_stock = "UPDATE productos SET stock_actual = :nuevo_stock WHERE id_producto = :id";
            $stmt_stock = $pdo->prepare($sql_stock);
            $stmt_stock->execute([
                ':nuevo_stock' => $nuevo_stock,
                ':id'          => $id_producto
            ]);

            // Confirmar la transacción
            $pdo->commit();

            header('Location: productos.php?msg=movimiento_ok');
            exit;

        } catch (PDOException $e) {
            // Si algo falla, revertir todos los cambios
            $pdo->rollBack();
            $error = 'Error al registrar el movimiento: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movimiento de Inventario - TecnoStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Registrar Movimiento</h5>
                    <a href="productos.php" class="btn btn-sm btn-light">Volver al Catálogo</a>
                </div>
                <div class="card-body p-4">

                    <!-- Información del Producto -->
                    <div class="alert alert-secondary mb-3">
                        <div class="row">
                            <div class="col-6"><strong>Producto:</strong> <?= htmlspecialchars($producto['nombre']) ?></div>
                            <div class="col-6"><strong>Código:</strong> <?= htmlspecialchars($producto['codigo']) ?></div>
                        </div>
                        <hr class="my-2">
                        <div>
                            <strong>Stock Actual:</strong> 
                            <span class="badge bg-primary fs-6"><?= $producto['stock_actual'] ?> unidades</span>
                        </div>
                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form action="movimiento.php?id=<?= $id_producto ?>" method="POST">
                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo de Movimiento *</label>
                            <select name="tipo" id="tipo" class="form-select" required>
                                <option value="entrada">Entrada (+ Sumar al stock)</option>
                                <option value="salida">Salida (- Restar del stock)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="cantidad" class="form-label">Cantidad *</label>
                            <input type="number" name="cantidad" id="cantidad" class="form-control" min="1" placeholder="Ej: 5" required>
                        </div>

                        <div class="mb-3">
                            <label for="observacion" class="form-label">Observación / Motivo</label>
                            <textarea name="observacion" id="observacion" class="form-control" rows="3" placeholder="Ej: Recepción de pedido, Venta cliente #104..."></textarea>
                        </div>

                        <div class="text-end">
                            <a href="productos.php" class="btn btn-secondary me-2">Cancelar</a>
                            <button type="submit" class="btn btn-info text-white">Guardar Movimiento</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
