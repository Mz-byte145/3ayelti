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
                $success_message = "Récompense obtenue avec succès ! 🎉";
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
    <title>Récompenses Familiales - 3ayelti</title>
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
            background: var(--gradient-bg);
            color: white;
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        header {
            text-align: center;
            margin-bottom: 30px;
        }

        h1 {
            font-family: 'Bangers', cursive;
            font-size: 44px;
            color: var(--gold);
            letter-spacing: 2px;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.4);
            margin-bottom: 10px;
        }

        .subtitle {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 300;
        }

        .sub-nav-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 35px;
            flex-wrap: wrap;
        }

        .action-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #ffcc00, #ff9900);
            color: #2c3e50;
            font-weight: 700;
            padding: 12px 24px;
            border-radius: 50px;
            font-size: 15px;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(255, 204, 0, 0.3);
            transition: all 0.3s ease;
        }

        .action-chip:hover {
            background: linear-gradient(135deg, #ffe066, #ffaa00);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 204, 0, 0.5);
        }

        .rewards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 25px;
        }

        .child-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--glass-shadow);
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
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        }

        .child-name {
            font-family: 'Bangers', cursive;
            font-size: 28px;
            letter-spacing: 1px;
            color: #a8e6cf;
        }

        .child-points {
            font-size: 16px;
            font-weight: 700;
            background: linear-gradient(135deg, #4CAF50, #2E7D32);
            padding: 6px 16px;
            border-radius: 50px;
            box-shadow: 0 4px 10px rgba(76, 175, 80, 0.3);
        }

        .form-group {
            margin-bottom: 15px;
        }

        select {
            width: 100%;
            padding: 12px 16px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            background: rgba(255, 255, 255, 0.15);
            font-size: 15px;
            color: white;
            font-family: 'Poppins', sans-serif;
            outline: none;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        select option {
            background: #2c3e50;
            color: white;
        }

        select:focus {
            background: rgba(255, 255, 255, 0.25);
            border-color: var(--gold);
        }

        .btn-redeem {
            width: 100%;
            background: linear-gradient(135deg, #ffcc00, #ff9900);
            color: #2c3e50;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            padding: 14px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 6px 16px rgba(255, 204, 0, 0.3);
        }

        .btn-redeem:hover {
            background: linear-gradient(135deg, #ffe066, #ffaa00);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 204, 0, 0.5);
        }

        .alert {
            padding: 14px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background: rgba(255, 82, 82, 0.25);
            border: 1px solid #ff5252;
            color: #ffcdd2;
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.25);
            border: 1px solid #4CAF50;
            color: #c8e6c9;
        }

        .points-warning {
            color: #ff8a80;
            font-size: 13px;
            margin-top: 8px;
            display: none;
            font-weight: 600;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .shake {
            animation: shake 0.4s ease-in-out;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="top-nav">
            <a href="tache.php" class="btn-back">⬅ Retour aux tâches</a>
        </div>
        
        <header>
            <h1>🏆 Récompenses Familiales</h1>
            <p class="subtitle">Échangez les points accumulés contre des récompenses bien méritées !</p>
        </header>

        <div class="sub-nav-actions">
            <a href="tirage.php" class="action-chip">
                🎡 Tirage au sort - Gagne une récompense !
            </a>
            <a href="performance.php" class="action-chip">
                ⚡ Performance de chaque enfant
            </a>
        </div>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                ❌ <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                ✅ <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <div class="rewards-grid">
            <?php foreach ($enfants as $enfant): ?>
                <div class="child-card" id="child-card-<?= $enfant['id'] ?>">
                    <div class="child-header">
                        <h3 class="child-name"><?= htmlspecialchars($enfant['nom_enfant']) ?></h3>
                        <div class="child-points">🌟 <?= $enfant['points_dispo'] ?> pts</div>
                    </div>

                    <form method="POST" class="reward-form" onsubmit="return validatePoints(<?= $enfant['id'] ?>, <?= $enfant['points_dispo'] ?>)">
                        <input type="hidden" name="enfant_id" value="<?= $enfant['id'] ?>">
                        
                        <div class="form-group">
                            <select name="recompense" required onchange="updatePoints(this, <?= $enfant['id'] ?>, <?= $enfant['points_dispo'] ?>)">
                                <option value="">-- Sélectionnez une récompense --</option>
                                <?php foreach ($recompenses as $nom => $details): ?>
                                    <option value="<?= $nom ?>" data-points="<?= $details['points'] ?>">
                                        <?= $details['icone'] ?> <?= $nom ?> (<?= $details['points'] ?> pts)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="points-warning" id="points-warning-<?= $enfant['id'] ?>">
                                ⚠️ Solde de points insuffisant !
                            </div>
                        </div>
                        
                        <input type="hidden" name="points" id="pointsInput-<?= $enfant['id'] ?>">
                        
                        <button type="submit" class="btn-redeem">
                            🎁 Obtenir la récompense
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