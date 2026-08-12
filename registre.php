<?php
require_once "connexion.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nom = $_POST["nom"] ?? "";
    $email = $_POST["email"] ?? "";
    $password = $_POST["password"] ?? "";
    $confirm = $_POST["confirmpss"] ?? "";

    if (empty($nom) || empty($email) || empty($password) || empty($confirm)) {
        die("Tous les champs sont obligatoires");
    }

    if ($password !== $confirm) {
        die("Les mots de passe ne correspondent pas");
    }

    // Vérifier email
    $check = $conn->prepare("SELECT id FROM user WHERE email=?");
    $check->bind_param("s", $email);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        die("Email déjà utilisé");
    }
    $check->close();

    // ✅ Hash du mot de passe avant stockage
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare(
        "INSERT INTO user (nom, email, mot_de_passe) VALUES (?, ?, ?)"
    );
    $stmt->bind_param("sss", $nom, $email, $hashed_password);

    if ($stmt->execute()) {
        $_SESSION["user_id"] = $conn->insert_id;
        header("Location: reception.html");
        exit();
    } else {
        die("Erreur inscription");
    }
}
$conn->close();
?>
