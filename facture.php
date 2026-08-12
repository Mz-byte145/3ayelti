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
    <title>Mes Factures - 3ayelti</title>
    <link href="https://fonts.googleapis.com/css2?family=Bangers&family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --gradient-bg: linear-gradient(135deg, #1f2440 0%, #3b4371 50%, #f3904f 100%);
            --gold: #ffcc00;
            --gold-hover: #ffe066;
            --glass-bg: rgba(255, 255, 255, 0.12);
            --glass-border: rgba(255, 255, 255, 0.2);
            --glass-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--gradient-bg);
            color: #fff;
            margin: 0;
            padding: 40px 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .retour {
            text-decoration: none;
            background: rgba(255, 255, 255, 0.15);
            color: white;
            padding: 10px 20px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .retour:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        h1 {
            font-family: 'Bangers', cursive;
            color: var(--gold);
            font-size: 42px;
            letter-spacing: 2px;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.4);
            margin-bottom: 25px;
            text-align: center;
        }

        .table-container {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            padding: 25px;
            border-radius: 20px;
            box-shadow: var(--glass-shadow);
            margin-bottom: 40px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 16px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        th {
            background: rgba(0, 0, 0, 0.3);
            color: var(--gold);
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .btn-payer {
            background: linear-gradient(135deg, #4CAF50, #2E7D32);
            color: white;
            padding: 10px 22px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
        }

        .btn-payer:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(76, 175, 80, 0.5);
            background: linear-gradient(135deg, #66BB6A, #388E3C);
        }

        .total {
            font-size: 22px;
            font-weight: 700;
            text-align: center;
            margin-top: 25px;
            color: var(--gold);
            background: rgba(0, 0, 0, 0.25);
            padding: 15px;
            border-radius: 12px;
            border: 1px solid rgba(255, 204, 0, 0.3);
        }

        .conseils {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            padding: 30px;
            border-radius: 20px;
            box-shadow: var(--glass-shadow);
        }

        .conseils h2 {
            text-align: center;
            color: var(--gold);
            font-family: 'Bangers', cursive;
            font-size: 32px;
            letter-spacing: 1px;
            margin-bottom: 25px;
        }

        .conseil-item {
            margin-bottom: 20px;
            background: rgba(255, 255, 255, 0.05);
            padding: 18px;
            border-radius: 14px;
        }

        .conseil-item h4 {
            font-size: 18px;
            color: #a8e6cf;
            margin-bottom: 10px;
        }

        .conseil-item ul {
            padding-left: 20px;
        }

        .conseil-item li {
            margin-bottom: 6px;
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
        }

        .empty-state {
            padding: 30px;
            font-size: 18px;
            color: rgba(255, 255, 255, 0.8);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="top-nav">
        <a href="tache.php" class="retour">⬅ Retour aux Tâches</a>
    </div>

    <h1>🧾 Factures à Payer</h1>

    <div class="table-container">
        <?php if (mysqli_num_rows($result) > 0): ?>
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
                            <td style="font-weight:600; font-size:16px;"><?= htmlspecialchars($row['nom_facture']) ?></td>
                            <td style="color:#ffcc00; font-weight:700;"><?= number_format($row['montant'], 2, ',', ' ') ?> TND</td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="facture_id" value="<?= $row['id'] ?>">
                                    <input type="hidden" name="montant" value="<?= $row['montant'] ?>">
                                    <button type="submit" class="btn-payer">Payer 💳</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <div class="total">Total des Factures à Payer : <?= number_format($total_factures, 2, ',', ' ') ?> TND</div>
        <?php else: ?>
            <div class="empty-state">🎉 Félicitations ! Toutes vos factures sont réglées.</div>
        <?php endif; ?>
    </div>

    <!-- Section des Conseils -->
    <div class="conseils">
        <h2>💡 Conseils Éco-Responsables</h2>

        <div class="conseil-item">
            <h4>💧 Économiser l'Eau :</h4>
            <ul>
                <li>Fermez le robinet pendant que vous vous brossez les dents.</li>
                <li>Réparez les fuites d'eau immédiatement.</li>
                <li>Installez des mousseurs d'eau à faible débit.</li>
            </ul>
        </div>

        <div class="conseil-item">
            <h4>⚡ Économiser l'Électricité :</h4>
            <ul>
                <li>Éteignez les lumières et appareils non utilisés.</li>
                <li>Utilisez des ampoules LED basse consommation.</li>
                <li>Débranchez les chargeurs et multiprises en veille.</li>
            </ul>
        </div>

        <div class="conseil-item">
            <h4>🌐 Utilisation Optimale d'Internet :</h4>
            <ul>
                <li>Évitez le streaming Ultra HD inutile pendant la nuit.</li>
                <li>Éteignez votre box Wi-Fi lorsque vous partez en vacances.</li>
            </ul>
        </div>
    </div>
</div>

</body>
</html>

<?php
mysqli_close($conn);
?>