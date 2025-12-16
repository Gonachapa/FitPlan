<?php
// Ficheiro: Login/processar_login.php

session_start();

// ----------------------------------------------------
// 1. Configuração da Base de Dados
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
$identifier = $_POST['identifier'] ?? ''; 
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

$is_email = filter_var($identifier, FILTER_VALIDATE_EMAIL);
$search_column = $is_email ? 'email' : 'nome';

// Consulta com 'foto_perfil'
$sql = "SELECT id_utilizador, nome, email, senha_hash, foto_perfil FROM Utilizador WHERE {$search_column} = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $identifier);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
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
if (password_verify($password_raw, $user['senha_hash'])) {
    
    // SUCESSO NO LOGIN: Criar a sessão
    session_regenerate_id(true); 

    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $user['id_utilizador'];
    
    // VARIÁVEIS PARA A SIDEBAR (Usando 'foto_perfil'):
    $_SESSION['user_nome'] = $user['nome'];
    $_SESSION['user_avatar'] = $user['foto_perfil']; // Armazena o caminho da imagem
    
    $_SESSION['user_email'] = $user['email'];

    $_SESSION['login_success'] = "Bem-vindo(a), " . htmlspecialchars($user['nome']) . "!";
    
    // CORREÇÃO DO REDIRECIONAMENTO para o ficheiro .php
    header("Location: ../dashboard.php"); 
    exit;

} else {
    // FALHA NA PALAVRA-PASSE
    $_SESSION['login_error'] = "Credenciais inválidas. Verifique as suas credenciais e tente denovo.";
    header("Location: login.php");
    exit;
}
?>