<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

$prenom = htmlspecialchars($_SESSION['user_prenom'] ?? 'Utilisateur');
$nom    = htmlspecialchars($_SESSION['user_nom']    ?? '');
$email  = htmlspecialchars($_SESSION['user_email']  ?? '');
$role   = htmlspecialchars($_SESSION['user_role']   ?? 'locataire');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mon Espace — SmartHome</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="style.css">
  <style>
    /* ── USER PAGE OVERRIDES ── */
    .user-page-body { background: var(--gray-light); }

    .user-page-main {
      max-width: 1100px;
      margin: 0 auto;
      padding: 2.5rem 5% 4rem;
    }

    /* Hero banner du profil */
    .up-hero {
      background: linear-gradient(135deg, var(--green-deep) 0%, var(--green-mid) 100%);
      border-radius: var(--radius-lg);
      padding: 2.5rem 2rem 2rem;
      display: flex;
      align-items: center;
      gap: 2rem;
      margin-bottom: 2rem;
      position: relative;
      overflow: hidden;
    }
    .up-hero::before {
      content: '';
      position: absolute; top: -80px; right: -80px;
      width: 300px; height: 300px; border-radius: 50%;
      background: radial-gradient(circle, rgba(116,215,196,0.18) 0%, transparent 70%);
      pointer-events: none;
    }
    .up-avatar {
      width: 88px; height: 88px;
      border-radius: 50%;
      border: 3px solid rgba(255,255,255,0.25);
      background: rgba(255,255,255,0.12);
      display: flex; align-items: center; justify-content: center;
      overflow: hidden;
      flex-shrink: 0;
    }
    .up-avatar img {
      width: 56px; height: 56px;
      object-fit: contain;
      opacity: 0.9;
      filter: brightness(0) invert(1);
    }
    .up-hero-info { flex: 1; }
    .up-hero-info .section-tag {
      background: rgba(116,215,196,0.18);
      color: var(--green-light);
      margin-bottom: 0.5rem;
    }
    .up-hero-info h1 {
      font-family: var(--font-display);
      font-size: clamp(1.6rem, 3vw, 2.2rem);
      font-weight: 800; color: var(--white);
      margin: 0 0 0.3rem;
    }
    .up-hero-info .up-role {
      font-size: 0.88rem;
      color: var(--green-light);
      font-weight: 500;
      text-transform: capitalize;
    }
    .up-hero-actions {
      display: flex; gap: 0.75rem; margin-top: 1.2rem; flex-wrap: wrap;
    }
    .up-btn {
      display: inline-flex; align-items: center; gap: 0.4rem;
      padding: 0.6rem 1.2rem;
      border-radius: 50px;
      font-family: var(--font-body);
      font-size: 0.88rem; font-weight: 500;
      text-decoration: none; cursor: pointer; border: none;
      transition: all 0.2s;
    }
    .up-btn-primary {
      background: var(--green-main); color: var(--white);
    }
    .up-btn-primary:hover { background: var(--green-light); color: var(--green-deep); }
    .up-btn-ghost {
      background: rgba(255,255,255,0.12);
      color: var(--white);
      border: 1px solid rgba(255,255,255,0.22);
    }
    .up-btn-ghost:hover { background: rgba(255,255,255,0.22); }

    /* Grille de cartes */
    .up-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.25rem;
    }
    @media (max-width: 700px) {
      .up-grid { grid-template-columns: 1fr; }
      .up-hero { flex-direction: column; text-align: center; }
      .up-hero-actions { justify-content: center; }
    }
    .up-card {
      background: var(--white);
      border-radius: var(--radius-lg);
      border: 1px solid var(--gray-mid);
      box-shadow: 0 2px 16px rgba(13,31,28,0.05);
      padding: 1.75rem;
    }
    .up-card-head {
      display: flex; align-items: center; gap: 0.75rem;
      margin-bottom: 1.25rem;
    }
    .up-card-icon {
      width: 42px; height: 42px;
      border-radius: var(--radius);
      background: var(--green-pale);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.2rem; flex-shrink: 0;
    }
    .up-card-head h2 {
      font-family: var(--font-display);
      font-size: 1rem; font-weight: 700;
      color: var(--dark-text); margin: 0;
    }
    .up-card-head span {
      font-size: 0.8rem; color: var(--gray-text);
    }

    /* Lignes profil */
    .up-profile-row {
      display: flex; align-items: center; justify-content: space-between;
      padding: 0.7rem 0;
      border-bottom: 1px solid var(--gray-light);
      font-size: 0.9rem;
    }
    .up-profile-row:last-child { border-bottom: none; }
    .up-profile-row .lbl { color: var(--gray-text); }
    .up-profile-row .val { font-weight: 500; color: var(--dark-text); }

    /* Empty state */
    .up-empty {
      text-align: center; padding: 2rem 1rem;
      color: var(--gray-text); font-size: 0.9rem; line-height: 1.7;
    }
    .up-empty .up-empty-icon { font-size: 2.2rem; display: block; margin-bottom: 0.6rem; }
    .up-empty a {
      display: inline-block; margin-top: 0.75rem;
      color: var(--green-mid); font-weight: 600; text-decoration: none;
      font-size: 0.88rem;
    }
    .up-empty a:hover { color: var(--green-deep); }

    /* Stats rapides */
    .up-stats {
      display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem;
    }
    .up-stat {
      background: var(--green-pale);
      border-radius: var(--radius);
      padding: 1rem 0.75rem; text-align: center;
    }
    .up-stat strong {
      display: block;
      font-family: var(--font-display);
      font-size: 1.6rem; font-weight: 800;
      color: var(--green-mid);
    }
    .up-stat span { font-size: 0.78rem; color: var(--gray-text); }

    /* Badge role */
    .role-badge {
      display: inline-flex; align-items: center;
      padding: 0.25rem 0.75rem; border-radius: 50px;
      font-size: 0.78rem; font-weight: 700;
      background: var(--green-pale); color: var(--green-mid);
    }
    .role-badge.admin { background: #fff8e1; color: #b45309; }
  </style>
</head>
<body class="user-page-body">

<a class="skip-link" href="#main-content">Aller au contenu</a>

<nav>
  <a href="index.php" class="nav-logo">🏠 Smart<span>Home</span></a>
  <ul class="nav-links">
    <li><a href="index.php">Accueil</a></li>
    <li><a href="ajouter_annonce.php">Publier une annonce</a></li>
    <?php if ($role === 'admin'): ?>
      <li><a href="administrator_page.php">Administration</a></li>
    <?php endif; ?>
    <li><span class="user-welcome"><?= $prenom ?></span></li>
    <li><a href="logout.php" class="nav-logout">Se déconnecter</a></li>
  </ul>
</nav>

<main id="main-content" class="user-page-main" tabindex="-1">

  <!-- Hero profil -->
  <div class="up-hero">
    <div class="up-avatar">
      <img src="data:image/jpeg;base64,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" alt="Avatar utilisateur">
    </div>
    <div class="up-hero-info">
      <span class="section-tag">Espace utilisateur</span>
      <h1>Bienvenue, <?= $prenom ?> <?= $nom ?> 👋</h1>
      <p class="up-role">
        <span class="role-badge <?= $role === 'admin' ? 'admin' : '' ?>">
          <?= $role === 'admin' ? '⭐ Administrateur' : '🏠 ' . ucfirst($role) ?>
        </span>
      </p>
      <div class="up-hero-actions">
        <a href="ajouter_annonce.php" class="up-btn up-btn-primary">+ Publier une annonce</a>
        <a href="index.php" class="up-btn up-btn-ghost">Voir les annonces</a>
        <?php if ($role === 'admin'): ?>
          <a href="administrator_page.php" class="up-btn up-btn-ghost">⚙️ Administration</a>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="up-grid">

    <!-- Carte Profil -->
    <div class="up-card">
      <div class="up-card-head">
        <div class="up-card-icon">👤</div>
        <div>
          <h2>Mon profil</h2>
          <span>Informations personnelles</span>
        </div>
      </div>
      <div class="up-profile-row">
        <span class="lbl">Prénom</span>
        <span class="val"><?= $prenom ?></span>
      </div>
      <div class="up-profile-row">
        <span class="lbl">Nom</span>
        <span class="val"><?= $nom ?: '—' ?></span>
      </div>
      <div class="up-profile-row">
        <span class="lbl">Email</span>
        <span class="val"><?= $email ?></span>
      </div>
      <div class="up-profile-row">
        <span class="lbl">Rôle</span>
        <span class="val"><?= ucfirst($role) ?></span>
      </div>
      <div style="margin-top:1.25rem;">
        <a href="modifier_profil.php" class="up-btn up-btn-primary" style="font-size:0.88rem;">✏️ Modifier le profil</a>
      </div>
    </div>

    <!-- Carte Stats -->
    <div class="up-card">
      <div class="up-card-head">
        <div class="up-card-icon">📊</div>
        <div>
          <h2>Activité</h2>
          <span>Résumé de votre compte</span>
        </div>
      </div>
      <div class="up-stats">
        <div class="up-stat">
          <strong>0</strong>
          <span>Annonces publiées</span>
        </div>
        <div class="up-stat">
          <strong>0</strong>
          <span>Favoris</span>
        </div>
        <div class="up-stat">
          <strong>0</strong>
          <span>Messages</span>
        </div>
      </div>
    </div>

    <!-- Carte Mes annonces -->
    <div class="up-card">
      <div class="up-card-head">
        <div class="up-card-icon">🏘️</div>
        <div>
          <h2>Mes annonces</h2>
          <span>Biens que vous gérez</span>
        </div>
      </div>
      <div class="up-empty">
        <span class="up-empty-icon">🏡</span>
        Vous n'avez pas encore publié d'annonce.<br>
        <a href="ajouter_annonce.php">Publier ma première annonce →</a>
      </div>
    </div>

    <!-- Carte Favoris -->
    <div class="up-card">
      <div class="up-card-head">
        <div class="up-card-icon">❤️</div>
        <div>
          <h2>Mes favoris</h2>
          <span>Logements sauvegardés</span>
        </div>
      </div>
      <div class="up-empty">
        <span class="up-empty-icon">🔖</span>
        Aucun bien en favoris pour l'instant.<br>
        <a href="index.php">Parcourir les annonces →</a>
      </div>
    </div>

  </div>

</main>

<footer id="footer-contact">
  <div class="footer-brand">
    <a href="index.php" class="nav-logo">🏠 SmartHome<span> </span></a>
    <p>La plateforme intelligente qui simplifie la recherche de logement pour les étudiants, travailleurs et étrangers.</p>
  </div>
  <div class="footer-col">
    <h4>Navigation</h4>
    <a href="index.php">Accueil</a>
    <a href="ajouter_annonce.php">Publier une annonce</a>
    <a href="index.php#cta">Connexion / Inscription</a>
  </div>
  <div class="footer-col">
    <h4>Contact</h4>
    <a href="mailto:contact@smarthome.fr">contact@smarthome.fr</a>
    <a href="#">Support 24h/24 et 7j/7</a>
    <a href="#">FAQ</a>
  </div>
</footer>
<div class="footer-bottom">
  © 2026 SmartHome — Tous droits réservés
</div>

</body>
</html>
