<?php
// 1. VARIABLES DE CONFIGURACIÓN
// Guardamos los datos de acceso en variables (palabras que empiezan con $) para no repetirlos a mano.
$host = 'localhost';       // Significa "mi propio computador" (donde corre XAMPP).
$dbname = 'tecnostock'; // El nombre exacto que le diste a la BD en phpMyAdmin.
$username = 'root';        // Usuario por defecto de XAMPP (administrador).
$password = '';            // En XAMPP la contraseña por defecto viene vacía.

// 2. BLOQUE DE SEGURIDAD (TRY - CATCH)
// "try" significa "intenta hacer esto". Si ocurre un fallo, "catch" lo atrapa para que la página no se caiga de forma fea.
try {

    // 3. CREACIÓN DE LA CONEXIÓN CON PDO
    // PDO (PHP Data Objects) es la herramienta moderna y segura de PHP para conectarse a bases de datos.
    // DSN (Data Source Name): Especifica el tipo de BD (mysql), el host, la base de datos y la codificación utf8 (para tildes y eñes).
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password, [
        
        // Opción A: Manejo de errores
        // Si hay una falla en una consulta SQL, PHP nos dirá exactamente cuál fue el error.
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        
        // Opción B: Modo de lectura por defecto
        // Nos entregará los datos de la base como un "arreglo asociativo" (ej: $producto['nombre']), lo cual es muy fácil de usar.
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        
        // Opción C: Consultas preparadas reales (Seguridad contra Inyección SQL)
        // Evita que un usuario malintencionado pueda hackear la base de datos a través de los formularios.
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    // Si llega a este punto sin fallar, la variable $pdo ya tiene el "puente" activo y listo para usarse.

} catch (PDOException $e) {
    // Si algo sale mal (ej: MySQL apagado en XAMPP o nombre de BD incorrecto), entra a este bloque.
    // die() detiene el programa e imprime el mensaje de error.
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>
