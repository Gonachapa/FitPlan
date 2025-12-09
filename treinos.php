<?php
// Ficheiro: treinos.php (Renomeado de treinos.html)
session_start();

// Obtém o nome do utilizador para usar no conteúdo principal
$user_name = isset($_SESSION['user_nome']) ? htmlspecialchars($_SESSION['user_nome']) : 'Visitante';
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Treinos - Fit Plan</title>
    
    <link rel="stylesheet" href="style.css"> 
    <link rel="stylesheet" href="sidebar/button.css"> 
    
</head>
<body>

    <button id="toggleSidebar">></button>
    <iframe id="sidebarIframe" src="sidebar/sidebar.php"></iframe>

    <div class="main-content">
        <h1>Treinos</h1>
        <p>Olá, **<?= $user_name ?>**! Aqui estão os seus planos de treino.</p>
        </div>

    <script>
        const toggleButton = document.getElementById('toggleSidebar');
        const sidebar = document.getElementById('sidebarIframe');

        toggleButton.addEventListener('click', () => {
            sidebar.classList.toggle('show');
            toggleButton.textContent = sidebar.classList.contains('show') ? '<' : '>';
        });
    </script>
</body>
</html>