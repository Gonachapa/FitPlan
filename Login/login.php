<?php
// Ficheiro: login.php
// (Este ficheiro agora é um ficheiro PHP)

// Inicia a sessão PHP para poder receber e exibir mensagens de erro/sucesso
session_start(); 

// Variáveis para guardar mensagens, se existirem
$message = null;
$message_type = null;

if (isset($_SESSION['login_error'])) {
    $message = $_SESSION['login_error'];
    $message_type = 'error';
    unset($_SESSION['login_error']); // Limpa a sessão após exibir
} elseif (isset($_SESSION['login_success'])) {
    $message = $_SESSION['login_success'];
    $message_type = 'success';
    unset($_SESSION['login_success']); // Limpa a sessão após exibir
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

    <style>
        /* Estilos Customizados (Manter para o Tailwind) */
        .text-primary { color: #10B981; } 
        .focus\:ring-primary:focus { --tw-ring-color: #10B981; }
        .bg-primary { background-color: #10B981; }
        .google-btn {
            background-color: #f8f9fa; 
            color: #1F2937; 
            border: 1px solid #d4d8d9;
        }
        .auth-card {
            background-color: #1f2937; 
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 bg-gray-950">

    <div id="authCardContent" class="auth-card w-full max-w-md rounded-xl p-8 text-white">
        <h1 class="text-3xl font-extrabold text-center mb-6 text-primary">Fit Plan</h1>
        <p class="text-center text-gray-400 mb-6">Aceda aos seus treinos e dieta.</p>
        
        <button id="googleLoginBtn" class="google-btn w-full flex items-center justify-center py-3 px-4 rounded-lg font-bold mb-6 opacity-50 cursor-not-allowed">
             <svg class="w-5 h-5 mr-3" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                 <path d="M44.5 20H24V28.5H35.4297C34.4844 31.625 32.5 34.375 29.875 36.125C27.25 37.875 24.3125 38.75 21 38.75C14.7188 38.75 9.40625 34.625 7.1875 28.5H15.6875C17.0625 31.5 19.3438 33.75 21 33.75C23.5938 33.75 25.5 32.25 26.625 30.625L32.875 35.5C30.3438 37.625 27.1875 38.75 24 38.75C16.875 38.75 10.875 34.125 8.125 27.5C5.375 20.875 5.375 13.625 8.125 7C10.875 0.375 16.875 -0.75 24 0C31.125 0.75 35.875 4.75 38.125 9.5L31.875 14.375C30.625 12.5 29 10.875 27.0625 9.875C25.125 8.875 23.0625 8.375 21 8.375C18.4062 8.375 16.5 9.875 15.375 11.5L9.125 6.625C11.6562 4.5 14.8125 3.375 18 3.375C25.125 3.375 31.125 7.875 33.875 14.5C36.625 21.125 36.625 28.375 33.875 35.375C31.125 42 25.125 46 18 46C11.125 46 5.375 42 2.625 35.375C-0.125 28.75 -0.125 21.5 2.625 14.875C5.375 8.25 11.375 4.375 18 4.375C21.125 4.375 24.2812 5.5 26.75 7.5L33 12.375C30.5 14.5 27.5 15.625 24 15.625C19.1875 15.625 15.0625 13.5 13.0625 9.875L18 10C21.25 10 23.875 11.375 25.4375 14.125L28.1875 19.375L33.4375 21.625L39.875 21.5C39.625 21.125 39.375 20.75 39.125 20.375Z" fill="#1F2937"/>
             </svg>
             Entrar com Google (Desativado)
        </button>

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
            <button type="submit" class="bg-primary hover:bg-green-600 w-full py-3 px-4 rounded-lg font-bold transition-colors shadow-lg shadow-green-900/40">
                Iniciar Sessão
            </button>
        </form>

        <p class="mt-4 text-center text-sm text-gray-400">
            Não tem uma conta? 
            <a href="../Regist/regist.php" class="text-primary hover:text-green-400 font-semibold transition-colors">Crie uma aqui.</a>
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
</body>
</html>