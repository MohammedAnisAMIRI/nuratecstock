<?php

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=nuratecstock;charset=utf8", 
        "nura_user",          // anciennement "root"
        "Nura1939@",          // votre mot de passe
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}


// 1) Toutes les catégories
$categories = $pdo->query("SELECT id, nom FROM categories ORDER BY nom")->fetchAll();

// 2) Toutes les marques distinctes
$brands = $pdo
    ->query("SELECT DISTINCT marque FROM produits WHERE marque<>'' ORDER BY marque")
    ->fetchAll(PDO::FETCH_COLUMN);

// 3) Filtres sélectionnés
$selectedCat   = isset($_GET['cat'])   && ctype_digit($_GET['cat'])   ? (int)$_GET['cat']   : null;
$selectedSub   = isset($_GET['subcat'])&& ctype_digit($_GET['subcat'])? (int)$_GET['subcat']: null;
$selectedBrand = isset($_GET['brand'])             ? trim($_GET['brand'])    : '';
$search        = isset($_GET['q'])                 ? trim($_GET['q'])        : '';

// 4) Sous-catégories de la catégorie sélectionnée
$subcategories = [];
if ($selectedCat !== null) {
    $stmtSc = $pdo->prepare("SELECT id, nom FROM sous_categories WHERE id_categorie=? ORDER BY nom");
    $stmtSc->execute([$selectedCat]);
    $subcategories = $stmtSc->fetchAll();
}

// 5) Nom de la catégorie pour “téléphonie”
$catNom = '';
if ($selectedCat !== null) {
    $stmt = $pdo->prepare("SELECT nom FROM categories WHERE id = ?");
    $stmt->execute([$selectedCat]);
    $catNom = $stmt->fetchColumn() ?: '';
}

// 6) Construction de la requête produits
$params = [];
$sql = "
  SELECT 
    p.id,
    p.nom,
    p.ean,
    p.nu,
    p.quantite,
    p.marque,
    p.reference,
    p.imei,
    p.ecid,
    p.numero_de_serie,
    p.photo1,
    p.photo2,
    p.photo3,
    p.qr_code
  FROM produits p
  WHERE 1
