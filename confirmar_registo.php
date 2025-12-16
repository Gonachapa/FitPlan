<?php
// Ficheiro: confirmar_registo.php (NA RAIZ)

session_start();

// Verifica se os dados de registo estão na sessão
if (!isset($_SESSION['registration_data'])) {
    // CORRIGIDO: Redireciona para DENTRO da pasta Regist/
    header("Location: Regist/registo.php?error=" . urlencode("Sessão expirada ou dados de registo em falta."));
    exit;
}

$email_display = htmlspecialchars($_GET['email'] ?? $_SESSION['registration_data']['email'] ?? 'o seu email');

$error_message = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
$success_message = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';

?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fit Plan - Confirmação</title>
    <link rel="stylesheet" href="style.css"> 
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-gray-950">

    <div class="auth-card w-full max-w-lg rounded-xl p-8 text-white shadow-2xl shadow-gray-900/70">
        <h1 class="text-3xl font-extrabold text-center mb-4 text-primary">Confirmação de Registo</h1>
        <p class="text-center text-gray-400 mb-6">
            Introduza o código de 6 dígitos que enviámos para o email **<?= $email_display ?>**.
            O código expira em 10 minutos.
        </p>

        <div id="messageBox" class="mt-4 text-center">
            <?php if (!empty($error_message)): ?>
                <p class="p-3 rounded-lg bg-red-800 text-red-200 text-sm font-medium">❌ <?= $error_message ?></p>
            <?php elseif (!empty($success_message)): ?>
                <p class="p-3 rounded-lg bg-green-800 text-green-200 text-sm font-medium">✅ <?= $success_message ?></p>
            <?php endif; ?>
        </div>

        <form id="confirmForm" class="space-y-4" method="POST" action="processar_confirmacao.php">
            <div>
                <label for="confirmation_code" class="block text-sm font-medium text-gray-300 mb-1">Código de Confirmação</label>
                <input type="text" id="confirmation_code" name="confirmation_code" placeholder="Ex: 123456" required 
                       maxlength="6" pattern="[0-9]{6}" 
                       class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg text-center text-xl tracking-widest focus:outline-none focus:ring-2 focus:ring-primary text-gray-200">
            </div>

            <button type="submit" class="color-primary color-primary-hover w-full py-3 px-4 rounded-lg font-bold transition-colors shadow-lg shadow-blue-900/40">
                Confirmar Conta
            </button>
        </form>

        <p class="mt-4 text-center text-sm text-gray-400">
            Não recebeu o código? Verifique o Spam.
        </p>
    </div>
</body>
</html>