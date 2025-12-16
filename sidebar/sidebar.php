<?php
// Ficheiro: sidebar/sidebar.php

session_start();

// -------------------------------------------------------------------
// Lógica de Avatar e Nome
// -------------------------------------------------------------------

// 1. CORRIGIDO: A lista deve corresponder aos seus ficheiros (miku.png, Nero.png, teto.png)
$default_icons = ['miku.png', 'Nero.png', 'teto.png']; 

// Isto apenas serve de fallback, pois o registo já define o ícone.
$random_default_icon = $default_icons[array_rand($default_icons)];
$default_path = "../DefaultIcons/"; 

$raw_avatar_path = $_SESSION['user_avatar'] ?? '';

// O caminho guardado na DB/Session é: DefaultIcons/file.png OU uploads/file.png.
// Como este script está dentro de 'sidebar/', todos os caminhos precisam de '../' no início.

if (!empty($raw_avatar_path)) {
    // 2. CORRIGIDO: Adiciona '../' no início do caminho guardado na DB/Sessão
    $avatar_src = '../' . htmlspecialchars($raw_avatar_path);
} else {
    // Se a sessão está vazia (nunca deve acontecer após o registo), usa um ícone default aleatório
    $avatar_src = $default_path . $random_default_icon;
}

$user_display_name = isset($_SESSION['user_nome']) && !empty($_SESSION['user_nome'])
    ? htmlspecialchars($_SESSION['user_nome']) 
    : "Convidado";

?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="sidebar.css">
</head>
<body>
    <div class="sidebar">
        <h2>Fit Plan</h2>
    
        <div class="search-box">
            <input type="text" placeholder="Pesquisar...">
        </div>

        <ul class="menu">
            <li><a href="../dashboard.php" target="_top">🏠 Dashboard</a></li>
            <li><a href="../treinos.php" target="_top">💪 Treinos</a></li>
            <li><a href="../dieta.php" target="_top">🥗 Dieta</a></li>
            <li><a href="../progresso.php" target="_top">📊 Progresso</a></li>
            <li><a href="../perfil.php" target="_top">⚙️ Perfil</a></li>
        </ul>

        <div class="user-info-wrapper">
            <div class="user-info">
                <div class="user-avatar-container">
                    <img 
                        class="user-avatar-img" 
                        src="<?= $avatar_src ?>" 
                        alt="Avatar do Utilizador"
                    />
                </div>
                <p class="user-name">
                    <?= $user_display_name ?>
                </p>
            </div>
        </div>
    </div>
    
    <script>
        // Mantém o script de navegação (para evitar recarregar o iFrame desnecessariamente)
        const links = document.querySelectorAll('.menu a');

        links.forEach(link => {
            link.addEventListener('click', e => {
                const currentPage = window.top.location.pathname.split('/').pop();
                const targetPage = link.getAttribute('href').split('/').pop();

                if (targetPage === currentPage) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>