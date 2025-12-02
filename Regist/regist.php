<?php
// Adicione o código PHP no topo para começar a saída HTML após as mensagens de erro
// Se não houver necessidade de processamento PHP antes do HTML, pode remover a tag <?php
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
        <p class="text-center text-gray-400 mb-6">Crie a sua conta para começar a planear a sua forma física.</p>
        
        <button id="googleRegisterBtn" class="google-btn w-full flex items-center justify-center py-3 px-4 rounded-lg font-bold mb-6 opacity-50 cursor-not-allowed">
             <svg class="w-5 h-5 mr-3" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M44.5 20H24V28.5H35.4297C34.4844 31.625 32.5 34.375 29.875 36.125C27.25 37.875 24.3125 38.75 21 38.75C14.7188 38.75 9.40625 34.625 7.1875 28.5H15.6875C17.0625 31.5 19.3438 33.75 21 33.75C23.5938 33.75 25.5 32.25 26.625 30.625L32.875 35.5C30.3438 37.625 27.1875 38.75 24 38.75C16.875 38.75 10.875 34.125 8.125 27.5C5.375 20.875 5.375 13.625 8.125 7C10.875 0.375 16.875 -0.75 24 0C31.125 0.75 35.875 4.75 38.125 9.5L31.875 14.375C30.625 12.5 29 10.875 27.0625 9.875C25.125 8.875 23.0625 8.375 21 8.375C18.4062 8.375 16.5 9.875 15.375 11.5L9.125 6.625C11.6562 4.5 14.8125 3.375 18 3.375C25.125 3.375 31.125 7.875 33.875 14.5C36.625 21.125 36.625 28.375 33.875 35.375C31.125 42 25.125 46 18 46C11.125 46 5.375 42 2.625 35.375C-0.125 28.75 -0.125 21.5 2.625 14.875C5.375 8.25 11.375 4.375 18 4.375C21.125 4.375 24.2812 5.5 26.75 7.5L33 12.375C30.5 14.5 27.5 15.625 24 15.625C19.1875 15.625 15.0625 13.5 13.0625 9.875L18 10C21.25 10 23.875 11.375 25.4375 14.125L28.1875 19.375L33.4375 21.625L39.875 21.5C39.625 21.125 39.375 20.75 39.125 20.375Z" fill="#1F2937"/></svg>
            Registar com Google (Desativado)
        </button>

        <div class="flex items-center mb-6">
            <hr class="flex-grow border-gray-700">
            <span class="px-3 text-gray-500 text-sm">OU</span>
            <hr class="flex-grow border-gray-700">
        </div>
        
        <div id="messageBox" class="mt-4 text-center">
            <?php
            // Exibir mensagem de sucesso após submissão
            if (isset($_GET['success'])) {
                echo '<p class="p-3 rounded-lg bg-green-700 text-green-100 text-sm font-medium">✅ ' . htmlspecialchars($_GET['success']) . '</p>';
            }
            // Exibir mensagem de erro após submissão
            elseif (isset($_GET['error'])) {
                echo '<p class="p-3 rounded-lg bg-red-800 text-red-200 text-sm font-medium">❌ ' . htmlspecialchars($_GET['error']) . '</p>';
            }
            ?>
        </div>

        <form id="registerForm" class="space-y-4" method="POST" action="processar_registo.php">
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

            <div class="pt-4 pb-2">
                <h3 class="text-lg font-semibold text-gray-300 mb-2 border-t border-gray-700 pt-4">Dados de Perfil (Opcional)</h3>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="reg_data_nascimento" class="block text-sm font-medium text-gray-300 mb-1">Data de Nascimento</label>
                    <input type="date" id="reg_data_nascimento" name="data_nascimento" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-gray-200">
                </div>
                <div>
                    <label for="reg_genero" class="block text-sm font-medium text-gray-300 mb-1">Género</label>
                    <select id="reg_genero" name="genero" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-gray-200">
                        <option value="">(Não especificar)</option>
                        <option value="M">Masculino</option>
                        <option value="F">Feminino</option>
                        <option value="Outro">Outro</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="reg_altura" class="block text-sm font-medium text-gray-300 mb-1">Altura (m)</label>
                    <input type="number" step="0.01" id="reg_altura" name="altura" placeholder="Ex: 1.75" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-gray-200">
                </div>
                <div>
                    <label for="reg_peso_atual" class="block text-sm font-medium text-gray-300 mb-1">Peso Atual (kg)</label>
                    <input type="number" step="0.1" id="reg_peso_atual" name="peso_atual" placeholder="Ex: 75.5" class="w-full px-4 py-3 bg-gray-800 border border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary text-gray-200">
                </div>
            </div>

            <button type="submit" id="submitBtn" class="color-primary color-primary-hover w-full py-3 px-4 rounded-lg font-bold transition-colors shadow-lg shadow-blue-900/40">
                Criar Conta
            </button>
        </form>

        <p class="mt-4 text-center text-sm text-gray-400">
            Já tem uma conta? 
            <a href="../Login/login.php" class="text-primary hover:text-green-400 font-semibold transition-colors">Iniciar Sessão.</a>
        </p>

    </div>

    <script>
        // Seleciona os campos
        const inputNome = document.getElementById('reg_nome');
        const inputEmail = document.getElementById('reg_email');
        const submitBtn = document.getElementById('submitBtn');
        const registerForm = document.getElementById('registerForm');
        
        let nomeIsDuplicate = false;
        let emailIsDuplicate = false;

        // Função para exibir ou ocultar a mensagem de erro específica
        function displayInlineError(inputElement, message, isError, fieldName) {
            let errorSpan = inputElement.nextElementSibling;
            
            // Se a span de erro não existe, cria
            if (!errorSpan || !errorSpan.classList.contains('input-error-message')) {
                errorSpan = document.createElement('span');
                errorSpan.classList.add('text-sm', 'font-medium', 'mt-1', 'block', 'input-error-message');
                inputElement.parentNode.insertBefore(errorSpan, inputElement.nextSibling);
            }

            if (isError) {
                errorSpan.textContent = `❌ ${message}`;
                errorSpan.classList.remove('text-green-500');
                errorSpan.classList.add('text-red-500');
            } else {
                errorSpan.textContent = "";
            }

            // Atualiza o estado global de duplicação
            if (fieldName === 'nome') {
                nomeIsDuplicate = isError;
            } else if (fieldName === 'email') {
                emailIsDuplicate = isError;
            }

            // Verifica se o botão de submissão deve ser desativado
            updateSubmitButtonState();
        }
        
        // Função para ativar/desativar o botão de submissão
        function updateSubmitButtonState() {
            submitBtn.disabled = nomeIsDuplicate || emailIsDuplicate;
            
            if (submitBtn.disabled) {
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }


        // Função principal que envia a requisição AJAX
        async function checkDuplication(inputElement, fieldName) {
            const value = inputElement.value.trim();
            
            // Requisito mínimo para verificar
            if (value.length < 3) {
                displayInlineError(inputElement, "", false, fieldName);
                return;
            }

            // Simular um "a carregar"
            displayInlineError(inputElement, "A verificar...", false, fieldName); 

            try {
                const formData = new FormData();
                formData.append(fieldName, value);
                
                // Ponto de chamada AJAX para o ficheiro PHP
                const response = await fetch('verificar_duplicidade.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.is_duplicate) {
                    // É duplicado!
                    const message = fieldName === 'nome' 
                        ? "Este nome já está registado." 
                        : "Este email já está registado.";
                    
                    displayInlineError(inputElement, message, true, fieldName);
                } else {
                    // Não é duplicado
                    displayInlineError(inputElement, "", false, fieldName); 
                }

            } catch (error) {
                console.error('Erro na requisição AJAX:', error);
                displayInlineError(inputElement, "Erro ao verificar a disponibilidade.", true, fieldName);
            }
        }

        // --- Adicionar Listeners de Eventos (verificação após 500ms de inatividade) ---
        
        let debounceTimeoutNome;
        inputNome.addEventListener('input', () => {
            clearTimeout(debounceTimeoutNome);
            debounceTimeoutNome = setTimeout(() => {
                checkDuplication(inputNome, 'nome');
            }, 500); // Espera 500ms depois de parar de digitar
        });

        let debounceTimeoutEmail;
        inputEmail.addEventListener('input', () => {
            clearTimeout(debounceTimeoutEmail);
            debounceTimeoutEmail = setTimeout(() => {
                checkDuplication(inputEmail, 'email');
            }, 500); // Espera 500ms depois de parar de digitar
        });

        // Garantir que a submissão é bloqueada pelo JavaScript, mesmo que o botão seja reativado (fallback)
        registerForm.addEventListener('submit', (event) => {
            if (nomeIsDuplicate || emailIsDuplicate) {
                event.preventDefault();
                alert('Por favor, corrija os erros de duplicação antes de criar a conta.');
            }
        });
        
    </script>
</body>
</html>