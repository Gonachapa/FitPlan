<?php
// 1. SESSÃO E CACHE (Topo absoluto)
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// 2. LÓGICA DE LOGOUT
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: Login/login.php"); 
    exit;
}

// 3. CONEXÃO
$servername = "localhost";
$username = "root";    
$password = "";        
$dbname = "fitness_app";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Erro fatal: " . $conn->connect_error); }

// 4. VERIFICAÇÃO DE ACESSO
if (!isset($_SESSION['user_id'])) {
    header("Location: Login/login.php");
    exit;
}

$logado_id = $_SESSION['user_id'];
$perfil_visualizado_id = isset($_GET['id']) ? intval($_GET['id']) : $logado_id;
$e_o_proprio = ($logado_id == $perfil_visualizado_id);

// 5. LÓGICA AJAX (Tema, Seguir, Apagar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($e_o_proprio && isset($_POST['ajax_tema'])) {
        $novo_tema = $_POST['ajax_tema'];
        $conn->query("UPDATE Utilizador SET tema = '$novo_tema' WHERE id_utilizador = $logado_id");
        $_SESSION['user_tema'] = $novo_tema; 
        exit;
    }
    if (!$e_o_proprio && isset($_POST['acao_seguir'])) {
        if ($_POST['acao_seguir'] === 'seguir') {
            $conn->query("INSERT IGNORE INTO Seguidores (id_seguidor, id_seguido) VALUES ($logado_id, $perfil_visualizado_id)");
        } else {
            $conn->query("DELETE FROM Seguidores WHERE id_seguidor = $logado_id AND id_seguido = $perfil_visualizado_id");
        }
        exit;
    }
}

