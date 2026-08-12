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
    <title>Tirage au Sort - 3ayelti</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #3b4371, #f3904f);
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: rgba(255, 255, 255, 0.1);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            text-align: center;
            width: 90%;
            max-width: 800px;
            position: relative;
        }

        h1 {
            color: #ffcc00;
            margin-bottom: 20px;
        }

        .wheel-container {
            position: relative;
            width: 300px;
            height: 300px;
            margin: 30px auto;
        }

        .wheel {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 10px solid #fff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(255,255,255,0.5);
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
            text-shadow: 1px 1px #000;
            display: flex;
            justify-content: flex-start;
            align-items: center;
            padding-left: 20px;
            font-size: 14px;
        }

        .pointer {
            width: 0;
            height: 0;
            border-left: 20px solid transparent;
            border-right: 20px solid transparent;
            border-bottom: 30px solid #ffcc00;
            position: absolute;
            top: -35px;
            left: calc(50% - 20px);
            z-index: 10;
        }

        .btn-spin {
            background-color: #ffcc00;
            color: #333;
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
            margin: 10px;
        }

        .btn-spin:hover {
            background-color: #e6b800;
        }

        .result {
            font-size: 20px;
            margin-top: 25px;
            color: #fff;
        }

        /* Styles pour la liste des enfants */
        .children-list {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
            margin: 20px 0;
        }

        .child-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 10px 20px;
            border-radius: 50px;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .child-item:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .child-item.active {
            background: #ffcc00;
            color: #333;
            font-weight: bold;
        }

        .child-icon {
            margin-right: 8px;
            font-size: 18px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            background-color: #2f3640;
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
            background-color: #1e272e;
        }

        .btn i {
            margin-right: 8px;
        }

        .btn-back {
            position: absolute;
            top: 20px;
            right: 20px;
            background-color: #2f3640;
            z-index: 10;
        }

        .btn-back:hover {
            background-color: #1e272e;
        }

        /* Style pour le message d'erreur */
        .error-message {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #ff4757;
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            animation: fadeIn 0.3s ease-out;
            display: none;
            align-items: center;
        }

        .error-message i {
            margin-right: 10px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateX(-50%) translateY(-20px); }
            to { opacity: 1; transform: translateX(-50%) translateY(0); }
        }
    </style>
</head>
<body>
    <div class="error-message" id="errorMessage">
        <i class="fas fa-exclamation-circle"></i>
        <span id="errorText"></span>
    </div>
    
    <div class="container">
        <a href="recompense.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Retour aux récompenses</a>
        
        <h1>🎡 Tirage au sort</h1>
        
        <?php if (!empty($enfants)): ?>
            <h3>Choisissez un enfant :</h3>
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
            <div class="wheel" id="wheel">
                <!-- Sections ajoutées dynamiquement via JS -->
            </div>
        </div>
        <button class="btn-spin" onclick="spinWheel()"><i class="fas fa-sync-alt"></i> Tourner la roue</button>
        <div class="result" id="result"></div>
    </div>

    <script>
        const wheel = document.getElementById('wheel');
        const resultDiv = document.getElementById('result');
        const errorMessage = document.getElementById('errorMessage');
        const errorText = document.getElementById('errorText');
        let selectedChildId = null;

        const rewards = [
            '🎮 1h de jeux vidéo',
            '🎁 Petit cadeau',
            '🎥 Cinéma',
            '🍽️ Choisir le dîner',
            '💰 10 dinars',
            '💰 20 dinars',
            '💰 50 dinars',
            '✨ Activité spéciale'
        ];

        // Create wheel segments
        const anglePerSegment = 360 / rewards.length;
        rewards.forEach((reward, index) => {
            const segment = document.createElement('div');
            segment.style.transform = `rotate(${anglePerSegment * index}deg) skewY(-60deg)`;
            segment.style.backgroundColor = index % 2 === 0 ? 'rgba(255,255,255,0.15)' : 'rgba(255,255,255,0.25)';
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
                showError('Veuillez sélectionner un enfant avant de tourner la roue');
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
                resultDiv.innerHTML = `🎉 <strong>${document.querySelector('.child-item.active').textContent.trim()}</strong> a gagné : <strong>${reward}</strong>`;
                
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