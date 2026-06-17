<?php
session_start();

$host = 'localhost';
$db   = 'smarthome';
$user = 'root';
$pass = 'root';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Erreur de base de données : ' . htmlspecialchars($e->getMessage());
    exit;
}

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']   ?? '');
    $mdp   = $_POST['password']     ?? '';

    if (!$email || !$mdp) {
        $error = 'Veuillez remplir tous les champs.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide.';
    } else {
        $stmt = $pdo->prepare('SELECT id, prenom, nom, mot_de_passe, role, actif FROM utilisateurs WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($mdp, $user['mot_de_passe'])) {
            $error = 'Email ou mot de passe incorrect.';
        } elseif (!$user['actif']) {
            $error = 'Ce compte est désactivé.';
        } else {
            // CORRIGÉ : clé 'user_prenom' unifiée (même clé que dans inscription.php et les vues)
            $_SESSION['user_id']     = $user['id'];
            $_SESSION['user_role']   = $user['role'];
            $_SESSION['user_prenom'] = $user['prenom'];
            $_SESSION['user_nom']    = $user['nom'];
            $_SESSION['user_email']  = $email;

            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion — SmartHome</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      background-color: #f4f7f6; display: flex;
      justify-content: center; align-items: center;
      min-height: 100vh; padding: 20px;
      /* CORRIGÉ : pas de padding-top superflu — body de style.css en a déjà un,
         mais sur les pages auth le nav n'est pas présent donc on le neutralise */
      padding-top: 20px !important;
    }
    .auth-container {
      background: #fff; border: 1px solid #e2e8f0; border-radius: 24px;
      padding: 2.5rem; max-width: 450px; width: 100%;
      box-shadow: 0 10px 30px rgba(13,31,28,0.04);
    }
    .auth-logo {
      font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 700;
      color: #0d1f1c; text-decoration: none; display: block;
      text-align: center; margin-bottom: 2rem;
    }
    .auth-logo span { color: #00b894; }
    h1 { font-family: 'Syne', sans-serif; font-size: 1.8rem; font-weight: 700; margin-bottom: 0.5rem; }
    .subtitle { color: #637470; font-size: 0.95rem; margin-bottom: 2rem; }
    .form-group { margin-bottom: 1.25rem; }
    label { display: block; font-weight: 600; margin-bottom: 0.4rem; color: #42504d; font-size: 0.9rem; }
    input[type="email"], input[type="password"] {
      width: 100%; padding: 0.85rem 1rem; border: 1px solid #d1dbd9;
      border-radius: 12px; font-size: 1rem; font-family: inherit;
      color: #0d1f1c; box-sizing: border-box; transition: border-color 0.3s;
    }
    input:focus { outline: none; border-color: #00b894; }
    .alert { padding: 0.95rem 1rem; border-radius: 14px; margin-bottom: 1.5rem; font-size: 0.95rem; }
    .alert-error { background: #feecec; color: #8f1b1b; border: 1px solid #f1c1c1; }
    .btn-submit {
      width: 100%; background: #00b894; color: #fff; border: none;
      padding: 1rem; border-radius: 12px; font-size: 1rem; font-weight: 600;
      cursor: pointer; transition: background 0.3s; margin-top: 1rem;
    }
    .btn-submit:hover { background: #00a383; }
    .auth-footer { text-align: center; margin-top: 2rem; font-size: 0.95rem; color: #637470; }
    .auth-footer a { color: #00b894; text-decoration: none; font-weight: 600; }
    .auth-footer a:hover { text-decoration: underline; }
  </style>
</head>
<body>
  <div class="auth-container">
    <a href="index.php" class="auth-logo">🏠 Smart<span>Home</span></a>

    <h1>Connexion</h1>
    <p class="subtitle">Accédez à votre espace pour gérer vos recherches ou vos biens.</p>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form action="connexion.php" method="POST">
      <div class="form-group">
        <label for="email">Adresse Email</label>
        <input type="email" id="email" name="email" required
               placeholder="exemple@smarthome.fr"
               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
      </div>
      <div class="form-group">
        <label for="password">Mot de passe</label>
        <input type="password" id="password" name="password" required placeholder="••••••••">
      </div>
      <button type="submit" class="btn-submit">Se connecter</button>
    </form>

    <div class="auth-footer">
      Nouveau sur SmartHome ? <a href="inscription.php">Créer un compte</a>
    </div>
  </div>
</body>
</html>
