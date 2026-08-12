<?php
include 'connexion.php';

if (!isset($_SESSION['user_id'])) {
    die("Vous devez être connecté pour effectuer ce paiement.");
}

if (!isset($_POST['factureId']) || !isset($_POST['montant'])) {
    die("Données de paiement manquantes.");
}

$user_id = $_SESSION['user_id'];
$facture_id = intval($_POST['factureId']);
$montant = floatval($_POST['montant']);

// Récupérer le salaire de l'utilisateur
$sql = "SELECT salaire FROM budget_familial WHERE user_id = ? ORDER BY id DESC LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$budget = mysqli_fetch_assoc($result);

// Vérifier si le salaire est suffisant
if ($budget['salaire'] >= $montant) {
    // Mettre à jour le salaire de l'utilisateur
    $nouveau_salaire = $budget['salaire'] - $montant;
    $sql_update = "UPDATE budget_familial SET salaire = ? WHERE user_id = ?";
    $stmt_update = mysqli_prepare($conn, $sql_update);
    mysqli_stmt_bind_param($stmt_update, "di", $nouveau_salaire, $user_id);
    if (mysqli_stmt_execute($stmt_update)) {
        // Marquer la facture comme payée (ou la supprimer)
        $sql_facture_paye = "DELETE FROM facture WHERE id = ?";
        $stmt_facture_paye = mysqli_prepare($conn, $sql_facture_paye);
        mysqli_stmt_bind_param($stmt_facture_paye, "i", $facture_id);
        mysqli_stmt_execute($stmt_facture_paye);
        
        echo $nouveau_salaire; // Retourner le nouveau salaire après paiement
    } else {
        echo "Erreur de mise à jour du budget.";
    }
} else {
    echo "Fonds insuffisants pour payer cette facture.";
}
?>
