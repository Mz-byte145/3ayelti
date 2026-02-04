<?php
session_start();
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
<title>Connexion</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body{
    background: linear-gradient(135deg, #3b4371, #f3904f);
    font-family: Poppins, sans-serif;
    color:white;
    height:100vh;
    margin:0;
    display:flex;
    justify-content:center;
    align-items:center;
}
.container{
    background:rgba(255,255,255,0.2);
    padding:30px;
    border-radius:12px;
    width:320px;
    text-align:center;
}
input{
    width:100%;
    padding:10px;
    margin:10px 0;
    border:none;
    border-radius:5px;
}
button{
    width:100%;
    padding:10px;
    background:#ffcc00;
    border:none;
    border-radius:5px;
    font-weight:bold;
    cursor:pointer;
}
.message{
    margin:10px 0;
    font-weight:bold;
}
.error{ color:#ffdddd; }
.success{ color:#caffca; }
</style>
</head>

<body>
<div class="container">
    <h2>Connexion</h2>

    <?php if (!empty($message)): ?>
        <div class="message <?= $message_type ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <input type="email" name="email" placeholder="Email">
        <input type="password" name="password" placeholder="Mot de passe">
        <button type="submit">Se connecter</button>
    </form>
</div>
</body>
</html>
