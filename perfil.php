<?php
// Ficheiro: perfil.php (Versão com Tema Persistente)
session_start();

// Redireciona se o utilizador não estiver logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ----------------------------------------------------
// 1. CONFIGURAÇÃO E CONEXÃO À BASE DE DADOS
// ----------------------------------------------------
$servername = "localhost";
$username = "root";    
$password = "";        
$dbname = "fitness_app";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Erro fatal: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

// ----------------------------------------------------
// 2. LÓGICA AJAX PARA GUARDAR O TEMA (SOLICITADO PELO JS)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_tema'])) {
    $novo_tema = $_POST['ajax_tema'];
    $stmt = $conn->prepare("UPDATE Utilizador SET tema = ? WHERE id_utilizador = ?");
    $stmt->bind_param("si", $novo_tema, $user_id);
    $stmt->execute();
    $_SESSION['user_tema'] = $novo_tema; // Atualiza a sessão para o resto do site
    $stmt->close();
    $conn->close();
    exit; 
}

// ----------------------------------------------------
// 3. CARREGAMENTO DOS DADOS DO UTILIZADOR
// ----------------------------------------------------
$stmt = $conn->prepare("SELECT nome, biografia, foto_perfil, tema FROM Utilizador WHERE id_utilizador = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$user_theme = 'dark'; // Default
if ($row = $result->fetch_assoc()) {
    $user_name = htmlspecialchars($row['nome']);
    $user_bio = htmlspecialchars($row['biografia']);
    $avatar_from_db = htmlspecialchars($row['foto_perfil']);
    $user_theme = $row['tema'];
    
    $_SESSION['user_nome'] = $user_name;
    $_SESSION['user_tema'] = $user_theme;
}
$stmt->close();
$conn->close();

$profile_image_url = !empty($avatar_from_db) ? $avatar_from_db : 'DefaultIcons/miku.png';
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil - Fit Plan</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="sidebar/button.css">
</head>
<body class="<?= $user_theme === 'light' ? 'light-theme' : '' ?>">

    <button id="toggleSidebar">></button>
    <iframe id="sidebarIframe" src="sidebar/sidebar.php"></iframe>

    <div class="main-content">
        <form action="processar_perfil.php" method="POST" enctype="multipart/form-data">
            <div class="settings-card">
                <h2 class="section-title">Configurações de Perfil</h2>

                <div class="profile-pic-container" onclick="document.getElementById('fileInput').click()">
                    <img class="profile-pic" id="preview" src="<?= $profile_image_url ?>" alt="Imagem de Perfil" />
                    <div class="overlay-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 8l2-3h3l2-2h4l2 2h3l2 3" /><circle cx="12" cy="13" r="4" />
                        </svg>
                    </div>
                </div>

                <input type="file" id="fileInput" name="avatar" accept="image/*" style="display: none">
                <input type="hidden" name="current_avatar_path" value="<?= htmlspecialchars($profile_image_url) ?>">

                <div class="theme-toggle">
                    <span>Cor:</span>
                    <button type="button" id="themeBtn">Alterar</button>
                </div>

                <div class="input-group">
                    <label>Nome:</label>
                    <input type="text" name="nome" placeholder="Insira seu nome..." value="<?= $user_name ?>" />
                </div>

                <div class="input-group">
                    <label>Bio:</label>
                    <textarea rows="4" name="bio" placeholder="Escreve a tua nova bio..."><?= $user_bio ?></textarea>
                </div>

                <button type="submit" class="save-btn">Guardar Alterações</button>
            </div>
        </form>
    </div>

    <script>
        // ---- Lógica de Tema Persistente ----
        document.getElementById("themeBtn").addEventListener("click", () => {
            document.body.classList.toggle("light-theme");
            
            // Descobrir qual tema está ativo agora
            const temaAtivo = document.body.classList.contains("light-theme") ? "light" : "dark";

            // Enviar para o PHP guardar na BD via AJAX
            const formData = new FormData();
            formData.append('ajax_tema', temaAtivo);

            fetch('perfil.php', {
                method: 'POST',
                body: formData
            }).then(response => {
                console.log("Tema guardado: " + temaAtivo);
            });
        });

        // ---- Pré-visualização da foto ----
        document.getElementById("fileInput").addEventListener("change", (event) => {
            const reader = new FileReader();
            reader.onload = () => {
                document.getElementById("preview").src = reader.result;
            };
            if(event.target.files[0]) reader.readAsDataURL(event.target.files[0]);
        });

        // ---- Botão da Sidebar ----
        const toggleButton = document.getElementById('toggleSidebar');
        const sidebar = document.getElementById('sidebarIframe');
        if (toggleButton && sidebar) {
            toggleButton.addEventListener('click', () => {
                sidebar.classList.toggle('show');
                toggleButton.textContent = sidebar.classList.contains('show') ? '<' : '>';
            });
        }
    </script>
</body>
</html>