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

$idAnnonce = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($idAnnonce <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare('
    SELECT a.*, v.nom AS ville_nom, v.code_postal,
           CONCAT(u.prenom, " ", u.nom) AS proprietaire_nom
    FROM annonces a
    LEFT JOIN villes v ON a.ville_id = v.id
    LEFT JOIN utilisateurs u ON a.proprietaire_id = u.id
    WHERE a.id = ? AND a.statut = "disponible"
');
$stmt->execute([$idAnnonce]);
$annonce = $stmt->fetch();

if (!$annonce) {
    die("Annonce introuvable ou indisponible.");
}

$imgPrincipal = $annonce['image_unsplash']
    ? 'https://images.unsplash.com/photo-' . $annonce['image_unsplash'] . '?w=1200&q=80'
    : 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=1200&q=80';

$meuble  = $annonce['meuble'] ? 'Meublé' : 'Non meublé';
$charges = $annonce['charges_incluses'] ? 'Charges comprises' : 'Hors charges';
$userConnecte = !empty($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($annonce['titre']) ?> — SmartHome</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="style.css">
  <style>
    /* CORRIGÉ : conteneur avec padding-top naturel via body, pas de margin-top manuel */
    .annonce-container {
      max-width: 1200px;
      margin: 2rem auto 60px;
      padding: 0 20px;
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 40px;
    }
    @media (max-width: 968px) {
      .annonce-container { grid-template-columns: 1fr; }
    }
    .annonce-main-img {
      width: 100%; height: 450px;
      border-radius: 24px; background-size: cover; background-position: center;
      position: relative; margin-bottom: 2rem;
    }
    .annonce-badges { position: absolute; top: 20px; left: 20px; display: flex; gap: 10px; }
    .badge-item { padding: 6px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 700; }
    .badge-green { background: #00b894; color: #fff; }
    .badge-white { background: #fff; color: #00b894; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
    .annonce-header h1 { font-family: 'Syne', sans-serif; font-size: 2.2rem; color: #0d1f1c; margin-bottom: 0.5rem; }
    .annonce-location { font-size: 1.1rem; color: #637470; margin-bottom: 1.5rem; }
    .annonce-meta-grid {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(140px,1fr));
      gap: 20px; margin-bottom: 2rem; padding: 1.5rem;
      background: #f4f7f6; border-radius: 16px;
    }
    .meta-item { display: flex; flex-direction: column; gap: 5px; }
    .meta-label { font-size: 0.85rem; color: #8a9e99; text-transform: uppercase; letter-spacing: 0.5px; }
    .meta-value { font-size: 1.1rem; font-weight: 600; color: #0d1f1c; }
    .annonce-section { margin-bottom: 2.5rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 2rem; }
    .annonce-section h2 { font-family: 'Syne', sans-serif; font-size: 1.5rem; color: #0d1f1c; margin-bottom: 1rem; }
    .annonce-section p { color: #4a5568; line-height: 1.7; font-size: 1.05rem; }
    .equipements-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px,1fr)); gap: 15px; }
    .equipement-item { display: flex; align-items: center; gap: 10px; }
    .equipement-item.checked { color: #0d1f1c; font-weight: 500; }
    .equipement-item.unchecked { color: #a0aec0; text-decoration: line-through; }
    .sidebar-card {
      background: #fff; border: 1px solid #e2e8f0; border-radius: 24px;
      padding: 2rem; position: sticky; top: calc(var(--nav-h) + 1rem);
      box-shadow: 0 10px 30px rgba(13,31,28,0.04); height: fit-content;
    }
    .sidebar-price { margin-bottom: 1.5rem; }
    .sidebar-price strong { font-size: 2.2rem; color: #00b894; display: block; }
    .sidebar-price span { color: #8a9e99; font-size: 1rem; }
    .sidebar-owner {
      display: flex; align-items: center; gap: 15px;
      padding: 1rem 0; border-top: 1px solid #f0f4f3;
      border-bottom: 1px solid #f0f4f3; margin-bottom: 1.5rem;
    }
    .owner-avatar {
      width: 45px; height: 45px; background: #00b894; color: #fff;
      border-radius: 50%; display: flex; align-items: center;
      justify-content: center; font-weight: bold; font-size: 1.2rem;
    }
    .btn-contact {
      display: block; width: 100%; text-align: center;
      background: #00b894; color: #fff; padding: 1rem; border-radius: 12px;
      font-weight: 600; text-decoration: none; transition: background 0.3s;
      box-shadow: 0 4px 12px rgba(0,184,148,0.2);
    }
    .btn-contact:hover { background: #00a383; }
  </style>
</head>
<body>

<nav>
  <a href="index.php" class="nav-logo">🏠 Smart<span>Home</span></a>
  <ul class="nav-links">
    <li><a href="index.php">Accueil</a></li>
    <li><a href="index.php#how">Comment ça marche</a></li>
    <li><a href="index.php#footer-contact">Contacts</a></li>

    <?php if ($userConnecte): ?>
      <li><span class="user-welcome">Bonjour <?= htmlspecialchars($_SESSION['user_prenom'] ?? '') ?></span></li>
      <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
        <li><a href="administrator_page.php">Administration</a></li>
      <?php endif; ?>
      <li><a href="logout.php" class="nav-logout">Se déconnecter</a></li>
    <?php else: ?>
      <li><a href="connexion.php" class="nav-cta">Mon Compte</a></li>
    <?php endif; ?>
  </ul>
</nav>

<main class="annonce-container">
  <div>
    <div class="annonce-main-img" style="background-image:url('<?= htmlspecialchars($imgPrincipal) ?>');">
      <div class="annonce-badges">
        <?php if (!empty($annonce['badge'])): ?>
          <span class="badge-item badge-green"><?= htmlspecialchars($annonce['badge']) ?></span>
        <?php endif; ?>
        <?php if ($annonce['verifie']): ?>
          <span class="badge-item badge-white">✓ Vérifié par SmartHome</span>
        <?php endif; ?>
      </div>
    </div>

    <div class="annonce-header">
      <h1><?= htmlspecialchars($annonce['titre']) ?></h1>
      <p class="annonce-location">
        📍 <?= htmlspecialchars($annonce['quartier'] ?? '') ?>
        — <?= htmlspecialchars($annonce['ville_nom'] ?? '') ?>
        (<?= htmlspecialchars($annonce['code_postal'] ?? '') ?>)
      </p>
    </div>

    <div class="annonce-meta-grid">
      <div class="meta-item">
        <span class="meta-label">Surface</span>
        <span class="meta-value"><?= htmlspecialchars($annonce['surface_m2']) ?> m²</span>
      </div>
      <div class="meta-item">
        <span class="meta-label">Type</span>
        <span class="meta-value"><?= htmlspecialchars($annonce['type_logement']) ?></span>
      </div>
      <div class="meta-item">
        <span class="meta-label">Mobilier</span>
        <span class="meta-value"><?= $meuble ?></span>
      </div>
      <div class="meta-item">
        <span class="meta-label">Pièces</span>
        <span class="meta-value"><?= htmlspecialchars($annonce['nb_pieces'] ?? 1) ?></span>
      </div>
    </div>

    <div class="annonce-section">
      <h2>Description du bien</h2>
      <p><?= nl2br(htmlspecialchars($annonce['description'])) ?></p>
    </div>

    <div class="annonce-section">
      <h2>Équipements &amp; Caractéristiques</h2>
      <div class="equipements-list">
        <?php
        $equipements = [
            'ascenseur'  => '🛗 Ascenseur',
            'digicode'   => '🔒 Digicode / Code d\'accès',
            'gardien'    => '💂 Gardien de l\'immeuble',
            'cave'       => '📦 Cave privative',
            'parking'    => '🚗 Place de parking',
            'balcon'     => '🌿 Balcon / Extérieur',
            'fibre'      => '⚡ Connexion Fibre Internet',
            'lave_linge' => '🧺 Lave-linge inclus'
        ];
        foreach ($equipements as $cle => $libelle):
            $present = !empty($annonce[$cle]);
        ?>
          <div class="equipement-item <?= $present ? 'checked' : 'unchecked' ?>">
            <?= $present ? '✓' : '✗' ?> <?= $libelle ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <aside>
    <div class="sidebar-card">
      <div class="sidebar-price">
        <strong><?= number_format($annonce['loyer'],0,',',' ') ?> €<span>/mois</span></strong>
        <span><?= $charges ?> — Dépôt : <?= number_format($annonce['depot_garantie'] ?? $annonce['loyer'],0,',',' ') ?> €</span>
      </div>

      <div class="sidebar-owner">
        <div class="owner-avatar">
          <?= strtoupper(substr($annonce['proprietaire_nom'] ?? 'P', 0, 1)) ?>
        </div>
        <div>
          <span style="font-size:0.85rem; color:#8a9e99; display:block;">Propriétaire</span>
          <strong style="color:#0d1f1c;"><?= htmlspecialchars($annonce['proprietaire_nom'] ?? 'Non renseigné') ?></strong>
        </div>
      </div>

      <?php if ($userConnecte): ?>
        <a href="contact_proprietaire.php?annonce_id=<?= (int)$annonce['id'] ?>" class="btn-contact">✉️ Contacter le propriétaire</a>
      <?php else: ?>
        <a href="connexion.php" class="btn-contact">Se connecter pour réserver</a>
      <?php endif; ?>
    </div>
  </aside>
</main>

<footer id="footer-contact">
  <div class="footer-brand">
    <a href="index.php" class="nav-logo">🏠 SmartHome<span> </span></a>
    <p>La plateforme intelligente qui simplifie la recherche de logement pour les étudiants, travailleurs et étrangers.</p>
  </div>
  <div class="footer-col">
    <h4>Navigation</h4>
    <a href="index.php#how">Comment ça marche</a>
    <a href="index.php#why">Pourquoi nous ?</a>
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
