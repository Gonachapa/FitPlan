<?php
// Ficheiro: processar_registo.php

// Define as credenciais de acesso à base de dados
$servername = "localhost";
$username = "root"; 
$password = "";     
$dbname = "fitness_app";

// --- 1. Conexão à Base de Dados ---
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    header("Location: regist.php?error=" . urlencode("Erro de sistema: Falha na conexão com a base de dados."));
    exit;
}

// --- 2. Receber e Filtrar Dados (Método POST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Receber campos
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $password_raw = $_POST['password'] ?? '';
    
    // Campos Opcionais
    $data_nascimento = $_POST['data_nascimento'] ?: NULL;
    $genero = $_POST['genero'] ?: NULL;
    $altura = $_POST['altura'] ?: NULL; 
    $peso_atual = $_POST['peso_atual'] ?: NULL; 

    // Validação básica do lado do servidor
    if (empty($nome) || empty($email) || empty($password_raw) || strlen($password_raw) < 6) {
        $conn->close();
        header("Location: regist.php?error=" . urlencode("Dados obrigatórios em falta ou palavra-passe fraca."));
        exit;
    }

    // --- 3. Hash da Palavra-passe ---
    $senha_hash = password_hash($password_raw, PASSWORD_DEFAULT);

    // --- 4. Preparação da Query de Inserção ---
    $stmt = $conn->prepare("INSERT INTO Utilizador (nome, email, senha_hash, genero, altura, peso_atual, data_nascimento) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("ssssdds", 
        $nome, 
        $email, 
        $senha_hash, 
        $genero, 
        $altura, 
        $peso_atual, 
        $data_nascimento
    );

    // --- 5. Execução da Query ---
    if ($stmt->execute()) {
        
        // SUCESSO
        $stmt->close(); 
        $conn->close(); 
        
        // Redireciona para o login (../index.html)
        header("Location: ../index.html?success=" . urlencode("Registo efetuado com sucesso! Pode iniciar sessão."));
        exit;
    } else {
        // ERRO (Duplicidade 1062 ou outro)
        
        // Mensagem de erro genérica para falha na submissão
        $error_message = "O registo não pode ser concluído. Verifique os dados fornecidos e tente novamente.";
        
        $stmt->close(); 
        $conn->close();
        
        // Redireciona para a página de registo com a mensagem de erro
        header("Location: regist.php?error=" . urlencode($error_message));
        exit;
    }
} else {
    // Se a página for acedida via GET (diretamente)
    $conn->close();
    header("Location: regist.php");
    exit;
}
?>