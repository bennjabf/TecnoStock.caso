<?php
// 1. Iniciamos la sesión para poder guardar los datos del usuario logueado
session_start();

// Si el usuario ya está logueado, lo redirigimos directamente a la página principal
if (isset($_SESSION['usuario_id'])) {
    header('Location: productos.php');
    exit;
}

// Incluimos la conexión a la base de datos
require_once 'conexion.php';

$error = '';

// 2. Evaluamos si el usuario presionó el botón de Enviar (método POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo']);
    $clave  = trim($_POST['clave']);

    // Validación básica en el servidor
    if (empty($correo) || empty($clave)) {
        $error = 'Por favor, completa todos los campos.';
    } else {
        try {
            // Buscamos el usuario por su correo y verificamos que esté activo (estado = 1)
            $sql = "SELECT * FROM usuarios WHERE correo = :correo AND estado = 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':correo' => $correo]);
            $usuario = $stmt->fetch();

            // Verificamos si el usuario existe Y si la contraseña coincide con el hash
            if ($usuario && password_verify($clave, $usuario['clave'])) {
                // Guardamos la información clave en la sesión
                $_SESSION['usuario_id']     = $usuario['id_usuario'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'];

                // Redirigimos a la vista de productos
                header('Location: productos.php');
                exit;
            } else {
                $error = 'El correo o la contraseña son incorrectos (o el usuario está inactivo).';
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
    <title>TecnoStock - Iniciar Sesión</title>
    <!-- CSS de Bootstrap 5 desde CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center vh-100">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h3 class="card-title text-center mb-4 font-weight-bold">TecnoStock</h3>
                    <p class="text-muted text-center small">Control de Inventario</p>

                    <!-- Mensaje de error si las credenciales fallan -->
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger p-2 small text-center" role="alert">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form action="login.php" method="POST" novalidate>
                        <div class="mb-3">
                            <label for="correo" class="form-label">Correo electrónico</label>
                            <input type="email" name="correo" id="correo" class="form-control" placeholder="ejemplo@tecnostock.cl" required>
                        </div>

                        <div class="mb-3">
                            <label for="clave" class="form-label">Contraseña</label>
                            <input type="password" name="clave" id="clave" class="form-control" placeholder="••••••••" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Iniciar Sesión</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
