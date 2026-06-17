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

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    if (!empty($search)) {
        $stmt = $pdo->prepare('SELECT * FROM v_annonces_disponibles WHERE titre LIKE ? OR quartier LIKE ? OR description LIKE ? ORDER BY cree_le DESC');
        $stmt->execute(["%$search%", "%$search%", "%$search%"]);
        $annonces = $stmt->fetchAll();
    } else {
        $annonces = $pdo->query('SELECT * FROM v_annonces_disponibles ORDER BY cree_le DESC')->fetchAll();
    }
} catch (PDOException $e) {
    $annonces = [];
}

$userConnecte = !empty($_SESSION['user_id']);
// CORRIGÉ : clé unifiée 'user_prenom' (connexion.php et inscription.php utilisent tous les deux cette clé)
$prenomAffiche = !empty($_SESSION['user_prenom']) ? htmlspecialchars($_SESSION['user_prenom']) : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>SmartHome — Trouvez votre logement intelligemment</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<nav>
  <a href="index.php" class="nav-logo">🏠 Smart<span>Home</span></a>
  <ul class="nav-links">
    <li><a href="#how">Comment ça marche</a></li>
    <li><a href="#why">Pourquoi nous ?</a></li>
    <li><a href="#footer-contact">Contacts</a></li>
    <li><a href="user_page.php" class="nav-user-photo" aria-label="Accéder à mon espace utilisateur"><img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 40 40'%3E%3Crect width='40' height='40' rx='20' fill='%2300b894'/%3E%3Ccircle cx='20' cy='14' r='8' fill='%23fff'/%3E%3Cpath d='M10 34c0-6 8-8 10-8s10 2 10 8' fill='none' stroke='%23fff' stroke-width='3' stroke-linecap='round'/%3E%3C/svg%3E" alt="Photo profil"></a></li>

    <?php if ($userConnecte): ?>
      <li><span class="user-welcome">Bonjour<?= $prenomAffiche ? ' ' . $prenomAffiche : ' !' ?></span></li>
      <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
        <li><a href="administrator_page.php">Administration</a></li>
      <?php endif; ?>
      <li><a href="ajouter_annonce.php">Publier</a></li>
      <li><a href="logout.php" class="nav-logout">Se déconnecter</a></li>
    <?php else: ?>
      <li><a href="#cta" class="nav-cta">Mon Compte</a></li>
    <?php endif; ?>
  </ul>
</nav>

<!-- Section annonces -->
<section style="max-width:1200px; margin: 2.5rem auto 40px; padding: 0 20px;">

  <div style="text-align:center; margin-bottom:40px;">
    <h2 style="font-family:'Syne',sans-serif; font-size:2.5rem; margin-bottom:10px; color:#0d1f1c;">Annonces disponibles</h2>
    <p style="color:#637470; margin-bottom:25px;">Trouvez le bien idéal parmi nos logements vérifiés</p>

    <div class="search-container">
      <form action="index.php" method="GET" class="search-form">
        <input type="text" name="search" class="search-input"
               placeholder="Rechercher par ville, quartier, mot-clé..."
               value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn-search">Rechercher</button>
      </form>
      <?php if (!empty($search)): ?>
        <a href="index.php" class="clear-search">❌ Réinitialiser la recherche</a>
      <?php endif; ?>
    </div>
  </div>

  <?php if (empty($annonces)): ?>
    <div style="text-align:center; padding:40px; background:#fff; border-radius:20px; border:1px dashed #e2e8f0;">
      <p style="color:#637470; font-size:1.1rem;">
        <?= !empty($search)
          ? 'Aucune annonce ne correspond à votre recherche "<strong>' . htmlspecialchars($search) . '</strong>".'
          : 'Aucune annonce disponible pour le moment.' ?>
      </p>
    </div>
  <?php else: ?>
    <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:30px;">
      <?php foreach ($annonces as $a): ?>
        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:20px; overflow:hidden; box-shadow:0 4px 15px rgba(0,0,0,0.02);">
          <div style="height:200px; background-size:cover; background-position:center;
               background-image:url('https://images.unsplash.com/photo-<?= !empty($a['image_unsplash']) ? htmlspecialchars($a['image_unsplash']) : '1502672260266-1c1ef2d93688' ?>?w=600&q=80');"></div>
          <div style="padding:20px;">
            <h3 style="font-family:'Syne',sans-serif; font-size:1.2rem; margin-bottom:10px;"><?= htmlspecialchars($a['titre']) ?></h3>
            <p style="color:#637470; margin-bottom:15px; font-size:0.95rem;"><?= number_format($a['loyer'],0,',',' ') ?> €/mois</p>
            <a href="annonce.php?id=<?= (int)$a['id'] ?>" style="color:#00b894; font-weight:600; text-decoration:none;">Voir les détails →</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</section>

<!-- CTA inscription/connexion -->
<section class="cta-section" id="cta">
  <span class="section-tag">Rejoignez-nous</span>

  <?php if ($userConnecte): ?>
    <h2 class="section-title">Heureux de vous revoir<?= $prenomAffiche ? ', ' . $prenomAffiche : '' ?> !</h2>
    <p class="section-sub">Parcourez nos annonces vérifiées et trouvez le logement idéal en quelques clics.</p>
    <div class="cta-buttons" style="margin-top:20px;">
      <a href="ajouter_annonce.php" class="btn-primary">Publier une annonce</a>
      <a href="logout.php" class="btn-secondary">Se déconnecter</a>
    </div>
  <?php else: ?>
    <h2 class="section-title">Prêt à trouver votre logement ?</h2>
    <p class="section-sub">Que vous soyez à la recherche d'un toit ou propriétaire souhaitant louer, SmartHome AI est fait pour vous.</p>
    <div class="cta-buttons">
      <a href="inscription.php" class="btn-primary">Inscription</a>
      <a href="connexion.php" class="btn-secondary">Connexion</a>
    </div>
  <?php endif; ?>
</section>

<footer id="footer-contact">
  <div class="footer-brand">
    <a href="index.php" class="nav-logo">🏠 SmartHome<span> </span></a>
    <p>La plateforme intelligente qui simplifie la recherche de logement pour les étudiants, travailleurs et étrangers.</p>
  </div>
  <div class="footer-col">
    <h4>Navigation</h4>
    <a href="#how">Comment ça marche</a>
    <a href="#why">Pourquoi nous ?</a>
    <a href="#cta">Connexion / Inscription</a>
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
