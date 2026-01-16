<?php
session_start();

// 1. SEGURANÇA E CONEXÃO
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) { 
    exit; 
}

$conn = new mysqli("localhost", "root", "", "fitness_app");
if ($conn->connect_error) { die("Erro: " . $conn->connect_error); }

$user_id = $_SESSION['user_id'];
$nome = trim($_POST['nome']);
$bio = trim($_POST['bio']);

// 2. VERIFICAÇÃO DE NOME ÚNICO
// Procuramos se existe algum utilizador com este nome que NÃO seja o próprio utilizador logado
$check_stmt = $conn->prepare("SELECT id_utilizador FROM Utilizador WHERE nome = ? AND id_utilizador != ?");
$check_stmt->bind_param("si", $nome, $user_id);
$check_stmt->execute();
$res_check = $check_stmt->get_result();

if ($res_check->num_rows > 0) {
    // Se encontrou alguém, redireciona com erro de nome duplicado
    header("Location: perfil.php?erro=nome_duplicado");
    exit;
}

// 3. GESTÃO DA IMAGEM
$res = $conn->query("SELECT foto_perfil FROM Utilizador WHERE id_utilizador = $user_id");
$current_db_image = $res->fetch_assoc()['foto_perfil'];

$new_image_path = !empty($_POST['current_avatar_path']) ? $_POST['current_avatar_path'] : $current_db_image;

if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $dir = "uploads/";
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
    $target = $dir . "avatar_" . $user_id . "_" . time() . "." . $ext;

    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
        // Apaga a imagem antiga se não for um ícone padrão
        if (!empty($current_db_image) && strpos($current_db_image, 'DefaultIcons') === false && file_exists($current_db_image)) {
            unlink($current_db_image);
        }
        $new_image_path = $target;
    }
}

// 4. ATUALIZAÇÃO FINAL
$stmt = $conn->prepare("UPDATE Utilizador SET nome = ?, biografia = ?, foto_perfil = ? WHERE id_utilizador = ?");
$stmt->bind_param("sssi", $nome, $bio, $new_image_path, $user_id);

if ($stmt->execute()) {
    // Atualiza as variáveis de sessão para refletir as mudanças no resto do site
    $_SESSION['user_nome'] = $nome;
    $_SESSION['user_avatar'] = $new_image_path;
    header("Location: perfil.php?success=1");
} else {
    header("Location: perfil.php?error=db");
}

$conn->close();
?>