<?php
session_start();
$user_id = $_SESSION['user_id'] ?? 0;
$conn = new mysqli("localhost", "root", "", "fitness_app");

$avatar_src = "../DefaultIcons/miku.png"; 
$user_display_name = "Convidado";

if ($user_id > 0) {
    $stmt = $conn->prepare("SELECT nome, foto_perfil FROM Utilizador WHERE id_utilizador = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) {
        $user_display_name = htmlspecialchars($row['nome']);
        if (!empty($row['foto_perfil'])) {
            $avatar_src = "../" . $row['foto_perfil'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-pt">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="sidebar.css">
</head>
<body>
    <div class="sidebar">
        <h2>Fit Plan</h2>
        <div class="search-box"><input type="text" placeholder="Pesquisar..."></div>
        <ul class="menu">
            <li><a href="../dashboard.php" target="_top">🏠 Dashboard</a></li>
            <li><a href="../treinos.php" target="_top">💪 Treinos</a></li>
            <li><a href="../dieta.php" target="_top">🥗 Registo físico</a></li>
            <li><a href="../perfil.php" target="_top">⚙️ Perfil</a></li>
        </ul>
        <div class="user-info-wrapper">
            <div class="user-info">
                <div class="user-avatar-container">
                    <img class="user-avatar-img" src="<?= $avatar_src ?>">
                </div>
                <p class="user-name"><?= $user_display_name ?></p>
            </div>
        </div>
    </div>
</body>
</html>