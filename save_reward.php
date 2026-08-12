<?php
include 'connexion.php';

if (!isset($_SESSION['user_id'])) {
    die("Non autorisé");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enfant_id = intval($_POST['enfant_id']);
    $recompense = $_POST['recompense'] ?? '';
    
    if (empty($recompense)) {
        die("Récompense non spécifiée");
    }
    
    // Vérifier que l'enfant appartient bien à l'utilisateur
    $sql = "SELECT id FROM enfants WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $enfant_id, $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        // Enregistrer la récompense (vous pouvez adapter cette partie selon votre structure de base de données)
        $sql_insert = "INSERT INTO recompenses (enfant_id, nom, points_utilises) VALUES (?, ?, 0)";
        $stmt = mysqli_prepare($conn, $sql_insert);
        mysqli_stmt_bind_param($stmt, "is", $enfant_id, $recompense);
        mysqli_stmt_execute($stmt);
        
        echo "Récompense enregistrée";
    } else {
        echo "Enfant non trouvé";
    }
}
?>