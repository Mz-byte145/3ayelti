<?php
// Connexion à la base de données
include 'connexion.php';

if (!isset($_SESSION['user_id'])) {
    die("Vous devez être connecté pour voir vos factures.");
}

$user_id = $_SESSION['user_id'];

// Traitement du paiement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['facture_id']) && isset($_POST['montant'])) {
    $facture_id = intval($_POST['facture_id']);
    $montant = floatval($_POST['montant']);
    
    // Vérifier si la facture n'est pas déjà payée
    $sql_check = "SELECT paye FROM facture WHERE id = ?";
    $stmt_check = mysqli_prepare($conn, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "i", $facture_id);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    $facture = mysqli_fetch_assoc($result_check);
    
    if ($facture['paye'] == 1) {
        die("Cette facture a déjà été payée.");
    }
    
    // Récupérer le budget actuel
    $sql = "SELECT salaire FROM budget_familial WHERE user_id = ? ORDER BY id DESC LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $budget = mysqli_fetch_assoc($result);
    
    $nouveau_salaire = $budget['salaire'] - $montant;
    
    // Mettre à jour le budget
    $sql = "UPDATE budget_familial SET salaire = ? WHERE user_id = ? ORDER BY id DESC LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "di", $nouveau_salaire, $user_id);
    mysqli_stmt_execute($stmt);
    
    // Marquer la facture comme payée
    $sql_update = "UPDATE facture SET paye = 1 WHERE id = ?";
    $stmt_update = mysqli_prepare($conn, $sql_update);
    mysqli_stmt_bind_param($stmt_update, "i", $facture_id);
    mysqli_stmt_execute($stmt_update);
    
    // Rediriger pour éviter la resoumission du formulaire
    header("Location: facture.php?message=facture_paye");
    exit();
}

// Récupérer les factures non payées de l'utilisateur
$sql = "SELECT f.id, f.nom_facture, f.montant
        FROM facture f
        JOIN budget_familial b ON f.budget_id = b.id
        WHERE b.user_id = ? AND f.paye = 0";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Calculer la somme des montants des factures non payées
$total_factures = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $total_factures += $row['montant'];
}
mysqli_free_result($result);

// Récupérer à nouveau les factures non payées pour affichage
$stmt2 = mysqli_prepare($conn, "SELECT f.id, f.nom_facture, f.montant
                               FROM facture f
                               JOIN budget_familial b ON f.budget_id = b.id
                               WHERE b.user_id = ? AND f.paye = 0");
mysqli_stmt_bind_param($stmt2, "i", $user_id);
mysqli_stmt_execute($stmt2);
$result = mysqli_stmt_get_result($stmt2);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Factures - Gestion de Budget</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #3b4371, #f3904f);
            background-size: cover;
            color: #fff;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 85%;
            margin: 0 auto;
            padding-top: 100px;
        }

        h1 {
            text-align: center;
            font-family: 'Poppins', sans-serif;
            color: #ffffff;
            font-size: 36px;
            margin-bottom: 30px;
        }

        .table-container {
            background-color: rgba(0, 0, 0, 0.6);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 15px;
            text-align: center;
            border: 1px solid #ddd;
        }

        th {
            background-color: #007BFF;
            color: white;
        }

        tr:nth-child(even) {
            background-color:rgba(0, 0, 0, 0.1);
        }

        .btn-payer {
            background-color: #28a745;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-payer:hover {
            background-color: #218838;
        }

        .total {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin-top: 20px;
            color: #ffbb33;
        }

        .conseils {
            margin-top: 50px;
            background-color: rgba(0, 0, 0, 0.7);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .conseils h2 {
            text-align: center;
            color: #ffbb33;
            margin-bottom: 20px;
        }

        .conseil-item {
            margin-bottom: 20px;
            font-size: 16px;
        }

        .retour {
            text-decoration: none;
            background-color: #dc3545;
            color: white;
            padding: 12px 20px;
            border-radius: 5px;
            font-weight: bold;
            margin-top: 20px;
            display: inline-block;
        }

        .retour:hover {
            background-color: #c82333;
        }

    </style>
</head>
<body>

<div class="container">
    <a href="tache.php" class="retour">Retour à tache familial</a>

    <h1>Mes Factures à Payer</h1>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Nom de la Facture</th>
                    <th>Montant (TND)</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['nom_facture']) ?></td>
                        <td><?= number_format($row['montant'], 2, ',', ' ') ?> TND</td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="facture_id" value="<?= $row['id'] ?>">
                                <input type="hidden" name="montant" value="<?= $row['montant'] ?>">
                                <button type="submit" class="btn-payer">Payer</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <p class="total">Total des Factures à Payer : <?= number_format($total_factures, 2, ',', ' ') ?> TND</p>
    </div>

    <!-- Section des Conseils -->
    <div class="conseils">
        <h2>Conseils pour économiser</h2>

        <div class="conseil-item">
            <h4>💧 Économiser l'Eau :</h4>
            <ul>
                <li>Fermez le robinet pendant que vous vous brossez les dents.</li>
                <li>Réparez les fuites immédiatement.</li>
                <li>Utilisez des dispositifs de réduction du débit d'eau.</li>
            </ul>
        </div>

        <div class="conseil-item">
            <h4>💡 Économiser l'Électricité :</h4>
            <ul>
                <li>Éteignez les lumières et appareils lorsque vous ne les utilisez pas.</li>
                <li>Utilisez des ampoules LED à faible consommation d'énergie.</li>
                <li>Débranchez les appareils non utilisés pour éviter la consommation en veille.</li>
            </ul>
        </div>

        <div class="conseil-item">
            <h4>🌐 Optimiser votre Utilisation de l'Internet :</h4>
            <ul>
                <li>Évitez de télécharger des fichiers volumineux pendant les heures de pointe.</li>
                <li>Limitez l'utilisation des vidéos en haute définition.</li>
                <li>Coupez le Wi-Fi lorsque vous ne l'utilisez pas pour économiser la bande passante.</li>
            </ul>
        </div>
    </div>
</div>

</body>
</html>

<?php
// Fermer la connexion à la base de données
mysqli_close($conn);
?>