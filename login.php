<?php
require_once "connexion.php";

// Si l'utilisateur est déjà connecté → redirection directe
if (isset($_SESSION['user_id'])) {
    header("Location: tache.php");
    exit();
}

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {
        $message = "❌ Veuillez remplir tous les champs.";
        $message_type = "error";
    } else {

        $stmt = $conn->prepare(
            "SELECT id, email, mot_de_passe FROM user WHERE email = ?"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            // ✅ vérification du mot de passe
            if (password_verify($password, $user["mot_de_passe"])) {

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["email"] = $user["email"];

                header("Location: tache.php");
                exit();

            } else {
                $message = "❌ Mot de passe incorrect.";
                $message_type = "error";
            }

        } else {
            $message = "❌ Aucun compte trouvé avec cet email.";
            $message_type = "error";
        }

        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Connexion - 3ayelti</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
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

.container {
    background: var(--glass-bg);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid var(--glass-border);
    padding: 40px 30px;
    border-radius: 20px;
    box-shadow: var(--glass-shadow);
    width: 100%;
    max-width: 380px;
    position: relative;
    animation: fadeInUp 0.7s ease-out;
}

h2 {
    font-family: 'Bangers', cursive;
    font-size: 38px;
    margin-bottom: 20px;
    color: var(--gold);
    letter-spacing: 2px;
    text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.4);
}

.form-group {
    margin-bottom: 18px;
    text-align: left;
}

label {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 6px;
    display: block;
    color: rgba(255, 255, 255, 0.9);
}

input {
    padding: 12px 16px;
    width: 100%;
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.15);
    color: white;
    font-family: 'Poppins', sans-serif;
    font-size: 15px;
    outline: none;
    transition: all 0.3s ease;
}

input::placeholder {
    color: rgba(255, 255, 255, 0.6);
}

input:focus {
    background: rgba(255, 255, 255, 0.25);
    border-color: var(--gold);
    box-shadow: 0 0 12px rgba(255, 204, 0, 0.4);
}

button {
    width: 100%;
    padding: 14px;
    margin-top: 10px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(135deg, #ffcc00, #ff9900);
    color: #2c3e50;
    font-family: 'Poppins', sans-serif;
    font-size: 17px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 6px 16px rgba(255, 204, 0, 0.3);
}

button:hover {
    background: linear-gradient(135deg, #ffe066, #ffaa00);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(255, 204, 0, 0.5);
}

.message {
    margin-bottom: 20px;
    padding: 12px 15px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    animation: shake 0.4s ease-in-out;
}

.error {
    background: rgba(255, 82, 82, 0.25);
    border: 1px solid #ff5252;
    color: #ffcdd2;
}

.success {
    background: rgba(76, 175, 80, 0.25);
    border: 1px solid #4CAF50;
    color: #c8e6c9;
}

.links {
    margin-top: 20px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

a {
    color: var(--gold);
    text-decoration: none;
    font-size: 14px;
    transition: color 0.3s;
}

a:hover {
    color: var(--gold-hover);
    text-decoration: underline;
}

.retour {
    position: absolute;
    top: 20px;
    left: 20px;
    font-size: 18px;
    color: white;
    text-decoration: none;
    background: rgba(255, 255, 255, 0.2);
    padding: 6px 12px;
    border-radius: 8px;
    transition: background 0.3s ease;
}

.retour:hover {
    background: rgba(255, 255, 255, 0.4);
    text-decoration: none;
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}
</style>
</head>

<body>
<a href="acceuil.html" class="retour">⬅ Accueil</a>
<div class="container">
    <h2>Connexion</h2>

    <?php if (!empty($message)): ?>
        <div class="message <?= $message_type ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label for="email">Adresse Email</label>
            <input type="email" id="email" name="email" placeholder="exemple@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="password">Mot de Passe</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>

        <button type="submit">Se connecter 🚀</button>
    </form>

    <div class="links">
        <a href="inscription.html">Pas encore de compte ? Créer un compte 3ayelti</a>
    </div>
</div>
</body>
</html>
