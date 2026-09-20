<?php
session_start();

// Si no ha iniciado sesión, redirigir al login
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'conexion.php';

$error = '';

// 1. Cargar la lista de categorías activas para el <select>
try {
    $stmtCat = $pdo->query("SELECT id_categoria, nombre FROM categorías WHERE estado = 1 ORDER BY nombre ASC");
    $categorias = $stmtCat->fetchAll();
} catch (PDOException $e) {
    die("Error al cargar categorías: " . $e->getMessage());
}

// 2. Procesar el formulario cuando se envía por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo       = trim($_POST['codigo']);
    $nombre       = trim($_POST['nombre']);
    $descripcion  = trim($_POST['descripcion']);
    $precio       = floatval($_POST['precio']);
    $stock_actual = intval($_POST['stock_actual']);
    $stock_minimo = intval($_POST['stock_minimo']);
    $id_categoria = intval($_POST['id_categoria']);

    // Validaciones en servidor (PHP)
    if (empty($codigo) || empty($nombre) || empty($id_categoria)) {
        $error = 'El código, el nombre y la categoría son obligatorios.';
    } elseif ($precio < 0 || $stock_actual < 0 || $stock_minimo < 0) {
        $error = 'El precio y los valores de stock no pueden ser negativos.';
    } else {
        try {
            // Verificar si el código ya existe
            $stmtCheck = $pdo->prepare("SELECT id_producto FROM productos WHERE codigo = :codigo");
            $stmtCheck->execute([':codigo' => $codigo]);

            if ($stmtCheck->fetch()) {
                $error = 'El código ingresado ya pertenece a otro producto.';
            } else {
                // Inserción segura con PDO
                $sql = "INSERT INTO productos (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, estado, id_categoria) 
                        VALUES (:codigo, :nombre, :descripcion, :precio, :stock_actual, :stock_minimo, 1, :id_categoria)";
                
                $stmtInsert = $pdo->prepare($sql);
                $stmtInsert->execute([
                    ':codigo'       => $codigo,
                    ':nombre'       => $nombre,
                    ':descripcion'  => $descripcion,
                    ':precio'       => $precio,
                    ':stock_actual' => $stock_actual,
                    ':stock_minimo' => $stock_minimo,
                    ':id_categoria' => $id_categoria
                ]);

                header('Location: productos.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Error en el sistema: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TecnoStock - Crear Producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Registrar Nuevo Producto</h4>
                </div>
                <div class="card-body p-4">

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form action="crear_producto.php" method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="codigo" class="form-label">Código *</label>
                                <input type="text" name="codigo" id="codigo" class="form-control" placeholder="Ej: AUD-001" required>
                            </div>

                            <div class="col-md-6">
                                <label for="id_categoria" class="form-label">Categoría *</label>
                                <select name="id_categoria" id="id_categoria" class="form-select" required>
                                    <option value="">-- Seleccionar --</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?= $cat['id_categoria'] ?>"><?= htmlspecialchars($cat['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-12">
                                <label for="nombre" class="form-label">Nombre del Producto *</label>
                                <input type="text" name="nombre" id="nombre" class="form-control" placeholder="Ej: Audífonos Bluetooth Pro" required>
                            </div>

                            <div class="col-md-12">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea name="descripcion" id="descripcion" class="form-rows-3 form-control" placeholder="Detalles del producto..."></textarea>
                            </div>

                            <div class="col-md-4">
                                <label for="precio" class="form-label">Precio ($) *</label>
                                <input type="number" step="0.01" min="0" name="precio" id="precio" class="form-control" required>
                            </div>

                            <div class="col-md-4">
                                <label for="stock_actual" class="form-label">Stock Inicial *</label>
                                <input type="number" min="0" name="stock_actual" id="stock_actual" class="form-control" value="0" required>
                            </div>

                            <div class="col-md-4">
                                <label for="stock_minimo" class="form-label">Stock Mínimo *</label>
                                <input type="number" min="0" name="stock_minimo" id="stock_minimo" class="form-control" value="5" required>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="productos.php" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-success">Guardar Producto</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
