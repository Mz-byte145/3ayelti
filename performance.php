<?php
include 'connexion.php';

if (!isset($_SESSION['user_id'])) {
    die("Vous devez être connecté pour voir cette page.");
}

$user_id = $_SESSION['user_id'];

// Fetch children data
$sql_enfants = "SELECT * FROM enfants WHERE user_id = ?";
$stmt_enfants = mysqli_prepare($conn, $sql_enfants);
mysqli_stmt_bind_param($stmt_enfants, "i", $user_id);
mysqli_stmt_execute($stmt_enfants);
$result_enfants = mysqli_stmt_get_result($stmt_enfants);
$enfants = mysqli_fetch_all($result_enfants, MYSQLI_ASSOC);

// Calculate family summary
$total_family_points = 0;
$total_family_tasks = 0;

// Fetch performance data for each child
foreach ($enfants as $key => $enfant) {
    // Total points
    $sql_points = "SELECT SUM(points_gagnes) as total FROM point WHERE enfant_id = ?";
    $stmt_points = mysqli_prepare($conn, $sql_points);
    mysqli_stmt_bind_param($stmt_points, "i", $enfant['id']);
    mysqli_stmt_execute($stmt_points);
    $result_points = mysqli_stmt_get_result($stmt_points);
    $total_points = mysqli_fetch_assoc($result_points);
    $enfants[$key]['total_points'] = $total_points['total'] ?? 0;
    $total_family_points += $enfants[$key]['total_points'];

    // Number of tasks completed
    $sql_tasks = "SELECT COUNT(*) as task_count FROM point WHERE enfant_id = ?";
    $stmt_tasks = mysqli_prepare($conn, $sql_tasks);
    mysqli_stmt_bind_param($stmt_tasks, "i", $enfant['id']);
    mysqli_stmt_execute($stmt_tasks);
    $result_tasks = mysqli_stmt_get_result($stmt_tasks);
    $task_count = mysqli_fetch_assoc($result_tasks);
    $enfants[$key]['task_count'] = $task_count['task_count'] ?? 0;
    $total_family_tasks += $enfants[$key]['task_count'];

    // Assign motivational title based on points
    $points = $enfants[$key]['total_points'];
    if ($points >= 100) {
        $enfants[$key]['title'] = "Superstar de la Famille 🌟";
    } elseif ($points >= 50) {
        $enfants[$key]['title'] = "Champion des Tâches 🏆";
    } elseif ($points >= 20) {
        $enfants[$key]['title'] = "Étoile Montante ✨";
    } else {
        $enfants[$key]['title'] = "Petit Héros en Herbe 🌱";
    }

    // Fetch recent tasks (last 5)
    $sql_recent_tasks = "SELECT tache, points_gagnes, date_ajout FROM point WHERE enfant_id = ? ORDER BY date_ajout DESC LIMIT 5";
    $stmt_recent_tasks = mysqli_prepare($conn, $sql_recent_tasks);
    mysqli_stmt_bind_param($stmt_recent_tasks, "i", $enfant['id']);
    mysqli_stmt_execute($stmt_recent_tasks);
    $result_recent_tasks = mysqli_stmt_get_result($stmt_recent_tasks);
    $enfants[$key]['recent_tasks'] = mysqli_fetch_all($result_recent_tasks, MYSQLI_ASSOC);

    // Fetch points history for chart (grouped by date)
    $sql_points_history = "SELECT DATE(date_ajout) as date, SUM(points_gagnes) as daily_points 
                         FROM point 
                         WHERE enfant_id = ? 
                         GROUP BY DATE(date_ajout) 
                         ORDER BY date_ajout ASC 
                         LIMIT 30";
    $stmt_points_history = mysqli_prepare($conn, $sql_points_history);
    mysqli_stmt_bind_param($stmt_points_history, "i", $enfant['id']);
    mysqli_stmt_execute($stmt_points_history);
    $result_points_history = mysqli_stmt_get_result($stmt_points_history);
    $points_history = mysqli_fetch_all($result_points_history, MYSQLI_ASSOC);
    $enfants[$key]['points_history'] = $points_history;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance des Enfants - 3ayelti</title>
    <link href="https://fonts.googleapis.com/css2?family=Bangers&family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --gradient-bg: linear-gradient(135deg, #1f2440 0%, #3b4371 50%, #f3904f 100%);
            --gold: #ffcc00;
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
            font-family: 'Poppins', sans-serif;
            color: white;
            min-height: 100vh;
            padding: 40px 20px;
        }

        .container {
            max-width: 1300px;
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

        h2.page-title {
            font-family: 'Bangers', cursive;
            font-size: 44px;
            color: var(--gold);
            letter-spacing: 2px;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.4);
            margin-bottom: 25px;
            text-align: center;
        }

        .summary-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            padding: 22px 30px;
            border-radius: 20px;
            box-shadow: var(--glass-shadow);
            margin-bottom: 35px;
            display: flex;
            justify-content: space-around;
            align-items: center;
            text-align: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .summary-card p {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }

        .summary-card .highlight {
            color: var(--gold);
            font-size: 24px;
            font-weight: 700;
        }

        .enfant-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 25px;
        }

        .enfant-box {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            padding: 25px;
            border-radius: 20px;
            box-shadow: var(--glass-shadow);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .enfant-box:hover {
            transform: translateY(-5px);
        }

        .enfant-box h3 {
            font-family: 'Bangers', cursive;
            font-size: 32px;
            color: #a8e6cf;
            margin-bottom: 8px;
        }

        .motivational-title {
            font-size: 15px;
            color: var(--gold);
            background: rgba(0, 0, 0, 0.25);
            padding: 6px 14px;
            border-radius: 50px;
            margin-bottom: 15px;
            display: inline-block;
            border: 1px solid rgba(255, 204, 0, 0.3);
        }

        .stats p {
            font-size: 14px;
            margin: 8px 0;
            color: rgba(255, 255, 255, 0.9);
        }

        .stats .highlight {
            font-weight: 700;
            color: var(--gold);
        }

        .chart-container {
            margin: 20px 0;
            height: 200px;
            width: 100%;
        }

        .recent-tasks {
            margin-top: 20px;
            text-align: left;
        }

        .recent-tasks h4 {
            font-size: 15px;
            color: var(--gold);
            margin-bottom: 10px;
        }

        .recent-tasks ul {
            list-style: none;
            padding: 0;
        }

        .recent-tasks li {
            font-size: 13px;
            margin: 6px 0;
            display: flex;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.08);
            padding: 8px 12px;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="top-nav">
            <a href="tache.php" class="retour">⬅ Retour aux Tâches</a>
        </div>

        <div class="summary-card">
            <p>Total Points Familiaux: <span class="highlight"><?= $total_family_points ?> 🌟</span></p>
            <p>Total Tâches Accomplies: <span class="highlight"><?= $total_family_tasks ?> 🎯</span></p>
        </div>

        <h2 class="page-title">⚡ Performance des Enfants</h2>

        <div class="enfant-container">
            <?php foreach ($enfants as $enfant): ?>
                <div class="enfant-box">
                    <h3><?= htmlspecialchars($enfant['nom_enfant']) ?></h3>
                    <div class="motivational-title"><?= htmlspecialchars($enfant['title']) ?></div>

                    <div class="stats">
                        <p>👤 Âge: <span class="highlight"><?= $enfant['age_enfant'] ?> ans</span></p>
                        <p>🌟 Points totaux: <span class="highlight"><?= $enfant['total_points'] ?> pts</span></p>
                        <p>✅ Tâches complétées: <span class="highlight"><?= $enfant['task_count'] ?></span></p>
                    </div>

                    <div class="chart-container">
                        <canvas id="chart-<?= $enfant['id'] ?>"></canvas>
                    </div>

                    <div class="recent-tasks">
                        <h4>📋 Dernières Tâches</h4>
                        <ul>
                            <?php if (empty($enfant['recent_tasks'])): ?>
                                <li>Aucune tâche récente</li>
                            <?php else: ?>
                                <?php foreach ($enfant['recent_tasks'] as $task): ?>
                                    <li>
                                        <span><?= htmlspecialchars($task['tache']) ?></span>
                                        <span style="color:#ffcc00; font-weight:bold;"><?= $task['points_gagnes'] ?> pts</span>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            <?php foreach ($enfants as $enfant): ?>
                const labels<?= $enfant['id'] ?> = [
                    <?php
                    $labels = array_map(function($entry) {
                        return "'" . $entry['date'] . "'";
                    }, $enfant['points_history']);
                    echo implode(',', $labels);
                    ?>
                ];
                const data<?= $enfant['id'] ?> = [
                    <?php
                    $data = array_map(function($entry) {
                        return $entry['daily_points'];
                    }, $enfant['points_history']);
                    echo implode(',', $data);
                    ?>
                ];

                const ctx<?= $enfant['id'] ?> = document.getElementById('chart-<?= $enfant['id'] ?>').getContext('2d');
                new Chart(ctx<?= $enfant['id'] ?>, {
                    type: 'line',
                    data: {
                        labels: labels<?= $enfant['id'] ?>,
                        datasets: [{
                            label: 'Points Gagnés',
                            data: data<?= $enfant['id'] ?>,
                            borderColor: '#ffcc00',
                            backgroundColor: 'rgba(255, 204, 0, 0.15)',
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#ffcc00',
                            pointBorderColor: '#fff',
                            pointRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: { ticks: { color: 'rgba(255, 255, 255, 0.7)' }, grid: { color: 'rgba(255, 255, 255, 0.1)' } },
                            y: { ticks: { color: 'rgba(255, 255, 255, 0.7)' }, grid: { color: 'rgba(255, 255, 255, 0.1)' }, beginAtZero: true }
                        },
                        plugins: {
                            legend: { labels: { color: 'rgba(255, 255, 255, 0.9)' } }
                        }
                    }
                });
            <?php endforeach; ?>
        });
    </script>
</body>
</html>