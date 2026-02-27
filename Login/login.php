<?php
// Ficheiro: login.php
session_start(); 

$message = null;
$message_type = null;

if (isset($_SESSION['login_error'])) {
    $message = $_SESSION['login_error'];
    $message_type = 'error';
    unset($_SESSION['login_error']);
} elseif (isset($_SESSION['login_success'])) {
    $message = $_SESSION['login_success'];
    $message_type = 'success';
    unset($_SESSION['login_success']);
}
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fit Plan - Iniciar Sessão</title>
    <link rel="stylesheet" href="../style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script src="https://accounts.google.com/gsi/client" async defer></script>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-gray-950">

    <div id="authCardContent" class="auth-card w-full max-w-md rounded-xl p-8 text-white">
        <h1 class="text-3xl font-extrabold text-center mb-6 text-primary">Fit Plan</h1>
        <p class="text-center text-gray-400 mb-6">Aceda aos seus treinos e dieta.</p>
        
        <div class="flex flex-col items-center mb-6">
            <div id="g_id_onload"
                data-client_id="786468382357-vf8fdrqr45geq7a5572s5sbhua11obv3.apps.googleusercontent.com"
                data-context="signin"
                data-ux_mode="popup"
                data-callback="onGoogleLogin"
                data-auto_prompt="false">
            </div>

            <div class="g_id_signin"
                data-type="standard"
                data-shape="rectangular"
                data-theme="filled_blue"
                data-text="signin_with"
                data-size="large"
                data-logo_alignment="left"
                data-width="320">
            </div>
        </div>

        <div class="flex items-center mb-6">
            <hr class="flex-grow border-gray-700">
            <span class="px-3 text-gray-500 text-sm">OU</span>
            <hr class="flex-grow border-gray-700">
        </div>

        <form id="loginForm" class="space-y-4" method="POST" action="processar_login.php">
            <div>
                <label for="log_identifier" class="block text-sm font-medium text-gray-300 mb-1">Email ou Nome de Utilizador</label>
                <input type="text" id="log_identifier" name="identifier" placeholder="o seu email ou nome" required class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-gray-200">
            </div>
            <div>
                <label for="log_password" class="block text-sm font-medium text-gray-300 mb-1">Palavra-passe</label>
                <input type="password" id="log_password" name="password" placeholder="a sua palavra-passe" required class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-gray-200">
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 w-full py-3 px-4 rounded-lg font-bold transition-colors shadow-lg shadow-blue-900/40">
                Iniciar Sessão
            </button>
        </form>

        <p class="mt-4 text-center text-sm text-gray-400">
            Não tem uma conta? 
            <a href="../Regist/registo.php" class="text-primary hover:text-green-400 font-semibold transition-colors">Crie uma aqui.</a>
        </p>

        <div id="messageBox" class="mt-4 text-center">
            <?php if ($message): ?>
                <p class="p-3 rounded-lg text-sm font-medium text-center 
                    <?php echo ($message_type === 'error') ? 'bg-red-800 text-red-200' : 'bg-green-700 text-white'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function onGoogleLogin(response) {
        // Enviar o token JWT para o PHP
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'processar_google.php';

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'google_token';
        input.value = response.credential;

        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
    </script>
</body>
</html>