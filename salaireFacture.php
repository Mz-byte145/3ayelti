<?php
include 'connexion.php';

if (!isset($_SESSION['user_id'])) {
    die("Vous devez être connecté pour enregistrer un budget familial.");
}
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $salaire = $_POST['salaire'] ?? 0;
    $nb_enfants = $_POST['nb_enfants'] ?? 0;

    // Insertion du budget familial dans la base de données
    $sql = "INSERT INTO budget_familial (salaire, nb_enfants, user_id) VALUES (?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "dii", $salaire, $nb_enfants, $user_id);

    if (mysqli_stmt_execute($stmt)) {
        $budget_id = mysqli_insert_id($conn);

        // Insertion des enfants dans la base de données
        for ($i = 0; $i < $nb_enfants; $i++) {
            if (isset($_POST["nomEnfant" . $i]) && isset($_POST["ageEnfant" . $i])) {
                $nom_enfant = $_POST["nomEnfant" . $i];
                $age_enfant = $_POST["ageEnfant" . $i];

                $sql_enfant = "INSERT INTO enfants (user_id, nom_enfant, age_enfant) VALUES (?, ?, ?)";
                $stmt_enfant = mysqli_prepare($conn, $sql_enfant);
                mysqli_stmt_bind_param($stmt_enfant, "iss", $user_id, $nom_enfant, $age_enfant);
                mysqli_stmt_execute($stmt_enfant);
            }
        }
        // Insertion des factures
        if (isset($_POST['facture']) && isset($_POST['montant'])) {
            foreach ($_POST['facture'] as $facture) {
                if (!empty($_POST['montant'][$facture])) {
                    $montant = $_POST['montant'][$facture];
        
                    $sql_facture = "INSERT INTO facture (budget_id, nom_facture, montant) VALUES (?, ?, ?)";
                    $stmt_facture = mysqli_prepare($conn, $sql_facture);
                    
                    if ($stmt_facture) {
                        mysqli_stmt_bind_param($stmt_facture, "isd", $budget_id, $facture, $montant);
                        mysqli_stmt_execute($stmt_facture);
                        mysqli_stmt_close($stmt_facture);
                    }
                }
            }
        }
        
        header("Location: tache.php");
        exit();
    } else {
        die("Erreur d'insertion du budget : " . mysqli_error($conn));
    }
}
?>

