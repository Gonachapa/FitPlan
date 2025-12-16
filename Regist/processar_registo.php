<?php
// Ficheiro: processar_registo.php
session_start();

// ----------------------------------------------------
// 1. CONFIGURAÇÃO E CONEXÃO À BASE DE DADOS
// ----------------------------------------------------
$servername = "localhost";
$username = "root";     
$password = "";         
$dbname = "fitness_app";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    header("Location: registo.php?error=" . urlencode("Erro de conexão ao servidor. Tente mais tarde."));
    exit;
}

// ----------------------------------------------------
// 2. RECEÇÃO E VALIDAÇÃO DE DADOS
// ----------------------------------------------------
$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$password_raw = $_POST['password'] ?? '';
$data_nascimento = $_POST['data_nascimento'] ?? null;
$genero = $_POST['genero'] ?? null;
$altura = $_POST['altura'] ?? null;
$peso_atual = $_POST['peso_atual'] ?? null;

// Validação básica
if (empty($nome) || empty($email) || strlen($password_raw) < 6) {
    header("Location: registo.php?error=" . urlencode("Por favor, preencha todos os campos obrigatórios corretamente."));
    $conn->close();
    exit;
}

// ----------------------------------------------------
// 3. VERIFICAR DUPLICAÇÃO FINAL (Nome e Email)
// ----------------------------------------------------
$stmt = $conn->prepare("SELECT id_utilizador FROM Utilizador WHERE nome = ? OR email = ?");
$stmt->bind_param("ss", $nome, $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    header("Location: registo.php?error=" . urlencode("O Nome de Utilizador ou Email já estão registados."));
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();


// ----------------------------------------------------
// 4. Lógica de Seleção de Avatar Default (Aleatório)
// ----------------------------------------------------
$default_icons = ['miku.png', 'Nero.png', 'teto.png']; 
$random_default_icon_file = $default_icons[array_rand($default_icons)];
$default_avatar_path = "DefaultIcons/" . $random_default_icon_file;


// ----------------------------------------------------
// 5. INSERÇÃO DO NOVO UTILIZADOR
// ----------------------------------------------------

$password_hashed = password_hash($password_raw, PASSWORD_DEFAULT);

// LINHA 57 CORRIGIDA: Usa 'senha_hash' e não inclui 'data_registo'
$sql_insert = "INSERT INTO Utilizador (nome, email, senha_hash, foto_perfil) VALUES (?, ?, ?, ?)";
$stmt_insert = $conn->prepare($sql_insert);
// 4 parâmetros de string (nome, email, senha_hash, foto_perfil)
$stmt_insert->bind_param("ssss", $nome, $email, $password_hashed, $default_avatar_path);

if ($stmt_insert->execute()) {
    
    $new_user_id = $conn->insert_id; 

    // ----------------------------------------------------
    // 6. ATUALIZAR DADOS Opcionais
    // ----------------------------------------------------
    $update_fields = [];
    $update_values = [];
    $update_types = "";
    
    if (!empty($data_nascimento)) {
        $update_fields[] = "data_nascimento = ?";
        $update_values[] = $data_nascimento;
        $update_types .= "s";
    }

    if (!empty($genero)) {
        $update_fields[] = "genero = ?";
        $update_values[] = $genero;
        $update_types .= "s";
    }

    if (!empty($altura)) {
        $update_fields[] = "altura = ?";
        $update_values[] = floatval($altura);
        $update_types .= "d";
    }

    if (!empty($peso_atual)) {
        $update_fields[] = "peso_atual = ?";
        $update_values[] = floatval($peso_atual);
        $update_types .= "d";
    }

    if (!empty($update_fields)) {
        $sql_update = "UPDATE Utilizador SET " . implode(", ", $update_fields) . " WHERE id_utilizador = ?";
        $stmt_update = $conn->prepare($sql_update);

        $update_values[] = $new_user_id;
        $update_types .= "i"; 

        $stmt_update->bind_param($update_types, ...$update_values);
        $stmt_update->execute();
        $stmt_update->close();
    }
    
    // ----------------------------------------------------
    // 7. LOGIN AUTOMÁTICO E REDIRECIONAMENTO
    // ----------------------------------------------------
    
    $_SESSION['user_id'] = $new_user_id;
    $_SESSION['user_nome'] = $nome; 
    $_SESSION['user_avatar'] = $default_avatar_path; // Guarda o caminho escolhido
    $_SESSION['logged_in'] = true;

    // Redireciona para a dashboard
    header("Location: ../dashboard.php");
    exit;

} else {
    header("Location: registo.php?error=" . urlencode("Erro ao registar utilizador. Por favor, tente novamente."));
}

$stmt_insert->close();
$conn->close();

?>