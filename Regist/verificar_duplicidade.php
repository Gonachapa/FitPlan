<?php
// Ficheiro: verificar_duplicidade.php

// Define as credenciais de acesso à base de dados
$servername = "localhost";
$username = "root"; 
$password = "";     
$dbname = "fitness_app";

// Resposta padrão (assume que não é duplicado)
$response = ['is_duplicate' => false];
header('Content-Type: application/json'); // Garante que a resposta é JSON

// --- 1. Conexão à Base de Dados ---
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    // Em caso de falha na DB, retorna erro JSON e termina
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

// --- 2. Obter Dados (Nome ou Email) ---
$campo = '';
$valor = '';

if (isset($_POST['nome'])) {
    $campo = 'nome';
    $valor = $_POST['nome'];
} elseif (isset($_POST['email'])) {
    $campo = 'email';
    $valor = $_POST['email'];
}

// --- 3. Executar Consulta ---
if ($campo && $valor) {
    // Consulta preparada para verificar se o valor já existe
    $stmt = $conn->prepare("SELECT 1 FROM Utilizador WHERE {$campo} = ? LIMIT 1");
    $stmt->bind_param("s", $valor);
    $stmt->execute();
    $stmt->store_result();

    // Se o número de linhas for 1 ou mais, é uma duplicação.
    if ($stmt->num_rows > 0) {
        $response['is_duplicate'] = true;
    }

    $stmt->close();
}

$conn->close();

// Devolve a resposta JSON
echo json_encode($response);
?>