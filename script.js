function alpha(ch) {
    var i = 0;
    var test = true;
    while (i < ch.length && test) {
        var x = ch.charAt(i);
        if ((x >= "A" && x <= "Z") || (x >= "a" && x <= "z")) {
            i++;
        } else {
            test = false;
        }
    }
    return test;
}

function showError(id, message) {
    document.getElementById(id).innerText = message;
}

function clearErrors() {
    showError("nomError", "");
    showError("emailError", "");
    showError("passwordError", "");
    showError("confirmError", "");
}

function valid() {
    clearErrors();

    var nom = document.getElementById("nom").value.trim();
    var email = document.getElementById("email").value.trim();
    var password = document.getElementById("password").value.trim();
    var confirmPassword = document.getElementById("confirmpss").value.trim();

    if (!nom || !email || !password || !confirmPassword) {
        showError("nomError", "Tous les champs doivent être remplis.");
        return false;
    }

    if (!alpha(nom) || nom.length < 3) {
        showError("nomError", "Le nom doit être alphabétique et contenir au moins 3 caractères.");
        return false;
    }

    if (email.indexOf("@") === -1) {
        showError("emailError", "L'email doit contenir un '@'.");
        return false;
    }

    var emailDomain = email.split("@")[1];
    if (emailDomain && (emailDomain.indexOf(".com") === -1 && emailDomain.indexOf(".yahoo") === -1 && emailDomain.indexOf(".org") === -1)) {
        showError("emailError", "L'email doit contenir un domaine valide tel que '@gmail.com', '@yahoo.com', ou '@org'.");
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

    if (password.includes(" ") || confirmPassword.includes(" ")) {
        showError("passwordError", "Le mot de passe ne doit pas contenir d'espaces.");
        return false;
    }

    alert("Inscription réussie !");
    return true;
}
