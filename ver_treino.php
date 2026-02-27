<?php
session_start();

// 1. CONEXÃO
$servername = "localhost";
$username = "root";    
$password = "";        
$dbname = "fitness_app";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Erro fatal: " . $conn->connect_error); }

// 2. SEGURANÇA E PARÂMETROS
$treino_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id_logado = $_SESSION['user_id'] ?? null;

if (!$treino_id) {
    header("Location: perfil.php");
    exit;
}

// 3. CARREGAR DADOS DO TREINO E DO CRIADOR
$queryTreino = "SELECT t.*, u.nome as criador, u.foto_perfil, u.id_utilizador as dono_id 
                FROM Treino t 
                JOIN Utilizador u ON t.id_utilizador = u.id_utilizador 
                WHERE t.id_treino = ?";
$stmt = $conn->prepare($queryTreino);
$stmt->bind_param("i", $treino_id);
$stmt->execute();
$treino = $stmt->get_result()->fetch_assoc();

if (!$treino) { die("Treino não encontrado."); }

// Se o treino for privado e o utilizador logado não for o dono, negar acesso
if ($treino['privado'] && $user_id_logado !== $treino['dono_id']) {
    header("Location: perfil.php?id=" . $treino['dono_id'] . "&erro=acesso_negado");
    exit;
}

// 4. CARREGAR EXERCÍCIOS (Usando as tuas colunas exatas)
$queryExs = "SELECT e.nome, e.grupo_muscular, e.equipamento, te.sets_sugeridos, te.nota_planeada, te.tempo_descanso_seg, te.ordem 
             FROM Treino_Exercicio te 
             JOIN Exercicio e ON te.id_exercicio = e.id_exercicio 
             WHERE te.id_treino = ? 
             ORDER BY te.ordem ASC";
$stmtEx = $conn->prepare($queryExs);
$stmtEx->bind_param("i", $treino_id);
$stmtEx->execute();
$exercicios = $stmtEx->get_result();

