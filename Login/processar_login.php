<?php
// Ficheiro: processar_login.php

session_start();

// ----------------------------------------------------
// 1. Configuração da Base de Dados
// Certifique-se que estas credenciais correspondem ao seu processar_registo.php
$servername = "localhost";
$username = "root"; 
$password = "";     
$dbname = "fitness_app";
// ----------------------------------------------------

// Verifica se o método é POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit;
}

// Receber dados do formulário
$identifier = $_POST['identifier'] ?? ''; // Pode ser email ou nome
$password_raw = $_POST['password'] ?? '';

// Validação básica
if (empty($identifier) || empty($password_raw)) {
    $_SESSION['login_error'] = "Por favor, preencha o identificador e a palavra-passe.";
    header("Location: login.php");
    exit;
}

// ----------------------------------------------------
// 2. Conexão à Base de Dados
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    $_SESSION['login_error'] = "Erro de sistema: Falha na conexão com a base de dados.";
    header("Location: login.php");
    exit;
}

// ----------------------------------------------------
// 3. Determinar o campo de pesquisa (Email ou Nome)

// Verifica se o identificador parece um email
$is_email = filter_var($identifier, FILTER_VALIDATE_EMAIL);

// Seleciona a coluna para pesquisar
$search_column = $is_email ? 'email' : 'nome';
$sql = "SELECT id_utilizador, nome, email, senha_hash FROM Utilizador WHERE {$search_column} = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $identifier);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Utilizador não encontrado
    $stmt->close();
    $conn->close();
    $_SESSION['login_error'] = "Credenciais inválidas. Verifique o identificador e a palavra-passe.";
    header("Location: login.php");
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();
$conn->close();

// ----------------------------------------------------
// 4. Verificação da Palavra-passe

// Usa password_verify para comparar a palavra-passe crua com o hash guardado
if (password_verify($password_raw, $user['senha_hash'])) {
    
    // SUCESSO NO LOGIN: Criar a sessão
    
    // Impedir ataques de fixação de sessão
    session_regenerate_id(true); 

    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $user['id_utilizador'];
    $_SESSION['user_nome'] = $user['nome'];
    $_SESSION['user_email'] = $user['email'];

    // Opcional: Mensagem de sucesso (Pode ser removida para redirecionar diretamente)
    $_SESSION['login_success'] = "Bem-vindo(a), " . htmlspecialchars($user['nome']) . "!";
    
    // Redirecionar para o dashboard (Assumindo que está em ../dashboard.html)
    header("Location: ../dashboard.html"); 
    exit;

} else {
    // FALHA NA PALAVRA-PASSE
    $_SESSION['login_error'] = "Credenciais inválidas. Verifique as suas credenciais e tente denovo.";
    header("Location: login.php");
    exit;
}
?>