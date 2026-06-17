-- ============================================================
--  SMARTHOME AI — Base de données complète
--  Compatible : MySQL 8+ / MariaDB 10.5+
--  Généré pour le projet SmartHome AI (smarthome.fr)
-- ============================================================

CREATE DATABASE IF NOT EXISTS smarthome
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE smarthome;

-- ============================================================
-- 1. UTILISATEURS
--    Regroupe locataires ET propriétaires (rôle différencié)
-- ============================================================
CREATE TABLE utilisateurs (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom             VARCHAR(80)  NOT NULL,
  prenom          VARCHAR(80)  NOT NULL,
  email           VARCHAR(180) NOT NULL UNIQUE,
  mot_de_passe    VARCHAR(255) NOT NULL COMMENT 'Hash bcrypt',
  telephone       VARCHAR(20),
  avatar_url      VARCHAR(500),
  role            ENUM('locataire','proprietaire','admin') NOT NULL DEFAULT 'locataire',

  -- Profil locataire (rempli à l'étape 1 « Créez votre profil »)
  situation       ENUM('etudiant','travailleur','etranger','autre'),
  budget_min      SMALLINT UNSIGNED COMMENT 'Loyer minimum souhaité (€)',
  budget_max      SMALLINT UNSIGNED COMMENT 'Loyer maximum souhaité (€)',
  ville_cible     VARCHAR(100) COMMENT 'Ville ou quartier recherché',
  surface_min     TINYINT UNSIGNED COMMENT 'Surface minimum souhaitée (m²)',
  type_logement   SET('Studio','T1','T2','T3','Colocation','Résidence étudiante')
                  COMMENT 'Types acceptés (multi-sélection)',
  preference_meuble ENUM('oui','non','indifferent') DEFAULT 'indifferent',
  preferences_texte TEXT COMMENT 'Description libre des besoins',

  -- Métadonnées
  email_verifie   TINYINT(1) NOT NULL DEFAULT 0,
  actif           TINYINT(1) NOT NULL DEFAULT 1,
  cree_le         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  mis_a_jour_le   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- ============================================================
-- 2. VILLES / ZONES
--    Référentiel géographique pour normaliser les annonces
-- ============================================================
CREATE TABLE villes (
  id              SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom             VARCHAR(100) NOT NULL,
  code_postal     VARCHAR(10),
  departement     VARCHAR(80),
  region          VARCHAR(80)  DEFAULT 'Île-de-France',
  latitude        DECIMAL(9,6),
  longitude       DECIMAL(9,6)
) ENGINE=InnoDB;

INSERT INTO villes (nom, code_postal, departement) VALUES
  ('Paris 1er',          '75001', 'Paris'),
  ('Paris 3e',           '75003', 'Paris'),
  ('Paris 5e',           '75005', 'Paris'),
  ('Paris 7e',           '75007', 'Paris'),
  ('Paris 9e',           '75009', 'Paris'),
  ('Paris 10e',          '75010', 'Paris'),
  ('Paris 11e',          '75011', 'Paris'),
  ('Paris 12e',          '75012', 'Paris'),
  ('Paris 13e',          '75013', 'Paris'),
  ('Paris 14e',          '75014', 'Paris'),
  ('Paris 15e',          '75015', 'Paris'),
  ('Paris 16e',          '75016', 'Paris'),
  ('Paris 18e',          '75018', 'Paris'),
  ('Paris 19e',          '75019', 'Paris'),
  ('Paris 20e',          '75020', 'Paris'),
  ('Boulogne-Billancourt','92100','Hauts-de-Seine'),
  ('Levallois-Perret',   '92300', 'Hauts-de-Seine'),
  ('Neuilly-sur-Seine',  '92200', 'Hauts-de-Seine'),
  ('Puteaux',            '92800', 'Hauts-de-Seine'),
  ('Vincennes',          '94300', 'Val-de-Marne'),
  ('Créteil',            '94000', 'Val-de-Marne'),
  ('Ivry-sur-Seine',     '94200', 'Val-de-Marne'),
  ('Cachan',             '94230', 'Val-de-Marne'),
  ('Saint-Denis',        '93200', 'Seine-Saint-Denis'),
  ('Pantin',             '93500', 'Seine-Saint-Denis'),
  ('Montreuil',          '93100', 'Seine-Saint-Denis'),
  ('Massy',              '91300', 'Essonne'),
  ('Gif-sur-Yvette',     '91190', 'Essonne'),
  ('Versailles',         '78000', 'Yvelines');


-- ============================================================
-- 3. ANNONCES
--    Table centrale du site
-- ============================================================
CREATE TABLE annonces (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  proprietaire_id INT UNSIGNED NOT NULL,
  ville_id        SMALLINT UNSIGNED,

  -- Description
  titre           VARCHAR(150) NOT NULL,
  description     TEXT,
  adresse         VARCHAR(200),
  quartier        VARCHAR(100),

  -- Caractéristiques
  type_logement   ENUM('Studio','T1','T2','T3','T4+','Colocation','Résidence étudiante') NOT NULL,
  surface_m2      TINYINT UNSIGNED NOT NULL,
  nb_pieces       TINYINT UNSIGNED DEFAULT 1,
  etage           TINYINT,
  meuble          TINYINT(1) NOT NULL DEFAULT 0,

  -- Prix
  loyer           SMALLINT UNSIGNED NOT NULL COMMENT 'Loyer mensuel en €',
  charges         SMALLINT UNSIGNED DEFAULT 0 COMMENT 'Charges mensuelles en €',
  charges_incluses TINYINT(1) DEFAULT 0 COMMENT '1 = charges incluses dans le loyer',
  depot_garantie  SMALLINT UNSIGNED COMMENT 'Dépôt de garantie en €',

  -- Équipements (flags booléens)
  ascenseur       TINYINT(1) DEFAULT 0,
  digicode        TINYINT(1) DEFAULT 0,
  gardien         TINYINT(1) DEFAULT 0,
  cave            TINYINT(1) DEFAULT 0,
  parking         TINYINT(1) DEFAULT 0,
  balcon          TINYINT(1) DEFAULT 0,
  fibre           TINYINT(1) DEFAULT 0,
  lave_linge      TINYINT(1) DEFAULT 0,

  -- Badge / mise en avant
  badge           ENUM('','Nouveau','Populaire','Étudiant') DEFAULT '',
  image_unsplash  VARCHAR(100) COMMENT 'ID Unsplash pour la photo principale',

  -- Statut & dates
  statut          ENUM('disponible','loué','suspendu') NOT NULL DEFAULT 'disponible',
  verifie         TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Annonce validée par équipe SmartHome',
  date_disponible DATE,
  cree_le         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  mis_a_jour_le   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT fk_annonce_proprio  FOREIGN KEY (proprietaire_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
  CONSTRAINT fk_annonce_ville    FOREIGN KEY (ville_id)        REFERENCES villes(id)       ON DELETE SET NULL,
  INDEX idx_type    (type_logement),
  INDEX idx_loyer   (loyer),
  INDEX idx_ville   (ville_id),
  INDEX idx_statut  (statut)
) ENGINE=InnoDB;


-- ============================================================
-- 4. PHOTOS DES ANNONCES
--    Une annonce peut avoir plusieurs photos
-- ============================================================
CREATE TABLE annonce_photos (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  annonce_id  INT UNSIGNED NOT NULL,
  url         VARCHAR(500) NOT NULL,
  alt_text    VARCHAR(200),
  ordre       TINYINT UNSIGNED DEFAULT 0 COMMENT 'Ordre d\'affichage',
  principale  TINYINT(1) DEFAULT 0,
  CONSTRAINT fk_photo_annonce FOREIGN KEY (annonce_id) REFERENCES annonces(id) ON DELETE CASCADE,
  INDEX idx_annonce (annonce_id)
) ENGINE=InnoDB;


-- ============================================================
-- 5. FAVORIS
--    Locataire peut sauvegarder des annonces
-- ============================================================
CREATE TABLE favoris (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  utilisateur_id  INT UNSIGNED NOT NULL,
  annonce_id      INT UNSIGNED NOT NULL,
  cree_le         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_favori (utilisateur_id, annonce_id),
  CONSTRAINT fk_favori_user    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
  CONSTRAINT fk_favori_annonce FOREIGN KEY (annonce_id)     REFERENCES annonces(id)     ON DELETE CASCADE
) ENGINE=InnoDB;


-- ============================================================
-- 6. MESSAGES (Contact propriétaire ↔ locataire)
--    Correspond à l'étape 2 « Entrez en contact »
-- ============================================================
CREATE TABLE messages (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  annonce_id      INT UNSIGNED NOT NULL,
  expediteur_id   INT UNSIGNED NOT NULL,
  destinataire_id INT UNSIGNED NOT NULL,
  contenu         TEXT NOT NULL,
  lu              TINYINT(1) NOT NULL DEFAULT 0,
  envoye_le       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_msg_annonce FOREIGN KEY (annonce_id)      REFERENCES annonces(id)     ON DELETE CASCADE,
  CONSTRAINT fk_msg_exp     FOREIGN KEY (expediteur_id)   REFERENCES utilisateurs(id) ON DELETE CASCADE,
  CONSTRAINT fk_msg_dest    FOREIGN KEY (destinataire_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
  INDEX idx_annonce (annonce_id),
  INDEX idx_dest    (destinataire_id)
) ENGINE=InnoDB;


-- ============================================================
-- 7. VISITES
--    Planification de visites via la plateforme
-- ============================================================
CREATE TABLE visites (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  annonce_id      INT UNSIGNED NOT NULL,
  locataire_id    INT UNSIGNED NOT NULL,
  date_visite     DATETIME NOT NULL,
  statut          ENUM('en_attente','confirmée','annulée','effectuée') DEFAULT 'en_attente',
  note_locataire  TEXT COMMENT 'Message accompagnant la demande',
  note_proprio    TEXT COMMENT 'Réponse du propriétaire',
  cree_le         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_visite_annonce   FOREIGN KEY (annonce_id)   REFERENCES annonces(id)     ON DELETE CASCADE,
  CONSTRAINT fk_visite_locataire FOREIGN KEY (locataire_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;


-- ============================================================
-- 8. AVIS / ÉVALUATIONS
--    Locataire évalue la plateforme ou le logement
-- ============================================================
CREATE TABLE avis (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  auteur_id       INT UNSIGNED NOT NULL,
  annonce_id      INT UNSIGNED,
  note            TINYINT UNSIGNED NOT NULL CHECK (note BETWEEN 1 AND 5),
  commentaire     TEXT,
  verifie         TINYINT(1) DEFAULT 0,
  cree_le         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_avis_auteur  FOREIGN KEY (auteur_id)  REFERENCES utilisateurs(id) ON DELETE CASCADE,
  CONSTRAINT fk_avis_annonce FOREIGN KEY (annonce_id) REFERENCES annonces(id)     ON DELETE SET NULL
) ENGINE=InnoDB;


-- ============================================================
-- 9. RECHERCHES SAUVEGARDÉES
--    Alerte email quand une annonce correspond
-- ============================================================
CREATE TABLE recherches_sauvegardees (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  utilisateur_id  INT UNSIGNED NOT NULL,
  nom             VARCHAR(100) COMMENT 'Ex : "Mon studio Montmartre"',
  ville_id        SMALLINT UNSIGNED,
  type_logement   VARCHAR(100),
  loyer_max       SMALLINT UNSIGNED,
  surface_min     TINYINT UNSIGNED,
  meuble          TINYINT(1),
  alerte_email    TINYINT(1) DEFAULT 1,
  cree_le         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_rech_user FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
) ENGINE=InnoDB;


-- ============================================================
-- 10. DONNÉES DE DÉMONSTRATION
--     Correspond exactement aux annonces du site
-- ============================================================

-- Propriétaires fictifs
INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, actif) VALUES
  ('Martin',   'Jean',    'jean.martin@demo.fr',   '$2b$12$demo', 'proprietaire', 1),
  ('Dubois',   'Sophie',  'sophie.dubois@demo.fr', '$2b$12$demo', 'proprietaire', 1),
  ('Bernard',  'Marc',    'marc.bernard@demo.fr',  '$2b$12$demo', 'proprietaire', 1),
  ('Admin',    'SmartHome','admin@smarthome.fr',   '$2b$12$demo', 'admin',        1);

-- Locataires fictifs
INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, situation, budget_min, budget_max, ville_cible) VALUES
  ('Leroy',  'Emma',    'emma.leroy@demo.fr',  '$2b$12$demo', 'locataire', 'etudiante',  400, 700,  'Paris'),
  ('Nguyen', 'Thierry', 'thierry.n@demo.fr',   '$2b$12$demo', 'locataire', 'travailleur',700, 1200, 'Paris'),
  ('Garcia', 'Sofia',   'sofia.g@demo.fr',     '$2b$12$demo', 'locataire', 'etranger',  500, 900,  'Paris');

-- Annonces (correspondant aux cards du site)
INSERT INTO annonces
  (proprietaire_id, ville_id, titre, description, quartier, type_logement, surface_m2, meuble, loyer, charges_incluses, badge, image_unsplash, statut, verifie)
VALUES
  (1, 13, 'Studio — Montmartre',         'Studio lumineux au 3e étage, cuisine équipée, parquet, proche Sacré-Cœur.',              'Montmartre',    'Studio',    18, 1,  620, 1, 'Nouveau',  '1502672260266-1c1ef2d93688', 'disponible', 1),
  (1, 15, 'Chambre — Belleville',        'Chambre dans coloc de 3 personnes, espaces communs spacieux, ambiance conviviale.',       'Belleville',    'Colocation',12, 1,  450, 0, 'Étudiant', '1560448204-e02f11c3d0e2',   'disponible', 1),
  (2, 12, 'Appartement T1 — Nation',     'T1 refait à neuf, double vitrage, ascenseur, cave. Quartier calme.',                      'Nation',        'T1',        32, 0,  980, 0, 'Populaire','1493809842364-78817add7ffb', 'disponible', 1),
  (1, 11, 'Studio — Oberkampf',          'Studio avec balcon filant et vue dégagée, cuisine ouverte, lave-linge inclus.',           'Oberkampf',     'Studio',    22, 1,  750, 0, '',         '1484154218962-a197022b5858', 'disponible', 1),
  (2, 11, 'T2 — Bastille',              'Beau T2 avec séjour lumineux, chambre séparée, parquet chêne, gardien.',                  'Bastille',      'T2',        45, 0, 1350, 0, 'Nouveau',  '1522708323474-4769b6b16f0c', 'disponible', 1),
  (3, 10, 'Chambre — République',        'Chambre meublée dans grande coloc, cuisine moderne, très bien desservie.',                'République',    'Colocation',14, 1,  490, 0, 'Étudiant', '1555636222-cae831e670b3',   'disponible', 1),
  (1, 9,  'Studio — Pigalle',           'Studio cosy en rez-de-chaussée surélevé, tout équipé, charges comprises.',                'Pigalle',       'Studio',    20, 1,  700, 1, '',         '1506905925346-21bda4d32df4', 'disponible', 1),
  (2, 2,  'T1 — Marais',               'T1 meublé en plein Marais, cachet haussmannien, parquet, lumineux.',                       'Le Marais',     'T1',        28, 1, 1100, 0, 'Populaire','1519125323398-675f0ddb6308', 'disponible', 1),
  (3, 19, 'Studio — La Défense',        'Studio moderne résidence neuve, accès direct RER A, parking inclus.',                     'La Défense',    'Studio',    24, 1,  830, 0, '',         '1486325212027-8081e485255e', 'disponible', 1),
  (1, 20, 'Chambre — Vincennes',        'Chambre dans maison avec jardin, coloc calme et sérieuse, proche bois.',                  'Vincennes',     'Colocation',16, 1,  520, 0, 'Étudiant', '1573496359142-b8d87734a5a2', 'disponible', 1),
  (2, 1,  'T2 — Châtelet',             'T2 hypercentre, vue sur toits parisiens, double séjour, cave et digicode.',                'Châtelet',      'T2',        48, 0, 1600, 0, 'Nouveau',  '1512917774080-9991f1c4c750', 'disponible', 1),
  (3, 14, 'Studio — Alésia',           'Studio refait, cuisine américaine, proche marché, quartier résidentiel.',                  'Alésia',        'Studio',    19, 1,  660, 0, '',         '1502005229762-cf1b2da7c5d6', 'disponible', 1),
  (1, 3,  'Chambre — Jussieu',         'Chambre à 5 min de Jussieu, parfait pour étudiants, internet fibre inclus.',              'Jussieu',       'Colocation',13, 1,  510, 1, 'Étudiant', '1555636222-cae831e670b3',   'disponible', 1),
  (2, 12, 'T1 — Daumesnil',           'T1 calme, double exposition, proche lac Daumesnil, cave incluse.',                         'Daumesnil',     'T1',        30, 0,  920, 0, '',         '1493809842364-78817add7ffb', 'disponible', 1),
  (3, 10, 'Studio — Strasbourg-Saint-Denis','Studio animé, proche bars et restos, tout meublé, disponible de suite.',            'Strasbourg-Saint-Denis','Studio',17,1, 640, 0, 'Populaire','1502672260266-1c1ef2d93688', 'disponible', 1),
  (1, 16, 'T3 — Boulogne',            'Grand T3 lumineux, deux chambres, balcon, parking, gardien, très calme.',                  'Boulogne',      'T3',        65, 0, 1800, 0, 'Nouveau',  '1522708323474-4769b6b16f0c', 'disponible', 1),
  (2, 25, 'Chambre — Pantin',          'Chambre dans loft atypique, proche ligne 5, coloc artistes et créatifs.',                  'Pantin',        'Colocation',15, 1,  430, 0, 'Étudiant', '1560448204-e02f11c3d0e2',   'disponible', 1),
  (3, 17, 'Studio — Levallois',        'Studio tout équipé résidence récente, digicode, interphone, calme.',                      'Levallois',     'Studio',    25, 1,  880, 0, '',         '1484154218962-a197022b5858', 'disponible', 1),
  (1, 15, 'T1 — Gambetta',            'T1 ensoleillé, parquet, cuisine équipée, proche métro Gambetta ligne 3.',                  'Gambetta',      'T1',        29, 0,  870, 0, '',         '1486325212027-8081e485255e', 'disponible', 1),
  (2, 23, 'Chambre — Cachan',          'Chambre en résidence étudiante, salle de bain privative, cafétéria sur place.',           'Cachan',        'Colocation',12, 1,  400, 1, 'Étudiant', '1573496359142-b8d87734a5a2', 'disponible', 1);


-- ============================================================
-- 11. VUES UTILES POUR LE FRONTEND
-- ============================================================

-- Vue : annonces disponibles avec nom de ville
CREATE OR REPLACE VIEW v_annonces_disponibles AS
SELECT
  a.id,
  a.titre,
  a.description,
  a.quartier,
  v.nom            AS ville,
  v.code_postal,
  a.type_logement,
  a.surface_m2,
  a.meuble,
  a.loyer,
  a.charges,
  a.charges_incluses,
  a.badge,
  a.image_unsplash,
  a.verifie,
  a.cree_le,
  CONCAT(u.prenom, ' ', u.nom) AS proprietaire
FROM annonces a
LEFT JOIN villes       v ON a.ville_id        = v.id
LEFT JOIN utilisateurs u ON a.proprietaire_id = u.id
WHERE a.statut = 'disponible';

-- Vue : statistiques homepage (2 400+ annonces, 98% satisfaction…)
CREATE OR REPLACE VIEW v_stats_homepage AS
SELECT
  (SELECT COUNT(*) FROM annonces WHERE statut = 'disponible')   AS annonces_disponibles,
  (SELECT COUNT(*) FROM utilisateurs WHERE role = 'locataire')  AS locataires_inscrits,
  (SELECT COUNT(*) FROM utilisateurs WHERE role = 'proprietaire') AS proprietaires_inscrits,
  (SELECT ROUND(AVG(note) * 20, 0) FROM avis WHERE verifie = 1) AS satisfaction_pct;

-- Vue : nombre de favoris par annonce
CREATE OR REPLACE VIEW v_favoris_count AS
SELECT annonce_id, COUNT(*) AS nb_favoris
FROM favoris
GROUP BY annonce_id;


-- ============================================================
-- 12. REQUÊTES EXEMPLES (commentées)
-- ============================================================

-- Recherche filtrée (barre de recherche + filtres)
-- SELECT * FROM v_annonces_disponibles
-- WHERE ville LIKE '%Paris%'
--   AND type_logement IN ('Studio','T1')
--   AND loyer <= 800
--   AND meuble = 1
-- ORDER BY cree_le DESC;

-- Recommandations personnalisées pour un locataire
-- SELECT a.* FROM annonces a
-- JOIN utilisateurs u ON u.id = 5  -- id du locataire connecté
-- WHERE a.loyer    BETWEEN u.budget_min AND u.budget_max
--   AND a.statut = 'disponible'
--   AND FIND_IN_SET(a.type_logement, u.type_logement) > 0
-- ORDER BY a.verifie DESC, a.cree_le DESC
-- LIMIT 6;

-- Messages non lus pour un propriétaire
-- SELECT m.*, a.titre AS annonce, CONCAT(u.prenom,' ',u.nom) AS de
-- FROM messages m
-- JOIN annonces      a ON m.annonce_id     = a.id
-- JOIN utilisateurs  u ON m.expediteur_id  = u.id
-- WHERE m.destinataire_id = 1 AND m.lu = 0
-- ORDER BY m.envoye_le DESC;
