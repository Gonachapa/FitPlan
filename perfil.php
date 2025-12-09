<?php
// Ficheiro: perfil.php (Versão Otimizada - Lógica de Default Removida)

session_start();

// Redireciona se o utilizador não estiver logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ----------------------------------------------------
// 1. CONFIGURAÇÃO E CONEXÃO À BASE DE DADOS (CREDENCIAIS XAMPP)
// ----------------------------------------------------
$servername = "localhost";
$username = "root";    
$password = "";        
$dbname = "fitness_app";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Erro fatal: Falha na conexão com a Base de Dados. Detalhe: " . $conn->connect_error);
}

// ----------------------------------------------------
// 2. CARREGAMENTO PERMANENTE DA BIO, NOME E AVATAR
// ----------------------------------------------------
$user_id = $_SESSION['user_id'];
$user_bio = '';
$user_name = isset($_SESSION['user_nome']) ? htmlspecialchars($_SESSION['user_nome']) : '';
$avatar_from_db = ''; // Inicializa

$stmt = $conn->prepare("SELECT nome, biografia, foto_perfil FROM Utilizador WHERE id_utilizador = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $user_bio = htmlspecialchars($row['biografia']);
    $user_name = htmlspecialchars($row['nome']);
    $avatar_from_db = htmlspecialchars($row['foto_perfil']);
    
    // Atualiza a sessão com o caminho guardado na BD
    $_SESSION['user_nome'] = $user_name;
    $_SESSION['user_avatar'] = $avatar_from_db;
}

$stmt->close();
$conn->close();

// ----------------------------------------------------
// 3. Lógica de Seleção de Avatar (Simplificada)
// ----------------------------------------------------

// O URL da imagem é simplesmente o que foi carregado da BD (avatar_from_db).
$profile_image_url = $avatar_from_db;

// Fallback de segurança (deve ser redundante se o registo funcionar corretamente)
if (empty($profile_image_url)) {
    $profile_image_url = 'DefaultIcons/miku.png'; 
}

// ----------------------------------------------------
// 4. HTML
// ----------------------------------------------------
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
<body>

    <button id="toggleSidebar">></button>
    <iframe id="sidebarIframe" src="sidebar/sidebar.php"></iframe>

    <div class="main-content">
        <form action="processar_perfil.php" method="POST" enctype="multipart/form-data">
            <div class="settings-card">

                <h2 class="section-title">Configurações de Perfil</h2>

                <div class="profile-pic-container" onclick="document.getElementById('fileInput').click()">
                    <img 
                        class="profile-pic"
                        id="preview"
                        src="<?= $profile_image_url ?>"
                        alt="Imagem de Perfil"
                    />
                    <div class="overlay-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 8l2-3h3l2-2h4l2 2h3l2 3" />
                            <circle cx="12" cy="13" r="4" />
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

                <button type="submit" class="save-btn">Guardar</button>
            </div>
        </form>
    </div>

    <script>
        // ---- Tema ----
        document.getElementById("themeBtn").addEventListener("click", () => {
            document.body.classList.toggle("light-theme");
        });

        // ---- Pré-visualização da foto ----
        document.getElementById("fileInput").addEventListener("change", (event) => {
            const reader = new FileReader();
            reader.onload = () => {
                document.getElementById("preview").src = reader.result;
            };
            reader.readAsDataURL(event.target.files[0]);
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