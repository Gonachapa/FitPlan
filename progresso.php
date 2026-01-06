<?php
// Ficheiro: progresso.php (Renomeado de progresso.html)
session_start();

// --- NOVO: PEGAR O TEMA DA SESSÃO ---
$user_theme = isset($_SESSION['user_tema']) ? $_SESSION['user_tema'] : 'dark';

// Obtém o nome do utilizador para usar no conteúdo principal
$user_name = isset($_SESSION['user_nome']) ? htmlspecialchars($_SESSION['user_nome']) : 'Visitante';
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progresso - Fit Plan</title>
    
    <link rel="stylesheet" href="style.css"> 
    <link rel="stylesheet" href="sidebar/button.css"> 
    
</head>
<body class="<?= $user_theme === 'light' ? 'light-theme' : '' ?>">

    <button id="toggleSidebar">></button>
    <iframe id="sidebarIframe" src="sidebar/sidebar.php"></iframe>

    <div class="main-content">
        <h1>Progresso</h1>
        <p>Olá, <strong><?= $user_name ?></strong>! Acompanhe o seu progresso aqui.</p>
    </div>

    <script>
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