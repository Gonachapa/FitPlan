<?php
/**
 * Script para adicionar suporte ao Google Auth
 * Execute este ficheiro uma única vez: http://localhost/FitPlan/setup_google_auth.php
 */

$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "fitness_app";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Verificar se a coluna já existe
$sql_check = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='Utilizador' AND COLUMN_NAME='google_id'";
$result = $conn->query($sql_check);

if ($result->num_rows === 0) {
    // Adicionar a coluna
    $sql = "ALTER TABLE Utilizador ADD COLUMN google_id VARCHAR(255) UNIQUE";
    
    if ($conn->query($sql) === TRUE) {
        echo "<h2 style='color: green;'>✓ Coluna 'google_id' adicionada com sucesso!</h2>";
        echo "<p>Agora pode usar o login com Google.</p>";
    } else {
        echo "<h2 style='color: red;'>✗ Erro ao adicionar coluna: " . $conn->error . "</h2>";
    }
} else {
    echo "<h2 style='color: blue;'>✓ A coluna 'google_id' já existe!</h2>";
    echo "<p>O sistema de Google Auth já está configurado.</p>";
}

$conn->close();
?>
