<?php
// Ficheiro: treinos.php (Tudo num só ficheiro com conexão direta)
session_start();

// ===============================================
// DADOS DE CONEXÃO COM A BASE DE DADOS (PREENCHER!)
// ===============================================
$host = 'localhost'; 
$db   = 'fitness_app'; 
$user = 'root'; 
$pass = ''; 

// ===============================================
// INCLUIR CONEXÃO COM A BASE DE DADOS (PDO)
// ===============================================
$pdo = null;
$message_status = null;
try {
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE               => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE    => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES      => false,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    $message_status = "❌ Erro Crítico: Falha na conexão com a base de dados. Detalhe: " . $e->getMessage();
    $pdo = false; 
}

// 2. Simulação de Utilizador Logado (ou checagem real)
if (!isset($_SESSION['user_id'])) {
    // Para fins de teste, define um ID de utilizador temporário se não houver login
    $_SESSION['user_id'] = 123; 
    $_SESSION['user_nome'] = 'Utilizador Teste';
}

$user_id = $_SESSION['user_id'];
$routine_title = "Novo Treino"; 
$exercises_in_routine = []; 

// ===============================================
// LÓGICA DE GUARDAR O TREINO (POST)
// ===============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_routine' && $pdo !== false) {
    
    $routine_title = trim($_POST['routine-title'] ?? 'Treino Sem Nome');
    $exercise_ids = $_POST['exercise_id'] ?? [];    
    $sets_counts = $_POST['sets_count'] ?? [];      
    $notes = $_POST['exercise_note'] ?? [];         
    
    // Validação básica
    if (empty($routine_title) || empty($exercise_ids)) {
        $message_status = "🔴 Erro: O treino deve ter um nome e pelo menos um exercício.";
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Inserir o novo treino
            $sql_treino = "INSERT INTO Treino (id_utilizador, nome) VALUES (:user_id, :nome)";
            $stmt_treino = $pdo->prepare($sql_treino);
            $stmt_treino->execute(['user_id' => $user_id, 'nome' => $routine_title]);
            $new_treino_id = $pdo->lastInsertId();

            // 2. Inserir os exercícios do treino
            $sql_ex = "INSERT INTO Treino_Exercicio (id_treino, id_exercicio, ordem, sets_sugeridos, nota_planeada) 
                        VALUES (:id_treino, :id_exercicio, :ordem, :sets, :nota)";
            $stmt_ex = $pdo->prepare($sql_ex);
            
            $ordem = 1;
            foreach ($exercise_ids as $key => $exercicio_id) {
                
                // Os arrays devem ter o mesmo número de elementos
                $sets = (int)($sets_counts[$key] ?? 3);
                $note_text = trim($notes[$key] ?? '');
                
                if ((int)$exercicio_id > 0) {
                    $stmt_ex->execute([
                        'id_treino'     => $new_treino_id,
                        'id_exercicio'  => $exercicio_id,
                        'ordem'         => $ordem,
                        'sets'          => $sets,
                        'nota'          => $note_text
                    ]);
                    $ordem++;
                }
            }

            $pdo->commit();
            $message_status = "✅ Sucesso! O treino '{$routine_title}' foi guardado com sucesso. ID: {$new_treino_id}";

            // Limpa o formulário após guardar
            $routine_title = 'Novo Treino';

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message_status = "❌ Erro ao guardar o treino: " . $e->getMessage();
        }
    }
}


// ===============================================
// LÓGICA DE BUSCA DE EXERCÍCIOS E FILTROS (BD)
// ===============================================
$catalog_exercises = [];
$equipment_options = [];
$muscle_options = [];

