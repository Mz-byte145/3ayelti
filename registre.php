<?php
require_once "connexion.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nom = trim($_POST["nom"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["confirmpss"] ?? "";

    if (empty($nom) || empty($email) || empty($password) || empty($confirm)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif ($password !== $confirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        // Vérifier email
        $check = $conn->prepare("SELECT id FROM user WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            $error = "Cet email est déjà utilisé. Veuillez en choisir un autre.";
        }
        $check->close();

        if (empty($error)) {
            // ✅ Hash du mot de passe avant stockage
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare(
                "INSERT INTO user (nom, email, mot_de_passe) VALUES (?, ?, ?)"
            );
            $stmt->bind_param("sss", $nom, $email, $hashed_password);

            if ($stmt->execute()) {
                $_SESSION["user_id"] = $conn->insert_id;
                $_SESSION["email"] = $email;
                header("Location: reception.html");
                exit();
            } else {
                $error = "Erreur lors de l'inscription. Veuillez réessayer.";
            }
            $stmt->close();
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur Inscription - 3ayelti</title>
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
            margin: 0;
            padding: 20px;
            background: var(--gradient-bg);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Poppins', sans-serif;
            color: white;
            text-align: center;
        }

        .error-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            padding: 40px 30px;
            border-radius: 20px;
            box-shadow: var(--glass-shadow);
            max-width: 420px;
            width: 100%;
            animation: fadeInUp 0.7s ease-out;
        }

        h2 {
            font-family: 'Bangers', cursive;
            font-size: 38px;
            color: var(--gold);
            margin-bottom: 15px;
            letter-spacing: 2px;
        }

        p {
            font-size: 15px;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .btn-retry {
            display: inline-block;
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #ffcc00, #ff9900);
            color: #2c3e50;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 6px 16px rgba(255, 204, 0, 0.3);
        }

        .btn-retry:hover {
            background: linear-gradient(135deg, #ffe066, #ffaa00);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 204, 0, 0.5);
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="error-card">
        <h2>❌ Inscription Échouée</h2>
        <p><?= htmlspecialchars($error ?: "Une erreur est survenue.") ?></p>
        <a href="inscription.html" class="btn-retry">⬅ Réessayer</a>
    </div>
</body>
</html>
