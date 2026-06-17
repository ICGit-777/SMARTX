<?php
// Démarre la session ou reprend la session existante
session_start();

// Vide le tableau des variables de session (supprime les données côté serveur)
$_SESSION = array();

// Si les sessions utilisent des cookies, supprimer le cookie de session côté client
if (ini_get("session.use_cookies")) {
    // Récupère les paramètres actuels du cookie de session
    $params = session_get_cookie_params();
    // Définit le cookie de session avec une date passée pour forcer sa suppression
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Détruit la session côté serveur
session_destroy();
?>
<!DOCTYPE html>
<!-- Page HTML affichée après déconnexion -->
<html lang="fr">
<head>
  <!-- Encodage et viewport -->
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <!-- Titre de la page -->
  <title>Déconnexion — SmartHome</title>
  <!-- Police Google Fonts utilisée dans la page -->
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet"/>
  
  <!-- Styles locaux spécifiques à cette page (inline pour simplicité) -->
  <style>
    /* Réinitialisation basique pour cette page */
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    /* Styles du <body> : police, fond, centrage vertical/horizontal */
    body {
      font-family: 'DM Sans', sans-serif;
      background-color: #f4f7f6;
      color: #0d1f1c;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 20px;
    }
    /* Carte contenant le message de déconnexion */
    .logout-card {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 24px;
      padding: 3rem 2rem;
      max-width: 450px;
      width: 100%;
      text-align: center;
      box-shadow: 0 10px 30px rgba(13, 31, 28, 0.04);
    }
    /* Logo texte en haut de la carte */
    .logo {
      font-family: 'Syne', sans-serif;
      font-size: 1.8rem;
      font-weight: 700;
      color: #0d1f1c;
      text-decoration: none;
      display: inline-block;
      margin-bottom: 2rem;
    }
    /* Variation colorée du mot "Home" */
    .logo span {
      color: #00b894;
    }
    /* Cercle icône au-dessus du message */
    .icon-wrapper {
      width: 80px;
      height: 80px;
      background-color: #eaf8f4;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem auto;
    }
    /* Taille du texte/icon à l'intérieur du wrapper */
    .icon-wrapper text {
      font-size: 2rem;
    }
    /* Titre principal de la page */
    h1 {
      font-family: 'Syne', sans-serif;
      font-size: 1.8rem;
      font-weight: 700;
      color: #0d1f1c;
      margin-bottom: 0.75rem;
    }
    /* Paragraphe explicatif sous le titre */
    p {
      color: #637470;
      font-size: 1.05rem;
      line-height: 1.6;
      margin-bottom: 2rem;
    }
    /* Bouton renvoyant vers l'accueil */
    .btn-home {
      display: inline-block;
      background: #00b894;
      color: #ffffff;
      padding: 0.85rem 2rem;
      border-radius: 12px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px rgba(0, 184, 148, 0.15);
    }
    /* Effet visuel au survol du bouton */
    .btn-home:hover {
      background: #00a383;
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(0, 184, 148, 0.25);
    }
    /* Barre de chargement visuelle (progression avant redirection) */
    .loader-bar {
      width: 100%;
      height: 4px;
      background-color: #e2e8f0;
      border-radius: 2px;
      margin-top: 2.5rem;
      overflow: hidden;
      position: relative;
    }
    /* Élément animé représentant la progression */
    .loader-progress {
      width: 100%;
      height: 100%;
      background-color: #00b894;
      position: absolute;
      left: -100%;
      animation: load 3s linear forwards;
    }
    /* Animation qui fait glisser la barre de gauche à droite */
    @keyframes load {
      to { left: 0; }
    }
  </style>
  
  <!-- Redirection automatique vers index.php après 3 secondes -->
  <meta http-equiv="refresh" content="3;url=index.php">
</head>
<body>

  <!-- Carte de confirmation de déconnexion -->
  <div class="logout-card">
    <!-- Logo cliquable renvoyant vers l'accueil -->
    <a href="index.php" class="logo">🏠 Smart<span>Home</span></a>
    
    <!-- Icône d'au revoir -->
    <div class="icon-wrapper">
      <span>👋</span>
    </div>

    <!-- Message principal -->
    <h1>À bientôt !</h1>
    <!-- Message secondaire expliquant la redirection -->
    <p>Vous avez été déconnecté avec succès. Vous allez être redirigé vers l'accueil dans un instant.</p>

    <!-- Lien-bouton vers l'accueil (au clic immédiat) -->
    <a href="index.php" class="btn-home">Retourner à l'accueil</a>

    <!-- Barre de chargement animée -> montre le délai avant redirection -->
    <div class="loader-bar">
      <div class="loader-progress"></div>
    </div>
  </div>

</body>
</html>
