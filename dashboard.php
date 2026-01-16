<?php
session_start();

// 1. Configurações e Tema
$user_id = $_SESSION['user_id'] ?? 1;
$user_theme = $_SESSION['user_tema'] ?? 'dark';

try {
    $pdo = new PDO("mysql:host=localhost;dbname=fitness_app;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na ligação: " . $e->getMessage());
}

// --- NOVO: Lógica para filtrar o gráfico (AJAX) ---
if (isset($_GET['action']) && $_GET['action'] == 'get_weight_data') {
    $periodo = $_GET['periodo'] ?? 'dia';
    
    switch ($periodo) {
        case 'semana':
            // Agrupa por semana do ano
            $sql = "SELECT DATE_FORMAT(data_registo, 'Sem. %u') as label, AVG(peso) as peso FROM Dieta WHERE id_utilizador = ? GROUP BY YEARWEEK(data_registo) ORDER BY data_registo ASC LIMIT 12";
            break;
        case 'mes':
            // Agrupa por mês/ano
            $sql = "SELECT DATE_FORMAT(data_registo, '%b/%y') as label, AVG(peso) as peso FROM Dieta WHERE id_utilizador = ? GROUP BY MONTH(data_registo), YEAR(data_registo) ORDER BY data_registo ASC LIMIT 12";
            break;
        case 'ano':
            // Agrupa por ano
            $sql = "SELECT DATE_FORMAT(data_registo, '%Y') as label, AVG(peso) as peso FROM Dieta WHERE id_utilizador = ? GROUP BY YEAR(data_registo) ORDER BY data_registo ASC";
            break;
        default: // dia
            $sql = "SELECT DATE_FORMAT(data_registo, '%d/%m') as label, peso FROM Dieta WHERE id_utilizador = ? ORDER BY data_registo ASC LIMIT 15";
            break;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit; // Importante para não carregar o resto do HTML no pedido AJAX
}

// 2. Buscar Dados Físicos (Tabela Utilizador)
$queryUser = $pdo->prepare("SELECT nome, altura, data_nascimento FROM Utilizador WHERE id_utilizador = ?");
$queryUser->execute([$user_id]);
$dadosUser = $queryUser->fetch(PDO::FETCH_ASSOC);

$user_nome = $dadosUser['nome'] ?? 'Atleta';
$altura_base = $dadosUser['altura'] ?? 1.75; 
$nascimento = new DateTime($dadosUser['data_nascimento'] ?? '2000-01-01');
$idade = $nascimento->diff(new DateTime())->y;

// 3. Buscar Objetivo
$queryObj = $pdo->prepare("SELECT objetivo FROM Treino WHERE id_utilizador = ? ORDER BY id_treino DESC LIMIT 1");
$queryObj->execute([$user_id]);
$objetivo_user = $queryObj->fetchColumn() ?: "Manutenção";

// 4. Buscar Histórico (para o estado inicial)
$queryProgresso = $pdo->prepare("
    SELECT 
        DATE_FORMAT(data_registo, '%d/%m/%Y') as data_formatada,
        peso, 
        gordura_percent, 
        data_registo
    FROM Dieta 
    WHERE id_utilizador = ? 
    ORDER BY data_registo ASC LIMIT 12
");
$queryProgresso->execute([$user_id]);
$historico = $queryProgresso->fetchAll(PDO::FETCH_ASSOC);

// 5. Cálculos de IMC e Saúde
$peso_atual = !empty($historico) ? end($historico)['peso'] : 0;
$altura_atual = !empty($historico) ? (end($historico)['altura'] ?? $altura_base) : $altura_base;

$imc = ($peso_atual > 0 && $altura_atual > 0) ? $peso_atual / ($altura_atual * $altura_atual) : 0;
$peso_ideal = 22 * ($altura_atual * $altura_atual);

if ($imc < 18.5) { $status_saude = "Abaixo do Peso"; $cor_saude = "#3498db"; }
elseif ($imc < 25) { $status_saude = "Peso Saudável"; $cor_saude = "#2ecc71"; }
elseif ($imc < 30) { $status_saude = "Excesso de Peso"; $cor_saude = "#f1c40f"; }
else { $status_saude = "Obesidade"; $cor_saude = "#e74c3c"; }

// 6. Lógica de Evolução
$diagnostico = "Análise Pendente";
$status_cor = "#888";
$refeicoes = null;
$var_musculo_kg = 0;

if (count($historico) == 1) {
    $diagnostico = "Primeira Semana";
    $status_cor = "#9b59b6";
} elseif (count($historico) >= 2) {
    $at = end($historico);
    $pa = $historico[count($historico)-2];
    $gord_kg_at = $at['peso'] * ($at['gordura_percent'] / 100);
    $musc_kg_at = $at['peso'] - $gord_kg_at;
    $gord_kg_pa = $pa['peso'] * ($pa['gordura_percent'] / 100);
    $musc_kg_pa = $pa['peso'] - $gord_kg_pa;
    $var_gordura = $gord_kg_at - $gord_kg_pa;
    $var_musculo_kg = $musc_kg_at - $musc_kg_pa;

    if ($var_musculo_kg > 0.1 && $var_gordura <= 0.1) {
        $diagnostico = "Massa Limpa"; $status_cor = "#2ecc71";
        $refeicoes = ["pequeno_almoço" => "Panquecas de Aveia e 3 Ovos", "almoço" => "Massa Integral com Frango", "jantar" => "Salmão com Batata Doce"];
    } elseif ($var_gordura < -0.1) {
        $diagnostico = "Cutting Ativo"; $status_cor = "#3498db";
        $refeicoes = ["pequeno_almoço" => "Iogurte Grego com Chia", "almoço" => "Bife de Peru com Brócolos", "jantar" => "Sopa de Legumes com Claras"];
    } elseif ($var_gordura > 0.3) {
        $diagnostico = "Ganho de Gordura"; $status_cor = "#e74c3c";
        $refeicoes = ["pequeno_almoço" => "Ovos Mexidos (sem pão)", "almoço" => "Salada Variada com Atum", "jantar" => "Peixe Cozido com Espinafres"];
    } else {
        $diagnostico = "Manutenção"; $status_cor = "#f1c40f";
        $refeicoes = ["pequeno_almoço" => "Pão Integral com Queijo Fresco", "almoço" => "Arroz, Feijão e Carne Magra", "jantar" => "Canja ou Salada de Frango"];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Fit Plan</title>
    <link rel="stylesheet" href="sidebar/button.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg: <?= $user_theme === 'light' ? '#f8fafc' : '#0c0f16' ?>;
            --card: <?= $user_theme === 'light' ? '#ffffff' : '#1a1d23' ?>;
            --text: <?= $user_theme === 'light' ? '#1e293b' : '#f8fafc' ?>;
            --border: <?= $user_theme === 'light' ? '#e2e8f0' : '#333742' ?>;
            --accent: #2a62fc;
            --success: #2ecc71;
            --highlight: #e67e22; /* Cor Laranja */
        }
        body { background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; margin: 0; overflow-x: hidden; }
        #sidebarIframe { position: fixed; left: -300px; top: 0; width: 300px; height: 100vh; border: none; z-index: 1000; transition: 0.3s; background: var(--card); }
        #sidebarIframe.show { left: 0; }
        #toggleSidebar { position: fixed; z-index: 1100; }

        .wrapper { padding: 40px 80px; display: grid; grid-template-columns: 1fr 350px; gap: 25px; max-width: 1400px; margin: 0 auto; }
        .card { background: var(--card); border-radius: 16px; padding: 25px; border: 1px solid var(--border); box-shadow: 0 4px 20px rgba(0,0,0,0.1); margin-bottom: 25px; }
        
        .status-header { border-left: 8px solid <?= $status_cor ?>; display: flex; justify-content: space-between; align-items: center; }
        .bmi-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; color: white; margin-left: 10px; }
        
        .charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; }
        .extra-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        
        .section-title { font-size: 16px; font-weight: bold; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        
        .item-row { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            padding: 10px; 
            background: rgba(128,128,128,0.05); 
            border-radius: 12px; 
            margin-bottom: 8px; 
            text-decoration: none; 
            color: inherit; 
            transition: 0.2s; 
            border: 1px solid transparent;
        }
        .item-row:hover { 
            background: rgba(42, 98, 252, 0.1); 
            border-color: var(--accent); 
            transform: translateY(-2px);
        }
        
        .influencer-img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent); }
        .icon-box { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; }
        
        .social-badge { font-size: 10px; padding: 3px 8px; border-radius: 6px; color: white; margin-left: auto; text-transform: uppercase; font-weight: bold; }
        
        .meal-item { background: var(--bg); padding: 12px; border-radius: 8px; margin-bottom: 10px; border-left: 3px solid var(--accent); font-size: 14px; }
        
        .state-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 8px; }
        .legend-box p { font-size: 12px; margin: 12px 0; line-height: 1.4; opacity: 0.9; display: flex; align-items: center; }

        /* Estilos dos Filtros */
        .chart-controls { display: flex; gap: 5px; }
        .filter-btn { 
            background: var(--bg); border: 1px solid var(--border); color: var(--text); 
            padding: 4px 10px; border-radius: 6px; cursor: pointer; font-size: 11px; transition: 0.2s;
        }
        .filter-btn.active { background: var(--accent); color: white; border-color: var(--accent); }
    </style>
