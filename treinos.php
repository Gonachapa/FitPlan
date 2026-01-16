<?php
// Ficheiro: treinos.php
session_start();

// --- LÓGICA DE TEMA DINÂMICO ---
$user_theme = isset($_SESSION['user_tema']) ? $_SESSION['user_tema'] : 'dark';

// ===============================================
// CONEXÃO COM A BASE DE DADOS
// ===============================================
$host = 'localhost'; 
$db   = 'fitness_app'; 
$user = 'root'; 
$pass = ''; 

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("❌ Erro de Conexão: " . $e->getMessage());
}

if (!isset($_SESSION['user_id'])) { $_SESSION['user_id'] = 1; }
$user_id = $_SESSION['user_id'];

// ===============================================
// LÓGICA DE GUARDAR O TREINO (POST)
// ===============================================
$message_status = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_routine') {
    $routine_title = trim($_POST['routine-title'] ?? 'Novo Treino');
    $exercise_ids = $_POST['exercise_id'] ?? [];    
    $sets_counts = $_POST['sets_count'] ?? [];      
    $rests = $_POST['rest_time'] ?? []; 
    $notes = $_POST['exercise_note'] ?? [];         
    
    if (!empty($exercise_ids)) {
        try {
            $pdo->beginTransaction();
            $stmt_treino = $pdo->prepare("INSERT INTO Treino (id_utilizador, nome) VALUES (?, ?)");
            $stmt_treino->execute([$user_id, $routine_title]);
            $new_treino_id = $pdo->lastInsertId();

            $stmt_ex = $pdo->prepare("INSERT INTO Treino_Exercicio (id_treino, id_exercicio, ordem, sets_sugeridos, tempo_descanso_seg, nota_planeada) VALUES (?, ?, ?, ?, ?, ?)");
            
            $ordem = 1;
            foreach ($exercise_ids as $key => $ex_id) {
                $sets = (int)($sets_counts[$key] ?? 3);
                $rest = (int)($rests[$key] ?? 60);
                $note_text = trim($notes[$key] ?? '');
                $stmt_ex->execute([$new_treino_id, $ex_id, $ordem, $sets, $rest, $note_text]);
                $ordem++;
            }
            $pdo->commit();
            $message_status = "✅ Treino '{$routine_title}' guardado com sucesso!";
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $message_status = "❌ Erro ao guardar: " . $e->getMessage();
        }
    } else { $message_status = "🔴 Erro: Adicione pelo menos um exercício."; }
}

// BUSCA DE DADOS
$catalog_exercises = $pdo->query("SELECT id_exercicio, nome, grupo_muscular FROM Exercicio ORDER BY nome ASC")->fetchAll();

$queryMeusTreinos = "SELECT t.*, (SELECT COUNT(*) FROM Treino_Exercicio te WHERE te.id_treino = t.id_treino) as total_exercicios 
                     FROM Treino t WHERE t.id_utilizador = ? ORDER BY t.data_criacao DESC";
