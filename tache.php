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
    <title>Gestion des Tâches</title>
    <link href="https://fonts.googleapis.com/css2?family=Dancing+Script&family=Bangers&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #3b4371, #f3904f);
            font-family: 'Roboto', sans-serif;
            color: white;
            text-align: center;
        }

        h2 {
            font-family: 'Bangers', cursive;
            font-size: 36px;
            color: #ffcc00;
            margin-top: 20px;
        }

        .user-info .budget-box {
            background: linear-gradient(135deg, #3b4371, #f3904f);
            color: #ffcc00;
            padding: 20px;
            border-radius: 20px;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.4);
            font-size: 22px;
            width: 220px;
            text-align: center;
            position: fixed;
            top: 15px;
            left: 13px;
            z-index: 100;
        }

        .enfant-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            margin: 60px auto;
            padding: 20px;
            max-width: 1200px;
        }

        .enfant-box {
            background: rgba(255, 255, 255, 0.2);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.4);
            width: 270px;
            position: relative;
        }

        .enfant-box h3 {
            font-family: 'Bangers', cursive;
            font-size: 36px;
            color: #a8e6cf;
            margin-top: 20px;
        }

        .enfant-box h4 {
            font-size: 24px;
            font-weight: bold;
            color: #ffcc00;
            background: rgba(8, 44, 23, 0.8);
            padding: 10px;
            border-radius: 12px;
            margin-bottom: 15px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            transition: transform 0.3s ease, background 0.3s ease;
        }

        .enfant-box h4.point-anim {
            transform: scale(1.1);
            background: rgba(76, 175, 80, 0.8);
        }

        ul {
            list-style: none;
            padding: 0;
        }

        li {
            font-size: 20px;
            margin: 10px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .task-btn {
            background: #4CAF50;
            border: none;
            color: white;
            padding: 6px 10px;
            border-radius: 8px;
            cursor: pointer;
        }

        .task-btn:hover {
            background: #45a049;
        }

        .task-btn.completed {
            background: #aaa;
            color: white;
            text-decoration: line-through;
            cursor: default;
        }

        .retour {
            position: absolute;
            top: 6px;
            right: 6px;
            color: white;
            background: red;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            transition: background 0.6s ease, transform 0.4s ease;
            display: inline-block;
            font-weight: bold;
        }
        .retour:hover {
            background: #ffe066;
            transform: scale(1.05);
        }
        

        .facture-btn {
            background: #ffcc00;
            color: #3b4371;
            padding: 15px 40px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            margin-top: 10px;
            transition: background 0.3s ease, transform 0.2s ease;
            position: absolute;
            top: -8px;
            right: 332px;
        }

        .facture-btn:hover {
            background: #ffe066;
            transform: scale(1.05);
        }

        .success-message {
            background: #4CAF50;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 20px auto;
            width: 80%;
            animation: fadeIn 0.5s;
        }

        /* Styles pour l'ajout de tâches */
        .add-task-btn {
            background: #ff9800;
            border: none;
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            cursor: pointer;
            margin-bottom: 15px;
            font-size: 16px;
            transition: background 0.3s;
        }

        .add-task-btn:hover {
            background: #f57c00;
        }

        .add-task-form {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: none;
        }

        .task-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .task-form input {
            padding: 8px;
            border-radius: 5px;
            border: none;
        }

        .task-submit-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px;
            border-radius: 5px;
            cursor: pointer;
        }

        .task-cancel-btn {
            background: #f44336;
            color: white;
            border: none;
            padding: 8px;
            border-radius: 5px;
            cursor: pointer;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .recompense-btn {
            background: #ffcc00;
            color: #3b4371;
            padding: 15px 5px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            margin-top: 10px;
            transition: background 0.3s ease, transform 0.2s ease;
            position: absolute;
            top: -8px;
            right: 100px;
        }

        .recompense-btn:hover {
            background: #ffe066;
            transform: scale(1.05);
        }
        
    </style>
</head>
<body>
    <a href="formulaire.html" class="retour">Retour</a>

    <h2>Ton effort, ta récompense !</h2>

    <div class="user-info">
        <div class="budget-box">
            <p><strong>Budget :</strong> <?= $salaire ?> TND</p>
        </div>
    </div>

    <h3>Liste des tâches familiales</h3>

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

    <div class="facture-box">
        <a href="facture.php" class="facture-btn">Voir les factures🧾</a>
        <a href="recompense.php" class="recompense-btn">consulter les recompenses🏆</a>
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