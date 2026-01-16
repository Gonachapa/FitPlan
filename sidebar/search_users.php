<?php
// Ficheiro: sidebar/search_users.php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=fitness_app;charset=utf8mb4", "root", "");
    
    $query = $_GET['q'] ?? '';
    
    if (strlen($query) > 1) {
        // Pesquisa utilizadores que começam com o texto digitado
        $stmt = $pdo->prepare("SELECT id_utilizador, nome, foto_perfil FROM Utilizador WHERE nome LIKE ? LIMIT 5");
        $stmt->execute(['%' . $query . '%']);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($results);
    } else {
        echo json_encode([]);
    }
} catch (Exception $e) {
    echo json_encode([]);
}