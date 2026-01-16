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

// --- PEGAR O TEMA DA SESSÃO ---
$user_theme = isset($_SESSION['user_tema']) ? $_SESSION['user_tema'] : 'dark';

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

// --- 4. BUSCAR ÚLTIMOS 10 REGISTOS ---
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
    <link rel="stylesheet" href="sidebar/button.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --bg: <?= $user_theme === 'light' ? '#f8fafc' : '#0c0f16' ?>;
            --card: <?= $user_theme === 'light' ? '#ffffff' : '#1a1d23' ?>;
            --text: <?= $user_theme === 'light' ? '#1e293b' : '#f8fafc' ?>;
            --accent: #2a62fc;
            --border: <?= $user_theme === 'light' ? '#e2e8f0' : '#333742' ?>;
        }

        body { 
            background-color: var(--bg); 
            color: var(--text); 
            font-family: 'Inter', sans-serif; 
            margin: 0; 
            transition: 0.3s;
            overflow-x: hidden;
        }

        /* CENTRALIZAÇÃO DA PÁGINA */
        .main-content { 
            display: flex; 
            justify-content: center; 
            align-items: flex-start; /* Alinha ao topo com padding */
            min-height: 100vh;
            padding: 60px 20px;
            transition: 0.3s ease;
            width: 100%;
            box-sizing: border-box;
        }

        .settings-card { 
            background: var(--card); 
            padding: 30px; 
            border-radius: 20px; 
            border: 1px solid var(--border); 
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 550px; /* Um pouco mais estreito para parecer mais elegante */
            text-align: center;
        }

        #toggleSidebar { position: fixed; z-index: 1100; }

        #sidebarIframe {
            position: fixed;
            left: -300px;
            top: 0;
            width: 300px;
            height: 100vh;
            border: none;
            z-index: 1000;
            transition: 0.3s;
            background: var(--card);
        }
        #sidebarIframe.show { left: 0; }
        
        h1 { margin-bottom: 10px; font-size: 24px; }
        
        .input-group { margin-bottom: 15px; text-align: left; }
        .input-group label { display: block; margin-bottom: 6px; font-weight: 500; font-size: 13px; opacity: 0.8; }
        
        .input-group input, .input-group select { 
            width: 100%; 
            padding: 12px; 
            border-radius: 10px; 
            border: 1px solid var(--border); 
            background: <?= $user_theme === 'light' ? '#fff' : '#222' ?>; 
            color: var(--text);
            box-sizing: border-box;
            outline: none;
        }

        .save-btn { 
            width: 100%; 
            padding: 14px; 
            background: var(--accent); 
            color: white; 
            border: none; 
            border-radius: 10px; 
            font-weight: bold; 
            cursor: pointer; 
            font-size: 16px;
            margin-top: 10px;
        }

        .save-btn:hover { filter: brightness(1.1); }

        #resultado { 
            margin-top: 20px; 
            padding: 15px; 
            background: rgba(42, 98, 252, 0.1); 
            border-radius: 12px; 
            border: 1px solid var(--accent);
            text-align: left;
        }

        .hist-item {
            border-bottom: 1px solid var(--border);
            padding: 12px 0;
            font-size: 13px;
            color: var(--text);
            text-align: left;
        }
        
        .hist-item:last-child { border-bottom: none; }
    </style>
</head>
<body>

<button id="toggleSidebar">></button>
<iframe id="sidebarIframe" src="sidebar/sidebar.php"></iframe>

<div class="main-content" id="mainContent">
    <div class="settings-card">
        <h1>Calculadora de Dieta</h1>
        <p style="margin-bottom: 25px; opacity: 0.7;">Olá, <strong><?= $user_name ?></strong>! Insira os seus dados abaixo.</p>
        
        <div class="input-group">
            <label>Peso (kg)</label>
            <input type="number" id="peso" placeholder="Ex: 70" step="0.1">
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
                <option value="1.2">Sedentário (Pouco ou nenhum exercício)</option>
                <option value="1.375">Leve (1-3 dias por semana)</option>
                <option value="1.55">Moderado (3-5 dias por semana)</option>
                <option value="1.725">Intenso (6-7 dias por semana)</option>
                <option value="1.9">Muito intenso (Atleta/Trabalho físico)</option>
            </select>
        </div>

        <button class="save-btn" onclick="calcular()">Calcular e Guardar</button>

        <div id="resultado" style="display:none;"></div>

        <div id="historico-container" style="margin-top: 30px; border-top: 1px solid var(--border); padding-top: 20px;">
            <h4 style="text-align: left; margin-bottom: 15px;"><i class="fa-solid fa-clock-rotate-left"></i> Histórico Recente</h4>
            <div id="lista-historico">
                <?php if (!$user_id): ?>
                    <em>Faça login para ver o seu histórico.</em>
                <?php elseif (empty($historico_bd)): ?>
                    <em>Sem registos anteriores.</em>
                <?php else: ?>
                    <?php foreach ($historico_bd as $reg): ?>
                        <div class="hist-item">
                            <span style="color: var(--accent); font-weight: bold;"><?= date('d/m', strtotime($reg['data_registo'])) ?></span>: 
                            <?= $reg['peso'] ?>kg | IMC: <?= $reg['imc'] ?> | <strong><?= $reg['calorias_totais'] ?> kcal</strong>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
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
    formData.append('altura', alturaM);
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
             // Mostra o resultado antes de recarregar
             document.getElementById("resultado").style.display = "block";
             document.getElementById("resultado").innerHTML = `
                <h3 style="margin-top:0">Resultados Guardados!</h3>
                <p><b>Manutenção:</b> ${man.toFixed(0)} kcal/dia</p>
                <p><b>Cutting:</b> ${cut.toFixed(0)} kcal | <b>Bulking:</b> ${bulk.toFixed(0)} kcal</p>
                <p><b>IMC:</b> ${imc.toFixed(1)} | <b>Gordura:</b> ${gord.toFixed(1)}%</p>
             `;
             setTimeout(() => location.reload(), 1500);
        }
    });
}

// Lógica de Sidebar igual à Dashboard e Perfil
const toggleBtn = document.getElementById('toggleSidebar');
const sidebar = document.getElementById('sidebarIframe');
const mainContent = document.getElementById('mainContent');

if (toggleBtn && sidebar) {
    toggleBtn.onclick = () => {
        const isOpen = sidebar.classList.toggle('show');
        toggleBtn.textContent = isOpen ? '<' : '>';
        // Move o conteúdo para o lado quando a sidebar abre
        mainContent.style.paddingLeft = isOpen ? "320px" : "20px";
    };
}
</script>

</body>
</html>