</head>
<body>

    <button id="toggleSidebar">></button>
    <iframe id="sidebarIframe" src="sidebar/sidebar.php"></iframe>

    <div class="wrapper">
        <main>
            <div style="margin-bottom: 25px;">
                <h1 style="margin:0;">Olá, <?= htmlspecialchars($user_nome) ?>! 👋</h1>
                <p style="opacity: 0.6; margin: 5px 0;">O teu painel de controlo pessoal.</p>
            </div>

            <div class="card status-header">
                <div>
                    <h2 style="margin:0; color: <?= $status_cor ?>;"><?= $diagnostico ?></h2>
                    <p style="margin: 10px 0; display: flex; align-items: center;">
                        IMC: <b><?= number_format($imc, 1) ?></b> 
                        <span class="bmi-badge" style="background: <?= $cor_saude ?>;"><?= $status_saude ?></span>
                    </p>
                </div>
                <div style="text-align: right;">
                    <small>Músculo (Variação Semanal):</small>
                    <div style="font-size: 24px; font-weight: bold; color: #2ecc71;">
                        <?= ($var_musculo_kg >= 0 ? "+" : "") . number_format($var_musculo_kg, 2) ?> kg
                    </div>
                </div>
            </div>

            <div class="charts-grid">
                <div class="card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3 style="margin:0;">📈 Evolução do Peso</h3>
                        <div class="chart-controls">
                            <button class="filter-btn active" onclick="changePeriod('dia', this)">Dia</button>
                            <button class="filter-btn" onclick="changePeriod('semana', this)">Semana</button>
                            <button class="filter-btn" onclick="changePeriod('mes', this)">Mês</button>
                            <button class="filter-btn" onclick="changePeriod('ano', this)">Ano</button>
                        </div>
                    </div>
                    <canvas id="chartEvolucao"></canvas>
                </div>
                <div class="card">
                    <h3>⚖️ Peso vs Ideal</h3>
                    <canvas id="chartIdeal"></canvas>
                </div>
            </div>

            <div class="extra-grid">
                <div class="card" style="margin-bottom: 0;">
                    <div class="section-title"><i class="fa-solid fa-cart-shopping"></i> Produtos Recomendados</div>
                    
                    <a href="https://www.prozis.com/pt/pt/prozis/creatina-monoidratada-300-g" target="_blank" class="item-row">
                        <div class="icon-box" style="background: var(--highlight);"><i class="fa-solid fa-flask"></i></div>
                        <div>
                            <div style="font-size: 14px; font-weight: 600;">Creatina Monoidratada</div>
                            <div style="font-size: 11px; opacity: 0.6;">Aumento de Força e Volume</div>
                        </div>
                        <span style="margin-left:auto; font-weight: bold; color: var(--success);">€18.99</span>
                    </a>

                    <a href="https://www.prozis.com/pt/pt/prozis/100-real-whey-protein-1000-g" target="_blank" class="item-row">
                        <div class="icon-box" style="background: #27ae60;"><i class="fa-solid fa-dumbbell"></i></div>
                        <div>
                            <div style="font-size: 14px; font-weight: 600;">100% Real Whey Protein</div>
                            <div style="font-size: 11px; opacity: 0.6;">Recuperação Muscular</div>
                        </div>
                        <span style="margin-left:auto; font-weight: bold; color: var(--success);">€24.99</span>
                    </a>
                </div>

                <div class="card" style="margin-bottom: 0;">
                    <div class="section-title"><i class="fa-solid fa-users"></i> Criadores Recomendados</div>
                    
                    <a href="https://www.youtube.com/@NoelDeyzel" target="_blank" class="item-row">
                        <img src="Influencers/NoelDyezel.jpg" class="influencer-img" alt="Noel Dyezel">
                        <div>
                            <div style="font-size: 14px; font-weight: 600;">Noel Dyezel</div>
                            <div style="font-size: 11px; opacity: 0.6;">Dicas de Powerlifting</div>
                        </div>
                        <span class="social-badge" style="background: #ff0000;">YouTube</span>
                    </a>

                    <a href="https://www.youtube.com/@MocvideoProductions" target="_blank" class="item-row">
                        <img src="Influencers/MocVideo.jpg" class="influencer-img" alt="Moc Video">
                        <div>
                            <div style="font-size: 14px; font-weight: 600;">Moc Video</div>
                            <div style="font-size: 11px; opacity: 0.6;">Motivação e Treino</div>
                        </div>
                        <span class="social-badge" style="background: #ff0000;">YouTube</span>
                    </a>
                </div>
            </div>
        </main>

        <aside>
            <div class="card">
                <h3 style="margin-top:0;">🍎 Dieta Sugerida</h3>
                <?php if ($refeicoes): ?>
                    <div class="meal-item"><small style="display:block; font-size:10px; color:#888;">PEQUENO-ALMOÇO</small><?= $refeicoes['pequeno_almoço'] ?></div>
                    <div class="meal-item"><small style="display:block; font-size:10px; color:#888;">ALMOÇO</small><?= $refeicoes['almoço'] ?></div>
                    <div class="meal-item"><small style="display:block; font-size:10px; color:#888;">JANTAR</small><?= $refeicoes['jantar'] ?></div>
                <?php else: ?>
                    <div style="text-align: center; padding: 20px; border: 1px dashed var(--border); border-radius: 10px; color: #888;">
                        <i class="fa-solid fa-lock" style="margin-bottom:10px; font-size: 20px;"></i>
                        <p style="font-size: 13px;">Disponível após o 2º registo.</p>
                    </div>
                <?php endif; ?>

                <div class="legend-box" style="margin-top: 25px; border-top: 1px solid var(--border); padding-top: 15px;">
                    <h4><i class="fa-solid fa-circle-info"></i> Guia de Evolução</h4>
                    <p><span class="state-dot" style="background: var(--highlight);"></span> <b>Ponto Atual</b></p>
                    <p><span class="state-dot" style="background: #9b59b6;"></span> <b>1ª Semana</b></p>
                    <p><span class="state-dot" style="background: #2ecc71;"></span> <b>Massa Limpa</b></p>
                    <p><span class="state-dot" style="background: #3498db;"></span> <b>Cutting</b></p>
                    <p><span class="state-dot" style="background: #f1c40f;"></span> <b>Manutenção</b></p>
                    <p><span class="state-dot" style="background: #e74c3c;"></span> <b>Alerta</b></p>
                </div>
            </div>
        </aside>
    </div>

    <script>
        // Sidebar logic
        const toggleBtn = document.getElementById('toggleSidebar');
        const sidebar = document.getElementById('sidebarIframe');
        toggleBtn.onclick = () => {
            const isOpen = sidebar.classList.toggle('show');
            toggleBtn.textContent = isOpen ? '<' : '>';
        };

        // Gráfico de Evolução (Com Filtros)
        let chartEvolucao;
        
        async function changePeriod(periodo, btn) {
            // Atualizar botões
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            if(btn) btn.classList.add('active');

            // Buscar dados via AJAX (usando o próprio ficheiro como API)
            const response = await fetch(`?action=get_weight_data&periodo=${periodo}`);
            const data = await response.json();

            const labels = data.map(d => d.label);
            const values = data.map(d => d.peso);
            
            // Cores dinâmicas: último ponto é sempre laranja
            const pointColors = values.map((_, index) => index === values.length - 1 ? '#e67e22' : '#2a62fc');
            const pointRadius = values.map((_, index) => index === values.length - 1 ? 8 : 4);

            if (chartEvolucao) {
                chartEvolucao.data.labels = labels;
                chartEvolucao.data.datasets[0].data = values;
                chartEvolucao.data.datasets[0].pointBackgroundColor = pointColors;
                chartEvolucao.data.datasets[0].pointBorderColor = pointColors;
                chartEvolucao.data.datasets[0].pointRadius = pointRadius;
                chartEvolucao.update();
            } else {
                const ctx = document.getElementById('chartEvolucao').getContext('2d');
                chartEvolucao = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Peso (kg)',
                            data: values,
                            borderColor: '#2a62fc',
                            borderWidth: 3,
                            tension: 0.4,
                            fill: false,
                            pointBackgroundColor: pointColors,
                            pointBorderColor: pointColors,
                            pointRadius: pointRadius,
                            pointHoverRadius: 10
                        }]
                    },
                    options: { plugins: { legend: { display: false } } }
                });
            }
        }

        // Gráfico Ideal (Estático)
        new Chart(document.getElementById('chartIdeal'), {
            type: 'bar',
            data: {
                labels: ['Peso Atual', 'Peso Ideal'],
                datasets: [{
                    label: 'Kg',
                    data: [<?= (float)$peso_atual ?>, <?= round($peso_ideal, 1) ?>],
                    backgroundColor: ['#e67e22', '#888']
                }]
            },
            options: { scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } }
        });

        // Iniciar gráfico com período "dia"
        changePeriod('dia');
    </script>
</body>
</html>