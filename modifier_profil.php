<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

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

$prenom = htmlspecialchars($_SESSION['user_prenom'] ?? 'Utilisateur');
$nom    = htmlspecialchars($_SESSION['user_nom']    ?? '');
$email  = htmlspecialchars($_SESSION['user_email']  ?? '');

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_prenom = trim($_POST['prenom'] ?? '');
    $new_nom    = trim($_POST['nom']    ?? '');
    $new_email  = trim($_POST['email']  ?? '');

    if (!$new_prenom) {
        $error = 'Le prénom est obligatoire.';
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Adresse email invalide.';
    } else {
        try {
            // Vérifier si l'email est déjà utilisé par un autre utilisateur
            $check = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = ? AND id != ?');
            $check->execute([$new_email, $_SESSION['user_id']]);
            if ($check->fetch()) {
                $error = 'Cet email est déjà utilisé par un autre compte.';
            } else {
                $stmt = $pdo->prepare('UPDATE utilisateurs SET prenom = ?, nom = ?, email = ? WHERE id = ?');
                $stmt->execute([$new_prenom, $new_nom, $new_email, $_SESSION['user_id']]);

                $_SESSION['user_prenom'] = $new_prenom;
                $_SESSION['user_nom']    = $new_nom;
                $_SESSION['user_email']  = $new_email;

                $success = 'Profil mis à jour avec succès !';
                $prenom = htmlspecialchars($new_prenom);
                $nom    = htmlspecialchars($new_nom);
                $email  = htmlspecialchars($new_email);
            }
        } catch (PDOException $e) {
            $error = 'Erreur lors de la mise à jour. Veuillez réessayer.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Modifier mon profil — SmartHome</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="style.css">
  <style>
    .page-container {
      max-width: 600px;
      margin: calc(var(--nav-h) + 2rem) auto 4rem;
      padding: 0 20px;
    }
    .card-edit {
      background: var(--white);
      border-radius: var(--radius-lg);
      border: 1px solid var(--gray-mid);
      box-shadow: 0 2px 16px rgba(13,31,28,0.05);
      padding: 2rem;
    }
    .card-edit h1 {
      font-family: var(--font-display);
      font-size: 2rem;
      font-weight: 800;
      margin-bottom: 0.5rem;
      color: var(--dark-text);
    }
    .card-edit p {
      color: var(--gray-text);
      margin-bottom: 1.75rem;
    }
    .form-group {
      margin-bottom: 1.5rem;
    }
    .form-group label {
      display: block;
      font-weight: 600;
      color: var(--dark-text);
      margin-bottom: 0.5rem;
      font-size: 0.9rem;
    }
    .form-group input {
      width: 100%;
      padding: 0.85rem 1rem;
      border: 1px solid var(--gray-mid);
      border-radius: var(--radius);
      font-size: 1rem;
      font-family: var(--font-body);
      color: var(--dark-text);
      transition: border-color 0.2s;
      box-sizing: border-box;
    }
    .form-group input:focus {
      outline: none;
      border-color: var(--green-main);
      box-shadow: 0 0 0 3px rgba(0, 184, 148, 0.1);
    }
    .form-group input:disabled {
      background: var(--gray-light);
      color: var(--gray-text);
      cursor: not-allowed;
    }
    .form-actions {
      display: flex;
      gap: 0.75rem;
      margin-top: 2rem;
      justify-content: flex-end;
    }
    .btn {
      padding: 0.75rem 1.5rem;
      border-radius: 50px;
      border: none;
      cursor: pointer;
      font-weight: 600;
      font-size: 0.9rem;
      transition: all 0.2s;
      font-family: var(--font-body);
    }
    .btn-primary {
      background: var(--green-main);
      color: var(--white);
    }
    .btn-primary:hover {
      background: var(--green-mid);
      transform: translateY(-1px);
    }
    .btn-secondary {
      background: var(--gray-light);
      color: var(--dark-text);
      border: 1px solid var(--gray-mid);
    }
    .btn-secondary:hover {
      background: var(--gray-mid);
      color: var(--white);
    }
    .alert {
      padding: 1rem;
      border-radius: var(--radius);
      margin-bottom: 1.5rem;
      font-size: 0.9rem;
    }
    .alert-success {
      background: #d1f5e8;
      color: #0a6e4a;
      border: 1px solid #a0dcc8;
    }
    .alert-error {
      background: #fde3e3;
      color: #8f1b1b;
      border: 1px solid #f5c4c4;
    }
  </style>
</head>
<body class="page-padded">

<nav>
  <a href="index.php" class="nav-logo">🏠 Smart<span>Home</span></a>
  <ul class="nav-links">
    <li><a href="user_page.php">Mon profil</a></li>
    <li><a href="index.php">Accueil</a></li>
    <li><a href="logout.php" class="nav-logout">Se déconnecter</a></li>
  </ul>
</nav>

<div class="page-container">
  <div class="card-edit">
    <h1>Modifier mon profil</h1>
    <p>Mettez à jour vos informations personnelles</p>

    <?php if ($success): ?>
      <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label for="prenom">Prénom *</label>
        <input type="text" id="prenom" name="prenom" value="<?= $prenom ?>" required>
      </div>

      <div class="form-group">
        <label for="nom">Nom</label>
        <input type="text" id="nom" name="nom" value="<?= $nom ?>">
      </div>

      <div class="form-group">
        <label for="email">Email *</label>
        <input type="email" id="email" name="email" value="<?= $email ?>" required>
      </div>

      <div class="form-actions">
        <a href="user_page.php" class="btn btn-secondary">Annuler</a>
        <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
      </div>
    </form>
  </div>
</div>

<footer id="footer-contact" style="margin-top: 4rem;">
  <div class="footer-brand">
    <a href="index.php" class="nav-logo">🏠 SmartHome<span> </span></a>
  </div>
  <div class="footer-col">
    <a href="index.php">Accueil</a>
    <a href="user_page.php">Mon Espace</a>
  </div>
</footer>

</body>
</html>
