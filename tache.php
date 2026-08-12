<?php
include 'connexion.php';

if (!isset($_SESSION['user_id'])) {
    die("Vous devez être connecté pour voir cette page.");
}

$user_id = $_SESSION['user_id'];

// Traitement AJAX pour enregistrer les points et ajouter des tâches
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['enfant_id'], $_POST['points'], $_POST['tache'])) {
        // Enregistrement d'une tâche complétée
        $enfant_id = $_POST['enfant_id'];
        $points = $_POST['points'];
        $tache = $_POST['tache'];

        $sql = "INSERT INTO point (enfant_id, points_gagnes, tache) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iis", $enfant_id, $points, $tache);
        mysqli_stmt_execute($stmt);

        echo "Points enregistrés !";
        exit;
    }
    elseif (isset($_POST['add_task'], $_POST['enfant_id'], $_POST['task_name'], $_POST['task_points'])) {
        // Ajout d'une nouvelle tâche personnalisée
        $enfant_id = $_POST['enfant_id'];
        $task_name = $_POST['task_name'];
        $task_points = $_POST['task_points'];

        // Vérifier que l'enfant appartient à l'utilisateur
        $sql = "SELECT id FROM enfants WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $enfant_id, $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            // Ajouter la tâche à la table point
            $sql = "INSERT INTO point (enfant_id, tache, points_gagnes) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "isi", $enfant_id, $task_name, $task_points);
            mysqli_stmt_execute($stmt);
            
            echo "Tâche ajoutée avec succès!";
            exit;
        } else {
            echo "Erreur: Enfant non trouvé";
            exit;
        }
    }
}

// Budget
$sql = "SELECT salaire, nb_enfants FROM budget_familial WHERE user_id = ? ORDER BY id DESC LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$budget = mysqli_fetch_assoc($result);
$salaire = $budget['salaire'] ?? 'Non défini';
$nb_enfants = $budget['nb_enfants'] ?? 'Non défini';

// Enfants
$sql_enfants = "SELECT * FROM enfants WHERE user_id = ?";
$stmt_enfants = mysqli_prepare($conn, $sql_enfants);
mysqli_stmt_bind_param($stmt_enfants, "i", $user_id);
mysqli_stmt_execute($stmt_enfants);
$result_enfants = mysqli_stmt_get_result($stmt_enfants);
$enfants = mysqli_fetch_all($result_enfants, MYSQLI_ASSOC);

// Récupérer le total des points pour chaque enfant
foreach ($enfants as $key => $enfant) {
    $sql_points = "SELECT SUM(points_gagnes) as total FROM point WHERE enfant_id = ?";
    $stmt_points = mysqli_prepare($conn, $sql_points);
    mysqli_stmt_bind_param($stmt_points, "i", $enfant['id']);
    mysqli_stmt_execute($stmt_points);
    $result_points = mysqli_stmt_get_result($stmt_points);
    $total_points = mysqli_fetch_assoc($result_points);
    $enfants[$key]['total_points'] = $total_points['total'] ?? 0;
    
    // Récupérer les tâches personnalisées pour chaque enfant
    $sql_taches = "SELECT tache, points_gagnes FROM point WHERE enfant_id = ?";
    $stmt_taches = mysqli_prepare($conn, $sql_taches);
    mysqli_stmt_bind_param($stmt_taches, "i", $enfant['id']);
    mysqli_stmt_execute($stmt_taches);
    $result_taches = mysqli_stmt_get_result($stmt_taches);
    $enfants[$key]['taches_perso'] = mysqli_fetch_all($result_taches, MYSQLI_ASSOC);
}

