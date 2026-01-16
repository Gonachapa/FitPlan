<?php
// Ficheiro: editar_treino.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 1. CONEXÃO
$servername = "localhost";
$username = "root";    
$password = "";        
$dbname = "fitness_app";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Erro: " . $conn->connect_error); }

$user_id = $_SESSION['user_id'];
$treino_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// SEGURANÇA: Verificar se o treino existe e pertence ao utilizador
$stmtCheck = $conn->prepare("SELECT id_utilizador FROM Treino WHERE id_treino = ?");
$stmtCheck->bind_param("i", $treino_id);
$stmtCheck->execute();
$resCheck = $stmtCheck->get_result()->fetch_assoc();

if (!$resCheck || $resCheck['id_utilizador'] != $user_id) {
    // Se tentar aceder a um ID de outra pessoa ou inexistente
    header("Location: perfil.php?erro=acesso_negado");
    exit;
}

$mensagem = $_SESSION['feedback_msg'] ?? "";
$tipo_alerta = $_SESSION['feedback_tipo'] ?? "success";
unset($_SESSION['feedback_msg'], $_SESSION['feedback_tipo']);

// 2. PROCESSAR AÇÕES (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['atualizar_treino'])) {
        $nome = $_POST['nome'];
        $objetivo = $_POST['objetivo'];
        $descricao = $_POST['descricao'];
        $privado = isset($_POST['privado']) ? 1 : 0; // Novo campo para privacidade

        $stmt = $conn->prepare("UPDATE Treino SET nome = ?, objetivo = ?, descricao = ?, privado = ? WHERE id_treino = ? AND id_utilizador = ?");
        $stmt->bind_param("sssiii", $nome, $objetivo, $descricao, $privado, $treino_id, $user_id);
        $stmt->execute();
        $_SESSION['feedback_msg'] = "Dados do treino atualizados!";
        $stmt->close();
    }

    if (isset($_POST['remover_exercicio'])) {
        $id_ex_remover = intval($_POST['id_exercicio']);
        $ordem_remover = intval($_POST['ordem']);
        $stmt = $conn->prepare("DELETE FROM Treino_Exercicio WHERE id_treino = ? AND id_exercicio = ? AND ordem = ?");
        $stmt->bind_param("iii", $treino_id, $id_ex_remover, $ordem_remover);
        $stmt->execute();
        $_SESSION['feedback_msg'] = "Exercício removido.";
        $stmt->close();
    }

    if (isset($_POST['adicionar_exercicio'])) {
        $id_ex_novo = intval($_POST['id_exercicio_catalogo']);
        $sets = intval($_POST['sets']);
        $descanso = intval($_POST['descanso']);
        $nota = $_POST['nota'];

        $resOrdem = $conn->query("SELECT MAX(ordem) as ultima FROM Treino_Exercicio WHERE id_treino = $treino_id");
        $rowOrdem = $resOrdem->fetch_assoc();
        $nova_ordem = ($rowOrdem['ultima'] ?? 0) + 1;

        $stmt = $conn->prepare("INSERT INTO Treino_Exercicio (id_treino, id_exercicio, ordem, sets_sugeridos, tempo_descanso_seg, nota_planeada) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiiis", $treino_id, $id_ex_novo, $nova_ordem, $sets, $descanso, $nota);
        $stmt->execute();
        $_SESSION['feedback_msg'] = "Exercício adicionado!";
        $stmt->close();
    }

    if (isset($_POST['atualizar_detalhes_exercicio'])) {
        $id_ex_edit = intval($_POST['id_exercicio']);
        $ordem_edit = intval($_POST['ordem']);
        $novos_sets = intval($_POST['novos_sets']);
        $novo_descanso = intval($_POST['novo_descanso']);
        $nova_nota = $_POST['nova_nota'];

        $stmt = $conn->prepare("UPDATE Treino_Exercicio SET sets_sugeridos = ?, tempo_descanso_seg = ?, nota_planeada = ? WHERE id_treino = ? AND id_exercicio = ? AND ordem = ?");
        $stmt->bind_param("iisiii", $novos_sets, $novo_descanso, $nova_nota, $treino_id, $id_ex_edit, $ordem_edit);
        $stmt->execute();
        $_SESSION['feedback_msg'] = "Alterações guardadas.";
        $stmt->close();
    }

    header("Location: editar_treino.php?id=" . $treino_id);
    exit;
}

// 3. CARREGAR DADOS COMPLETOS
$stmt = $conn->prepare("SELECT nome, objetivo, descricao, privado FROM Treino WHERE id_treino = ? AND id_utilizador = ?");
$stmt->bind_param("ii", $treino_id, $user_id);
$stmt->execute();
$treino = $stmt->get_result()->fetch_assoc();

