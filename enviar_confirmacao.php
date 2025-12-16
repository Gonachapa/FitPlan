<?php
// Ficheiro: enviar_confirmacao.php (NA RAIZ)

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
    header("Location: Regist/registo.php?error=" . urlencode("Erro de conexão ao servidor. Tente mais tarde."));
    exit;
}

// ----------------------------------------------------
// 2. RECEÇÃO E VALIDAÇÃO DE DADOS INICIAIS
// ----------------------------------------------------
$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$password_raw = $_POST['password'] ?? '';
// Receber dados opcionais (usar coalescing operator ?? para null se não vierem)
$data_nascimento = $_POST['data_nascimento'] ?? null;
$genero = $_POST['genero'] ?? null;
$altura = $_POST['altura'] ?? null;
$peso_atual = $_POST['peso_atual'] ?? null;

// Validação básica
if (empty($nome) || empty($email) || strlen($password_raw) < 6 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: Regist/registo.php?error=" . urlencode("Por favor, preencha os campos obrigatórios corretamente."));
    $conn->close();
    exit;
}

// ----------------------------------------------------
// 3. VERIFICAR DUPLICAÇÃO FINAL (Redundância ao AJAX)
// ----------------------------------------------------
$stmt = $conn->prepare("SELECT id_utilizador FROM Utilizador WHERE nome = ? OR email = ?");
$stmt->bind_param("ss", $nome, $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    header("Location: Regist/registo.php?error=" . urlencode("O Nome de Utilizador ou Email já estão registados."));
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();
$conn->close(); 

// ----------------------------------------------------
// 4. GERAÇÃO E ENVIO DO CÓDIGO DE CONFIRMAÇÃO
// ----------------------------------------------------

$confirmation_code = strval(rand(100000, 999999));
$expiration_time = time() + (10 * 60); 

$subject = "Fit Plan: Código de Confirmação de Registo";
$message = "Olá " . $nome . ",\n\nO seu código de confirmação para o Fit Plan é: " . $confirmation_code . "\n\nEste código expira em 10 minutos.";
$headers = 'From: danielfg5827@gmail.com' . "\r\n" . // Usa o seu email configurado no XAMPP
           'Reply-To: noreply@fitplanapp.com' . "\r\n" .
           'X-Mailer: PHP/' . phpversion();

$mail_sent = mail($email, $subject, $message, $headers);

if (!$mail_sent) {
    // ERRO: Se o envio falhar (provavelmente problema de XAMPP/Gmail App Password)
    header("Location: Regist/registo.php?error=" . urlencode("Erro ao enviar email de confirmação. Por favor, verifique a sua caixa de Spam ou as configurações do XAMPP."));
    exit;
}

// ----------------------------------------------------
// 5. GUARDAR DADOS NA SESSÃO E REDIRECIONAR
// ----------------------------------------------------

$_SESSION['registration_data'] = [
    'nome' => $nome,
    'email' => $email,
    'password_hash' => password_hash($password_raw, PASSWORD_DEFAULT), 
    'code' => $confirmation_code,
    'code_expires' => $expiration_time,
    // Incluir dados opcionais
    'data_nascimento' => $data_nascimento,
    'genero' => $genero,
    'altura' => $altura,
    'peso_atual' => $peso_atual
];

// Redireciona para confirmar_registo.php (na raiz)
header("Location: confirmar_registo.php?email=" . urlencode($email));
exit;