if ($pdo !== false) {
    try {
        // 1. Busca todos os exercícios (Catálogo)
        $stmt_catalog = $pdo->query("SELECT id_exercicio, nome, grupo_muscular, equipamento FROM Exercicio ORDER BY grupo_muscular, nome ASC");
        $catalog_exercises = $stmt_catalog->fetchAll(PDO::FETCH_ASSOC);

        // 2. Busca de valores únicos para o filtro de EQUIPAMENTO
        $stmt_equip = $pdo->query("SELECT DISTINCT equipamento FROM Exercicio ORDER BY equipamento ASC");
        $equipment_options = $stmt_equip->fetchAll(PDO::FETCH_COLUMN);

        // 3. Busca de valores únicos para o filtro de MÚSCULO
        $stmt_muscle = $pdo->query("SELECT DISTINCT grupo_muscular FROM Exercicio ORDER BY grupo_muscular ASC");
        $muscle_options = $stmt_muscle->fetchAll(PDO::FETCH_COLUMN);

    } catch (PDOException $e) {
        $message_status = (is_null($message_status)) ? "❌ Erro ao carregar o catálogo/filtros: " . $e->getMessage() : $message_status;
    }
}


// --- FIM DO CÓDIGO PHP ---
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Rotina - <?= htmlspecialchars($routine_title) ?> - Fit Plan</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <link rel="stylesheet" href="style.css"> 
    
    <link rel="stylesheet" href="sidebar/button.css">
    
    <style>
        /* =========================================== */
        /* == TEMA ESCURO E ESTILOS GERAIS == */
        /* =========================================== */

        :root {
            /* Definido o Tema de Cores Escuras */
            --background-primary: #0c0f16; 
            --card-background: #1a1d23; 
            --input-background: #1f2229; 
            --border-color: #3a3d45; 
            --text-light: #ffffff;
            --text-muted: #aaaaaa;
            --accent-blue: #3b82f6; 
            --accent-blue-hover: #2563eb; 
            --sidebar-width: 280px; 
            --main-padding-top: 30px; 
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--background-primary);
            color: var(--text-light);
            margin: 0; padding: 0; line-height: 1.6;
            display: flex; flex-direction: column; min-height: 100vh;
            transition: background-color 0.3s, color 0.3s;
            /* Garante que o body não tem padding que interfira com a sidebar */
            padding-top: 0; 
        }

        /* --- Container Principal (Layout de 2 Colunas) --- */
        .main-container {
            display: flex; flex: 1; max-width: 1400px; 
            margin: 0 auto; padding: var(--main-padding-top) 30px 20px 30px;
            width: 100%; gap: 30px;
        }
        .routine-editor-form { flex: 2; }
        .routine-editor { padding-right: 0; }
        .exercise-library {
            flex: 1; background-color: var(--card-background); padding: 25px; border-radius: 12px; 
            min-width: 350px;
            position: sticky; top: var(--main-padding-top); align-self: flex-start; 
            max-height: calc(100vh - 2 * var(--main-padding-top)); 
            overflow-y: auto;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }

        /* --- Formulário e Inputs --- */
        label { 
            display: block; font-size: 0.9em; color: var(--text-muted); 
            margin-bottom: 5px; margin-top: 15px; font-weight: 500; 
        }
        input[type="text"], input[type="number"], textarea, select {
            width: 100%; padding: 10px 15px; 
            background-color: var(--input-background); 
            border: 1px solid var(--border-color);
            border-radius: 8px; 
            color: var(--text-light); 
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        input[type="text"]:focus, input[type="number"]:focus, textarea:focus, select:focus {
            border-color: var(--accent-blue);
            box-shadow: 0 0 5px rgba(59, 130, 246, 0.5);
        }
        .routine-title-section { margin-bottom: 25px; }
        .routine-title-section input { font-size: 1.5em; font-weight: 700; padding: 12px 15px; }
        
        /* ESTILO GLOBAL PARA BOTÕES DE AÇÃO */
        .update-button {
            background-color: var(--accent-blue); 
            color: var(--text-light); 
            border: none; 
            padding: 10px 25px; 
            border-radius: 8px; 
            font-weight: bold; 
            cursor: pointer; 
            transition: background-color 0.2s, box-shadow 0.2s;
        }
        .update-button:hover {
            background-color: var(--accent-blue-hover);
            box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3);
        }

        /* ESTILO ESPECÍFICO PARA O BOTÃO GUARDAR NO FUNDO */
        .save-button-container {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
            text-align: center; 
        }
        .save-button-container .update-button {
            width: 50%; 
            max-width: 300px;
            font-size: 1.1em;
            padding: 12px 25px;
        }

        /* --- Cartão de Exercício e Sets --- */
        .exercise-card { background-color: var(--card-background); border-radius: 12px; padding: 20px; margin-bottom: 20px; border: 1px solid var(--border-color); box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2); transition: transform 0.2s; }
        .card-header { display: flex; align-items: center; margin-bottom: 15px; cursor: move; }
        .card-header .fa-dumbbell { font-size: 1.8em; margin-right: 15px; color: var(--accent-blue); }
        .exercise-name { font-size: 1.2em; font-weight: bold; flex-grow: 1; }
        .delete-exercise { color: #ff5555; font-size: 1.5em; cursor: pointer; transition: color 0.2s; margin-left: 10px; } 
        .delete-exercise:hover { color: #ff0000; }
        .card-body { padding-left: 0; }
        textarea { min-height: 80px; resize: vertical; }
        .sets-table-container { margin-top: 20px; }
        .sets-table-container table { width: 100%; border-collapse: separate; border-spacing: 0 5px; }
        .sets-table-container thead th { color: var(--text-muted); font-weight: 600; padding: 8px 10px; text-align: left; font-size: 0.9em; border-bottom: 1px solid var(--border-color); }
        .set-input { width: 100%; padding: 8px; text-align: center; font-weight: bold; border: 1px solid var(--border-color); border-radius: 6px; background-color: var(--input-background); color: var(--text-light); }
        .add-set-button { background: none; color: var(--accent-blue); border: 2px dashed var(--accent-blue); padding: 10px 15px; border-radius: 8px; cursor: pointer; width: 100%; font-weight: bold; margin-top: 10px; transition: background-color 0.2s, opacity 0.2s; }
        .add-set-button:hover { background-color: rgba(59, 130, 246, 0.1); opacity: 0.9; }
        .empty-routine-message { text-align: center; padding: 50px 20px; background-color: var(--card-background); border: 2px dashed var(--border-color); border-radius: 12px; color: var(--text-muted); font-size: 1.2em; margin-top: 20px; display: flex; flex-direction: column; justify-content: center; align-items: center; }
        .empty-routine-message p { margin: 5px 0; }
        .empty-routine-message .fa-list-check { font-size: 2.5em; margin-bottom: 10px; color: var(--text-muted); }


        /* --- Biblioteca de Exercícios (Library) --- */
        .library-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .library-header h3 { color: var(--accent-blue); margin: 0; font-size: 1.3em; }
        .library-filters { display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; }
        .library-filters select { padding: 8px 12px; font-size: 0.9em; }
        .search-box { position: relative; display: flex; align-items: center; }
        .search-box i { position: absolute; left: 15px; color: var(--text-muted); font-size: 0.9em; }
        #searchExercises { padding-left: 40px; }
        .exercise-item { display: flex; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--border-color); transition: background-color 0.2s; }
        .exercise-item:last-child { border-bottom: none; }
        .add-exercise-button { background-color: var(--accent-blue); color: var(--text-light); border: none; border-radius: 50%; width: 30px; height: 30px; line-height: 30px; text-align: center; margin-right: 15px; font-size: 1.2em; cursor: pointer; flex-shrink: 0; transition: background-color 0.2s, transform 0.1s; }
        .add-exercise-button:hover { background-color: var(--accent-blue-hover); transform: scale(1.05); }
        .exercise-details { display: flex; flex-direction: column; flex-grow: 1; }
        .exercise-details .name { font-weight: 600; font-size: 1em; }
        .exercise-details .muscle { color: var(--text-muted); font-size: 0.8em; margin-top: 2px; }

        /* Mensagens de Status */
        .status-message {
            text-align: center; padding: 12px; font-weight: bold; 
            transition: background-color 0.3s;
            position: sticky; top: 0; z-index: 9;
        }
        .status-message-success { background-color: #28a745; color: white; }
        .status-message-error { background-color: #dc3545; color: white; }

        /* Responsividade */
        @media (max-width: 1050px) {
            .main-container { flex-direction: column; padding: 20px; }
            .routine-editor-form { flex: auto; }
            .exercise-library { 
                min-width: unset; position: relative; top: 0; max-height: unset; 
                margin-top: 20px;
            }
            .save-button-container .update-button {
                width: 80%;
            }
        }
        @media (max-width: 600px) {
            .main-container { padding: 10px; gap: 15px; }
            .routine-editor, .exercise-library { padding: 15px; }
            .routine-title-section input { font-size: 1.2em; }
        }
    </style>
</head>
<body>

    <button id="toggleSidebar">></button>
    <iframe id="sidebarIframe" src="sidebar/sidebar.php"></iframe> 

    <?php if (isset($message_status)): 
        $is_error = strpos($message_status, '❌') !== false || strpos($message_status, '🔴') !== false;
    ?>
        <div class="status-message <?= $is_error ? 'status-message-error' : 'status-message-success' ?>">
            <?= $message_status ?>
        </div>
    <?php endif; ?>

    <div class="main-container">
        
        <form method="POST" id="routineForm" action="treinos.php" class="routine-editor-form">
            <input type="hidden" name="action" value="save_routine">
            
            <div class="routine-editor">
                
                <div class="routine-title-section">
                    <label for="routine-title">Nome da Rotina</label>
                    <input type="text" id="routine-title" name="routine-title" value="<?= htmlspecialchars($routine_title) ?>" required>
                </div>

                <div id="exercisesContainer">
                    <div class="empty-routine-message" id="emptyMessage">
                        <p><i class="fa-solid fa-list-check"></i></p>
                        <p>Rotina vazia. Adicione o seu primeiro exercício a partir do **Catálogo de Exercícios** ao lado.</p>
                    </div>
                </div>

                <div class="save-button-container">
                    <button type="submit" class="update-button" id="submitRoutineButton"><i class="fa-solid fa-floppy-disk"></i> Guardar Rotina</button>
                </div>
            </div>
        </form>
        
        <div class="exercise-library">
            <div class="library-header">
                <h3><i class="fa-solid fa-book"></i> Catálogo de Exercícios</h3>
            </div>
            
            <div class="library-filters">
                <select id="filterEquipment" title="Filtrar por Equipamento">
                    <option value="">Todos os Equipamentos</option>
                    <?php foreach ($equipment_options as $equip): ?>
                        <option value="<?= htmlspecialchars($equip) ?>"><?= htmlspecialchars($equip) ?></option>
                    <?php endforeach; ?>
                </select>
                
                <select id="filterMuscle" title="Filtrar por Grupo Muscular">
                    <option value="">Todos os Músculos</option>
                    <?php foreach ($muscle_options as $muscle): ?>
                        <option value="<?= htmlspecialchars($muscle) ?>"><?= htmlspecialchars($muscle) ?></option>
                    <?php endforeach; ?>
                </select>
                
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="searchExercises" placeholder="Pesquisar Exercícios">
                </div>
            </div>
            
            <div class="all-exercises" id="allExercisesList">
                
                <?php if (empty($catalog_exercises) && $pdo !== false): ?>
                    <p style="color: orange; text-align: center; margin-top: 20px;">Não existem exercícios no seu catálogo.</p>
                <?php endif; ?>

                <?php if ($pdo === false): ?>
                    <p style="color: red; font-weight: bold; text-align: center; margin-top: 20px;">Sem conexão à BD. A lista de exercícios está indisponível.</p>
                <?php endif; ?>

                <?php foreach ($catalog_exercises as $ex): ?>
                <div class="exercise-item" 
                     data-muscle="<?= htmlspecialchars($ex['grupo_muscular']) ?>" 
                     data-equipment="<?= htmlspecialchars($ex['equipamento']) ?>">
                    <button type="button" 
                            class="add-exercise-button" 
                            data-id="<?= $ex['id_exercicio'] ?>" 
                            data-name="<?= htmlspecialchars($ex['nome']) ?>"
                            data-muscle-group="<?= htmlspecialchars($ex['grupo_muscular']) ?>"
                            title="Adicionar à Rotina">+</button>
                    <div class="exercise-details">
                        <span class="name"><?= htmlspecialchars($ex['nome']) ?></span>
                        <span class="muscle"><?= htmlspecialchars($ex['grupo_muscular']) ?> (<?= htmlspecialchars($ex['equipamento']) ?>)</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Variáveis globais
        const exercisesContainer = document.getElementById('exercisesContainer');
        const emptyMessage = document.getElementById('emptyMessage');
        const submitButton = document.getElementById('submitRoutineButton');
        const routineForm = document.getElementById('routineForm');
        
        // Elementos de Filtro e Catálogo
        const filterEquipment = document.getElementById('filterEquipment');
        const filterMuscle = document.getElementById('filterMuscle');
        const searchExercises = document.getElementById('searchExercises');
        const exerciseItems = document.querySelectorAll('.exercise-item'); 

        // 1. Script da Sidebar (APENAS A LÓGICA DE TOGGLE E ÍCONE)
        const toggleButton = document.getElementById('toggleSidebar');
        const sidebar = document.getElementById('sidebarIframe');
        
        // Define o ícone inicial (assume que começa fechada)
        toggleButton.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';

        toggleButton.addEventListener('click', () => {
            const isShown = sidebar.classList.toggle('show');
            // Altera o ícone com base no estado da sidebar
            toggleButton.innerHTML = isShown ? '<i class="fa-solid fa-chevron-left"></i>' : '<i class="fa-solid fa-chevron-right"></i>';
        });

        // 2. Funções Dinâmicas (Adicionar/Remover Exercícios)
        
        function updateEmptyMessageVisibility() {
             const cards = exercisesContainer.querySelectorAll('.exercise-card');
             if (emptyMessage) {
                 emptyMessage.style.display = (cards.length === 0) ? 'flex' : 'none'; 
             }
        }
        
        function createSetRow(setNumber) {
            return `
                <tr>
                    <td>${setNumber}</td>
                    <td><input type="number" class="set-input kg-input" value="0" min="0"></td>
                    <td><input type="number" class="set-input reps-input" value="10" min="1" max="100"></td>
                </tr>
            `;
        }

        /**
         * Liga os listeners de evento aos botões dentro de um novo 'exercise-card'.
         * @param {HTMLElement} card O elemento div do cartão de exercício recém-criado.
         */
        function attachCardListeners(card) {
            // Listener para o botão 'Remover'
            const deleteButton = card.querySelector('.delete-exercise');
            if (deleteButton) {
                deleteButton.addEventListener('click', () => {
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.9)';
                    setTimeout(() => {
                        card.remove();
                        updateEmptyMessageVisibility();
                    }, 300);
                });
            }

            // Listener para o botão '+ Adicionar Set'
            const addSetButton = card.querySelector('.add-set-button');
            if (addSetButton) {
                addSetButton.addEventListener('click', (e) => {
                    const setsBody = card.querySelector('.sets-body');
                    const setsCounter = card.querySelector('.sets-counter'); 
                    let currentSets = parseInt(setsCounter.value);
                    
                    currentSets++;
                    
                    setsBody.insertAdjacentHTML('beforeend', createSetRow(currentSets));
                    
                    // Atualiza o valor do input hidden sets-counter
                    setsCounter.value = currentSets;
                });
            }
        }
        
        function createExerciseCard(id, name, muscle) {
            
            const card = document.createElement('div');
            card.className = 'exercise-card';
            
            card.innerHTML = `
                <div class="card-header">
                    <i class="fa-solid fa-dumbbell"></i>
                    <span class="exercise-name">${name}</span>
                    <input type="hidden" name="exercise_id[]" value="${id}"> 
                    <span class="more-options delete-exercise" title="Remover"><i class="fa-solid fa-trash-can"></i></span>
                </div>

                <div class="card-body">
                    <label>Nota de Planeamento (${muscle})</label>
                    <textarea placeholder="Adicione notas específicas para este exercício (ex: Drop Set)" name="exercise_note[]"></textarea> 

                    <div class="sets-table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>SET</th>
                                    <th>KG (Sugestão)</th>
                                    <th>REPS (Sugestão)</th>
                                </tr>
                            </thead>
                            <tbody class="sets-body">
                                ${createSetRow(1)}
                            </tbody>
                        </table>
                        <input type="hidden" name="sets_count[]" value="1" class="sets-counter"> 
                        <button type="button" class="add-set-button">+ Adicionar Set</button>
                    </div>
                </div>
            `;
            
            exercisesContainer.appendChild(card);
            
            // LIGA OS LISTENERS IMEDIATAMENTE 
            attachCardListeners(card); 
            
            updateEmptyMessageVisibility();
        }

        
        // Listener para os botões '+' na Library (Adicionar Exercício)
        document.querySelectorAll('.add-exercise-button').forEach(button => {
            button.addEventListener('click', (e) => {
                const id = e.currentTarget.dataset.id;
                const name = e.currentTarget.dataset.name;
                const muscle = e.currentTarget.dataset.muscleGroup;
                
                if (id) {
                    createExerciseCard(id, name, muscle);
                }
            });
        });

        // 3. Lógica de Filtragem no JavaScript
        function filterExercises() {
            const selectedEquipment = filterEquipment.value.toLowerCase();
            const selectedMuscle = filterMuscle.value.toLowerCase();
            const searchText = searchExercises.value.toLowerCase();
            let visibleCount = 0;

            exerciseItems.forEach(item => {
                const itemMuscle = item.dataset.muscle.toLowerCase();
                const itemEquipment = item.dataset.equipment.toLowerCase();
                const itemName = item.querySelector('.name').textContent.toLowerCase();

                const matchesEquipment = (selectedEquipment === "" || itemEquipment.includes(selectedEquipment));
                const matchesMuscle = (selectedMuscle === "" || itemMuscle.includes(selectedMuscle));
                const matchesSearch = (searchText === "" || itemName.includes(searchText) || itemMuscle.includes(searchText));

                if (matchesEquipment && matchesMuscle && matchesSearch) {
                    item.style.display = 'flex';
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            // Mensagem "Nenhum resultado"
            const allExercisesList = document.getElementById('allExercisesList');
            let noResults = allExercisesList.querySelector('.no-results-message');
            if (visibleCount === 0 && (selectedEquipment !== "" || selectedMuscle !== "" || searchText !== "")) {
                if (!noResults) {
                    noResults = document.createElement('p');
                    noResults.className = 'no-results-message';
                    noResults.style.color = 'var(--text-muted)';
                    noResults.style.textAlign = 'center';
                    allExercisesList.appendChild(noResults);
                }
                noResults.textContent = "Nenhum exercício encontrado com estes filtros.";
                noResults.style.display = 'block';
            } else if (noResults) {
                noResults.style.display = 'none';
            }
        }

        // Adicionar Listeners aos filtros e pesquisa
        filterEquipment.addEventListener('change', filterExercises);
        filterMuscle.addEventListener('change', filterExercises);
        searchExercises.addEventListener('input', filterExercises);
        
        // 4. Submissão do Formulário
        submitButton.addEventListener('click', (e) => {
            if (exercisesContainer.querySelectorAll('.exercise-card').length === 0) {
                e.preventDefault(); 
                alert("🚫 Por favor, adicione pelo menos um exercício antes de guardar o treino.");
            }
        });

        // Chamada inicial (para mostrar/esconder a mensagem de rotina vazia)
        updateEmptyMessageVisibility();
    </script>
</body>
</html>