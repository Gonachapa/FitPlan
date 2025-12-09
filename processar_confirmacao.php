<?php
// Ficheiro: processar_confirmacao.php (NA RAIZ)

session_start();

// ----------------------------------------------------
// 1. VERIFICAR SESSÃO E DADOS
// ----------------------------------------------------
if (!isset($_SESSION['registration_data']) || !isset($_POST['confirmation_code'])) {
    header("Location: confirmar_registo.php?error=" . urlencode("Dados em falta. Tente novamente."));
    exit;
}

$user_data = $_SESSION['registration_data'];
$submitted_code = trim($_POST['confirmation_code']);

// ----------------------------------------------------
// 2. VALIDAÇÃO DO CÓDIGO E EXPIRAÇÃO
// ----------------------------------------------------

if (time() > $user_data['code_expires']) {
    unset($_SESSION['registration_data']); 
    // CORRIGIDO: Redireciona para DENTRO da pasta Regist/
    header("Location: Regist/registo.php?error=" . urlencode("O código de confirmação expirou. Por favor, registe-se novamente."));
    exit;
}

if ($submitted_code !== $user_data['code']) {
    header("Location: confirmar_registo.php?error=" . urlencode("Código de confirmação incorreto."));
    exit;
}

// ----------------------------------------------------
// 3. INSERÇÃO FINAL NA BASE DE DADOS
// ----------------------------------------------------

$servername = "localhost";
$username = "root";     
$password = "";         
$dbname = "fitness_app";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    header("Location: confirmar_registo.php?error=" . urlencode("Erro de conexão ao servidor para finalização."));
    exit;
}

$nome = $user_data['nome'];
$email = $user_data['email'];
$password_hashed = $user_data['password_hash']; 

// Avatar Default: Usa um ícone aleatório para o registo inicial
$default_icons = ['miku.png', 'Nero.png', 'teto.png']; 
$random_default_icon_file = $default_icons[array_rand($default_icons)];
$default_avatar_path = "DefaultIcons/" . $random_default_icon_file;


$sql_insert = "INSERT INTO Utilizador (nome, email, senha_hash, foto_perfil) VALUES (?, ?, ?, ?)";
$stmt_insert = $conn->prepare($sql_insert);
$stmt_insert->bind_param("ssss", $nome, $email, $password_hashed, $default_avatar_path);

if ($stmt_insert->execute()) {
    
    $new_user_id = $conn->insert_id; 

    // --- Lógica para inserir dados opcionais (Peso, Altura, etc.) aqui ---
    
    // ----------------------------------------------------
    // 4. LOGIN AUTOMÁTICO E FINALIZAÇÃO
    // ----------------------------------------------------
    
    $_SESSION['user_id'] = $new_user_id;
    $_SESSION['user_nome'] = $nome; 
    $_SESSION['user_avatar'] = $default_avatar_path; 
    $_SESSION['logged_in'] = true;
    
    unset($_SESSION['registration_data']); 
    
    $stmt_insert->close();
    $conn->close();

    // Redireciona para dashboard.php (na raiz)
    header("Location: dashboard.php");
    exit;

} else {
    header("Location: confirmar_registo.php?error=" . urlencode("Erro fatal ao criar a conta. Contacte o suporte."));
}

$stmt_insert->close();
$conn->close();