";
if ($selectedCat !== null) {
    $sql     .= " AND p.id_categorie = ?";
    $params[] = $selectedCat;
}
if ($selectedSub !== null) {
    $sql     .= " AND p.id_souscategorie = ?";
    $params[] = $selectedSub;
}
if ($selectedBrand !== '') {
    $sql     .= " AND p.marque = ?";
    $params[] = $selectedBrand;
}
if ($search !== '') {
    $sql     .= " AND (p.nom LIKE ? OR p.reference LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY p.nom";

$stmt    = $pdo->prepare($sql);
$stmt->execute($params);
$produits = $stmt->fetchAll();

// 7) Afficher colonnes téléphonie si “téléphonie”
$afficherTelephonie = in_array(mb_strtolower($catNom,'UTF-8'), ['téléphonie','telephonie']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Catalogue Nuratec</title>
  <style>
    :root {
      --accent:    #FFD600;
      --bg-light:  #f1f2f6;
      --bg-white:  #fff;
      --text-dark: #2d3436;
      --radius:    6px;
      --shadow:    0 2px 6px rgba(0,0,0,0.1);
    }
    * { box-sizing:border-box; margin:0; padding:0; font-weight:bold; }
    body {
      background:var(--bg-light);
      color:var(--text-dark);
      font-family:"Segoe UI",sans-serif;
      font-size:13px;
    }
    header {
      background:var(--bg-white);
      border-bottom:3px solid var(--accent);
      display:flex; align-items:center; justify-content:center;
      padding:10px; position:relative; box-shadow:var(--shadow);
    }
    .admin-link {
      position:absolute; top:10px; right:10px;
      background:rgba(255,255,255,0.8);
      color:var(--text-dark);
      padding:4px 8px;
      border:2px solid var(--accent);
      border-radius:var(--radius);
      text-decoration:none;
      font-size:0.8em;
      transition:background .2s,color .2s;
    }
    .admin-link:hover { background:var(--accent); color:#fff; }

    nav.categories-nav {
      display:flex; justify-content:center;
      overflow-x:auto; background:var(--bg-white);
      box-shadow:var(--shadow); font-size:0.85em;
      margin-bottom:16px;
    }
    nav.categories-nav a {
      padding:6px 12px; color:var(--text-dark);
      text-decoration:none; white-space:nowrap;
      transition:background .2s,color .2s;
    }
    nav.categories-nav a.active,
    nav.categories-nav a:hover {
      background:var(--accent); color:#fff;
    }

    .filter-bar {
      display:flex; justify-content:flex-start;
      gap:8px; margin:0 0 16px 16px;
      font-size:0.85em;
    }
    .filter-bar select,
    .filter-bar input[type="text"] {
      padding:4px; border:1px solid #ccc;
      border-radius:var(--radius);
    }
    .filter-bar input[type="submit"] {
      padding:4px 8px;
      background:var(--accent);
      color:#fff;
      border:none;
      cursor:pointer;
      border-radius:var(--radius);
    }

    .produits-table {
      width:96%; max-width:1000px;
      margin:0 0 32px 16px;
      border-collapse:collapse;
      background:var(--bg-white);
      border-radius:var(--radius);
      overflow:hidden;
      box-shadow:var(--shadow);
      font-size:0.8em;
    }
    .produits-table th,
    .produits-table td {
      padding:6px; text-align:center;
      border-bottom:1px solid #eee;
    }
    .produits-table th {
      background:var(--accent); color:#fff;
    }
    .produits-table tr:last-child td {
      border-bottom:none;
    }

    /* cacher colonnes téléphonie sauf si activé */
    .produits-table th.imei-col,
    .produits-table th.ecid-col,
    .produits-table th.snie-col,
    .produits-table td.imei-col,
    .produits-table td.ecid-col,
    .produits-table td.snie-col {
      display:none;
    }
    .produits-table.telephonie th.imei-col,
    .produits-table.telephonie th.ecid-col,
    .produits-table.telephonie th.snie-col,
    .produits-table.telephonie td.imei-col,
    .produits-table.telephonie td.ecid-col,
    .produits-table.telephonie td.snie-col {
      display:table-cell;
    }

    .produits-table td img {
      max-width:80px; border-radius:var(--radius);
      transition:transform .2s,box-shadow .2s; cursor:zoom-in;
    }
    .produits-table td img:hover {
      transform:scale(1.05); box-shadow:0 0 6px rgba(0,0,0,0.2);
    }
    .overlay {
      display:none; position:fixed; top:0; left:0;
      width:100%; height:100%; background:rgba(0,0,0,0.8);
      align-items:center; justify-content:center; z-index:1000;
    }
    .overlay:target { display:flex; }
    .overlay img {
      max-width:95vw; max-height:95vh; transition:transform .3s ease;
    }
    .overlay:target img { transform:scale(2); }
    .overlay .close-btn {
      position:absolute; top:10px; right:14px; color:#fff;
      font-size:1.8rem; text-decoration:none; transition:color .2s;
    }
    .overlay .close-btn:hover { color:var(--accent); }
  </style>
</head>
<body>

<header>
  Catalogue Nuratec
  <a href="login.php" class="admin-link">Connexion Admin</a>
</header>

<nav class="categories-nav">
  <?php foreach ($categories as $cat):
      $act = $selectedCat === (int)$cat['id'] ? 'active' : '';
  ?>
    <a href="index.php?cat=<?= $cat['id'] ?>&subcat=<?= $selectedSub ?>&brand=<?= urlencode($selectedBrand) ?>&q=<?= urlencode($search) ?>"
       class="<?= $act ?>">
      <?= htmlspecialchars($cat['nom']) ?>
    </a>
  <?php endforeach; ?>
</nav>

<form method="get" class="filter-bar">
  <input type="hidden" name="cat"    value="<?= $selectedCat ?>">
  <input type="hidden" name="subcat" value="<?= $selectedSub ?>">
  <select name="subcat" <?= $selectedCat===null?'disabled':''?>>
    <option value="">Toutes sous-catégories</option>
    <?php foreach ($subcategories as $sc): ?>
      <option value="<?= $sc['id'] ?>"
        <?= $sc['id']===$selectedSub?'selected':''?>>
        <?= htmlspecialchars($sc['nom']) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <select name="brand">
    <option value="">Toutes marques</option>
    <?php foreach ($brands as $br): ?>
      <option value="<?= htmlspecialchars($br) ?>"
        <?= $br === $selectedBrand ? 'selected' : '' ?>>
        <?= htmlspecialchars($br) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <input type="text" name="q" placeholder="Recherche nom ou référence…" 
         value="<?= htmlspecialchars($search) ?>">
  <input type="submit" value="Filtrer">
</form>

<?php if ($selectedCat===null && $selectedBrand===''): ?>
  <p style="text-align:center;margin:40px 0;">
    Sélectionnez une catégorie pour afficher les filtres.
  </p>
<?php elseif (empty($produits)): ?>
  <p style="text-align:center;margin:40px 0;">
    Aucun produit disponible.
  </p>
<?php else: ?>
  <table class="produits-table<?= $afficherTelephonie ? ' telephonie' : '' ?>">
    <thead>
      <tr>
        <th>NOM</th>
        <th>EAN</th>
        <th>NU</th>
        <th>QUANTIT2</th>
        <th>MARQUE</th>
        <th>REFERANCE</th>
        <th class="imei-col">IMEI</th>
        <th class="ecid-col">ECID</th>
        <th class="snie-col">NUMERO DE SERIE</th>
        <th>IMAGE</th>
        <th>QR CODE</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($produits as $p): ?>
        <tr>
          <td><?= htmlspecialchars($p['nom']) ?></td>
          <td><?= htmlspecialchars($p['ean']) ?></td>
          <td><?= htmlspecialchars($p['nu']) ?></td>
          <td><?= (int)$p['quantite'] ?></td>
          <td><?= htmlspecialchars($p['marque']) ?></td>
          <td><?= htmlspecialchars($p['reference']) ?></td>
          <td class="imei-col"><?= htmlspecialchars($p['imei'] ?? '') ?></td>
          <td class="ecid-col"><?= htmlspecialchars($p['ecid'] ?? '') ?></td>
          <td class="snie-col"><?= htmlspecialchars($p['numero_de_serie'] ?? '') ?></td>
          <td>
            <?php for ($i=1; $i<=3; $i++):
              $col = "photo$i";
              if (!empty($p[$col])) {
                $zoomId = "zoom-{$p['id']}-$i";
            ?>
              <a href="#<?= $zoomId ?>"><img src="<?= htmlspecialchars($p[$col]) ?>" alt=""></a>
              <div class="overlay" id="<?= $zoomId ?>">
                <a href="#" class="close-btn">&times;</a>
                <img src="<?= htmlspecialchars($p[$col]) ?>" alt="">
              </div>
            <?php } endfor; ?>
          </td>
          <td>
            <?php if (!empty($p['qr_code'])):
              $zqr = "zoom-qr-{$p['id']}";
            ?>
              <a href="#<?= $zqr ?>"><img src="<?= htmlspecialchars($p['qr_code']) ?>" alt=""></a>
              <div class="overlay" id="<?= $zqr ?>">
                <a href="#" class="close-btn">&times;</a>
                <img src="<?= htmlspecialchars($p['qr_code']) ?>" alt="">
              </div>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

</body>
</html>