// Tâches prédéfinies
$taches = [
    'Faire les courses 🛒' => ['min_age' => 18, 'max_age' => 22, 'points' => 15],
    'Nettoyer la maison 🧹' => ['min_age' => 16, 'max_age' => 22, 'points' => 12],
    'Lire un livre 📚' => ['min_age' => 5, 'max_age' => 17, 'points' => 8],
    'Avoir une bonne note dans un devoir ✍️' => ['min_age' => 6, 'max_age' => 18, 'points' => 10],
    'Ranger les jouets 🧸' => ['min_age' => 3, 'max_age' => 6, 'points' => 5],
    'Faire son lit 🛏️' => ['min_age' => 6, 'max_age' => 14, 'points' => 4],
    'Aider à vider le lave-vaisselle 🍽️' => ['min_age' => 18, 'max_age' => 22, 'points' => 7],
    'Arroser les plantes 🌱' => ['min_age' => 13, 'max_age' => 17, 'points' => 6],
    'Gérer les comptes d\'électricité et d\'eau 💡💧' => ['min_age' => 19, 'max_age' => 22, 'points' => 18],
    'Apprendre à sortir les poubelles 🗑️' => ['min_age' => 10, 'max_age' => 15, 'points' => 5],
    'brosser ses dents 🪥🦷' => ['min_age' => 3, 'max_age' => 15, 'points' => 3],
];

function afficherTaches($age) {
    global $taches;
    $taches_adaptees = [];
    foreach ($taches as $tache => $age_limits) {
        if ($age >= $age_limits['min_age'] && $age <= $age_limits['max_age']) {
            $taches_adaptees[$tache] = $age_limits['points'];
        }
    }
    return $taches_adaptees;
}

