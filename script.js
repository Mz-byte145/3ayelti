function alpha(ch) {
    return /^[a-zA-ZàáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūýÿʐżźŚśĆćŹźŻż\s'-]+$/.test(ch);
}

function showError(id, message) {
    var elem = document.getElementById(id);
    if (elem) {
        elem.innerText = message;
    }
}

function clearErrors() {
    showError("nomError", "");
    showError("emailError", "");
    showError("passwordError", "");
    showError("confirmError", "");
}

function valid() {
    clearErrors();

    var nomElem = document.getElementById("nom");
    var emailElem = document.getElementById("email");
    var passwordElem = document.getElementById("password");
    var confirmPasswordElem = document.getElementById("confirmpss");

    if (!nomElem || !emailElem || !passwordElem || !confirmPasswordElem) {
        return true;
    }

    var nom = nomElem.value.trim();
    var email = emailElem.value.trim();
    var password = passwordElem.value;
    var confirmPassword = confirmPasswordElem.value;

    if (!nom || !email || !password || !confirmPassword) {
        showError("nomError", "Tous les champs doivent être remplis.");
        return false;
    }

    if (nom.length < 3) {
        showError("nomError", "Le nom doit contenir au moins 3 caractères.");
        return false;
    }

    if (email.indexOf("@") === -1 || email.indexOf(".") === -1) {
        showError("emailError", "Veuillez entrer une adresse email valide.");
        return false;
    }

    if (password.length < 6) {
        showError("passwordError", "Le mot de passe doit contenir au moins 6 caractères.");
        return false;
    }

    if (password !== confirmPassword) {
        showError("confirmError", "Les mots de passe ne correspondent pas.");
        return false;
    }

    return true;
}