$queryExs = "SELECT e.id_exercicio, e.nome, e.grupo_muscular, te.sets_sugeridos, te.tempo_descanso_seg, te.nota_planeada, te.ordem 
             FROM Treino_Exercicio te JOIN Exercicio e ON te.id_exercicio = e.id_exercicio 
             WHERE te.id_treino = ? ORDER BY te.ordem ASC";
$stmtEx = $conn->prepare($queryExs);
$stmtEx->bind_param("i", $treino_id);
$stmtEx->execute();
$lista_exercicios = $stmtEx->get_result();

$catalogo_exercicios = $conn->query("SELECT id_exercicio, nome, grupo_muscular FROM Exercicio ORDER BY grupo_muscular, nome");
$user_theme = $_SESSION['user_tema'] ?? 'dark';
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Treino - Fit Plan</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --bg-body: #0c0f16;
            --bg-card: #1a1d23;
            --bg-input: #1f2229;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: #2a2d35;
            --accent: #3b82f6;
            --success: #22c55e;
            --danger: #ef4444;
        }

        body.light-theme {
            --bg-body: #f1f5f9;
            --bg-card: #ffffff;
            --bg-input: #f8fafc;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
        }

        body { 
            background-color: var(--bg-body); 
            color: var(--text-main); 
            font-family: 'Inter', sans-serif; 
            margin: 0; 
            transition: background-color 0.3s, color 0.3s;
        }

        #sidebarIframe { position: fixed; left: 0; top: 0; width: 300px; height: 100vh; border: none; z-index: 1000; }
        .main-content { margin-left: 300px; padding: 40px; min-height: 100vh; }
        .edit-container { width: 100%; max-width: 900px; margin: 0 auto; }

        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        .alert-success { background: rgba(34, 197, 94, 0.15); color: var(--success); border: 1px solid var(--success); }

        .section-card { 
            background: var(--bg-card); 
            padding: 25px; 
            border-radius: 16px; 
            margin-bottom: 30px; 
            border: 1px solid var(--border-color); 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        h1, h3 { margin-top: 0; }
        label { display: block; margin-bottom: 8px; font-size: 0.9em; color: var(--text-muted); }

        .mini-input { 
            background: var(--bg-input); 
            border: 1px solid var(--border-color); 
            border-radius: 8px; 
            color: var(--text-main); 
            padding: 12px; 
            width: 100%; 
            box-sizing: border-box; 
            margin-bottom: 15px;
            font-size: 14px;
        }

        .checkbox-container { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; cursor: pointer; }
        .checkbox-container input { width: 18px; height: 18px; }

        .ex-item { 
            border: 1px solid var(--border-color); 
            padding: 20px; 
            border-radius: 12px; 
            margin-bottom: 15px; 
            position: relative; 
            background: var(--bg-body);
        }

        .btn-remove { position: absolute; top: 15px; right: 15px; background: var(--danger); color: #fff; border: none; width: 30px; height: 30px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
        .btn-remove:hover { transform: scale(1.1); }

        .ex-details-grid { display: grid; grid-template-columns: 100px 120px 1fr 50px; gap: 15px; align-items: flex-end; }
        
        .btn-primary { background: var(--accent); color: white; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; transition: 0.2s; }
        .btn-primary:hover { opacity: 0.9; }

        .btn-save-ex { background: var(--accent); color: white; border: none; height: 42px; border-radius: 8px; cursor: pointer; transition: 0.2s; }
        .btn-save-ex:hover { background: #2563eb; }

        .back-link { color: var(--accent); text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 20px; }
    </style>
</head>
<body class="<?= $user_theme === 'light' ? 'light-theme' : '' ?>">

    <iframe id="sidebarIframe" src="sidebar/sidebar.php"></iframe>

    <div class="main-content">
        <div class="edit-container">
            <a href="perfil.php" class="back-link"><i class="fa-solid fa-chevron-left"></i> Voltar ao Perfil</a>
            
            <h1>Editar Plano de Treino</h1>

            <?php if ($mensagem): ?>
                <div class="alert alert-success"><?= $mensagem ?></div>
            <?php endif; ?>

            <form action="" method="POST" class="section-card">
                <h3 style="color: var(--accent);"><i class="fa-solid fa-pen-to-square"></i> Detalhes do Plano</h3>
                
                <label>Nome do Treino</label>
                <input type="text" name="nome" class="mini-input" value="<?= htmlspecialchars($treino['nome']) ?>" required>
                
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <div>
                        <label>Objetivo</label>
                        <select name="objetivo" class="mini-input">
                            <option <?= $treino['objetivo']=='Hipertrofia'?'selected':'' ?>>Hipertrofia</option>
                            <option <?= $treino['objetivo']=='Força'?'selected':'' ?>>Força</option>
                            <option <?= $treino['objetivo']=='Resistência'?'selected':'' ?>>Resistência</option>
                        </select>
                    </div>
                    <div>
                        <label>Frequência / Descrição</label>
                        <input type="text" name="descricao" class="mini-input" value="<?= htmlspecialchars($treino['descricao']) ?>">
                    </div>
                </div>

                <label class="checkbox-container">
                    <input type="checkbox" name="privado" <?= $treino['privado'] ? 'checked' : '' ?>>
                    <span>Tornar este treino <strong>Privado</strong> (Só eu poderei ver)</span>
                </label>

                <button type="submit" name="atualizar_treino" class="btn-primary">Atualizar Informação Base</button>
            </form>

            <form action="" method="POST" class="section-card">
                <h3 style="color: var(--success);"><i class="fa-solid fa-plus-circle"></i> Adicionar Exercício</h3>
                <label>Exercício do Catálogo</label>
                <select name="id_exercicio_catalogo" class="mini-input" required>
                    <option value="">Procurar exercício...</option>
                    <?php while($cat = $catalogo_exercicios->fetch_assoc()): ?>
                        <option value="<?= $cat['id_exercicio'] ?>"><?= htmlspecialchars($cat['nome']) ?> (<?= $cat['grupo_muscular'] ?>)</option>
                    <?php endwhile; ?>
                </select>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <div>
                        <label>Nº de Séries</label>
                        <input type="number" name="sets" class="mini-input" value="3">
                    </div>
                    <div>
                        <label>Descanso (segundos)</label>
                        <input type="number" name="descanso" class="mini-input" value="60">
                    </div>
                </div>
                <label>Nota Especial (Ex: Drop-set na última)</label>
                <input type="text" name="nota" class="mini-input">
                <button type="submit" name="adicionar_exercicio" class="btn-primary" style="background: var(--success);">Incluir no Plano</button>
            </form>

            <div class="section-card">
                <h3><i class="fa-solid fa-layer-group"></i> Estrutura de Exercícios</h3>
                <?php if($lista_exercicios->num_rows == 0): ?>
                    <p style="color: var(--text-muted); text-align: center; padding: 20px;">Nenhum exercício adicionado.</p>
                <?php endif; ?>

                <?php while ($ex = $lista_exercicios->fetch_assoc()): ?>
                    <div class="ex-item">
                        <form action="" method="POST">
                            <input type="hidden" name="id_exercicio" value="<?= $ex['id_exercicio'] ?>">
                            <input type="hidden" name="ordem" value="<?= $ex['ordem'] ?>">
                            <button type="submit" name="remover_exercicio" class="btn-remove" onclick="return confirm('Apagar este exercício do treino?')"><i class="fa-solid fa-xmark"></i></button>
                        </form>
                        
                        <div style="margin-bottom: 15px;">
                            <span style="background: var(--accent); color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; margin-right: 8px;">#<?= $ex['ordem'] ?></span>
                            <strong style="font-size: 1.1em;"><?= htmlspecialchars($ex['nome']) ?></strong>
                            <small style="color: var(--text-muted); margin-left: 10px;">(<?= $ex['grupo_muscular'] ?>)</small>
                        </div>
                        
                        <form action="" method="POST" class="ex-details-grid">
                            <input type="hidden" name="id_exercicio" value="<?= $ex['id_exercicio'] ?>">
                            <input type="hidden" name="ordem" value="<?= $ex['ordem'] ?>">
                            <div>
                                <label>Séries</label>
                                <input type="number" name="novos_sets" class="mini-input" style="margin-bottom:0;" value="<?= $ex['sets_sugeridos'] ?>">
                            </div>
                            <div>
                                <label>Descanso (s)</label>
                                <input type="number" name="novo_descanso" class="mini-input" style="margin-bottom:0;" value="<?= $ex['tempo_descanso_seg'] ?>">
                            </div>
                            <div>
                                <label>Observação</label>
                                <input type="text" name="nova_nota" class="mini-input" style="margin-bottom:0;" value="<?= htmlspecialchars($ex['nota_planeada']) ?>">
                            </div>
                            <button type="submit" name="atualizar_detalhes_exercicio" class="btn-save-ex" title="Guardar"><i class="fa-solid fa-check"></i></button>
                        </form>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <script>
        if ( window.history.replaceState ) {
            window.history.replaceState( null, null, window.location.href );
        }
    </script>
</body>
</html>