// 6. CARREGAR DADOS
$stmt = $conn->prepare("SELECT nome, biografia, foto_perfil, tema FROM Utilizador WHERE id_utilizador = ?");
$stmt->bind_param("i", $perfil_visualizado_id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

$user_name = htmlspecialchars($row['nome']);
$user_bio = htmlspecialchars($row['biografia'] ?? '');
$profile_image_url = !empty($row['foto_perfil']) ? $row['foto_perfil'] : 'DefaultIcons/miku.png';

$erro_nome = isset($_GET['erro']) && $_GET['erro'] == 'nome_duplicado' ? "Este nome já está em uso!" : "";

$lista_seguidores = $conn->query("SELECT u.id_utilizador, u.nome, u.foto_perfil FROM Seguidores s JOIN Utilizador u ON s.id_seguidor = u.id_utilizador WHERE s.id_seguido = $perfil_visualizado_id AND u.id_utilizador <> $perfil_visualizado_id");
$lista_seguindo = $conn->query("SELECT u.id_utilizador, u.nome, u.foto_perfil FROM Seguidores s JOIN Utilizador u ON s.id_seguido = u.id_utilizador WHERE s.id_seguidor = $perfil_visualizado_id AND u.id_utilizador <> $perfil_visualizado_id");
$num_seguidores = $lista_seguidores->num_rows;
$num_seguindo = $lista_seguindo->num_rows;
$check = $conn->query("SELECT * FROM Seguidores WHERE id_seguidor = $logado_id AND id_seguido = $perfil_visualizado_id");
$ja_segue = $check->num_rows > 0;
if ($e_o_proprio) {
    // owner can see all routines
    $lista_treinos = $conn->query("SELECT t.*, (SELECT COUNT(*) FROM Treino_Exercicio te WHERE te.id_treino = t.id_treino) as total_exercicios FROM Treino t WHERE t.id_utilizador = $perfil_visualizado_id ORDER BY t.data_criacao DESC");
} else {
    // only public routines for other users
    $lista_treinos = $conn->query("SELECT t.*, (SELECT COUNT(*) FROM Treino_Exercicio te WHERE te.id_treino = t.id_treino) as total_exercicios FROM Treino t WHERE t.id_utilizador = $perfil_visualizado_id AND privado = 0 ORDER BY t.data_criacao DESC");
}

$interface_theme = $_SESSION['user_tema'] ?? $row['tema'];
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <title>Perfil - <?= $user_name ?></title>
    <link rel="stylesheet" href="sidebar/button.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --primary: #3b82f6; --bg: #0c0f16; --card: #1a1d23; --text: #f8fafc; --border: #333742; --input: #222; --danger: #ef4444; }
        body.light-theme { --bg: #f8fafc; --card: #ffffff; --text: #1e293b; --border: #e2e8f0; --input: #fff; }
        body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); margin: 0; transition: 0.3s; overflow-x: hidden; }
        
        /* Estilos da Sidebar idênticos ao exemplo */
        #sidebarIframe { position: fixed; left: -300px; top: 0; width: 300px; height: 100vh; border: none; z-index: 1000; transition: 0.3s; background: var(--card); }
        #sidebarIframe.show { left: 0; }
        
        /* O botão usa apenas o position e z-index, as cores/tamanhos vêm do button.css */
        #toggleSidebar { position: fixed; z-index: 1100; }

        .main-content { 
            padding: 40px 20px; 
            display: flex; 
            justify-content: center; 
            min-height: 100vh; 
            transition: 0.3s; 
        }
        
        .btn-logout { position: absolute; top: 20px; right: 20px; background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 10px 18px; border-radius: 10px; border: 1px solid rgba(239, 68, 68, 0.2); text-decoration: none; font-size: 13px; font-weight: bold; display: flex; align-items: center; gap: 8px; z-index: 100; }
        .btn-logout:hover { background: var(--danger); color: white; }

        .profile-container { width: 100%; max-width: 800px; }
        .settings-card { background: var(--card); padding: 30px; border-radius: 20px; border: 1px solid var(--border); box-shadow: 0 4px 20px rgba(0,0,0,0.1); text-align: center; }
        
        .profile-pic-container { position: relative; display: inline-block; cursor: pointer; }
        .profile-pic { width: 130px; height: 130px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary); }
        .camera-icon { position: absolute; bottom: 5px; right: 5px; background: var(--primary); color: white; border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border: 3px solid var(--card); }

        .stats-row { display: flex; justify-content: center; gap: 40px; margin: 20px 0; }
        .stat-num { display: block; font-size: 22px; font-weight: bold; color: var(--primary); }
        .stat-label { font-size: 12px; opacity: 0.6; }

        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 2000; justify-content: center; align-items: center; }
        .modal-content { background: var(--card); padding: 25px; border-radius: 15px; width: 90%; max-width: 400px; border: 1px solid var(--border); max-height: 70vh; overflow-y: auto; }
        
        /* follower/following list items */
        .user-item { display: flex; align-items: center; gap: 12px; padding: 10px 8px; text-decoration: none; color: var(--text); border-bottom: 1px solid var(--border); transition: background 0.2s; }
        .user-item:hover { background: rgba(255,255,255,0.05); }
        .user-item img { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; }
        .user-item span { font-size: 14px; font-weight: 500; }
        .modal-content h3 { margin-top: 0; margin-bottom: 15px; font-size: 18px; }
        .modal-content { padding: 20px 25px; }

        .treino-card { background: var(--card); padding: 20px; border-radius: 15px; border: 1px solid var(--border); }
        .btn-action { padding: 10px 20px; border-radius: 8px; cursor: pointer; border: none; font-weight: bold; display: inline-block; text-decoration: none; font-size: 13px; }
        .btn-edit { background: var(--primary); color: #fff; width: 100%; margin-top: 10px; }
        
        input[type="text"], textarea { width: 100%; padding: 12px; background: var(--input); border: 1px solid var(--border); color: var(--text); border-radius: 8px; margin-bottom: 10px; box-sizing: border-box; }
    </style>
</head>
<body class="<?= $interface_theme === 'light' ? 'light-theme' : '' ?>">

    <button id="toggleSidebar">></button>
    <iframe id="sidebarIframe" src="sidebar/sidebar.php"></iframe>

    <div class="main-content">
        <?php if ($e_o_proprio): ?>
            <a href="perfil.php?logout=1" class="btn-logout"><i class="fa-solid fa-right-from-bracket"></i> Sair da Conta</a>
        <?php endif; ?>

        <div class="profile-container">
            <div class="settings-card">
                <?php if ($e_o_proprio): ?>
                    <form action="processar_perfil.php" method="POST" enctype="multipart/form-data">
                        <div class="profile-pic-container" onclick="document.getElementById('fileInput').click()">
                            <img class="profile-pic" id="profilePreview" src="<?= $profile_image_url ?>">
                            <div class="camera-icon"><i class="fa-solid fa-camera"></i></div>
                        </div>
                        <input type="file" id="fileInput" name="avatar" style="display:none" accept="image/*" onchange="previewImage(this)">
                        <h2 style="margin: 10px 0;"><?= $user_name ?></h2>
                <?php else: ?>
                    <img class="profile-pic" src="<?= $profile_image_url ?>">
                    <h2 style="margin: 10px 0;"><?= $user_name ?></h2>
                <?php endif; ?>

                <div class="stats-row">
                    <div class="stat-box" style="cursor:pointer" onclick="openListModal('modalSeguidores')">
                        <span class="stat-num"><?= $num_seguidores ?></span>
                        <span class="stat-label">Seguidores</span>
                    </div>
                    <div class="stat-box" style="cursor:pointer" onclick="openListModal('modalSeguindo')">
                        <span class="stat-num"><?= $num_seguindo ?></span>
                        <span class="stat-label">Seguindo</span>
                    </div>
                </div>

                <?php if ($e_o_proprio): ?>
                    <button type="button" id="themeBtn" class="btn-action" style="background:#444; color:#fff; margin-bottom:15px; width: 100%;"><i class="fa-solid fa-moon"></i> Alternar Tema</button>
                    <div style="text-align:left;">
                        <input type="text" name="nome" value="<?= $user_name ?>" required>
                        <textarea name="bio" rows="2" placeholder="Biografia"><?= $user_bio ?></textarea>
                    </div>
                    <button type="submit" class="btn-action btn-edit">Guardar Alterações</button>
                    </form>
                <?php else: ?>
                    <button id="followBtn" class="btn-action <?= $ja_segue ? 'btn-unfollow' : 'btn-follow' ?>"><?= $ja_segue ? 'Deixar de Seguir' : 'Seguir' ?></button>
                    <p style="opacity:0.8; margin-top:15px;"><?= $user_bio ?: 'Sem biografia.' ?></p>
                <?php endif; ?>
            </div>

            <h3 style="margin-top:40px; border-left:4px solid var(--primary); padding-left:15px;">Treinos</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                <?php while($t = $lista_treinos->fetch_assoc()): ?>
                <div class="treino-card" id="treino-<?= $t['id_treino'] ?>">
                    <h4 style="margin:0 0 5px 0"><?= htmlspecialchars($t['nome']) ?></h4>
                    <p style="font-size:12px; opacity:0.6;"><?= $t['total_exercicios'] ?> exercícios</p>
                    <div style="display:flex; gap:8px; margin-top:15px;">
                        <a href="ver_treino.php?id=<?= $t['id_treino'] ?>" class="btn-action" style="background:#444; color:#fff; flex:1; text-align:center;">Ver</a>
                        <?php if($e_o_proprio): ?>
                            <button onclick="apagarTreino(<?= $t['id_treino'] ?>)" class="btn-action" style="background:rgba(239,68,68,0.1); color:#ef4444;"><i class="fa-solid fa-trash"></i></button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <div id="modalSeguidores" class="modal" onclick="closeListModal('modalSeguidores')">
        <div class="modal-content" onclick="event.stopPropagation()">
            <h3>Seguidores</h3>
            <?php $lista_seguidores->data_seek(0); while($s = $lista_seguidores->fetch_assoc()): ?>
                <a href="perfil.php?id=<?= $s['id_utilizador'] ?>" class="user-item">
                    <img src="<?= $s['foto_perfil'] ?: 'DefaultIcons/miku.png' ?>">
                    <span><?= htmlspecialchars($s['nome']) ?></span>
                </a>
            <?php endwhile; ?>
        </div>
    </div>
    <div id="modalSeguindo" class="modal" onclick="closeListModal('modalSeguindo')">
        <div class="modal-content" onclick="event.stopPropagation()">
            <h3>A seguir</h3>
            <?php $lista_seguindo->data_seek(0); while($s = $lista_seguindo->fetch_assoc()): ?>
                <a href="perfil.php?id=<?= $s['id_utilizador'] ?>" class="user-item">
                    <img src="<?= $s['foto_perfil'] ?: 'DefaultIcons/miku.png' ?>">
                    <span><?= htmlspecialchars($s['nome']) ?></span>
                </a>
            <?php endwhile; ?>
        </div>
    </div>

    <script>
        // Lógica de toggle exatamente igual à Dashboard
        const toggleBtn = document.getElementById('toggleSidebar');
        const sidebar = document.getElementById('sidebarIframe');
        
        toggleBtn.onclick = () => {
            const isOpen = sidebar.classList.toggle('show');
            toggleBtn.textContent = isOpen ? '<' : '>';
        };

        function previewImage(input) { if (input.files && input.files[0]) { const reader = new FileReader(); reader.onload = e => document.getElementById('profilePreview').src = e.target.result; reader.readAsDataURL(input.files[0]); } }
        function openListModal(id) { document.getElementById(id).style.display = 'flex'; }
        function closeListModal(id) { document.getElementById(id).style.display = 'none'; }
        
        const themeBtn = document.getElementById('themeBtn');
        if(themeBtn) {
            themeBtn.addEventListener('click', () => {
                document.body.classList.toggle('light-theme');
                const tema = document.body.classList.contains('light-theme') ? 'light' : 'dark';
                const fd = new FormData(); fd.append('ajax_tema', tema);
                fetch(window.location.href, { method: 'POST', body: fd });
            });
        }

        // follow/unfollow button logic
        const followBtn = document.getElementById('followBtn');
        if (followBtn) {
            followBtn.addEventListener('click', () => {
                const isFollowing = followBtn.classList.contains('btn-unfollow');
                const action = isFollowing ? 'deixar' : 'seguir';
                const fd = new FormData();
                fd.append('acao_seguir', action);

                fetch(window.location.href, { method: 'POST', body: fd })
                    .then(() => {
                        if (isFollowing) {
                            followBtn.textContent = 'Seguir';
                            followBtn.classList.remove('btn-unfollow');
                            followBtn.classList.add('btn-follow');
                        } else {
                            followBtn.textContent = 'Deixar de Seguir';
                            followBtn.classList.remove('btn-follow');
                            followBtn.classList.add('btn-unfollow');
                        }
                    });
            });
        }
    </script>
</body>
</html>