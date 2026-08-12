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
    <style>
        body {
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #3b4371, #f3904f);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Poppins', sans-serif;
            color: white;
            text-align: center;
        }
        .error-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
            max-width: 400px;
            width: 90%;
        }
        h2 { color: #ffcc00; margin-bottom: 15px; }
        p { font-size: 1.1em; margin-bottom: 25px; }
        .btn-retry {
            display: inline-block;
            padding: 12px 25px;
            background: #ffcc00;
            color: #333;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: background 0.3s;
        }
        .btn-retry:hover { background: #e6b800; }
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

