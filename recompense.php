<?php
include 'connexion.php';

if (!isset($_SESSION['user_id'])) {
    die("Vous devez être connecté.");
}

$user_id = $_SESSION['user_id'];

// Liste des récompenses disponibles avec des icônes plus modernes
$recompenses = [
    '1h de jeux vidéo' => ['points' => 20, 'icone' => '🎮'],
    'Sortie au cinéma' => ['points' => 55, 'icone' => '🎥'],
    'Petit cadeau' => ['points' => 40, 'icone' => '🎁'],
    'Choisir le dîner' => ['points' => 25, 'icone' => '🍽️'],
    '20 dinars' => ['points' => 50, 'icone' => '💰'],
    '50 dinars' => ['points' => 100, 'icone' => '💰'],
    '10 dinars' => ['points' => 35, 'icone' => '💰'],
    'Activité spéciale' => ['points' => 45, 'icone' => '✨']
];

// Variables pour gérer les messages d'erreur/succès
$error_message = '';
$success_message = '';

// Traitement lorsqu'un enfant échange ses points
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enfant_id'], $_POST['recompense'], $_POST['points'])) {
    $enfant_id = intval($_POST['enfant_id']);
    $recompense = $_POST['recompense'];
    $points_requis = intval($_POST['points']);

    // Vérifier si l'enfant appartient au parent
    $sql_check = "SELECT id FROM enfants WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql_check);
    mysqli_stmt_bind_param($stmt, "ii", $enfant_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) === 0) {
        $error_message = "Enfant non trouvé.";
    } else {
        // Vérifier les points disponibles
        $sql_total = "SELECT SUM(points_gagnes) - IFNULL((SELECT SUM(points_utilises) FROM recompenses WHERE enfant_id = ?), 0) AS points_restants
                      FROM point WHERE enfant_id = ?";
        $stmt = mysqli_prepare($conn, $sql_total);
        mysqli_stmt_bind_param($stmt, "ii", $enfant_id, $enfant_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        $points_dispo = $row['points_restants'] ?? 0;

        if ($points_dispo < $points_requis) {
            $error_message = "Points insuffisants pour cette récompense !";
        } else {
            $sql_insert = "INSERT INTO recompenses (enfant_id, nom, points_utilises) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql_insert);
            mysqli_stmt_bind_param($stmt, "isi", $enfant_id, $recompense, $points_requis);
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Récompense obtenue avec succès !";
            } else {
                $error_message = "Erreur lors de l'échange des points.";
            }
        }
    }
}

