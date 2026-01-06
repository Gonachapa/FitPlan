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

$pdo = null;
$message_status = null;

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    $message_status = "❌ Erro de Conexão: " . $e->getMessage();
    $pdo = false; 
}

// Simulação de Utilizador (Ajustar conforme o teu login)
if (!isset($_SESSION['user_id'])) { $_SESSION['user_id'] = 1; }
$user_id = $_SESSION['user_id'];

// ===============================================
// LÓGICA DE GUARDAR O TREINO (POST)
// ===============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_routine' && $pdo) {
    $routine_title = trim($_POST['routine-title'] ?? 'Novo Treino');
    $exercise_ids = $_POST['exercise_id'] ?? [];    
    $sets_counts = $_POST['sets_count'] ?? [];      
    $notes = $_POST['exercise_note'] ?? [];         
    
    if (!empty($exercise_ids)) {
        try {
            $pdo->beginTransaction();
            $stmt_treino = $pdo->prepare("INSERT INTO Treino (id_utilizador, nome) VALUES (?, ?)");
            $stmt_treino->execute([$user_id, $routine_title]);
            $new_treino_id = $pdo->lastInsertId();

            $stmt_ex = $pdo->prepare("INSERT INTO Treino_Exercicio (id_treino, id_exercicio, ordem, sets_sugeridos, nota_planeada) VALUES (?, ?, ?, ?, ?)");
            $ordem = 1;
            foreach ($exercise_ids as $key => $ex_id) {
                $sets = (int)($sets_counts[$key] ?? 1);
                $note_text = trim($notes[$key] ?? '');
                $stmt_ex->execute([$new_treino_id, $ex_id, $ordem, $sets, $note_text]);
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
$catalog_exercises = [];
if ($pdo) {
    $catalog_exercises = $pdo->query("SELECT id_exercicio, nome, grupo_muscular FROM Exercicio ORDER BY nome ASC")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fit Plan - Treinos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        /* === CONFIGURAÇÃO DE TEMAS DINÂMICOS === */
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

        html, body {
            margin: 0;
            height: 100%;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--background-primary);
            color: var(--text-light);
        }

        /* === ESTILOS DA SIDEBAR E BOTÃO (FIXO) === */
        iframe { border: none; }

        #sidebarIframe {
            position: fixed;
            top: 0;
            left: -300px;
            width: 300px;
            height: 100%;
            border: none;
            transition: left 0.3s ease;
            z-index: 1000;
            box-shadow: 2px 0 5px rgba(0,0,0,0.3);
        }

        #sidebarIframe.show {
            left: 0;
        }

        #toggleSidebar {
            position: fixed;
            top: 55%;
            left: 0;
            transform: translateY(-50%);
            padding: 10px 15px;
            background-color: #0f1115;
            color: white;
            border: none;
            cursor: pointer;
            z-index: 1100;
            border-radius: 0 5px 5px 0;
            font-size: 18px;
            transition: background-color 0.3s;
        }

        #toggleSidebar:hover {
            background-color: #2a62fc;
        }

        /* === CONTEÚDO PRINCIPAL === */
        .main-container {
            display: flex; gap: 30px; padding: 60px 80px;
            max-width: 1400px; margin: 0 auto;
        }

        .routine-editor-form { flex: 2; }

        .routine-title-input {
            background: transparent; border: none; border-bottom: 2px solid var(--border-color);
            color: var(--text-light); font-size: 2.5em; font-weight: 800; width: 100%;
            padding: 10px 0; outline: none; margin-bottom: 30px;
        }

        .exercise-card {
            background: var(--card-background); border: 1px solid var(--border-color);
            border-radius: 12px; padding: 20px; margin-bottom: 20px;
        }

        .card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px; }
        .exercise-name { font-weight: 700; font-size: 1.2em; }
        .delete-btn { color: #ff4444; cursor: pointer; font-size: 1.2em; }

        textarea {
            width: 100%; background: var(--input-background); color: var(--text-light);
            border: 1px solid var(--border-color); padding: 12px; border-radius: 8px;
            resize: vertical; box-sizing: border-box; margin-bottom: 15px; font-family: inherit;
        }

        .sets-table { width: 100%; border-collapse: collapse; }
        .sets-table th { color: var(--text-muted); font-size: 0.8em; text-align: center; padding: 8px; }
        .sets-table td { text-align: center; padding: 5px; }
        .sets-table input {
            background: var(--input-background); border: 1px solid var(--border-color);
            color: var(--text-light); padding: 8px; border-radius: 6px; width: 70px; text-align: center;
        }

        .btn-add-set {
            width: 100%; background: transparent; color: var(--accent-blue);
            border: 2px dashed var(--accent-blue); padding: 10px; border-radius: 10px;
            cursor: pointer; margin-top: 10px; font-weight: 700; transition: 0.2s;
        }
        .btn-add-set:hover { background: rgba(59, 130, 246, 0.1); }

        /* === BIBLIOTECA === */
        .exercise-library {
            flex: 1; background: var(--card-background); border-radius: 15px;
            padding: 20px; border: 1px solid var(--border-color);
            height: fit-content; position: sticky; top: 40px;
        }

        .library-item {
            display: flex; align-items: center; gap: 12px; padding: 10px;
            border-bottom: 1px solid var(--border-color);
        }

        .btn-plus {
            background: var(--accent-blue); color: white; border: none;
            width: 32px; height: 32px; border-radius: 50%; cursor: pointer;
        }

        .status-bar {
            padding: 15px; text-align: center; font-weight: 700;
            position: fixed; top: 0; width: 100%; z-index: 1200;
        }
        .success { background: #2ecc71; color: white; }
        .error { background: #e74c3c; color: white; }

        .btn-save-main {
            background: var(--accent-blue); color: white; border: none;
            padding: 20px; border-radius: 15px; width: 100%; font-weight: 800;
            font-size: 1.1em; cursor: pointer; margin-top: 20px;
        }
    </style>
</head>
<body>

    <iframe id="sidebarIframe" src="sidebar/sidebar.php"></iframe> 
    <button id="toggleSidebar"><i class="fa-solid fa-chevron-right"></i></button>

    <?php if ($message_status): ?>
        <div class="status-bar <?= strpos($message_status, '✅') !== false ? 'success' : 'error' ?>">
            <?= $message_status ?>
        </div>
    <?php endif; ?>

    <div class="main-container">
        
        <form method="POST" class="routine-editor-form" id="routineForm">
            <input type="hidden" name="action" value="save_routine">
            <input type="text" name="routine-title" class="routine-title-input" placeholder="Novo Treino" required>

            <div id="exercisesContainer">
                <div style="text-align:center; padding: 50px; color: var(--text-muted); border: 2px dashed var(--border-color); border-radius: 20px;" id="emptyMsg">
                    <i class="fa-solid fa-layer-group" style="font-size: 3em; display: block; margin-bottom: 10px;"></i>
                    <p>Adicione exercícios do catálogo para montar a sua rotina.</p>
                </div>
            </div>

            <button type="submit" class="btn-save-main">
                <i class="fa-solid fa-save"></i> GUARDAR TREINO
            </button>
        </form>

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
        // Lógica da Sidebar (Apenas o Iframe se move)
        const toggleBtn = document.getElementById('toggleSidebar');
        const sidebar = document.getElementById('sidebarIframe');

        toggleBtn.onclick = () => {
            const isOpen = sidebar.classList.toggle('show');
            // Altera apenas o ícone, o botão fica parado no left: 0
            toggleBtn.innerHTML = isOpen ? 
                '<i class="fa-solid fa-chevron-left"></i>' : '<i class="fa-solid fa-chevron-right"></i>';
        };

        // Lógica de Exercícios
        const container = document.getElementById('exercisesContainer');
        const emptyMsg = document.getElementById('emptyMsg');

        function addExercise(id, name, muscle) {
            emptyMsg.style.display = 'none';
            const div = document.createElement('div');
            div.className = 'exercise-card';
            div.innerHTML = `
                <div class="card-header">
                    <span class="exercise-name">${name} <small style="font-weight:normal; opacity: 0.7;">(${muscle})</small></span>
                    <input type="hidden" name="exercise_id[]" value="${id}">
                    <i class="fa-solid fa-xmark delete-btn" onclick="this.closest('.exercise-card').remove(); checkEmpty();"></i>
                </div>
                <textarea name="exercise_note[]" placeholder="Notas e observações..."></textarea>
                <table class="sets-table">
                    <thead><tr><th>SÉRIE</th><th>CARGA (KG)</th><th>REPS</th></tr></thead>
                    <tbody class="sets-body">
                        <tr><td>1</td><td><input type="number" step="0.5" placeholder="0"></td><td><input type="number" value="10"></td></tr>
                    </tbody>
                </table>
                <input type="hidden" name="sets_count[]" value="1" class="scount">
                <button type="button" class="btn-add-set" onclick="addSet(this)">+ ADICIONAR SÉRIE</button>
            `;
            container.appendChild(div);
        }

        function addSet(btn) {
            const tbody = btn.parentElement.querySelector('.sets-body');
            const counter = btn.parentElement.querySelector('.scount');
            const num = tbody.rows.length + 1;
            const row = tbody.insertRow();
            row.innerHTML = `<td>${num}</td><td><input type="number" step="0.5" placeholder="0"></td><td><input type="number" value="10"></td>`;
            counter.value = num;
        }

        function checkEmpty() {
            if(container.querySelectorAll('.exercise-card').length === 0) emptyMsg.style.display = 'block';
        }
    </script>
</body>
</html>