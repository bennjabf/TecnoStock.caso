<?php
// Incluimos el archivo de conexión que ya probamos
require_once 'conexion.php';

// Datos del usuario que queremos crear
$nombre = 'Administrador TecnoStock';
$correo = 'admin@tecnostock.cl';
$clave_plana = 'admin123'; // Esta será la contraseña para entrar

// Encriptamos la contraseña de forma segura
$clave_encriptada = password_hash($clave_plana, PASSWORD_DEFAULT);

try {
    // Consulta preparada SQL (evita inyecciones SQL)
    $sql = "INSERT INTO usuarios (nombre, correo, clave, estado) VALUES (:nombre, :correo, :clave, 1)";
    
    // Preparamos la consulta
    $stmt = $pdo->prepare($sql);
    
    // Ejecutamos pasando los datos
    $stmt->execute([
        ':nombre' => $nombre,
        ':correo' => $correo,
        ':clave'  => $clave_encriptada
    ]);

    echo "<h1>¡Usuario creado con éxito!</h1>";
    echo "<p><strong>Correo:</strong> admin@tecnostock.cl</p>";
    echo "<p><strong>Contraseña:</strong> admin123</p>";

} catch (PDOException $e) {
    // Si el correo ya existe, nos avisará gracias al índice UNIQUE
    echo "Error al crear usuario: " . $e->getMessage();
}
?>
