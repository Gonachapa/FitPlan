<?php
// Ficheiro: dieta.php
session_start();

// --- 1. CONEXÃO À BASE DE DADOS ---
$host = "localhost";
$user = "root";
$pass = "";
$db   = "fitness_app";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Erro na ligação: " . $conn->connect_error); }
$conn->set_charset("utf8mb4");

// --- 2. DADOS DO UTILIZADOR ---
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$user_name = isset($_SESSION['user_nome']) ? htmlspecialchars($_SESSION['user_nome']) : 'Convidado';

// --- NOVO: PEGAR O TEMA DA SESSÃO ---
$user_theme = isset($_SESSION['user_tema']) ? $_SESSION['user_tema'] : 'dark';

// Se por algum motivo o tema não estiver na sessão mas o user estiver logado, podes ir buscar à BD
if ($user_id && !isset($_SESSION['user_tema'])) {
    $res_tema = $conn->query("SELECT tema FROM Utilizador WHERE id_utilizador = $user_id");
    if ($row_tema = $res_tema->fetch_assoc()) {
        $user_theme = $row_tema['tema'];
        $_SESSION['user_tema'] = $user_theme;
    }
}

// --- 3. LÓGICA PARA GUARDAR NOVO REGISTO (AJAX) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_save'])) {
    if (!$user_id) { echo json_encode(['status' => 'error', 'msg' => 'Login necessário']); exit; }

    $stmt = $conn->prepare("INSERT INTO Dieta (id_utilizador, peso, altura, idade, tmb, calorias_totais, cutting, bulking, imc, gordura_percent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("idddiiiidd", 
        $user_id, $_POST['peso'], $_POST['altura'], $_POST['idade'], 
        $_POST['tmb'], $_POST['man'], $_POST['cut'], $_POST['bulk'], 
        $_POST['imc'], $_POST['gord']
    );
    
    if ($stmt->execute()) echo json_encode(['status' => 'success']);
    else echo json_encode(['status' => 'error']);
    exit;
}

// --- 4. BUSCAR ÚLTIMOS 10 REGISTOS DA BASE DE DADOS ---
$historico_bd = [];
if ($user_id) {
    $res = $conn->query("SELECT * FROM Dieta WHERE id_utilizador = $user_id ORDER BY id_dieta DESC LIMIT 10");
    if ($res) {
        while ($row = $res->fetch_assoc()) { $historico_bd[] = $row; }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dieta Inteligente - Fit Plan</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="sidebar/button.css">
</head>

<body class="<?= $user_theme === 'light' ? 'light-theme' : '' ?>">

<button id="toggleSidebar">></button>
<iframe id="sidebarIframe" src="sidebar/sidebar.php"></iframe>

<div class="main-content">
    <div class="settings-card calculator-card">
        <h1>Calculadora de Dieta</h1>
        <p>Olá, <strong><?= $user_name ?></strong>! Calcule as suas necessidades calóricas.</p>
        
        <div class="input-group">
            <label>Peso (kg)</label>
            <input type="number" id="peso" placeholder="Ex: 70">
        </div>

        <div class="input-group">
            <label>Altura (cm)</label>
            <input type="number" id="altura" placeholder="Ex: 175">
        </div>

        <div class="input-group">
            <label>Idade</label>
            <input type="number" id="idade" placeholder="Ex: 25">
        </div>

        <div class="input-group">
            <label>Gênero</label>
            <select id="genero">
                <option value="m">Masculino</option>
                <option value="f">Feminino</option>
            </select>
        </div>

        <div class="input-group">
            <label>Nível de Atividade</label>
            <select id="atividade">
                <option value="1.2">Sedentário</option>
                <option value="1.375">Leve</option>
                <option value="1.55">Moderado</option>
                <option value="1.725">Intenso</option>
                <option value="1.9">Muito intenso</option>
            </select>
        </div>

        <button class="save-btn" onclick="calcular()">Calcular e Guardar</button>

        <div id="resultado" class="input-group"></div>

        <div id="historico-container" style="margin-top: 25px; border-top: 1px solid #eee; padding-top: 15px;">
            <h4 style="font-size: 0.85rem; color: #333; margin-bottom: 8px;">Histórico (Últimos 10 registos)</h4>
            <div id="lista-historico" style="font-size: 0.72rem; color: #666; line-height: 1.5;">
                <?php if (!$user_id): ?>
                    <em>Faça login para ver o seu histórico.</em>
                <?php elseif (empty($historico_bd)): ?>
                    <em>Sem registos anteriores na base de dados.</em>
                <?php else: ?>
                    <?php foreach ($historico_bd as $reg): ?>
                        <div style="border-bottom: 1px dashed #ddd; padding: 4px 0;">
                            <strong><?= date('d/m', strtotime($reg['data_registo'])) ?></strong>: 
                            <?= $reg['peso'] ?>kg | IMC: <?= $reg['imc'] ?> | TMB: <?= $reg['tmb'] ?> | Maint: <?= $reg['calorias_totais'] ?>kcal | G: <?= $reg['gordura_percent'] ?>%
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// O teu JavaScript de calcular() continua igual aqui...
function calcular() {
    const peso = parseFloat(document.getElementById("peso").value);
    const altura = parseFloat(document.getElementById("altura").value);
    const idade = parseFloat(document.getElementById("idade").value);
    const genero = document.getElementById("genero").value;
    const atividade = parseFloat(document.getElementById("atividade").value);

    if (!peso || !altura || !idade) {
        alert("Preencha todos os campos!");
        return;
    }

    const alturaM = altura / 100;
    let tmb = (genero === "m") ? (10 * peso + 6.25 * altura - 5 * idade + 5) : (10 * peso + 6.25 * altura - 5 * idade - 161);
    const man = tmb * atividade;
    const cut = man * 0.85;
    const bulk = man * 1.15;
    const imc = peso / (alturaM ** 2);
    const gord = (1.20 * imc) + (0.23 * idade) - (genero === "m" ? 16.2 : 5.4);

    const formData = new FormData();
    formData.append('ajax_save', '1');
    formData.append('peso', peso);
    formData.append('altura', altura);
    formData.append('idade', idade);
    formData.append('tmb', tmb.toFixed(0));
    formData.append('man', man.toFixed(0));
    formData.append('cut', cut.toFixed(0));
    formData.append('bulk', bulk.toFixed(0));
    formData.append('imc', imc.toFixed(1));
    formData.append('gord', gord.toFixed(1));

    fetch('dieta.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if(data.status === 'success') {
             location.reload(); 
        }
    });

    document.getElementById("resultado").innerHTML = `
        <h3>Resultados Atuais:</h3>
        <p><b>TMB:</b> ${tmb.toFixed(0)} kcal</p>
        <p><b>Manutenção:</b> ${man.toFixed(0)} kcal/dia</p>
        <hr>
        <p><b>IMC:</b> ${imc.toFixed(1)}</p>
        <p><b>Gordura:</b> ${gord.toFixed(1)}%</p>
    `;
}

// Sidebar logic
const toggleButton = document.getElementById('toggleSidebar');
const sidebar = document.getElementById('sidebarIframe');
if (toggleButton && sidebar) {
    toggleButton.addEventListener('click', () => {
        sidebar.classList.toggle('show');
        toggleButton.textContent = sidebar.classList.contains('show') ? '<' : '>';
    });
}
</script>

</body>
</html>