<?php
// Ficheiro: Regist/registo.php

// Exibir mensagens de erro se existirem
$error_message = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fit Plan - Registo</title>
    <link rel="stylesheet" href="../style.css"> 
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-gray-950">

    <div class="auth-card w-full max-w-lg rounded-xl p-8 text-white shadow-2xl shadow-gray-900/70">
        <h1 class="text-3xl font-extrabold text-center mb-4 text-primary">Registo Fit Plan</h1>
        <p class="text-center text-gray-400 mb-6">Crie a sua conta e valide o seu email para começar.</p>
        
        <div class="flex items-center mb-6">
            <hr class="flex-grow border-gray-700">
            <span class="px-3 text-gray-500 text-sm">OU</span>
            <hr class="flex-grow border-gray-700">
        </div>
        
        <div id="messageBox" class="mt-4 text-center">
            <?php if (!empty($error_message)): ?>
                <p class="p-3 rounded-lg bg-red-800 text-red-200 text-sm font-medium">❌ <?= $error_message ?></p>
            <?php endif; ?>
        </div>

        <form id="registerForm" class="space-y-4" method="POST" action="../enviar_confirmacao.php">
            <div>
                <label for="reg_nome" class="block text-sm font-medium text-gray-300 mb-1">Nome de Utilizador *</label>
                <input type="text" id="reg_nome" name="nome" placeholder="Escolha o seu nome público" required class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-gray-200">
            </div>
            <div>
                <label for="reg_email" class="block text-sm font-medium text-gray-300 mb-1">Email *</label>
                <input type="email" id="reg_email" name="email" placeholder="O seu endereço de email" required class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-gray-200">
            </div>
            <div>
                <label for="reg_password" class="block text-sm font-medium text-gray-300 mb-1">Palavra-passe * (min. 6 caracteres)</label>
                <input type="password" id="reg_password" name="password" placeholder="Defina uma palavra-passe segura" required minlength="6" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-gray-200">
            </div>

            <button type="submit" id="submitBtn" class="color-primary color-primary-hover w-full py-3 px-4 rounded-lg font-bold transition-colors shadow-lg shadow-blue-900/40">
                Criar Conta e Receber Código
            </button>
        </form>

        <p class="mt-4 text-center text-sm text-gray-400">
            Já tem uma conta? 
            <a href="../Login/login.php" class="text-primary hover:text-green-400 font-semibold transition-colors">Iniciar Sessão.</a>
        </p>
    </div>
    
    <script>
        // ... (O seu script JS completo para validação de duplicidade) ...
    </script>
</body>
</html>