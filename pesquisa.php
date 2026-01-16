<?php
// Ficheiro: pesquisa.php
session_start();

// Configurações do Tema e ID
$user_id = $_SESSION['user_id'] ?? 1;
$user_theme = $_SESSION['user_tema'] ?? 'dark';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=fitness_app;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na ligação: " . $e->getMessage());
}

$query_termo = $_GET['q'] ?? '';

// Pesquisa Completa na Tabela 'Utilizador'
$resultados = [];
if (!empty($query_termo)) {
    $stmt = $pdo->prepare("SELECT id_utilizador, nome, foto_perfil, biografia FROM Utilizador WHERE nome LIKE ?");
    $stmt->execute(['%' . $query_termo . '%']);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <title>Pesquisa - Fit Plan</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --bg: <?= $user_theme === 'light' ? '#f8fafc' : '#0c0f16' ?>;
            --card: <?= $user_theme === 'light' ? '#ffffff' : '#1a1d23' ?>;
            --text: <?= $user_theme === 'light' ? '#1e293b' : '#f8fafc' ?>;
            --border: <?= $user_theme === 'light' ? '#e2e8f0' : '#333742' ?>;
            --accent: #2a62fc;
        }

        body { background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; margin: 0; padding: 50px; }
        
        .header-search { max-width: 1200px; margin: 0 auto 40px; }
        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .user-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
        }

        .user-card:hover {
            transform: translateY(-8px);
            border-color: var(--accent);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .user-avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 15px;
            border: 3px solid var(--accent);
            display: block;
        }

        .user-card h3 { margin: 10px 0; font-size: 20px; }
        .user-card p { font-size: 14px; opacity: 0.6; line-height: 1.5; height: 42px; overflow: hidden; }

        .btn-view {
            margin-top: 15px;
            display: inline-block;
            background: var(--accent);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: bold;
        }

        .empty-state {
            text-align: center;
            grid-column: 1 / -1;
            padding: 100px;
            opacity: 0.5;
        }
    </style>
</head>
<body>

    <div class="header-search">
        <a href="dashboard.php" style="color: var(--accent); text-decoration: none; font-weight: bold;">
            <i class="fa-solid fa-arrow-left"></i> Voltar ao Dashboard
        </a>
        <h1 style="margin-top: 20px;">
            Resultados para "<span style="color: var(--accent);"><?= htmlspecialchars($query_termo) ?></span>"
        </h1>
        <p><?= count($resultados) ?> utilizadores encontrados.</p>
    </div>

    <div class="results-grid">
        <?php if (!empty($resultados)): ?>
            <?php foreach ($resultados as $user): 
                $foto = !empty($user['foto_perfil']) ? htmlspecialchars($user['foto_perfil']) : 'DefaultIcons/miku.png';
            ?>
                <a href="perfil.php?id=<?= $user['id_utilizador'] ?>" class="user-card">
                    <img src="<?= $foto ?>" class="user-avatar" alt="Foto de <?= htmlspecialchars($user['nome']) ?>">
                    <h3><?= htmlspecialchars($user['nome']) ?></h3>
                    <p><?= htmlspecialchars($user['biografia'] ?: 'Iniciante no Fit Plan...') ?></p>
                    <span class="btn-view">Ver Perfil Completo</span>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-magnifying-glass" style="font-size: 50px; margin-bottom: 20px;"></i>
                <h2>Não encontramos ninguém com esse nome.</h2>
                <p>Tenta pesquisar apenas pelo primeiro nome ou apelido.</p>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>