<?php
session_start();

// CORRIGÉ : Redirection si non connecté
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

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre       = trim($_POST['titre']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $ville       = trim($_POST['ville']        ?? '');
    $loyer       = trim($_POST['loyer']        ?? '');

    if (!$titre || !$description || !$ville || !$loyer) {
        $error = 'Veuillez remplir tous les champs requis.';
    } else {
        $uploaded = [];
        if (!empty($_FILES['photos']['tmp_name'][0])) {
            $dir = __DIR__ . '/uploads';
            if (!is_dir($dir)) mkdir($dir, 0755, true);

            foreach ($_FILES['photos']['tmp_name'] as $i => $tmp) {
                if (!$tmp) continue;
                $name = basename($_FILES['photos']['name'][$i]);
                $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg','jpeg','png','gif'])) continue;
                $target = $dir . '/' . time() . '_' . mt_rand(1000,9999) . '_' . preg_replace('/[^a-z0-9.\-_]/i','_',$name);
                if (move_uploaded_file($tmp, $target)) {
                    $uploaded[] = str_replace(__DIR__, '', $target);
                }
            }
        }

        // TODO: insérer l'annonce en BDD avec $pdo
        $message = 'Annonce créée avec succès. ' . (count($uploaded) ? count($uploaded) . ' photo(s) uploadée(s).' : 'Aucune photo uploadée.');
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Ajouter une annonce — SmartHome</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    .add-card { padding: 2rem; max-width: 920px; margin: 0 auto; }
    .form-row { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 1rem; }
    .form-group { margin-bottom: 1.25rem; }
    label { display: block; margin-bottom: 0.6rem; font-weight: 700; color: var(--dark-text); }
    input[type="text"], input[type="number"], textarea {
      width: 100%; padding: 1rem 1.1rem;
      border: 1px solid var(--gray-mid); border-radius: 14px;
      font-family: var(--font-body); font-size: 1rem; color: var(--dark-text);
      background: #fff; transition: border-color 0.25s;
    }
    textarea { min-height: 140px; resize: vertical; }
    input[type="text"]:focus, input[type="number"]:focus, textarea:focus {
      outline: none; border-color: var(--green-main);
      box-shadow: 0 0 0 4px rgba(0,184,148,0.12);
    }
    .photo-grid {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(140px,1fr));
      gap: 1rem; margin-top: 0.75rem;
    }
    .photo-box {
      min-height: 110px; border: 2px dashed var(--gray-mid);
      border-radius: 18px; display: flex; align-items: center;
      justify-content: center; position: relative; overflow: hidden;
      background: var(--white); cursor: pointer; transition: border-color 0.25s, box-shadow 0.25s;
    }
    .photo-box:hover { border-color: var(--green-main); box-shadow: 0 10px 30px rgba(0,184,148,0.12); }
    .photo-box img { width: 100%; height: 100%; object-fit: cover; }
    .photo-box input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .photo-box .remove-photo {
      position: absolute; top: 8px; right: 8px; width: 24px; height: 24px;
      border-radius: 50%; background: rgba(13,31,28,0.85); color: #fff; border: none;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; font-size: 0.9rem; opacity: 0.9; z-index: 1; transition: opacity 0.2s;
    }
    .photo-box .remove-photo:hover { opacity: 1; }
    .controls { display: flex; gap: 0.75rem; flex-wrap: wrap; margin-top: 1rem; }
    .btn {
      display: inline-flex; align-items: center; justify-content: center;
      background: var(--green-main); border: none; color: var(--white);
      padding: 0.95rem 1.3rem; border-radius: 14px; font-weight: 600;
      cursor: pointer; transition: background 0.25s; font-family: var(--font-body);
    }
    .btn:hover { background: var(--green-mid); }
    .btn.secondary {
      background: transparent; color: var(--green-mid); border: 1px solid var(--green-mid);
    }
    .btn.secondary:hover { background: var(--green-mid); color: var(--white); }
    .message-success {
      margin-bottom: 1.5rem; color: var(--dark-text);
      background: var(--green-pale); border: 1px solid rgba(0,184,148,0.2);
      padding: 1rem 1.2rem; border-radius: 16px;
    }
    .message-error {
      margin-bottom: 1.5rem;
      background: #feecec; color: #8f1b1b;
      border: 1px solid #f1c1c1; padding: 1rem 1.2rem; border-radius: 16px;
    }
  </style>
</head>
<body class="user-page-body">

  <nav>
    <!-- CORRIGÉ : liens cohérents -->
    <a href="index.php" class="nav-logo">🏠 Smart<span>Home</span></a>
    <ul class="nav-links">
      <li><a href="index.php">Accueil</a></li>
      <?php if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
        <li><a href="administrator_page.php">Administration</a></li>
      <?php endif; ?>
      <li><span class="user-welcome"><?= htmlspecialchars($_SESSION['user_prenom'] ?? '') ?></span></li>
      <li><a href="logout.php" class="nav-logout">Se déconnecter</a></li>
    </ul>
  </nav>

  <main id="main-content" class="user-page-main" tabindex="-1">
    <span class="section-tag">Annonces</span>
    <h1 class="section-title">Publier une annonce</h1>

    <section class="user-page-card add-card">
      <?php if ($message): ?>
        <div class="message-success"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="message-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <!-- CORRIGÉ : balise </form> présente et bien placée -->
      <form action="ajouter_annonce.php" method="post" enctype="multipart/form-data" id="annonceForm">
        <div class="form-group">
          <label for="titre">Titre</label>
          <input type="text" id="titre" name="titre" required
                 value="<?= isset($_POST['titre']) ? htmlspecialchars($_POST['titre']) : '' ?>">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="ville">Ville</label>
            <input type="text" id="ville" name="ville" required
                   value="<?= isset($_POST['ville']) ? htmlspecialchars($_POST['ville']) : '' ?>">
          </div>
          <div class="form-group">
            <label for="loyer">Loyer (€ / mois)</label>
            <input type="number" id="loyer" name="loyer" min="1" required
                   value="<?= isset($_POST['loyer']) ? htmlspecialchars($_POST['loyer']) : '' ?>">
          </div>
        </div>

        <div class="form-group">
          <label for="description">Description</label>
          <textarea id="description" name="description" required><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
        </div>

        <div class="form-group">
          <label>Photos</label>
          <div class="photo-grid" id="photoGrid">
            <label class="photo-box">
              <span>+</span>
              <input type="file" name="photos[]" accept="image/*" class="photo-input">
            </label>
          </div>
          <div class="controls">
            <button type="button" class="btn secondary" id="addPhoto">Ajouter une case photo</button>
            <button type="button" class="btn secondary" id="clearPhotos">Vider</button>
          </div>
        </div>

        <div style="margin-top:1.5rem;">
          <button type="submit" class="btn">Publier l'annonce</button>
        </div>
      </form><!-- CORRIGÉ : fermeture de <form> -->

    </section>
  </main>

  <script>
    const photoGrid  = document.getElementById('photoGrid');
    const addPhoto   = document.getElementById('addPhoto');
    const clearPhotos = document.getElementById('clearPhotos');

    function createPhotoBox(file) {
      const label  = document.createElement('label');
      label.className = 'photo-box';

      const input  = document.createElement('input');
      input.type   = 'file';
      input.name   = 'photos[]';
      input.accept = 'image/*';
      input.className = 'photo-input';
      input.addEventListener('change', handlePreview);
      label.appendChild(input);

      if (file) {
        const img    = document.createElement('img');
        const reader = new FileReader();
        reader.onload = ev => { img.src = ev.target.result; label.insertBefore(img, input); };
        reader.readAsDataURL(file);

        const removeBtn = document.createElement('button');
        removeBtn.type       = 'button';
        removeBtn.className  = 'remove-photo';
        removeBtn.innerHTML  = '&times;';
        removeBtn.addEventListener('click', () => label.remove());
        label.appendChild(removeBtn);
      } else {
        const plus = document.createElement('span');
        plus.textContent = '+';
        label.insertBefore(plus, input);
      }

      photoGrid.appendChild(label);
    }

    function handlePreview(e) {
      const file = e.target.files[0];
      if (!file) return;
      const box = e.target.closest('.photo-box');
      box.remove();
      createPhotoBox(file);
    }

    addPhoto.addEventListener('click', () => createPhotoBox());
    clearPhotos.addEventListener('click', () => { photoGrid.innerHTML = ''; createPhotoBox(); });
    document.querySelectorAll('.photo-input').forEach(i => i.addEventListener('change', handlePreview));
  </script>
</body>
</html>
