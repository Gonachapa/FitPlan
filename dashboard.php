<?php
// Ficheiro: dashboard.php

// 1. Inicia a sessão PHP para aceder aos dados do utilizador
session_start();

// --- PEGAR O TEMA DA SESSÃO ---
$user_theme = isset($_SESSION['user_tema']) ? $_SESSION['user_tema'] : 'dark';

// Obtém o nome do utilizador da sessão para personalizar a saudação
$user_name = isset($_SESSION['user_nome']) ? htmlspecialchars($_SESSION['user_nome']) : 'Convidado';
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Fit Plan</title>
    
    <link rel="stylesheet" href="style.css"> 
    <link rel="stylesheet" href="sidebar/button.css"> 

    <style>
        /* Apenas define as cores com base no tema */
        body {
            <?php if ($user_theme === 'light'): ?>
                background-color: #f8fafc;
                color: #0f172a;
            <?php else: ?>
                background-color: #0c0f16;
                color: #ffffff;
            <?php endif; ?>
        }
    </style>
</head>
<body>

    <iframe id="sidebarIframeContainer" src="sidebar/button.php" class="sidebar"></iframe>
    
    <div class="main-content">
        <h1>Dashboard</h1>
        <p>Bem-vindo(a) ao Fit Plan, **<?= $user_name ?>**!</p>
    </div>

    <script>
        // Use os IDs corretos de acordo com a sua escolha de layout (Opção A ou B)
        const toggleButton = document.getElementById('toggleSidebar');
        const sidebar = document.getElementById('sidebarIframeContainer'); 

        if (toggleButton && sidebar) {
            toggleButton.addEventListener('click', () => {
                sidebar.classList.toggle('show');
                toggleButton.textContent = sidebar.classList.contains('show') ? '<' : '>';
            });
        }
    </script>
</body>
</html>