$stmtT = $pdo->prepare($queryMeusTreinos);
$stmtT->execute([$user_id]);
$meus_treinos = $stmtT->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fit Plan - Treinos</title>
    <link rel="stylesheet" href="sidebar/button.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        :root {
            <?php if ($user_theme === 'light'): ?>
                --background-primary: #f8fafc;
                --card-background: #ffffff;
                --input-background: #f1f5f9;
                --border-color: #cbd5e1;
                --text-light: #0f172a;
                --text-muted: #64748b;
                --accent-blue: #2563eb;
            <?php else: ?>
                --background-primary: #0c0f16; 
                --card-background: #1a1d23; 
                --input-background: #1f2229; 
                --border-color: #3a3d45; 
                --text-light: #ffffff;
                --text-muted: #aaaaaa;
                --accent-blue: #3b82f6; 
            <?php endif; ?>
        }

        body {
            margin: 0; background-color: var(--background-primary);
            color: var(--text-light); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            transition: 0.3s; overflow-x: hidden;
        }

        /* === SIDEBAR (Restaurada conforme perfil.php) === */
        #sidebarIframe { 
            position: fixed; 
            left: -300px; 
            top: 0; 
            width: 300px; 
            height: 100vh; 
            border: none; 
            z-index: 1000; 
            transition: 0.3s;
            background: var(--card-background);
        }
        #sidebarIframe.show { left: 0; }
        
        #toggleSidebar { 
            /* Estilo vindo do teu sidebar/button.css, mas garantindo posição */
            position: fixed;
            z-index: 1100;
        }

        /* === CONTEÚDO === */
        .main-container { 
            display: grid; 
            grid-template-columns: 1fr 350px; 
            gap: 30px; 
            padding: 60px 80px; 
            max-width: 1400px; 
            margin: 0 auto;
            transition: 0.3s;
        }

        .treinos-section { grid-column: 1 / -1; margin-bottom: 40px; }
        .treinos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .treino-card-mini { 
            background: var(--card-background); border: 1px solid var(--border-color); 
            padding: 20px; border-radius: 12px; transition: 0.3s;
        }

        .routine-title-input {
            background: transparent; border: none; border-bottom: 2px solid var(--border-color);
            color: var(--text-light); font-size: 2.5em; font-weight: 800; width: 100%;
            padding: 10px 0; outline: none; margin-bottom: 30px;
        }

        .exercise-card { background: var(--card-background); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        textarea { width: 100%; background: var(--input-background); color: var(--text-light); border: 1px solid var(--border-color); padding: 12px; border-radius: 8px; margin-bottom: 15px; font-family: inherit; resize: vertical; }

        .exercise-library { background: var(--card-background); border: 1px solid var(--border-color); border-radius: 15px; padding: 20px; height: 75vh; overflow-y: auto; position: sticky; top: 40px; }
        .library-item { display: flex; align-items: center; gap: 12px; padding: 10px; border-bottom: 1px solid var(--border-color); }
        .btn-plus { background: var(--accent-blue); color: white; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-weight: bold; }

        .btn-save-main { background: var(--accent-blue); color: white; border: none; padding: 20px; border-radius: 15px; width: 100%; font-weight: 800; font-size: 1.1em; cursor: pointer; margin-top: 20px; }
        
        .input-row { display: flex; gap: 15px; margin-bottom: 15px; }
        .input-row div { flex: 1; }
        .input-row label { display: block; font-size: 11px; color: var(--text-muted); margin-bottom: 5px; text-transform: uppercase; font-weight: bold; }
        .mini-input { width: 100%; background: var(--input-background); border: 1px solid var(--border-color); color: var(--text-light); padding: 10px; border-radius: 8px; box-sizing: border-box; }

        .status-bar { position: fixed; top: 0; width: 100%; padding: 15px; text-align: center; z-index: 2000; font-weight: 700; }
        .success { background: #2ecc71; color: white; }
    </style>
</head>
<body>

    <button id="toggleSidebar">></button>
    <iframe id="sidebarIframe" src="sidebar/sidebar.php"></iframe>

    <?php if ($message_status): ?>
        <div class="status-bar success"><?= $message_status ?></div>
        <script>setTimeout(() => document.querySelector('.status-bar').remove(), 3000);</script>
    <?php endif; ?>

    <div class="main-container">
        
        <section class="treinos-section">
            <h2 style="border-left: 4px solid var(--accent-blue); padding-left: 15px;">Meus Treinos</h2>
            <div class="treinos-grid">
                <?php if (empty($meus_treinos)): ?>
                    <p style="color: var(--text-muted)">Ainda não criou treinos.</p>
                <?php else: ?>
                    <?php foreach ($meus_treinos as $t): ?>
                        <div class="treino-card-mini">
                            <h4 style="margin:0 0 10px 0"><?= htmlspecialchars($t['nome']) ?></h4>
                            <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 15px;">
                                <i class="fa-solid fa-dumbbell"></i> <?= $t['total_exercicios'] ?> exercícios
                            </div>
                            <a href="editar_treino.php?id=<?= $t['id_treino'] ?>" style="color: var(--accent-blue); text-decoration: none; font-weight: bold; font-size: 13px;">Editar</a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <div class="routine-editor">
            <form method="POST" id="routineForm">
                <input type="hidden" name="action" value="save_routine">
                <input type="text" name="routine-title" class="routine-title-input" placeholder="Novo Treino" required>

                <div id="exercisesContainer">
                    <div id="emptyMsg" style="text-align:center; padding: 60px; border: 2px dashed var(--border-color); border-radius: 20px; color: var(--text-muted);">
                        <i class="fa-solid fa-layer-group" style="font-size: 3em; display: block; margin-bottom: 10px;"></i>
                        Adicione exercícios do catálogo para montar a sua rotina.
                    </div>
                </div>

                <button type="submit" class="btn-save-main">
                    <i class="fa-solid fa-save"></i> GUARDAR TREINO
                </button>
            </form>
        </div>

        <aside class="exercise-library">
            <h3 style="margin-top:0"><i class="fa-solid fa-dumbbell"></i> Exercícios</h3>
            <div id="libList">
                <?php foreach ($catalog_exercises as $ex): ?>
                    <div class="library-item">
                        <button type="button" class="btn-plus" onclick="addExercise('<?= $ex['id_exercicio'] ?>', '<?= addslashes($ex['nome']) ?>', '<?= $ex['grupo_muscular'] ?>')">+</button>
                        <div>
                            <div style="font-weight:700"><?= htmlspecialchars($ex['nome']) ?></div>
                            <div style="font-size:0.8em; color:var(--text-muted)"><?= htmlspecialchars($ex['grupo_muscular']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </aside>
    </div>

    <script>
        // Lógica da Sidebar (EXATAMENTE COMO NO PERFIL)
        const toggleButton = document.getElementById('toggleSidebar');
        const sidebar = document.getElementById('sidebarIframe');

        if (toggleButton && sidebar) {
            toggleButton.addEventListener('click', () => {
                sidebar.classList.toggle('show');
                toggleButton.textContent = sidebar.classList.contains('show') ? '<' : '>';
            });
        }

        // Lógica de Exercícios
        const container = document.getElementById('exercisesContainer');
        const emptyMsg = document.getElementById('emptyMsg');

        function addExercise(id, name, muscle) {
            emptyMsg.style.display = 'none';
            const div = document.createElement('div');
            div.className = 'exercise-card';
            div.innerHTML = `
                <div style="display:flex; justify-content:space-between; margin-bottom:15px; align-items: center;">
                    <strong style="color:var(--accent-blue); font-size: 1.2em;">${name} <small style="font-weight:normal; color:var(--text-muted)">(${muscle})</small></strong>
                    <i class="fa-solid fa-xmark" style="color:#ef4444; cursor:pointer; font-size: 1.3em;" onclick="this.closest('.exercise-card').remove(); checkEmpty();"></i>
                </div>
                <input type="hidden" name="exercise_id[]" value="${id}">
                
                <div class="input-row">
                    <div>
                        <label>Séries Sugeridas</label>
                        <input type="number" name="sets_count[]" value="3" class="mini-input">
                    </div>
                    <div>
                        <label>Descanso (segundos)</label>
                        <input type="number" name="rest_time[]" value="60" class="mini-input">
                    </div>
                </div>

                <label style="display:block; font-size:11px; color:var(--text-muted); margin-bottom:5px; text-transform:uppercase; font-weight:bold;">Notas de Planeamento</label>
                <textarea name="exercise_note[]" placeholder="Ex: Focar na fase excêntrica, 2 segundos..."></textarea>
            `;
            container.appendChild(div);
        }

        function checkEmpty() {
            if(container.querySelectorAll('.exercise-card').length === 0) emptyMsg.style.display = 'block';
        }
    </script>
</body>
</html>