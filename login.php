<?php
// login.php
session_start();

// Si l’utilisateur est déjà connecté, on le redirige directement vers la bonne page
if (isset($_SESSION['username'], $_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin.php');
    } else {
        header('Location: index.php');
    }
    exit();
}

// Connexion à la base
try {
    $pdo = new PDO("mysql:host=localhost;dbname=nuratecstock", "root", "Nura1939@", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Veuillez renseigner votre identifiant et votre mot de passe.";
    } else {
        // On recherche l'utilisateur en base
        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Authentification réussie : on stocke en session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Redirection selon le rôle
            if ($user['role'] === 'admin') {
                header('Location: admin.php');
                exit();
            } else {
                header('Location: index.php');
                exit();
            }
        } else {
            $error = "Identifiant ou mot de passe incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - Nuratec Stock</title>
    <style>
        body {
            background-color: #f1f2f6;
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 300px;
        }
        .login-container h2 {
            margin-top: 0;
            text-align: center;
            color: #6c5ce7;
        }
        .login-container input[type="text"],
        .login-container input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 8px 0 16px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }
        .login-container input[type="submit"] {
            width: 100%;
            background-color: #6c5ce7;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
        }
        .login-container input[type="submit"]:hover {
            background-color: #00cec9;
        }
        .error {
            color: red;
            text-align: center;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>

<div class="login-container">
    <h2>Connexion</h2>
    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" action="login.php">
        <input type="text" name="username" placeholder="Identifiant" required>
        <input type="password" name="password" placeholder="Mot de passe" required>
        <input type="submit" value="Se connecter">
    </form>
</div>

</body>
</html>
