<?php
include 'connexion.php';

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom = trim($_POST['nom'] ?? '');
    $commentaire = trim($_POST['commentaire'] ?? '');

    if (!empty($nom) && !empty($commentaire)) {
        $stmt = mysqli_prepare($conn, "INSERT INTO commentaires (nom, commentaire) VALUES (?, ?)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $nom, $commentaire);
            if (mysqli_stmt_execute($stmt)) {
                $message = "Votre commentaire a été ajouté avec succès ! 🙏";
            } else {
                $message = "Erreur lors de l'ajout du commentaire. Veuillez réessayer.";
            }
            mysqli_stmt_close($stmt);
        } else {
            $message = "Erreur lors de la préparation de la requête.";
        }
    } else {
        $message = "Veuillez remplir tous les champs.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Aide & Commentaires - 3ayelti</title>
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
      font-family: 'Poppins', sans-serif;
      color: white;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 40px 20px;
    }

    .container {
      max-width: 650px;
      width: 100%;
      text-align: center;
    }

    .top-nav {
      display: flex;
      justify-content: flex-start;
      margin-bottom: 25px;
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
    }

    .retour:hover {
      background: rgba(255, 255, 255, 0.3);
      transform: translateY(-2px);
    }

    h1 {
      font-family: 'Bangers', cursive;
      font-size: 44px;
      color: var(--gold);
      letter-spacing: 2px;
      margin-bottom: 25px;
    }

    .card {
      background: var(--glass-bg);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid var(--glass-border);
      border-radius: 20px;
      padding: 35px 25px;
      box-shadow: var(--glass-shadow);
      margin-bottom: 25px;
    }

    h2 {
      font-family: 'Bangers', cursive;
      font-size: 28px;
      color: #a8e6cf;
      margin-bottom: 15px;
    }

    p {
      font-size: 16px;
      line-height: 1.6;
      color: rgba(255, 255, 255, 0.9);
    }

    .message-success {
      background: rgba(76, 175, 80, 0.25);
      border: 1px solid #4CAF50;
      color: #c8e6c9;
      padding: 15px;
      margin: 20px 0;
      font-weight: 600;
      border-radius: 12px;
    }

    .btn-home {
      display: inline-block;
      margin-top: 15px;
      padding: 12px 28px;
      background: linear-gradient(135deg, #ffcc00, #ff9900);
      color: #2c3e50;
      text-decoration: none;
      border-radius: 10px;
      font-weight: 700;
      transition: all 0.3s ease;
    }

    .btn-home:hover {
      background: linear-gradient(135deg, #ffe066, #ffaa00);
      transform: translateY(-2px);
    }

    footer {
      text-align: center;
      margin-top: 20px;
      font-size: 14px;
      color: rgba(255, 255, 255, 0.7);
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="top-nav">
      <a href="acceuil.html" class="retour">⬅ Accueil</a>
    </div>

    <h1>🛠 3ayelti Support</h1>

    <div class="card">
      <h2>Merci pour votre message !</h2>
      <p>Nous vous remercions d'avoir pris le temps de nous écrire.<br>Notre équipe vous répondra dans les plus brefs délais.</p>

      <?php if (isset($message)): ?>
        <div class="message-success">
          <?= htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

      <a href="acceuil.html" class="btn-home">Retourner à l'Accueil 🏠</a>
    </div>

    <footer>
      &copy; 3ayelti plateforme — Tous droits réservés
    </footer>
  </div>
</body>
</html>
