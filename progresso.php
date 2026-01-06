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

// 2. Buscar Objetivo do Utilizador
$queryObj = $pdo->prepare("SELECT objetivo FROM Treino WHERE id_utilizador = ? ORDER BY id_treino DESC LIMIT 1");
$queryObj->execute([$user_id]);
$objetivo_user = $queryObj->fetchColumn() ?: "Manutenção";

// 3. Buscar Histórico de Dados da Tabela Dieta
$queryProgresso = $pdo->prepare("
    SELECT 
        DATE_FORMAT(data_registo, '%d/%m/%Y') as data_formatada,
        peso, 
        gordura_percent, 
        calorias_totais,
        data_registo
    FROM Dieta 
    WHERE id_utilizador = ? 
    ORDER BY data_registo ASC LIMIT 12
");
$queryProgresso->execute([$user_id]);
$historico = $queryProgresso->fetchAll(PDO::FETCH_ASSOC);

// 4. Lógica de Verificação de Registo Semanal (Pop-up)
$mostrar_aviso = false;
if (empty($historico)) {
    $mostrar_aviso = true;
} else {
    $ultima_entrada = end($historico)['data_registo'];
    $hoje = new DateTime();
    $data_registo_dt = new DateTime($ultima_entrada);
    $intervalo = $hoje->diff($data_registo_dt);
    if ($intervalo->days > 7) { $mostrar_aviso = true; }
}

// 5. Cálculos de Composição e Mensagens Personalizadas
$diagnostico = "Faltam dados";
$mensagem_progresso = "Regista os teus dados semanalmente para obteres uma análise.";
$status_cor = "#888";
$var_musculo_kg = 0;
$refeicoes = ["pequeno_almoço" => "Aveia com fruta", "almoço" => "Grelhado com legumes", "jantar" => "Omelete de claras"];

if (count($historico) >= 2) {
    $at = end($historico);
    $pa = $historico[count($historico)-2];

    // Cálculos Matemáticos de Composição
    $gordura_kg_at = $at['peso'] * ($at['gordura_percent'] / 100);
    $musculo_kg_at = $at['peso'] - $gordura_kg_at;
    $gordura_kg_pa = $pa['peso'] * ($pa['gordura_percent'] / 100);
    $musculo_kg_pa = $pa['peso'] - $gordura_kg_pa;

    $var_peso = $at['peso'] - $pa['peso'];
    $var_gordura_kg = $gordura_kg_at - $gordura_kg_pa;
    $var_musculo_kg = $musculo_kg_at - $musculo_kg_pa;

    // Determinação do Estado e Sugestões
    if ($var_musculo_kg > 0.1 && $var_gordura_kg <= 0.1) {
        $diagnostico = "Massa Limpa"; $status_cor = "#2ecc71";
        $refeicoes = ["pequeno_almoço" => "Panquecas de Aveia e 3 Ovos", "almoço" => "Massa Integral com Frango", "jantar" => "Salmão com Batata Doce"];
    } elseif ($var_gordura_kg < -0.1) {
        $diagnostico = "Cutting Ativo"; $status_cor = "#3498db";
        $refeicoes = ["pequeno_almoço" => "Iogurte Grego com Chia", "almoço" => "Bife de Peru com Brócolos", "jantar" => "Sopa de Legumes com Claras"];
    } elseif ($var_gordura_kg > 0.3) {
        $diagnostico = "Ganho de Gordura"; $status_cor = "#e74c3c";
        $refeicoes = ["pequeno_almoço" => "Ovos Mexidos (sem pão)", "almoço" => "Salada Variada com Atum", "jantar" => "Peixe Cozido com Espinafres"];
    } else {
        $diagnostico = "Manutenção"; $status_cor = "#f1c40f";
        $refeicoes = ["pequeno_almoço" => "Pão Integral com Queijo Fresco", "almoço" => "Arroz, Feijão e Carne Magra", "jantar" => "Canja ou Salada de Frango"];
    }

    // Validação contra Objetivo
    if (stripos($objetivo_user, 'Ganhar') !== false || stripos($objetivo_user, 'Bulking') !== false) {
        $mensagem_progresso = ($var_musculo_kg > 0) ? "Objetivo Bulking: Excelente! Ganhaste ".number_format($var_musculo_kg,2)."kg de músculo." : "Atenção: Precisas de mais proteína/calorias para ganhar massa.";
    } else {
        $mensagem_progresso = ($var_gordura_kg < 0) ? "Objetivo Cutting: Ótimo! Perdeste ".number_format(abs($var_gordura_kg),2)."kg de gordura." : "Atenção: O défice calórico pode não estar a ser suficiente.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progresso - Fit Plan</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg: <?= $user_theme === 'light' ? '#f8fafc' : '#0c0f16' ?>;
            --card: <?= $user_theme === 'light' ? '#ffffff' : '#1a1d23' ?>;
            --text: <?= $user_theme === 'light' ? '#1e293b' : '#f8fafc' ?>;
            --accent: #2a62fc;
            --border: <?= $user_theme === 'light' ? '#e2e8f0' : '#333742' ?>;
        }

        body { background: var(--bg); color: var(--text); font-family: 'Inter', sans-serif; margin: 0; overflow-x: hidden; }
        .wrapper { margin-left: 300px; padding: 30px; display: grid; grid-template-columns: 1fr 350px; gap: 25px; transition: 0.3s; }
        .card { background: var(--card); border-radius: 16px; padding: 25px; border: 1px solid var(--border); box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .blur { filter: blur(8px); pointer-events: none; }

        /* Pop-up */
        #modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); display: <?= $mostrar_aviso ? 'flex' : 'none' ?>; justify-content: center; align-items: center; z-index: 1000; }
        .modal-box { background: var(--card); padding: 40px; border-radius: 20px; text-align: center; border: 1px solid var(--accent); }
        
        /* Resumo */
        .status-header { border-left: 8px solid <?= $status_cor ?>; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        .meal-item { background: var(--bg); padding: 10px; border-radius: 8px; margin-bottom: 8px; border-left: 3px solid var(--accent); font-size: 14px; }
        .state-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 5px; }
    </style>
</head>
<body>

    <div id="modal">
        <div class="modal-box">
            <h2 style="color: #ff4757;">⚠️ Atualização Necessária</h2>
            <p>Não detetamos registos nos últimos 7 dias. Atualiza os teus dados para veres o progresso.</p>
            <a href="dieta.php" style="background: var(--accent); color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; display: inline-block; margin-top: 15px;">Ir para Dieta</a>
        </div>
    </div>

    <iframe src="sidebar/button.php" style="position:fixed; left:0; top:0; width:300px; height:100vh; border:none;"></iframe>

    <div class="wrapper <?= $mostrar_aviso ? 'blur' : '' ?>">
        <main>
            <div class="card status-header">
                <div>
                    <h3 style="margin:0; color: <?= $status_cor ?>;">Objetivo: <?= $objetivo_user ?></h3>
                    <p style="margin: 10px 0;"><?= $mensagem_progresso ?></p>
                </div>
                <div style="text-align: right;">
                    <small>Músculo Ganhos:</small>
                    <div style="font-size: 20px; font-weight: bold; color: #2ecc71;"><?= ($var_musculo_kg >= 0 ? "+" : "") . number_format($var_musculo_kg, 2) ?> kg</div>
                </div>
            </div>

            <div class="card">
                <h3>📈 Evolução Biométrica</h3>
                <canvas id="progressoChart" height="120"></canvas>
            </div>
        </main>

        <aside>
            <div class="card">
                <h3>Estado: <span style="color: <?= $status_cor ?>"><?= $diagnostico ?></span></h3>
                <h4 style="margin-top:20px;">🍎 Sugestão Alimentar</h4>
                <div class="meal-item"><small style="display:block; font-size:10px; color:#888;">PEQUENO-ALMOÇO</small><?= $refeicoes['pequeno_almoço'] ?></div>
                <div class="meal-item"><small style="display:block; font-size:10px; color:#888;">ALMOÇO</small><?= $refeicoes['almoço'] ?></div>
                <div class="meal-item"><small style="display:block; font-size:10px; color:#888;">JANTAR</small><?= $refeicoes['jantar'] ?></div>

                <div style="margin-top: 25px; font-size: 12px; border-top: 1px solid var(--border); padding-top: 15px;">
                    <p><span class="state-dot" style="background: #2ecc71;"></span> <b>Massa Limpa:</b> Ganho de músculo.</p>
                    <p><span class="state-dot" style="background: #3498db;"></span> <b>Cutting:</b> Perda de gordura.</p>
                    <p><span class="state-dot" style="background: #e74c3c;"></span> <b>Alerta:</b> Ganho de gordura.</p>
                </div>
            </div>
        </aside>
    </div>

    <script>
        const ctx = document.getElementById('progressoChart').getContext('2d');
        const dbData = <?= json_encode($historico) ?>;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: dbData.map(d => d.data_formatada),
                datasets: [{
                    label: 'Peso (kg)',
                    data: dbData.map(d => d.peso),
                    borderColor: '#2a62fc',
                    backgroundColor: 'rgba(42, 98, 252, 0.1)',
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'y'
                }, {
                    label: 'Gordura (%)',
                    data: dbData.map(d => d.gordura_percent),
                    borderColor: '#ff4757',
                    borderDash: [5, 5],
                    tension: 0.4,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { type: 'linear', position: 'left', title: { display: true, text: 'Peso (kg)' } },
                    y1: { type: 'linear', position: 'right', title: { display: true, text: 'Gordura (%)' }, grid: { drawOnChartArea: false } }
                }
            }
        });
    </script>
</body>
</html>