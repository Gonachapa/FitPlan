<?php
// Ficheiro: processar_perfil.php (NA RAIZ)

session_start();

// ----------------------------------------------------
// 1. VALIDAÇÃO E CONFIGURAÇÃO
// ----------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: perfil.php");
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$novo_nome = trim($_POST['nome'] ?? '');
$nova_bio = trim($_POST['bio'] ?? '');
$current_avatar_path = $_POST['current_avatar_path'] ?? ''; 
$new_avatar_path = $current_avatar_path; // Mantém o antigo por padrão

// ----------------------------------------------------
// 2. CONEXÃO À BASE DE DADOS
// ----------------------------------------------------
$servername = "localhost";
$username = "root";     
$password = "";         
$dbname = "fitness_app";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    header("Location: perfil.php?error=" . urlencode("Erro de conexão ao servidor."));
    exit;
}

// ----------------------------------------------------
// 3. PROCESSAMENTO DO UPLOAD DE FOTO (Avatar)
// ----------------------------------------------------

if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $target_dir = "uploads/";
    $file_extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
    $new_file_name = uniqid('avatar_') . '.' . $file_extension;
    $target_file = $target_dir . $new_file_name;

    // Criar a pasta 'uploads/' se não existir
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
        $new_avatar_path = $target_file; 
        
        // Apagar o avatar antigo se não for um default (para não acumular ficheiros)
        if (strpos($current_avatar_path, 'DefaultIcons') === false && file_exists($current_avatar_path)) {
            unlink($current_avatar_path);
        }

    } else {
        header("Location: perfil.php?error=" . urlencode("Erro ao fazer upload da imagem."));
        $conn->close();
        exit;
    }
}

// ----------------------------------------------------
// 4. ATUALIZAÇÃO DA BASE DE DADOS
// ----------------------------------------------------

$stmt = $conn->prepare("UPDATE Utilizador SET nome = ?, biografia = ?, foto_perfil = ? WHERE id_utilizador = ?");
$stmt->bind_param("sssi", $novo_nome, $nova_bio, $new_avatar_path, $user_id);

if ($stmt->execute()) {
    // Atualiza a sessão
    $_SESSION['user_nome'] = $novo_nome;
    $_SESSION['user_avatar'] = $new_avatar_path;
    
    $stmt->close();
    $conn->close();
    
    header("Location: perfil.php?success=" . urlencode("Perfil atualizado com sucesso!"));
    exit;
} else {
    $stmt->close();
    $conn->close();
    header("Location: perfil.php?error=" . urlencode("Erro ao atualizar o perfil."));
    exit;
}
?>