<?php
// Ficheiro: dieta.php
session_start();

// Obtém o nome do utilizador da sessão para personalizar a saudação
$user_name = isset($_SESSION['user_nome']) ? htmlspecialchars($_SESSION['user_nome']) : 'Convidado';
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
<body>

<button id="toggleSidebar">></button>
<iframe id="sidebarIframe" src="sidebar/sidebar.php"></iframe>

<div class="main-content">
    <div class="settings-card calculator-card">
        <h1>Calculadora de Dieta</h1>
        <p>Olá, **<?= $user_name ?>**! Calcule as suas necessidades calóricas.</p>

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

        <button class="save-btn" onclick="calcular()">Calcular</button>

        <div id="resultado" class="input-group"></div>
    </div>
</div>

<script>
// --- LÓGICA DA CALCULADORA (MANTIDA INALTERADA) ---
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

    let tmb;
    if (genero === "m") tmb = 10 * peso + 6.25 * altura - 5 * idade + 5;
    else tmb = 10 * peso + 6.25 * altura - 5 * idade - 161;

    const manutencao = tmb * atividade;
    const cutting = manutencao * 0.85;
    const bulking = manutencao * 1.15;

    const imc = peso / (alturaM ** 2);
    let situacaoIMC = imc < 18.5 ? "Abaixo do peso" :
                        imc < 25 ? "Peso normal" :
                        imc < 30 ? "Sobrepeso" : "Obesidade";

    let gordura = genero === "m" ? (1.20 * imc) + (0.23 * idade) - 16.2
                                    : (1.20 * imc) + (0.23 * idade) - 5.4;

    let situacaoGordura = genero === "m"
        ? (gordura < 10 ? "Baixa gordura" : gordura <= 20 ? "Gordura saudável" : "Excesso de gordura")
        : (gordura < 18 ? "Baixa gordura" : gordura <= 28 ? "Gordura saudável" : "Excesso de gordura");

    let acao = imc < 18.5 ? "Você está abaixo do peso. Sugere-se ganhar massa muscular (bulking)."
            : imc < 25 ? "Seu peso está normal. Mantenha hábitos saudáveis e faça manutenção."
            : imc < 30 ? "Você está com sobrepeso. Sugere-se um déficit calórico (cutting leve)."
            : "Você está em estado de obesidade. Recomenda-se cutting com acompanhamento profissional.";

    document.getElementById("resultado").innerHTML = `
        <h3>Resultados:</h3>
        <p><b>TMB:</b> ${tmb.toFixed(0)} kcal</p>
        <p><b>Calorias para manutenção:</b> ${manutencao.toFixed(0)} kcal/dia</p>
        <p><b>Para Cutting:</b> ${cutting.toFixed(0)} kcal/dia</p>
        <p><b>Para Bulking:</b> ${bulking.toFixed(0)} kcal/dia</p>
        <hr>
        <p><b>IMC:</b> ${imc.toFixed(1)} (${situacaoIMC})</p>
        <p><b>Percentual estimado de gordura:</b> ${gordura.toFixed(1)}% (${situacaoGordura})</p>
        <p><b>Sugestão:</b> ${acao}</p>
    `;
}

// --- JAVASCRIPT PARA O BOTÃO DA SIDEBAR (NECESSÁRIO NA PÁGINA PRINCIPAL) ---
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