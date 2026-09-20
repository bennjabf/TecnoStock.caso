<?php
session_start();

// Si no ha iniciado sesión, redirigir al login
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'conexion.php';

$error = '';

// 1. Cargar las categorías activas (usando 'categoria')
try {
    $stmtCat = $pdo->query("SELECT * FROM categoria WHERE estado = 1 ORDER BY nombre ASC");
    $categorias = $stmtCat->fetchAll();
} catch (PDOException $e) {
    die("Error al cargar categorías: " . $e->getMessage());
}

// 2. Procesar la inserción cuando se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo       = trim($_POST['codigo'] ?? '');
    $nombre       = trim($_POST['nombre'] ?? '');
    $id_categoria = intval($_POST['id_categoria'] ?? 0);
    $precio       = floatval($_POST['precio'] ?? 0);
    $stock_actual = intval($_POST['stock_actual'] ?? 0);
    $stock_minimo = intval($_POST['stock_minimo'] ?? 0);
    $descripcion  = trim($_POST['descripcion'] ?? '');

    // Validaciones del lado del servidor (Reglas de Negocio)
    if (empty($codigo) || empty($nombre) || $id_categoria <= 0) {
        $error = 'Por favor completa los campos obligatorios (*).';
    } elseif ($precio < 0 || $stock_actual < 0 || $stock_minimo < 0) {
        $error = 'El precio, el stock actual y el stock mínimo no pueden ser valores negativos.';
    } else {
        try {
            // Verificar que el código sea único
            $stmtCheck = $pdo->prepare("SELECT id_producto FROM productos WHERE codigo = :codigo");
            $stmtCheck->execute([':codigo' => $codigo]);
            
            if ($stmtCheck->fetch()) {
                $error = 'El código ingresado ya está registrado en otro producto.';
            } else {
                // Insertar el nuevo producto en la tabla 'productos'
                $sql = "INSERT INTO productos (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, id_categoria, estado) 
                        VALUES (:codigo, :nombre, :descripcion, :precio, :stock_actual, :stock_minimo, :id_categoria, 1)";
                
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

                header('Location: productos.php?exito=1');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Error al registrar el producto: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TecnoStock - Nuevo Producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Registrar Nuevo Producto</h4>
                </div>
                <div class="card-body p-4">

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form action="agregar_producto.php" method="POST">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="codigo" class="form-label">Código del Producto *</label>
                                <input type="text" name="codigo" id="codigo" class="form-control" placeholder="Ej: AUD-001" 
                                       value="<?php echo htmlspecialchars($_POST['codigo'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="id_categoria" class="form-label">Categoría *</label>
                                <select name="id_categoria" id="id_categoria" class="form-select" required>
                                    <option value="">-- Seleccionar Categoría --</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?php echo $cat['id_categoria']; ?>"
                                            <?php echo (isset($_POST['id_categoria']) && $_POST['id_categoria'] == $cat['id_categoria']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre del Producto *</label>
                            <input type="text" name="nombre" id="nombre" class="form-control" placeholder="Ej: Audífonos Bluetooth" 
                                   value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea name="descripcion" id="descripcion" class="form-control" rows="3" placeholder="Descripción opcional del producto..."><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="precio" class="form-label">Precio ($) *</label>
                                <input type="number" step="0.01" min="0" name="precio" id="precio" class="form-control" placeholder="0.00" 
                                       value="<?php echo htmlspecialchars($_POST['precio'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="stock_actual" class="form-label">Stock Inicial *</label>
                                <input type="number" min="0" name="stock_actual" id="stock_actual" class="form-control" value="0" required>
                            </div>
                            <div class="col-md-4">
                                <label for="stock_minimo" class="form-label">Stock Mínimo *</label>
                                <input type="number" min="0" name="stock_minimo" id="stock_minimo" class="form-control" value="0" required>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
