<?php
session_start();
include 'connexion.php';

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = mysqli_real_escape_string($conn, $_POST['nom']);
    $commentaire = mysqli_real_escape_string($conn, $_POST['commentaire']);

    if (!empty($nom) && !empty($commentaire)) {
        $sql = "INSERT INTO commentaires (nom, commentaire) VALUES ('$nom', '$commentaire')";
        if (mysqli_query($conn, $sql)) {
            // Message de succès
            $message = "Votre commentaire a été ajouté avec succès !";
        } else {
            // Message d'erreur
            $message = "Erreur lors de l'ajout du commentaire. Veuillez réessayer.";
        }
    } else {
        // Message si des champs sont vides
        $message = "Veuillez remplir tous les champs.";
    }
}

// Récupération des commentaires (optionnel)
$sql = "SELECT * FROM commentaires ORDER BY id DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Aide - 3ayelti</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      margin: 0;
      padding: 0;
      background: linear-gradient(135deg, #3b4371, #f3904f);
      font-family: 'Poppins', sans-serif;
      color: #ffffff;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    header {
      background: transparent;
      padding: 20px 40px;
      text-align: center;
      font-size: 2.2em;
      font-weight: 600;
      letter-spacing: 1px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    }

    main {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 40px 20px;
    }

    section {
      background-color: rgba(255, 255, 255, 0.15);
      border-radius: 16px;
      padding: 30px;
      max-width: 700px;
      width: 100%;
      margin-bottom: 30px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
      backdrop-filter: blur(10px);
    }

    h2 {
      font-size: 1.8em;
      margin-bottom: 20px;
      color: #ffcc00;
      border-bottom: 2px solid #ffcc00;
      display: inline-block;
      padding-bottom: 5px;
    }

    p {
      font-size: 1.1em;
      line-height: 1.7;
      color: #fefefe;
    }

    .message-success {
      background-color: rgba(255, 255, 255, 0.25);
      color: #eaffea;
      border-left: 5px solid #4CAF50;
      padding: 15px;
      margin: 20px auto;
      max-width: 600px;
      text-align: center;
      font-weight: 600;
      border-radius: 8px;
      animation: fadeIn 0.8s ease forwards;
    }

    footer {
      text-align: center;
      padding: 20px;
      font-size: 1em;
      background: transparent;
      color: #fff;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    .retour {
            position: absolute;
            top: 15px;
            left: 15px;
            font-size: 1.5em;
            color: white;
            text-decoration: none;
            background-color: rgba(255, 255, 255, 0.2);
            padding: 5px 10px;
            border-radius: 5px;
            transition: background-color 0.3s ease;
}

.retour:hover {
    background-color: rgba(255, 255, 255, 0.4);
}

  </style>
</head>
<body>

  <header>
    Aide 🛠
  </header>
  <a href="acceuil.html" class="retour">⬅ </a>
  <main>
    <section id="introduction">
      <h2>Bienvenue à 3ayelti</h2>
      <p>Merci pour votre commentaire 🙏<br>Nous essaierons de vous répondre le plus rapidement possible.</p>
    </section>

    <?php if (isset($message)): ?>
      <div class="message-success">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>
  </main>

  <footer>
    &copy; 3ayelti plateforme
  </footer>

</body>
</html>


