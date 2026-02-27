<?php
session_start();
// Inclui a tua ligação à base de dados (ajusta o nome do ficheiro se necessário)
// require_once 'config.php'; 

if (isset($_POST['google_token'])) {
    $token = $_POST['google_token'];

    // 1. Descodificar o token para ler os dados (Nome e Email)
    // Nota: O token do Google é um JWT dividido em 3 partes separadas por pontos.
    $partes = explode(".", $token);
    if (count($partes) === 3) {
        // Adicionar padding correto ao base64
        $payload_encoded = $partes[1];
        $padding = 4 - strlen($payload_encoded) % 4;
        if ($padding !== 4) {
            $payload_encoded .= str_repeat('=', $padding);
        }
        
        $payload = json_decode(base64_decode($payload_encoded, true), true);
        
        if ($payload && isset($payload['email']) && isset($payload['name'])) {
            $email = $payload['email'];
            $nome  = $payload['name'];
            $google_id = $payload['sub']; // ID único do utilizador no Google

            // 2. Conexão à Base de Dados
            $servername = "localhost";
            $username = "root"; 
            $password = ""; 
            $dbname = "fitness_app";

            $conn = new mysqli($servername, $username, $password, $dbname);

            if ($conn->connect_error) {
                $_SESSION['login_error'] = "Erro de sistema: Falha na conexão com a base de dados.";
                header("Location: login.php");
                exit;
            }

            // 3. Verificar se o utilizador já existe
            $sql = "SELECT id_utilizador, nome, email, foto_perfil, tema FROM Utilizador WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Utilizador já existe: Inicia sessão
                $user = $result->fetch_assoc();
                $user_id = $user['id_utilizador'];
                $user_tema = $user['tema'] ?? 'dark';
                $user_avatar = $user['foto_perfil'] ?? null;
                
                $stmt->close();
                $conn->close();

                // 4. Criar a sessão
                session_regenerate_id(true);
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_nome'] = $nome;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_avatar'] = $user_avatar;
                $_SESSION['user_tema'] = $user_tema;

                $_SESSION['login_success'] = "Bem-vindo(a), " . htmlspecialchars($nome) . "!";
                header("Location: ../dashboard.php");
                exit();
            } else {
                // Utilizador não existe: Mostrar mensagem de erro
                $stmt->close();
                $conn->close();
                
                $_SESSION['login_error'] = "Este email não está registado. Por favor, crie uma conta primeiro.";
                header("Location: login.php");
                exit;
            }
        }
    }
}

// Se algo falhar, volta ao login
$_SESSION['login_error'] = "Falha ao autenticar com o Google.";
header("Location: login.php");
exit();