// Récupération des enfants et de leurs points disponibles
$sql = "SELECT e.id, e.nom_enfant,
               IFNULL(SUM(p.points_gagnes), 0) - IFNULL((SELECT SUM(r.points_utilises) FROM recompenses r WHERE r.enfant_id = e.id), 0) AS points_dispo
        FROM enfants e
        LEFT JOIN point p ON e.id = p.enfant_id
        WHERE e.user_id = ?
        GROUP BY e.id";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$enfants = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Récompenses Familiales | Tableau de Bord</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #4a6bff;
            --secondary: #ff6b6b;
            --accent: #feca57;
            --dark: #2f3640;
            --light: #f5f6fa;
            --success: #1dd1a1;
            --error: #ff6b6b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background:  linear-gradient(135deg, #3b4371, #f3904f);;
            color: var(--light);
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        header {
            text-align: center;
            margin-bottom: 40px;
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            background: linear-gradient(to right, var(--accent), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
        }

        .subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 20px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
            background-color: #3a56e8;
        }

        .btn i {
            margin-right: 8px;
        }

        .btn-back {
            background-color: var(--dark);
            margin-bottom: 30px;
        }

        .btn-back:hover {
            background-color: #1e272e;
        }

        .rewards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .child-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
            position: relative;
        }

        .child-card:hover {
            transform: translateY(-5px);
        }

        .child-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .child-name {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--accent);
        }

        .child-points {
            font-size: 1.2rem;
            font-weight: 700;
            background: var(--success);
            padding: 5px 15px;
            border-radius: 50px;
        }

        .reward-form {
            margin-top: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        select {
            width: 100%;
            padding: 12px 15px;
            border-radius: 8px;
            border: none;
            background-color: rgba(255, 255, 255, 0.9);
            font-size: 16px;
            color: var(--dark);
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 15px;
        }

        select:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(74, 107, 255, 0.3);
        }

        .btn-redeem {
            width: 100%;
            background-color: var(--accent);
            color: var(--dark);
            font-weight: 600;
            padding: 14px;
            margin-top: 10px;
        }

        .btn-redeem:hover {
            background-color: #ffbe33;
        }

        .reward-option {
            display: flex;
            justify-content: space-between;
        }

        .reward-icon {
            margin-right: 10px;
        }

        .reward-cost {
            color: var(--accent);
            font-weight: 600;
        }

        /* Messages d'alerte */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            animation: fadeIn 0.3s ease-out;
        }

        .alert-error {
            background-color: rgba(255, 107, 107, 0.2);
            border-left: 4px solid var(--error);
            color: white;
        }

        .alert-success {
            background-color: rgba(29, 209, 161, 0.2);
            border-left: 4px solid var(--success);
            color: white;
        }

        .alert i {
            margin-right: 10px;
            font-size: 1.2rem;
        }

        .points-warning {
            color: var(--error);
            font-size: 0.9rem;
            margin-top: 5px;
            display: none;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .rewards-grid {
                grid-template-columns: 1fr;
            }
            
            h1 {
                font-size: 2rem;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }

        .shake {
            animation: shake 0.4s ease-in-out;
        }

        .child-card {
            animation: fadeIn 0.5s ease forwards;
        }

        .child-card:nth-child(1) { animation-delay: 0.1s; }
        .child-card:nth-child(2) { animation-delay: 0.2s; }
        .child-card:nth-child(3) { animation-delay: 0.3s; }
        .child-card:nth-child(4) { animation-delay: 0.4s; }
    .tirage-btn {
    display: inline-block;
    background-color: #ffcc00;
    color: #2f3640;
    font-weight: bold;
    padding: 12px 24px;
    border-radius: 30px;
    font-size: 16px;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transition: background 0.3s, transform 0.2s;
}

.tirage-btn:hover {
    background-color: #e6b800;
    transform: translateY(-2px);
}
.performance-btn {
    display: inline-block;
    background-color: #ffcc00;
    color: #2f3640;
    font-weight: bold;
    padding: 12px 24px;
    border-radius: 30px;
    font-size: 16px;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transition: background 0.3s, transform 0.2s;
}

.performance-btn:hover {
    background-color: #e6b800;
    transform: translateY(-2px);
}

    </style>
</head>
<body>
    <div class="container">
        <a href="tache.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Retour aux tâches</a>
        
        <header>
            <h1>Récompenses Familiales</h1>
            <p class="subtitle">Échangez les points accumulés contre des récompenses amusantes !</p>
        </header>
        <div style="text-align: center; margin-top: 20px;">
    <a href="tirage.php" class="tirage-btn">
        🎡 Tirage au sort - Gagne une récompense !
    </a>
    <a href="performance.php" class="performance-btn">
        performance de chaque enfant⚡
    </a>
</div>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= $error_message ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= $success_message ?>
            </div>
        <?php endif; ?>

        <div class="rewards-grid">
            <?php foreach ($enfants as $enfant): ?>
                <div class="child-card" id="child-card-<?= $enfant['id'] ?>">
                    <div class="child-header">
                        <h3 class="child-name"><?= htmlspecialchars($enfant['nom_enfant']) ?></h3>
                        <div class="child-points"><?= $enfant['points_dispo'] ?> pts</div>
                    </div>

                    <form method="POST" class="reward-form" onsubmit="return validatePoints(<?= $enfant['id'] ?>, <?= $enfant['points_dispo'] ?>)">
                        <input type="hidden" name="enfant_id" value="<?= $enfant['id'] ?>">
                        
                        <div class="form-group">
                            <select name="recompense" required onchange="updatePoints(this, <?= $enfant['id'] ?>, <?= $enfant['points_dispo'] ?>)">
                                <option value="">-- Sélectionnez une récompense --</option>
                                <?php foreach ($recompenses as $nom => $details): ?>
                                    <option value="<?= $nom ?>" data-points="<?= $details['points'] ?>">
                                        <span class="reward-option">
                                            <span><?= $details['icone'] ?> <?= $nom ?></span>
                                            <span class="reward-cost"><?= $details['points'] ?> pts</span>
                                        </span>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="points-warning" id="points-warning-<?= $enfant['id'] ?>">
                                <i class="fas fa-exclamation-triangle"></i> Solde de points insuffisant !
                            </div>
                        </div>
                        
                        <input type="hidden" name="points" id="pointsInput-<?= $enfant['id'] ?>">
                        
                        <button type="submit" class="btn btn-redeem">
                            <i class="fas fa-gift"></i> Obtenir la récompense
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        function updatePoints(selectElement, enfantId, pointsDispo) {
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            const warningElement = document.getElementById(`points-warning-${enfantId}`);
            
            if (selectedOption.value) {
                const pointsRequires = parseInt(selectedOption.getAttribute('data-points'));
                document.getElementById(`pointsInput-${enfantId}`).value = pointsRequires;
                
                // Afficher l'avertissement si les points sont insuffisants
                if (pointsRequires > pointsDispo) {
                    warningElement.style.display = 'block';
                    document.getElementById(`child-card-${enfantId}`).classList.add('shake');
                    setTimeout(() => {
                        document.getElementById(`child-card-${enfantId}`).classList.remove('shake');
                    }, 400);
                } else {
                    warningElement.style.display = 'none';
                }
            } else {
                warningElement.style.display = 'none';
            }
        }

        function validatePoints(enfantId, pointsDispo) {
            const selectedOption = document.querySelector(`#child-card-${enfantId} select[name="recompense"]`);
            const pointsRequires = selectedOption.options[selectedOption.selectedIndex].getAttribute('data-points');
            
            if (parseInt(pointsRequires) > pointsDispo) {
                document.getElementById(`points-warning-${enfantId}`).style.display = 'block';
                document.getElementById(`child-card-${enfantId}`).classList.add('shake');
                setTimeout(() => {
                    document.getElementById(`child-card-${enfantId}`).classList.remove('shake');
                }, 400);
                return false;
            }
            return true;
        }
    </script>
</body>
</html>