<?php
// Ficheiro: sidebar/sidebar.php
session_start();

// 1. Configurações de Avatar e Fallback
$default_icons = ['miku.png', 'Nero.png', 'teto.png']; 
$random_default_icon = $default_icons[array_rand($default_icons)];
$default_path = "../DefaultIcons/"; 

$raw_avatar_path = $_SESSION['user_avatar'] ?? '';

if (!empty($raw_avatar_path)) {
    // Adiciona '../' porque este ficheiro está na pasta 'sidebar/'
    $avatar_src = '../' . htmlspecialchars($raw_avatar_path);
} else {
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Estilos para a Barra de Pesquisa e Sugestões */
        .search-container { position: relative; width: 100%; margin-bottom: 20px; }
        
        #search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #1a1d23;
            border: 1px solid #333;
            border-radius: 8px;
            z-index: 9999;
            max-height: 250px;
            overflow-y: auto;
            display: none;
            box-shadow: 0 10px 20px rgba(0,0,0,0.5);
        }

        .search-item {
            display: flex;
            align-items: center;
            padding: 10px;
            text-decoration: none;
            color: white;
            border-bottom: 1px solid #222;
            transition: 0.2s;
        }

        .search-item:hover { background: #2a62fc; }

        .search-item img {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }

        .search-item span { font-size: 14px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Fit Plan</h2>
    
        <div class="search-box">
            <div class="search-container">
                <input type="text" id="user-search" placeholder="Pesquisar (Enter p/ tudo)..." autocomplete="off">
                <div id="search-results"></div>
            </div>
        </div>

        <ul class="menu">
            <li><a href="../dashboard.php" target="_top">🏠 Dashboard</a></li>
            <li><a href="../treinos.php" target="_top">💪 Treinos</a></li>
            <li><a href="../dieta.php" target="_top">🥗 Registo físico</a></li>
            <li><a href="../perfil.php" target="_top">⚙️ Perfil</a></li>
        </ul>

        <div class="user-info-wrapper">
            <div class="user-info">
                <div class="user-avatar-container">
                    <img class="user-avatar-img" src="<?= $avatar_src ?>" alt="Avatar" />
                </div>
                <p class="user-name"><?= $user_display_name ?></p>
            </div>
        </div>
    </div>
    
    <script>
        const searchInput = document.getElementById('user-search');
        const resultsDiv = document.getElementById('search-results');

        // 1. Autocomplete (Sugestões Rápidas)
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            if (query.length > 1) {
                fetch(`search_users.php?q=${query}`)
                    .then(response => response.json())
                    .then(data => {
                        resultsDiv.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(user => {
                                const photo = user.foto_perfil ? '../' + user.foto_perfil : '../DefaultIcons/miku.png';
                                resultsDiv.innerHTML += `
                                    <a href="../perfil.php?id=${user.id_utilizador}" class="search-item" target="_top">
                                        <img src="${photo}" alt="">
                                        <span>${user.nome}</span>
                                    </a>
                                `;
                            });
                            resultsDiv.style.display = 'block';
                        } else {
                            resultsDiv.style.display = 'none';
                        }
                    });
            } else {
                resultsDiv.style.display = 'none';
            }
        });

        // 2. Redirecionar ao carregar no ENTER
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const query = this.value.trim();
                if (query.length > 0) {
                    // target="_top" para mudar a página pai (o site todo) e não apenas o iframe
                    window.top.location.href = `../pesquisa.php?q=${encodeURIComponent(query)}`;
                }
            }
        });

        // Fechar resultados ao clicar fora
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
                resultsDiv.style.display = 'none';
            }
        });
    </script>
</body>
</html>