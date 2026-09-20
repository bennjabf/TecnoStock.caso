<?php
session_start();

// Si no ha iniciado sesión, redirigir al login
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'conexion.php';

$error = '';
$id_producto = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_producto <= 0) {
    header('Location: productos.php');
    exit;
}

// 1. Cargar las categorías activas (usando la tabla 'categoria' corregida)
try {
    $stmtCat = $pdo->query("SELECT * FROM categoria WHERE estado = 1 ORDER BY nombre ASC");
    $categorias = $stmtCat->fetchAll();
} catch (PDOException $e) {
    die("Error al cargar categorías: " . $e->getMessage());
}

// 2. Cargar los datos actuales del producto
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

// 3. Procesar la actualización cuando se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre       = trim($_POST['nombre'] ?? '');
    $codigo       = trim($_POST['codigo'] ?? '');
    $id_categoria = intval($_POST['id_categoria'] ?? 0);
    $precio       = floatval($_POST['precio'] ?? 0);
    $stock_actual = intval($_POST['stock_actual'] ?? 0);
    $stock_minimo = intval($_POST['stock_minimo'] ?? 0);
    $descripcion  = trim($_POST['descripcion'] ?? '');

    // Validaciones del lado del servidor (Reglas de Negocio)
    if (empty($nombre) || empty($codigo) || $id_categoria <= 0) {
        $error = 'Por favor completa los campos obligatorios (*).';
    } elseif ($precio < 0 || $stock_actual < 0 || $stock_minimo < 0) {
        $error = 'El precio, el stock actual y el stock mínimo no pueden ser valores negativos.';
    } else {
        try {
            // Verificar que el código no lo tenga OTRO producto
            $stmtCheck = $pdo->prepare("SELECT id_producto FROM productos WHERE codigo = :codigo AND id_producto != :id");
            $stmtCheck->execute([':codigo' => $codigo, ':id' => $id_producto]);
            
            if ($stmtCheck->fetch()) {
                $error = 'El código ingresado ya pertenece a otro producto.';
            } else {
                // Actualizar el producto en la base de datos
                $sql = "UPDATE productos 
                        SET codigo = :codigo, 
                            nombre = :nombre, 
                            descripcion = :descripcion, 
                            precio = :precio, 
                            stock_actual = :stock_actual, 
                            stock_minimo = :stock_minimo, 
                            id_categoria = :id_categoria 
                        WHERE id_producto = :id";
                $stmtUpdate = $pdo->prepare($sql);
                $stmtUpdate->execute([
                    ':codigo'       => $codigo,
                    ':nombre'       => $nombre,
                    ':descripcion'  => $descripcion,
                    ':precio'       => $precio,
                    ':stock_actual' => $stock_actual,
                    ':stock_minimo' => $stock_minimo,
                    ':id_categoria' => $id_categoria,
                    ':id'           => $id_producto
                ]);

                header('Location: productos.php?exito=1');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Error al actualizar el producto: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TecnoStock - Editar Producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0">Editar Producto</h4>
                </div>
                <div class="card-body p-4">

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form action="editar_producto.php?id=<?php echo $id_producto; ?>" method="POST">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="codigo" class="form-label">Código del Producto *</label>
                                <input type="text" name="codigo" id="codigo" class="form-control" 
                                       value="<?php echo htmlspecialchars($producto['codigo']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="id_categoria" class="form-label">Categoría *</label>
                                <select name="id_categoria" id="id_categoria" class="form-select" required>
                                    <option value="">-- Seleccionar Categoría --</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?php echo $cat['id_categoria']; ?>" 
                                            <?php echo ($cat['id_categoria'] == $producto['id_categoria']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre del Producto *</label>
                            <input type="text" name="nombre" id="nombre" class="form-control" 
                                   value="<?php echo htmlspecialchars($producto['nombre']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea name="descripcion" id="descripcion" class="form-control" rows="3"><?php echo htmlspecialchars($producto['descripcion'] ?? ''); ?></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="precio" class="form-label">Precio ($) *</label>
                                <input type="number" step="0.01" min="0" name="precio" id="precio" class="form-control" 
                                       value="<?php echo $producto['precio']; ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="stock_actual" class="form-label">Stock Actual *</label>
                                <input type="number" min="0" name="stock_actual" id="stock_actual" class="form-control" 
                                       value="<?php echo $producto['stock_actual']; ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="stock_minimo" class="form-label">Stock Mínimo *</label>
                                <input type="number" min="0" name="stock_minimo" id="stock_minimo" class="form-control" 
                                       value="<?php echo $producto['stock_minimo']; ?>" required>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="productos.php" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-warning">Guardar Cambios</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
