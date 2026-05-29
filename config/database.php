<?php
/**
 * Capa de Persistencia - Configuración de Base de Datos (PDO)
 * Sigue principios Clean Code y Seguridad por Capas.
 */

// Centralización de credenciales por variables de entorno ficticias/locales
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Cambiar si tu entorno local maneja contraseña
define('DB_NAME', '01_calif');
define('DB_CHARSET', 'utf8mb4');

class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $name = DB_NAME;
    private $charset = DB_CHARSET;
    private $pdo;

    public function connect() {
        if ($this->pdo === null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->name};charset={$this->charset}";
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                
                $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
            } catch (PDOException $e) {
                // Manejo de excepción global controlado para evitar fugas de información en producción
                error_log("Database Connection Error: " . $e->getMessage());
                die("Error crítico de infraestructura. Por favor, intente más tarde.");
            }
        }
        return $this->pdo;
    }
}