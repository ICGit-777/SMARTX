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

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prenom = trim($_POST['prenom'] ?? '');
    $nom    = trim($_POST['nom']    ?? '');
    $email  = trim($_POST['email']  ?? '');
    $mdp    = $_POST['password']    ?? '';
    $profil = $_POST['profil']      ?? '';

    if (!$prenom || !$nom || !$email || !$mdp || !$profil) {
        $error = 'Tous les champs sont obligatoires.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide.';
    } elseif (strlen($mdp) < 8) {
        $error = 'Le mot de passe doit contenir au moins 8 caractères.';
    } else {
        $situationMap = [
            'Étudiant'    => 'etudiant',
            'Travailleur' => 'travailleur',
            'Étranger'    => 'etranger',
        ];
        $situation = $situationMap[$profil] ?? 'autre';

        $check = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = ? LIMIT 1');
        $check->execute([$email]);

        if ($check->fetch()) {
            $error = 'Cette adresse email est déjà utilisée.';
        } else {
            $hash = password_hash($mdp, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare(
                'INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, situation, actif)
                 VALUES (?, ?, ?, ?, "locataire", ?, 1)'
            );

            if ($stmt->execute([$nom, $prenom, $email, $hash, $situation])) {
                $newId = $pdo->lastInsertId();

                // CORRIGÉ : clé 'user_prenom' unifiée avec connexion.php et les vues
                $_SESSION['user_id']     = $newId;
                $_SESSION['user_role']   = 'locataire';
                $_SESSION['user_prenom'] = $prenom;
                $_SESSION['user_nom']    = $nom;
                $_SESSION['user_email']  = $email;

                header('Location: index.php');
                exit;
            } else {
                $error = 'Une erreur est survenue lors de l\'inscription.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inscription — SmartHome</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      background-color: #f4f7f6; display: flex;
      justify-content: center; align-items: center;
      min-height: 100vh; padding: 40px 20px;
      padding-top: 40px !important; /* pages auth : pas de nav fixe */
    }
    .auth-container {
      background: #fff; border: 1px solid #e2e8f0; border-radius: 24px;
      padding: 2.5rem; max-width: 500px; width: 100%;
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
    input[type="text"], input[type="email"], input[type="password"], select {
      width: 100%; padding: 0.85rem 1rem; border: 1px solid #d1dbd9;
      border-radius: 12px; font-size: 1rem; font-family: inherit;
      color: #0d1f1c; box-sizing: border-box; transition: border-color 0.3s;
      background-color: #fff;
    }
    input:focus, select:focus { outline: none; border-color: #00b894; }
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

    <h1>Créer un compte</h1>
    <p class="subtitle">Rejoignez SmartHome pour trouver votre logement facilement.</p>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form action="inscription.php" method="POST">
      <div class="form-group">
        <label for="prenom">Prénom</label>
        <input type="text" id="prenom" name="prenom" required placeholder="John"
               value="<?= isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : '' ?>">
      </div>
      <div class="form-group">
        <label for="nom">Nom</label>
        <input type="text" id="nom" name="nom" required placeholder="Doe"
               value="<?= isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : '' ?>">
      </div>
      <div class="form-group">
        <label for="email">Adresse Email</label>
        <input type="email" id="email" name="email" required placeholder="exemple@smarthome.fr"
               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
      </div>
      <div class="form-group">
        <label for="password">Mot de passe</label>
        <input type="password" id="password" name="password" required placeholder="8 caractères minimum">
      </div>
      <div class="form-group">
        <label for="profil">Profil</label>
        <select id="profil" name="profil" required>
          <option value="">— Sélectionner —</option>
          <?php foreach (['Étudiant','Travailleur','Étranger'] as $opt): ?>
            <option value="<?= $opt ?>" <?= (($_POST['profil'] ?? '') === $opt) ? 'selected' : '' ?>>
              <?= $opt ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn-submit">S'inscrire</button>
    </form>

    <div class="auth-footer">
      Déjà membre ? <a href="connexion.php">Se connecter</a>
    </div>
  </div>
</body>
</html>