$interface_theme = $_SESSION['user_tema'] ?? 'dark';
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($treino['nome']) ?> - Fit Plan</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary: #3b82f6;
            --bg-body: #0c0f16;
            --card-bg: #1a1d23;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border: #333742;
        }

        body.light-theme {
            --bg-body: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
        }

        body { font-family: 'Inter', sans-serif; background: var(--bg-body); color: var(--text-main); margin: 0; transition: 0.3s; }
        
        #sidebarIframe { position: fixed; left: 0; top: 0; width: 300px; height: 100vh; border: none; z-index: 1000; }
        .main-content { margin-left: 300px; padding: 40px 20px; display: flex; justify-content: center; }
        .container { width: 100%; max-width: 800px; }

        /* Cabeçalho */
        .treino-header { 
            background: var(--card-bg); padding: 30px; border-radius: 20px; border: 1px solid var(--border);
            margin-bottom: 30px; position: relative;
        }
        .badge { background: var(--primary); color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        
        /* Lista de Exercícios */
        .ex-card { 
            background: var(--card-bg); border: 1px solid var(--border); border-radius: 16px; 
            margin-bottom: 15px; padding: 20px; display: flex; align-items: center; gap: 20px;
        }
        .ex-order { font-size: 24px; font-weight: 900; color: var(--primary); opacity: 0.4; min-width: 40px; }
        .ex-main { flex: 1; }
        .ex-name { font-size: 18px; font-weight: bold; margin-bottom: 4px; }
        .ex-meta { font-size: 12px; color: var(--text-muted); display: flex; gap: 15px; }

        .stats-row { display: flex; gap: 20px; margin-top: 15px; border-top: 1px solid var(--border); padding-top: 15px; }
        .stat-box { display: flex; flex-direction: column; }
        .stat-label { font-size: 10px; text-transform: uppercase; color: var(--text-muted); letter-spacing: 1px; }
        .stat-value { font-size: 16px; font-weight: 600; color: var(--primary); }

        .note-bubble { 
            background: rgba(59, 130, 246, 0.05); border-left: 3px solid var(--primary); 
            padding: 10px; border-radius: 0 8px 8px 0; margin-top: 10px; font-size: 13px; font-style: italic;
        }

        .btn-back { display: inline-flex; align-items: center; gap: 8px; color: var(--text-main); text-decoration: none; margin-bottom: 20px; font-size: 14px; opacity: 0.7; }
        .btn-back:hover { opacity: 1; color: var(--primary); }
    </style>
</head>
<body class="<?= $interface_theme === 'light' ? 'light-theme' : '' ?>">

    <iframe id="sidebarIframe" src="sidebar/sidebar.php"></iframe>

    <div class="main-content">
        <div class="container">
            <a href="perfil.php?id=<?= $treino['dono_id'] ?>" class="btn-back">
                <i class="fa-solid fa-chevron-left"></i> Voltar ao Perfil
            </a>

            <div class="treino-header">
                <span class="badge"><?= htmlspecialchars($treino['objetivo'] ?: 'Treino') ?></span>
                <h1 style="margin: 15px 0 10px 0;"><?= htmlspecialchars($treino['nome']) ?></h1>
                <p style="color: var(--text-muted); margin-bottom: 20px;"><?= htmlspecialchars($treino['descricao']) ?></p>
                
                <div style="display: flex; align-items: center; gap: 10px; border-top: 1px solid var(--border); padding-top: 20px;">
                    <img src="<?= $treino['foto_perfil'] ?: 'DefaultIcons/miku.png' ?>" style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover;">
                    <span style="font-size: 14px; opacity: 0.8;">Criado por <strong><?= htmlspecialchars($treino['criador']) ?></strong></span>
                </div>

                <?php if ($treino['dono_id'] == $user_id_logado): ?>
                    <a href="editar_treino.php?id=<?= $treino_id ?>" style="position: absolute; top: 30px; right: 30px; color: var(--primary);">
                        <i class="fa-solid fa-pen-to-square fa-lg"></i>
                    </a>
                <?php endif; ?>
            </div>

            

            <h3 style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-list-ol" style="color: var(--primary);"></i> 
                Sequência do Treino
            </h3>

            <?php if ($exercicios->num_rows > 0): ?>
                <?php while ($ex = $exercicios->fetch_assoc()): ?>
                    <div class="ex-card">
                        <div class="ex-order"><?= sprintf("%02d", $ex['ordem']) ?></div>
                        
                        <div class="ex-main">
                            <div class="ex-name"><?= htmlspecialchars($ex['nome']) ?></div>
                            <div class="ex-meta">
                                <span><i class="fa-solid fa-dumbbell"></i> <?= htmlspecialchars($ex['grupo_muscular']) ?></span>
                                <span><i class="fa-solid fa-screwdriver-wrench"></i> <?= htmlspecialchars($ex['equipamento']) ?></span>
                            </div>

                            <div class="stats-row">
                                <div class="stat-box">
                                    <span class="stat-label">Séries sugeridas</span>
                                    <span class="stat-value"><?= $ex['sets_sugeridos'] ?> Sets</span>
                                </div>
                                <div class="stat-box">
                                    <span class="stat-label">Descanso</span>
                                    <span class="stat-value"><?= $ex['tempo_descanso_seg'] ?: '60' ?> segundos</span>
                                </div>
                            </div>

                            <?php if (!empty($ex['nota_planeada'])): ?>
                                <div class="note-bubble">
                                    <i class="fa-solid fa-quote-left" style="font-size: 10px; opacity: 0.5;"></i>
                                    <?= htmlspecialchars($ex['nota_planeada']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 50px; background: var(--card-bg); border-radius: 20px; border: 1px dashed var(--border);">
                    <i class="fa-solid fa-layer-group" style="font-size: 40px; opacity: 0.2; margin-bottom: 15px;"></i>
                    <p style="opacity: 0.5;">Este treino ainda não tem exercícios adicionados.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>