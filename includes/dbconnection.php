<?php 
// Central Database Configuration & Credentials (Single Source of Truth)
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'scott');
if (!defined('DB_PASS')) define('DB_PASS', 'tiger');
if (!defined('DB_NAME')) define('DB_NAME', 'studentmsdb');

// Establish PDO connection ($dbh)
if (!isset($dbh) || !($dbh instanceof PDO)) {
    try {
        $dbh = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS, array(
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ));
    } catch (PDOException $e) {
        exit("Database Connection Error (PDO): " . $e->getMessage());
    }
}

// Establish MySQLi connection ($conn & $con alias)
if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        exit("Database Connection Error (MySQLi): " . $conn->connect_error);
    }
    $conn->set_charset("utf8");
}

// Alias $con for files expecting $con
$con = $conn;
?>
