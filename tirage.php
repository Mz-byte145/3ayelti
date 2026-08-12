<?php
include 'connexion.php';

if (!isset($_SESSION['user_id'])) {
    die("Vous devez être connecté.");
}

$user_id = $_SESSION['user_id'];

// Récupération des enfants
$sql = "SELECT id, nom_enfant FROM enfants WHERE user_id = ?";
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
    <title>Tirage au Sort - 3ayelti</title>
    <link href="https://fonts.googleapis.com/css2?family=Bangers&family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
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
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px 20px;
        }

        .container {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            padding: 40px 30px;
            border-radius: 24px;
            box-shadow: var(--glass-shadow);
            text-align: center;
            width: 100%;
            max-width: 700px;
            position: relative;
        }

        .btn-back {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.15);
            color: white;
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 14px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        h1 {
            font-family: 'Bangers', cursive;
            font-size: 42px;
            color: var(--gold);
            letter-spacing: 2px;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.4);
            margin-top: 15px;
            margin-bottom: 20px;
        }

        .wheel-container {
            position: relative;
            width: 280px;
            height: 280px;
            margin: 25px auto;
        }

        .wheel {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 8px solid white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 0 25px rgba(255, 204, 0, 0.4);
            transition: transform 4s cubic-bezier(0.33, 1, 0.68, 1);
        }

        .wheel div {
            position: absolute;
            width: 50%;
            height: 50%;
            top: 50%;
            left: 50%;
            transform-origin: 0% 0%;
            background: rgba(255, 255, 255, 0.2);
            color: #fff;
            font-weight: bold;
            display: flex;
            justify-content: flex-start;
            align-items: center;
            padding-left: 15px;
            font-size: 13px;
        }

        .pointer {
            width: 0;
            height: 0;
            border-left: 18px solid transparent;
            border-right: 18px solid transparent;
            border-bottom: 28px solid var(--gold);
            position: absolute;
            top: -30px;
            left: calc(50% - 18px);
            z-index: 10;
            filter: drop-shadow(0 2px 5px rgba(0, 0, 0, 0.5));
        }

        .btn-spin {
            background: linear-gradient(135deg, #ffcc00, #ff9900);
            color: #2c3e50;
            padding: 14px 35px;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            cursor: pointer;
            font-weight: 700;
            margin-top: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 6px 18px rgba(255, 204, 0, 0.4);
            font-family: 'Poppins', sans-serif;
        }

        .btn-spin:hover {
            background: linear-gradient(135deg, #ffe066, #ffaa00);
            transform: translateY(-3px) scale(1.03);
            box-shadow: 0 10px 25px rgba(255, 204, 0, 0.6);
        }

        .result {
            font-size: 20px;
            margin-top: 25px;
            color: #fff;
            font-weight: 600;
            min-height: 40px;
        }

        .children-list {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            margin: 15px 0 20px;
        }

        .child-item {
            background: rgba(255, 255, 255, 0.15);
            padding: 10px 22px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-weight: 500;
        }

        .child-item:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .child-item.active {
            background: var(--gold);
            color: #2c3e50;
            font-weight: 700;
            border-color: var(--gold);
            box-shadow: 0 4px 12px rgba(255, 204, 0, 0.4);
        }

        .child-icon {
            margin-right: 8px;
        }

        .error-message {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(255, 82, 82, 0.9);
            color: white;
            padding: 12px 25px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            display: none;
            align-items: center;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="error-message" id="errorMessage">
        <span id="errorText"></span>
    </div>
    
    <div class="container">
        <a href="recompense.php" class="btn-back">⬅ Récompenses</a>
        
        <h1>🎡 Tirage au sort</h1>
        
        <?php if (!empty($enfants)): ?>
            <p style="color: rgba(255,255,255,0.85); font-size:15px;">Choisissez un enfant :</p>
            <div class="children-list">
                <?php foreach ($enfants as $enfant): ?>
                    <div class="child-item" data-child-id="<?= $enfant['id'] ?>" onclick="selectChild(this)">
                        <span class="child-icon">👧</span>
                        <?= htmlspecialchars($enfant['nom_enfant']) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>Aucun enfant enregistré. Veuillez d'abord ajouter des enfants.</p>
        <?php endif; ?>

        <div class="wheel-container">
            <div class="pointer"></div>
            <div class="wheel" id="wheel"></div>
        </div>
        <button class="btn-spin" onclick="spinWheel()">Tourner la roue 🚀</button>
        <div class="result" id="result"></div>
    </div>

    <script>
        const wheel = document.getElementById('wheel');
        const resultDiv = document.getElementById('result');
        const errorMessage = document.getElementById('errorMessage');
        const errorText = document.getElementById('errorText');
        let selectedChildId = null;

        const rewards = [
            '🎮 1h jeux vidéo',
            '🎁 Petit cadeau',
            '🎥 Cinéma',
            '🍽️ Choisir le dîner',
            '💰 10 dinars',
            '💰 20 dinars',
            '💰 50 dinars',
            '✨ Activité spéciale'
        ];

        const anglePerSegment = 360 / rewards.length;
        rewards.forEach((reward, index) => {
            const segment = document.createElement('div');
            segment.style.transform = `rotate(${anglePerSegment * index}deg) skewY(-60deg)`;
            segment.style.backgroundColor = index % 2 === 0 ? 'rgba(255,255,255,0.15)' : 'rgba(255,255,255,0.28)';
            segment.innerText = reward;
            wheel.appendChild(segment);
        });

        let isSpinning = false;

        function showError(message) {
            errorText.textContent = message;
            errorMessage.style.display = 'flex';
            setTimeout(() => {
                errorMessage.style.display = 'none';
            }, 3000);
        }

        function selectChild(element) {
            document.querySelectorAll('.child-item').forEach(item => {
                item.classList.remove('active');
            });
            element.classList.add('active');
            selectedChildId = element.getAttribute('data-child-id');
            errorMessage.style.display = 'none';
        }

        function spinWheel() {
            if (isSpinning) return;
            
            if (!selectedChildId) {
                showError('⚠️ Veuillez sélectionner un enfant avant de tourner la roue');
                return;
            }

            isSpinning = true;
            resultDiv.innerText = '';

            const spinDeg = 360 * 5 + Math.floor(Math.random() * 360);
            const finalDeg = spinDeg % 360;
            wheel.style.transition = 'transform 4s cubic-bezier(0.33, 1, 0.68, 1)';
            wheel.style.transform = `rotate(${spinDeg}deg)`;

            setTimeout(() => {
                const selectedIndex = Math.floor((360 - finalDeg) / anglePerSegment) % rewards.length;
                const reward = rewards[selectedIndex];
                resultDiv.innerHTML = `🎉 <strong>${document.querySelector('.child-item.active').textContent.trim()}</strong> a gagné : <span style="color:#ffcc00;">${reward}</span> !`;
                
                saveReward(selectedChildId, reward);
                isSpinning = false;
            }, 4200);
        }

        function saveReward(childId, reward) {
            fetch('save_reward.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `enfant_id=${childId}&recompense=${encodeURIComponent(reward)}`
            })
            .then(response => response.text())
            .then(data => {
                console.log('Récompense enregistrée:', data);
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
        }
    </script>
</body>
</html>