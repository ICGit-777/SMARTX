<?php
session_start();

// CORRIGÉ : Protection de la page admin — seuls les administrateurs peuvent y accéder
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
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

// Récupération des utilisateurs
try {
    $utilisateurs = $pdo->query('SELECT nom, prenom, email, role, actif FROM utilisateurs ORDER BY id DESC LIMIT 50')->fetchAll();
} catch (PDOException $e) {
    $utilisateurs = [];
}

// Récupération des annonces
try {
    $annonces = $pdo->query('SELECT titre, loyer, statut FROM annonces ORDER BY cree_le DESC LIMIT 50')->fetchAll();
} catch (PDOException $e) {
    $annonces = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SmartHome — Administration</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    .admin-header { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:1.5rem; flex-wrap:wrap; }
    .admin-header h1 { margin:0; }
    .admin-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(300px,1fr)); gap:1.5rem; }
    .admin-card { padding:1.5rem; }
    .admin-card h2 { margin-top:0; font-size:1.1rem; color:var(--green-deep); }
    .admin-card p { color:var(--gray-text); line-height:1.7; margin-bottom:1rem; }
    .admin-actions { display:flex; flex-wrap:wrap; gap:0.75rem; }
    .admin-actions a {
      text-decoration:none; color:var(--white);
      background:var(--green-main); padding:0.75rem 1.1rem;
      border-radius:12px; font-weight:600;
      transition:background 0.2s; font-size:0.9rem;
    }
    .admin-actions a:hover { background:var(--green-mid); }
    .admin-actions a.btn-outline {
      background:transparent; color:var(--green-mid);
      border:1px solid var(--green-mid);
    }
    .admin-actions a.btn-outline:hover { background:var(--green-mid); color:var(--white); }
    .admin-table { width:100%; border-collapse:collapse; margin-top:1rem; }
    .admin-table th, .admin-table td { padding:0.75rem 0.85rem; border-bottom:1px solid var(--gray-light); text-align:left; vertical-align:middle; }
    .admin-table th { font-weight:700; color:var(--dark-text); font-size:0.88rem; text-transform:uppercase; letter-spacing:0.03em; }
    .status-badge {
      display:inline-flex; align-items:center; justify-content:center;
      padding:0.3rem 0.8rem; border-radius:999px;
      background:var(--green-pale); color:var(--green-mid);
      font-size:0.78rem; font-weight:700; white-space:nowrap;
    }
    .status-badge.pending { background:#fff8e1; color:#b45309; }
    .status-badge.inactive { background:#feecec; color:#8f1b1b; }
  </style>
</head>
<body class="user-page-body">
  <a class="skip-link" href="#main-content">Aller au contenu</a>

  <nav>
    <!-- CORRIGÉ : tous les liens pointent vers des pages réelles -->
    <a href="index.php" class="nav-logo">🏠 Smart<span>Home</span></a>
    <ul class="nav-links">
      <li><a href="index.php">Accueil</a></li>
      <li><a href="ajouter_annonce.php">Publier une annonce</a></li>
      <li><span class="user-welcome">Admin : <?= htmlspecialchars($_SESSION['user_prenom'] ?? '') ?></span></li>
      <li><a href="logout.php" class="nav-logout">Se déconnecter</a></li>
    </ul>
  </nav>

  <main id="main-content" class="user-page-main" tabindex="-1">

    <div class="admin-header">
      <div>
        <span class="section-tag">Administration</span>
        <h1 class="section-title">Tableau de bord</h1>
        <p>Gérer les annonces, les utilisateurs et les messages.</p>
      </div>
      <div class="admin-actions">
        <a href="ajouter_annonce.php">+ Créer une annonce</a>
        <a href="index.php" class="btn-outline">Voir le site</a>
      </div>
    </div>

    <div class="admin-grid">

      <!-- Tableau utilisateurs (données réelles) -->
      <section class="user-page-card admin-card">
        <h2>Utilisateurs</h2>
        <p>Comptes inscrits sur la plateforme.</p>
        <?php if ($utilisateurs): ?>
        <table class="admin-table">
          <thead>
            <tr><th>Prénom Nom</th><th>Email</th><th>Rôle</th><th>Statut</th></tr>
          </thead>
          <tbody>
            <?php foreach ($utilisateurs as $u): ?>
            <tr>
              <td><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></td>
              <td><?= htmlspecialchars($u['email']) ?></td>
              <td><?= htmlspecialchars($u['role']) ?></td>
              <td>
                <span class="status-badge <?= $u['actif'] ? '' : 'inactive' ?>">
                  <?= $u['actif'] ? 'Actif' : 'Désactivé' ?>
                </span>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
          <p style="color:var(--gray-text);">Aucun utilisateur trouvé.</p>
        <?php endif; ?>
      </section>

      <!-- Tableau annonces (données réelles) -->
      <section class="user-page-card admin-card">
        <h2>Annonces</h2>
        <p>Annonces publiées sur la plateforme.</p>
        <?php if ($annonces): ?>
        <table class="admin-table">
          <thead>
            <tr><th>Titre</th><th>Loyer</th><th>Statut</th></tr>
          </thead>
          <tbody>
            <?php foreach ($annonces as $a): ?>
            <tr>
              <td><?= htmlspecialchars($a['titre']) ?></td>
              <td><?= number_format($a['loyer'],0,',',' ') ?> €</td>
              <td>
                <span class="status-badge <?= $a['statut'] !== 'disponible' ? 'pending' : '' ?>">
                  <?= htmlspecialchars($a['statut']) ?>
                </span>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
          <p style="color:var(--gray-text);">Aucune annonce trouvée.</p>
        <?php endif; ?>
      </section>

      <!-- Messages (statique pour l'instant) -->
      <section class="user-page-card admin-card">
        <h2>Messages signalés</h2>
        <p>Conversations en attente de modération.</p>
        <table class="admin-table">
          <thead>
            <tr><th>Expéditeur</th><th>Sujet</th><th>Reçu</th><th>Statut</th></tr>
          </thead>
          <tbody>
            <tr>
              <td>Emma R.</td><td>Problème de paiement</td><td>2 min</td>
              <td><span class="status-badge">Nouveau</span></td>
            </tr>
            <tr>
              <td>Alex B.</td><td>Signalement annonce</td><td>12 min</td>
              <td><span class="status-badge pending">En cours</span></td>
            </tr>
          </tbody>
        </table>
      </section>

    </div>
  </main>
</body>
</html>