if (isset($_GET['message']) && $_GET['message'] === 'facture_paye') {
    echo "<div class='success-message'>La facture a été payée avec succès!</div>";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Tâches - 3ayelti</title>
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
            margin: 0;
            padding: 0 0 40px;
            background: var(--gradient-bg);
            font-family: 'Poppins', sans-serif;
            color: white;
            text-align: center;
            min-height: 100vh;
        }

        /* Top Navbar */
        .top-navbar {
            background: rgba(15, 20, 35, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            flex-wrap: wrap;
            gap: 12px;
        }

        .budget-badge {
            background: linear-gradient(135deg, #ffcc00, #ff9900);
            color: #2c3e50;
            padding: 8px 18px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 16px;
            box-shadow: 0 4px 12px rgba(255, 204, 0, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .nav-btn {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            padding: 8px 16px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, 0.4);
        }

        .nav-btn.gold {
            background: var(--gold);
            color: #2c3e50;
            border: none;
            font-weight: 700;
        }

        .nav-btn.gold:hover {
            background: var(--gold-hover);
        }

        .nav-btn.danger {
            background: rgba(255, 82, 82, 0.3);
            border-color: #ff5252;
            color: #ffcdd2;
        }

        .nav-btn.danger:hover {
            background: rgba(255, 82, 82, 0.5);
        }

        h2.page-title {
            font-family: 'Bangers', cursive;
            font-size: 42px;
            color: var(--gold);
            margin: 30px 15px 10px;
            letter-spacing: 2px;
            text-shadow: 2px 2px 10px rgba(0, 0, 0, 0.4);
        }

        h3.section-subtitle {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 300;
            margin-bottom: 30px;
        }

        .enfant-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
            max-width: 1300px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .enfant-box {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            padding: 25px;
            border-radius: 20px;
            box-shadow: var(--glass-shadow);
            position: relative;
            text-align: left;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .enfant-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .enfant-box h3 {
            font-family: 'Bangers', cursive;
            font-size: 34px;
            color: #a8e6cf;
            margin-bottom: 10px;
            letter-spacing: 1px;
            text-align: center;
        }

        .enfant-box h4 {
            font-size: 18px;
            font-weight: 700;
            color: var(--gold);
            background: rgba(0, 0, 0, 0.3);
            padding: 10px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid rgba(255, 204, 0, 0.3);
            transition: all 0.3s ease;
        }

        .enfant-box h4.point-anim {
            transform: scale(1.08);
            background: rgba(76, 175, 80, 0.6);
            border-color: #4CAF50;
        }

        ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        li {
            font-size: 15px;
            margin: 10px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.08);
            padding: 10px 14px;
            border-radius: 10px;
            gap: 10px;
        }

        .task-btn {
            background: linear-gradient(135deg, #4CAF50, #2E7D32);
            border: none;
            color: white;
            padding: 8px 14px;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.2s ease;
            white-space: nowrap;
            box-shadow: 0 4px 10px rgba(76, 175, 80, 0.3);
        }

        .task-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 14px rgba(76, 175, 80, 0.5);
        }

        .task-btn.completed {
            background: rgba(255, 255, 255, 0.2);
            color: rgba(255, 255, 255, 0.6);
            text-decoration: line-through;
            cursor: default;
            box-shadow: none;
        }

        .add-task-btn {
            background: linear-gradient(135deg, #ff9800, #f57c00);
            border: none;
            color: white;
            padding: 10px 16px;
            border-radius: 10px;
            cursor: pointer;
            margin-bottom: 18px;
            font-size: 14px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
        }

        .add-task-btn:hover {
            background: linear-gradient(135deg, #ffa726, #fb8c00);
            transform: translateY(-2px);
        }

        .add-task-form {
            background: rgba(0, 0, 0, 0.25);
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 18px;
            display: none;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .task-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .task-form input {
            padding: 10px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.15);
            color: white;
            outline: none;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
        }

        .task-submit-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .task-cancel-btn {
            background: #f44336;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .success-message {
            background: rgba(76, 175, 80, 0.3);
            border: 1px solid #4CAF50;
            color: white;
            padding: 14px;
            border-radius: 12px;
            margin: 20px auto;
            max-width: 600px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <header class="top-navbar">
        <div class="budget-badge">
            💰 Budget : <?= htmlspecialchars($salaire) ?> TND
        </div>
        <div class="nav-actions">
            <a href="facture.php" class="nav-btn gold">🧾 Factures</a>
            <a href="recompense.php" class="nav-btn gold">🏆 Récompenses</a>
            <a href="deconnexion.php" class="nav-btn danger">🚪 Déconnexion</a>
        </div>
    </header>

    <h2 class="page-title">Ton effort, ta récompense ! 🌟</h2>
    <h3 class="section-subtitle">Sélectionne les tâches accomplies pour gagner des points !</h3>

    <div class="enfant-container">
        <?php foreach ($enfants as $enfant): ?>
            <div class="enfant-box">
                <h3><?= htmlspecialchars($enfant['nom_enfant']) ?></h3>
                <h4 id="points-<?= $enfant['id'] ?>">🌟 Total des points : <?= $enfant['total_points'] ?></h4>
                
                <!-- Bouton pour ajouter des tâches -->
                <button class="add-task-btn" data-enfant-id="<?= $enfant['id'] ?>">
                    + Ajouter une tâche
                </button>
                
                <!-- Formulaire d'ajout de tâche (caché par défaut) -->
                <div class="add-task-form" id="form-<?= $enfant['id'] ?>">
                    <form class="task-form" data-enfant-id="<?= $enfant['id'] ?>">
                        <input type="text" class="task-name" placeholder="Nom de la tâche" required>
                        <input type="number" class="task-points" placeholder="Points" min="1" required>
                        <button type="submit" class="task-submit-btn">Valider</button>
                        <button type="button" class="task-cancel-btn">Annuler</button>
                    </form>
                </div>
                
                <ul>
                <?php 
                    // 1. Afficher les tâches prédéfinies
                    $taches_enfant = afficherTaches($enfant['age_enfant']);
                    $taches_deja_vues = []; // Pour suivre toutes les tâches déjà affichées
                    
                    foreach ($taches_enfant as $tache_nom => $points): 
                        $taches_deja_vues[$tache_nom] = true;
                ?>
                    <li>
                        <?= htmlspecialchars($tache_nom) ?>
                        <button 
                            class="task-btn" 
                            data-points="<?= htmlspecialchars($points) ?>" 
                            data-target="points-<?= $enfant['id'] ?>"
                            data-enfant-id="<?= $enfant['id'] ?>"
                            data-tache="<?= htmlspecialchars($tache_nom) ?>"
                        >
                            Complétée
                        </button>
                    </li>
                <?php endforeach; ?>

                <?php 
                    // 2. Afficher les tâches personnalisées uniques
                    if (!empty($enfant['taches_perso'])) {
                        $taches_perso_uniques = [];
                        
                        foreach ($enfant['taches_perso'] as $tache) {
                            $nom_tache = $tache['tache'];
                            // Vérifier si la tâche n'est ni dans les prédéfinies, ni déjà dans les personnalisées
                            if (!isset($taches_deja_vues[$nom_tache]) && !isset($taches_perso_uniques[$nom_tache])) {
                                $taches_perso_uniques[$nom_tache] = $tache;
                                $taches_deja_vues[$nom_tache] = true; // Marquer comme vue
                            }
                        }
                        
                        foreach ($taches_perso_uniques as $tache): 
                ?>
                            <li>
                                <?= htmlspecialchars($tache['tache']) ?>
                                <button 
                                    class="task-btn" 
                                    data-points="<?= htmlspecialchars($tache['points_gagnes']) ?>" 
                                    data-target="points-<?= $enfant['id'] ?>"
                                    data-enfant-id="<?= $enfant['id'] ?>"
                                    data-tache="<?= htmlspecialchars($tache['tache']) ?>"
                                >
                                    Complétée
                                </button>
                            </li>
                <?php 
                        endforeach;
                    }
                ?>
            </ul>
        </div>
    <?php endforeach; ?>
</div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Gestion des boutons "Complétée"
            const taskButtons = document.querySelectorAll('.task-btn:not(.completed)');
            taskButtons.forEach(btn => {
                btn.addEventListener('click', async function() {
                    const points = parseInt(this.dataset.points);
                    const targetId = this.dataset.target;
                    const enfantId = this.dataset.enfantId;
                    const tache = this.dataset.tache;

                    try {
                        const response = await fetch('tache.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `enfant_id=${enfantId}&points=${points}&tache=${encodeURIComponent(tache)}`
                        });

                        if (response.ok) {
                            const display = document.getElementById(targetId);
                            const currentPoints = parseInt(display.textContent.match(/\d+/)[0]);
                            const newPoints = currentPoints + points;

                            display.textContent = `🌟 Total des points : ${newPoints}`;
                            display.classList.add("point-anim");

                            setTimeout(() => {
                                display.classList.remove("point-anim");
                            }, 300);

                            this.disabled = true;
                            this.textContent = "✔ Terminé";
                            this.classList.add("completed");
                        } else {
                            console.error("Erreur lors de l'enregistrement");
                        }
                    } catch (error) {
                        console.error("Erreur réseau :", error);
                    }
                });
            });

            // Gestion des boutons "Ajouter une tâche"
            document.querySelectorAll('.add-task-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const enfantId = this.dataset.enfantId;
                    const form = document.getElementById(`form-${enfantId}`);
                    
                    if (form.style.display === 'none' || !form.style.display) {
                        form.style.display = 'block';
                        this.textContent = '- Masquer le formulaire';
                    } else {
                        form.style.display = 'none';
                        this.textContent = '+ Ajouter une tâche';
                    }
                });
            });

            // Gestion de l'annulation
            document.querySelectorAll('.task-cancel-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const form = this.closest('.add-task-form');
                    form.style.display = 'none';
                    const addBtn = form.previousElementSibling;
                    if (addBtn.classList.contains('add-task-btn')) {
                        addBtn.textContent = '+ Ajouter une tâche';
                    }
                });
            });

            // Soumission des formulaires de tâche
            document.querySelectorAll('.task-form').forEach(form => {
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const enfantId = this.dataset.enfantId;
                    const taskName = this.querySelector('.task-name').value;
                    const taskPoints = this.querySelector('.task-points').value;
                    
                    try {
                        const response = await fetch('tache.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `add_task=true&enfant_id=${enfantId}&task_name=${encodeURIComponent(taskName)}&task_points=${taskPoints}`
                        });

                        if (response.ok) {
                            // Recharger la page pour afficher la nouvelle tâche
                            location.reload();
                        } else {
                            alert("Erreur lors de l'ajout de la tâche");
                        }
                    } catch (error) {
                        console.error("Erreur réseau :", error);
                    }
                });
            });
        });
    </script>
</body>
</html>