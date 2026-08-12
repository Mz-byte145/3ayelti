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
    <title>Performance des Enfants</title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Bangers&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Global Styles */
        body {
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #2c3e50, #fd746c);
            font-family: 'Poppins', sans-serif;
            color: white;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        .container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 20px;
        }

        h2 {
            font-family: 'Bangers', cursive;
            font-size: 48px;
            color: #ffeb3b;
            text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.3);
            margin-bottom: 30px;
            animation: fadeIn 1s ease-in;
        }

        /* Summary Card */
        .summary-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            margin-bottom: 40px;
            display: flex;
            justify-content: space-around;
            align-items: center;
            text-align: center;
            animation: slideIn 0.8s ease-out;
        }

        .summary-card p {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }

        .summary-card .highlight {
            color: #ffeb3b;
            font-size: 24px;
        }

        /* Child Container */
        .enfant-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
            padding: 0 10px;
        }

        .enfant-box {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
        }

        .enfant-box:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
        }

        .enfant-box h3 {
            font-family: 'Bangers', cursive;
            font-size: 32px;
            color: #a8e6cf;
            margin: 10px 0;
        }

        .motivational-title {
            font-size: 18px;
            color: #ffeb3b;
            background: rgba(0, 0, 0, 0.3);
            padding: 8px 15px;
            border-radius: 10px;
            margin: 10px 0;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        /* Stats Section */
        .stats {
            margin: 20px 0;
            position: relative;
        }

        .stats p {
            font-size: 16px;
            margin: 8px 0;
            color: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .stats .highlight {
            font-weight: bold;
            color: #ffeb3b;
        }

        /* Progress Circle */
        .progress-circle {
            position: relative;
            width: 80px;
            height: 80px;
            margin: 15px auto;
        }

        .progress-circle svg {
            transform: rotate(-90deg);
        }

        .progress-circle circle {
            fill: none;
            stroke-width: 8;
            stroke-linecap: round;
            cx: 40;
            cy: 40;
            r: 36;
        }

        .progress-circle .bg {
            stroke: rgba(255, 255, 255, 0.2);
        }

        .progress-circle .progress {
            stroke: #ffeb3b;
            stroke-dasharray: 226;
            stroke-dashoffset: 0;
            transition: stroke-dashoffset 1s ease;
        }

        .progress-circle span {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 18px;
            font-weight: bold;
            color: #ffeb3b;
        }

        /* Chart Section */
        .chart-container {
            margin: 20px 0;
            position: relative;
            height: 220px;
            width: 100%;
        }

        /* Recent Tasks */
        .recent-tasks {
            margin-top: 20px;
            text-align: left;
        }

        .recent-tasks h4 {
            font-size: 16px;
            color: #ffeb3b;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .recent-tasks ul {
            list-style: none;
            padding: 0;
        }

        .recent-tasks li {
            font-size: 14px;
            margin: 8px 0;
            display: flex;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.05);
            padding: 10px;
            border-radius: 8px;
            transition: background 0.3s ease;
        }

        .recent-tasks li:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        /* Back Button */
        .retour {
            position: fixed;
            top: 20px;
            right: 20px;
            color: white;
            background: #e63946;
            padding: 12px 25px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.3s ease, transform 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .retour:hover {
            background: #ffeb3b;
            color: #2c3e50;
            transform: scale(1.05);
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <a href="tache.php" class="retour">Retour</a>
    <div class="container">
        <!-- Family Summary -->
        <div class="summary-card">
            <p>Points Familiaux: <span class="highlight"><?= $total_family_points ?> 🌟</span></p>
            <p>Tâches Complétées: <span class="highlight"><?= $total_family_tasks ?></span></p>
        </div>

        <h2>Performance des Enfants</h2>
        <div class="enfant-container">
            <?php foreach ($enfants as $enfant): ?>
                <div class="enfant-box">
                    <h3><?= htmlspecialchars($enfant['nom_enfant']) ?></h3>
                    <div class="motivational-title"><?= htmlspecialchars($enfant['title']) ?></div>
                    
                    <!-- Progress Circle -->
                    <div class="progress-circle">
                        <svg width="80" height="80">
                            <circle class="bg" stroke="rgba(255, 255, 255, 0.2)"></circle>
                            <circle class="progress" stroke-dashoffset="<?= 226 - ($enfant['total_points'] / 100 * 226) ?>"></circle>
                        </svg>
                        <span><?= min($enfant['total_points'], 100) ?>%</span>
                    </div>

                    <div class="stats">
                        <p><i class="fas fa-user"></i> Âge: <span class="highlight"><?= $enfant['age_enfant'] ?> ans</span></p>
                        <p><i class="fas fa-star"></i> Points totaux: <span class="highlight"><?= $enfant['total_points'] ?> 🌟</span></p>
                        <p><i class="fas fa-check-circle"></i> Tâches complétées: <span class="highlight"><?= $enfant['task_count'] ?></span></p>
                    </div>

                    <!-- Points Trend Chart -->
                    <div class="chart-container">
                        <canvas id="chart-<?= $enfant['id'] ?>"></canvas>
                    </div>

                    <!-- Recent Tasks -->
                    <div class="recent-tasks">
                        <h4><i class="fas fa-tasks"></i> Dernières Tâches</h4>
                        <ul>
                            <?php if (empty($enfant['recent_tasks'])): ?>
                                <li>Aucune tâche récente</li>
                            <?php else: ?>
                                <?php foreach ($enfant['recent_tasks'] as $task): ?>
                                    <li>
                                        <span><?= htmlspecialchars($task['tache']) ?></span>
                                        <span><?= $task['points_gagnes'] ?> pts</span>
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
            // Render charts for each child
            <?php foreach ($enfants as $enfant): ?>
                // Prepare chart data
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

                // Create the chart
                const ctx<?= $enfant['id'] ?> = document.getElementById('chart-<?= $enfant['id'] ?>').getContext('2d');
                new Chart(ctx<?= $enfant['id'] ?>, {
                    type: 'line',
                    data: {
                        labels: labels<?= $enfant['id'] ?>,
                        datasets: [{
                            label: 'Points Gagnés',
                            data: data<?= $enfant['id'] ?>,
                            borderColor: '#ffeb3b',
                            backgroundColor: 'rgba(255, 235, 59, 0.2)',
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#ffeb3b',
                            pointBorderColor: '#fff',
                            pointHoverBackgroundColor: '#fff',
                            pointHoverBorderColor: '#ffeb3b',
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                title: {
                                    display: true,
                                    text: 'Date',
                                    color: '#e0e0e0',
                                    font: { size: 14 }
                                },
                                ticks: {
                                    color: '#e0e0e0',
                                    maxTicksLimit: 5
                                },
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                }
                            },
                            y: {
                                title: {
                                    display: true,
                                    text: 'Points',
                                    color: '#e0e0e0',
                                    font: { size: 14 }
                                },
                                ticks: {
                                    color: '#e0e0e0',
                                    stepSize: 10
                                },
                                grid: {
                                    color: 'rgba(255, 255, 255, 0.1)'
                                },
                                beginAtZero: true
                            }
                        },
                        plugins: {
                            legend: {
                                labels: {
                                    color: '#e0e0e0',
                                    font: { size: 14 }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: '#ffeb3b',
                                bodyColor: '#e0e0e0',
                                borderColor: '#ffeb3b',
                                borderWidth: 1
                            }
                        }
                    }
                });
            <?php endforeach; ?>
        });
    </script>
</body>
</html>