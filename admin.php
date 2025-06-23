<?php
// === ENDPOINT AJAX POUR RÉCUPÉRER LES SOUS-CATÉGORIES ===
if (isset($_GET['ajax']) && $_GET['ajax'] === 'souscats') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $pdoAjax = new PDO(
            "mysql:host=localhost;dbname=nuratecstock;charset=utf8",
            "nura_user",    // remplace « root »
            "Nura1939@",    // votre mot de passe
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    } catch (PDOException $e) {
        // En cas d’erreur de connexion, on renvoie un JSON vide
        http_response_code(500);
        echo json_encode([]);
        exit;
    }
    $idCat = isset($_GET['categorie']) && ctype_digit($_GET['categorie'])
        ? (int)$_GET['categorie'] : 0;
    $stmt = $pdoAjax->prepare("SELECT id, nom FROM sous_categories WHERE id_categorie = ?");
    $stmt->execute([$idCat]);
    echo json_encode($stmt->fetchAll());
    exit;
}

// === BACK-OFFICE PRINCIPAL ===
session_start();
if (!isset($_SESSION['username'], $_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Connexion PDO
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=nuratecstock;charset=utf8",
        "root",
        "Nura1939@",
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Librairie QR Code
require __DIR__ . '/phpqrcode/qrlib.php';

// Charger catégories pour formulaires
$categories = $pdo->query("SELECT id,nom FROM categories ORDER BY nom")->fetchAll();

// — GESTION UTILISATEURS —
if (isset($_POST['ajouter_utilisateur'])) {
    $u = trim($_POST['username']);
    $p = $_POST['password'];
    $r = $_POST['role'];
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username=?");
    $cnt->execute([$u]);
    if ($cnt->fetchColumn()>0) {
        $errorUsers = "Identifiant déjà pris.";
    } else {
        $h = password_hash($p,PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users(username,password,role) VALUES(?,?,?)")
            ->execute([$u,$h,$r]);
        header('Location: admin.php?section=utilisateurs');
        exit;
    }
}
if (isset($_POST['supprimer_user_id'])) {
    $pdo->prepare("DELETE FROM users WHERE id=?")
        ->execute([(int)$_POST['supprimer_user_id']]);
    header('Location: admin.php?section=utilisateurs');
    exit;
}

// — GESTION CATEGORIES —
if (isset($_POST['ajouter_categorie'])) {
    $pdo->prepare("INSERT INTO categories(nom) VALUES(?)")
        ->execute([$_POST['nom_categorie']]);
    header('Location: admin.php?section=categories');
    exit;
}
if (isset($_POST['supprimer_categorie_id'])) {
    $idCat = (int)$_POST['supprimer_categorie_id'];
    $f = $pdo->prepare("SELECT photo1,photo2,photo3,qr_code FROM produits WHERE id_categorie=?");
    $f->execute([$idCat]);
    foreach($f as $row){
        foreach(['photo1','photo2','photo3','qr_code'] as $c){
            if (!empty($row[$c]) && file_exists(__DIR__.'/'.$row[$c])) {
                unlink(__DIR__.'/'.$row[$c]);
            }
        }
    }
    $pdo->prepare("DELETE FROM produits WHERE id_categorie=?")->execute([$idCat]);
    $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$idCat]);
    header('Location: admin.php?section=categories');
    exit;
}

// — GESTION SOUS-CATEGORIES —
if (isset($_POST['ajouter_souscategorie'])) {
    $pdo->prepare("INSERT INTO sous_categories(nom,id_categorie) VALUES(?,?)")
        ->execute([trim($_POST['nom_souscategorie']),(int)$_POST['categorie_parente']]);
    header('Location: admin.php?section=categories');
    exit;
}
if (isset($_POST['supprimer_souscategorie_id'])) {
    $pdo->prepare("DELETE FROM sous_categories WHERE id=?")
        ->execute([(int)$_POST['supprimer_souscategorie_id']]);
    header('Location: admin.php?section=categories');
    exit;
}

// — GESTION FOURNISSEURS —
if (isset($_POST['ajouter_fournisseur'])) {
    $pdo->prepare("INSERT INTO fournisseurs(nom,adresse,telephone,email) VALUES(?,?,?,?)")
        ->execute([
            $_POST['nom_fournisseur'],
            $_POST['adresse_fournisseur'],
            $_POST['tel_fournisseur'],
            $_POST['email_fournisseur']
        ]);
    header('Location: admin.php?section=fournisseurs');
    exit;
}
if (isset($_POST['supprimer_fournisseur_id'])) {
    $pdo->prepare("DELETE FROM fournisseurs WHERE id=?")
        ->execute([(int)$_POST['supprimer_fournisseur_id']]);
    header('Location: admin.php?section=fournisseurs');
    exit;
}

// — GESTION CLIENTS —
if (isset($_POST['ajouter_client'])) {
    $pdo->prepare("INSERT INTO clients(nom,adresse,telephone,email) VALUES(?,?,?,?)")
        ->execute([
            $_POST['nom_client'],
            $_POST['adresse_client'],
            $_POST['tel_client'],
            $_POST['email_client']
        ]);
    header('Location: admin.php?section=clients');
    exit;
}
if (isset($_POST['supprimer_client_id'])) {
    $pdo->prepare("DELETE FROM clients WHERE id=?")
        ->execute([(int)$_POST['supprimer_client_id']]);
    header('Location: admin.php?section=clients');
    exit;
}

// — GESTION PRODUITS (AJOUT / MODIF / SUPP) —
if (isset($_POST['ajouter_produit']) || isset($_POST['modifier_produit'])) {
    $files=['photo1'=>'','photo2'=>'','photo3'=>''];
    for($i=1;$i<=3;$i++){
        $f="photo$i";
        if(!empty($_FILES[$f]['name'])){
            if(!is_dir(__DIR__.'/images')) mkdir(__DIR__.'/images',0755,true);
            $files[$f]='images/'.time()."_{$f}_".basename($_FILES[$f]['name']);
            move_uploaded_file($_FILES[$f]['tmp_name'],__DIR__.'/'.$files[$f]);
        }
    }
    // téléphonie
    $imei=$ecid=$serie=null;
    $cat=(int)$_POST['categorie'];
    $ncat=$pdo->prepare("SELECT nom FROM categories WHERE id=?");
    $ncat->execute([$cat]);
    if($ncat->fetchColumn()==='téléphonie'){
        $imei=$_POST['imei']??null;
        $ecid=$_POST['ecid']??null;
        $serie=$_POST['numero_de_serie']??null;
    }
    $sous = !empty($_POST['souscategorie']) ? (int)$_POST['souscategorie'] : null;

    if (isset($_POST['ajouter_produit'])) {
        $pdo->prepare("
            INSERT INTO produits(
              nom, ean, nu, quantite, marque, reference,
              id_categorie, id_souscategorie, imei, ecid, numero_de_serie,
              photo1, photo2, photo3
            ) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        )->execute([
            $_POST['nom_produit'],
            $_POST['ean'],
            $_POST['nu'],
            (int)$_POST['quantite'],
            $_POST['marque'],
            $_POST['reference'],
            $cat, $sous, $imei, $ecid, $serie,
            $files['photo1'], $files['photo2'], $files['photo3']
        ]);
        $lastId = $pdo->lastInsertId();
        $qrDir = __DIR__.'/qr/'; if(!is_dir($qrDir)) mkdir($qrDir,0755,true);
        $url = sprintf(
            "%s://%s%s/index.php?id=%d",
            (!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http',
            $_SERVER['HTTP_HOST'],dirname($_SERVER['PHP_SELF']).'/', $lastId
        );
        QRcode::png($url, $qrDir."qr_{$lastId}.png", QR_ECLEVEL_L, 4);
        $pdo->prepare("UPDATE produits SET qr_code=? WHERE id=?")
            ->execute(["qr/qr_{$lastId}.png", $lastId]);
    } else {
        // UPDATE
        $idp=(int)$_POST['id_produit'];
        $sql="UPDATE produits SET
            nom=?, ean=?, nu=?, quantite=?, marque=?, reference=?,
            id_categorie=?, id_souscategorie=?, imei=?, ecid=?, numero_de_serie=?";
        foreach(['photo1','photo2','photo3'] as $f){
            if($files[$f]!=='') $sql.=", $f=?";
        }
        $sql.=" WHERE id=?";
        $params=[
            $_POST['nom_produit'], $_POST['ean'], $_POST['nu'], (int)$_POST['quantite'],
            $_POST['marque'], $_POST['reference'], $cat, $sous, $imei, $ecid, $serie
        ];
        foreach(['photo1','photo2','photo3'] as $f){
            if($files[$f]!=='') $params[]=$files[$f];
        }
        $params[]=$idp;
        $pdo->prepare($sql)->execute($params);
    }
    header('Location: admin.php?section=produits');
    exit;
}

// SUPPRESSION PRODUIT
if(isset($_POST['supprimer_id'])){
    $d=$pdo->prepare("SELECT photo1,photo2,photo3,qr_code FROM produits WHERE id=?");
    $d->execute([$_POST['supprimer_id']]);
    $r=$d->fetch();
    foreach(['photo1','photo2','photo3','qr_code'] as $c){
        if(!empty($r[$c])&&file_exists(__DIR__.'/'.$r[$c])){
            unlink(__DIR__.'/'.$r[$c]);
        }
    }
    $pdo->prepare("DELETE FROM produits WHERE id=?")
        ->execute([(int)$_POST['supprimer_id']]);
    header('Location: admin.php?section=produits');
    exit;
}

// CHANGEMENT QUANTITÉ
if(isset($_POST['changer_quantite'],$_POST['id_produit'])){
    $id=(int)$_POST['id_produit']; $op=$_POST['changer_quantite'];
    $q=(int)$pdo->query("SELECT quantite FROM produits WHERE id=$id")->fetchColumn();
    if($op==='plus') $q++; elseif($op==='moins'&&$q>0) $q--;
    $pdo->prepare("UPDATE produits SET quantite=? WHERE id=?")->execute([$q,$id]);
    header('Location: admin.php?section=produits');
    exit;
}
if(isset($_POST['modifier_quantite'],$_POST['nouvelle_quantite'],$_POST['id_produit'])){
    $id=(int)$_POST['id_produit']; $n=max(0,(int)$_POST['nouvelle_quantite']);
    $pdo->prepare("UPDATE produits SET quantite=? WHERE id=?")->execute([$n,$id]);
    header('Location: admin.php?section=produits');
    exit;
}

// Section à afficher
$section = $_GET['section'] ?? 'produits';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Admin – Nuratec Stock</title>
  <style>
    :root{--accent:#FFD600;--bg-light:#f1f2f6;--bg-white:#fff;--text:#2d3436;--radius:6px;--shadow:0 2px 6px rgba(0,0,0,0.1)}
    *,*::before,*::after{margin:0;padding:0;box-sizing:border-box;font-weight:bold;}
    body{background:var(--bg-light);color:var(--text);font-family:"Segoe UI",sans-serif;font-size:13px;}
    header{background:var(--bg-white);border-bottom:4px solid var(--accent);box-shadow:var(--shadow);padding:12px;display:flex;justify-content:center;position:relative;}
    header a.btn-switch{position:absolute;left:12px;top:12px;background:var(--accent);color:#fff;padding:6px 12px;border-radius:var(--radius);text-decoration:none;}
    header a.btn-switch:hover{background:#FFC400;}
    header a.logout{position:absolute;right:12px;top:12px;background:rgba(255,255,255,0.8);color:var(--text);border:2px solid var(--accent);padding:6px 10px;border-radius:var(--radius);text-decoration:none;}
    header a.logout:hover{background:var(--accent);color:#fff;}
    .navbar{display:flex;justify-content:center;gap:8px;padding:8px;background:var(--bg-white);box-shadow:var(--shadow);}
    .navbar a{padding:4px 8px;border-radius:var(--radius);text-decoration:none;color:var(--text);transition:.2s;}
    .navbar a.active,.navbar a:hover{background:var(--accent);color:#fff;}
    .filter-bar{display:flex;justify-content:center;gap:8px;margin:16px;font-size:.85em;}
    .filter-bar select,.filter-bar input[type=text]{padding:4px;border:1px solid #ccc;border-radius:var(--radius);}
    .filter-bar input[type=submit]{padding:4px 8px;background:var(--accent);border:none;color:#fff;cursor:pointer;border-radius:var(--radius);}
    .filter-bar input[type=submit]:hover{background:#FFC400;}
    .section-container{display:flex;align-items:flex-start;gap:16px;overflow-x:auto;}
    .form-left{width:280px;background:var(--bg-white);padding:12px;border-radius:var(--radius);box-shadow:var(--shadow);}
    .form-left h2{color:var(--accent);margin-bottom:8px;}
    .form-left input,.form-left select,.form-left button{width:100%;margin:6px 0;padding:4px;font-size:.8em;border:1px solid #ccc;border-radius:var(--radius);}
    .form-left button{background:var(--accent);color:#fff;border:none;cursor:pointer;}
    .form-left button:hover{background:#FFC400;}
    table{width:100%;border-collapse:collapse;background:var(--bg-white);box-shadow:var(--shadow);font-size:.8em;}
    th,td{padding:4px;text-align:center;border-bottom:1px solid #eee;}
    th{background:var(--accent);color:#fff;font-weight:normal;}
    tr:last-child td{border-bottom:none;}
    td img{max-width:40px;border-radius:var(--radius);cursor:zoom-in;}
    .overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);align-items:center;justify-content:center;z-index:1000;}
    .overlay:target{display:flex;}
    .overlay img{max-width:95vw;max-height:95vh;transform:scale(1);transition:transform .3s;}
    .overlay:target img{transform:scale(2);}
    .overlay .close-btn{position:absolute;top:12px;right:20px;color:#fff;font-size:1.5rem;text-decoration:none;}
    .overlay .close-btn:hover{color:var(--accent);}
    td:empty{visibility:hidden;}
  </style>
</head>
<body>

<header>
  Admin – Nuratec Stock
  <a href="index.php" class="btn-switch">Vue Client</a>
  <a href="logout.php" class="logout">Déconnexion</a>
</header>

<nav class="navbar">
  <a href="admin.php?section=produits"     class="<?= $section==='produits'?'active':'' ?>">Produits</a>
  <a href="admin.php?section=fournisseurs" class="<?= $section==='fournisseurs'?'active':'' ?>">Fournisseurs</a>
  <a href="admin.php?section=clients"      class="<?= $section==='clients'?'active':'' ?>">Clients</a>
  <a href="admin.php?section=categories"   class="<?= $section==='categories'?'active':'' ?>">Catégories</a>
  <a href="admin.php?section=utilisateurs" class="<?= $section==='utilisateurs'?'active':'' ?>">Utilisateurs</a>
</nav>

<?php if($section==='produits'): ?>

  <!-- Section Produits -->
  <form method="get" class="filter-bar">
    <input type="hidden" name="section" value="produits">
    <select name="filtre_categorie">
      <option value="">Toutes catégories</option>
      <?php foreach($categories as $c): ?>
        <option value="<?= $c['id'] ?>" <?= (($_GET['filtre_categorie']??'')==$c['id'])?'selected':'' ?>>
          <?= htmlspecialchars($c['nom']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <input type="text" name="recherche" placeholder="Recherche…" value="<?= htmlspecialchars($_GET['recherche']??'') ?>">
    <input type="submit" value="Filtrer">
  </form>

  <div class="section-container">

    <!-- Formulaire Ajout / Édition -->
    <div class="form-left">
      <?php if(isset($_GET['edit_produit_id'])):
        $stmtE=$pdo->prepare("SELECT * FROM produits WHERE id=?");
        $stmtE->execute([$_GET['edit_produit_id']]);
        $prod=$stmtE->fetch();
        $stmtSC=$pdo->prepare("SELECT id,nom FROM sous_categories WHERE id_categorie=?");
        $stmtSC->execute([$prod['id_categorie']]);
        $souscats=$stmtSC->fetchAll();
      ?>
        <h2>Modifier produit</h2>
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="id_produit" value="<?= $prod['id'] ?>">
          <input type="text" name="nom_produit"      value="<?= htmlspecialchars($prod['nom']) ?>" placeholder="Nom" required>
          <input type="text" name="ean"               value="<?= htmlspecialchars($prod['ean']) ?>" placeholder="EAN" required>
          <input type="text" name="nu"                value="<?= htmlspecialchars($prod['nu']) ?>" placeholder="NU">
          <input type="number" name="quantite"       value="<?= $prod['quantite'] ?>" placeholder="Quantité" min="0" required>
          <input type="text" name="marque"            value="<?= htmlspecialchars($prod['marque']) ?>" placeholder="Marque" required>
          <input type="text" name="reference"        value="<?= htmlspecialchars($prod['reference']) ?>" placeholder="Référence" required>

          <label>Catégorie</label>
          <select name="categorie" id="cat-select" required>
            <option value="">Sélectionnez</option>
            <?php foreach($categories as $c): ?>
              <option value="<?= $c['id'] ?>" <?= $c['id']==$prod['id_categorie']?'selected':'' ?>>
                <?= htmlspecialchars($c['nom']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <label>Sous-catégorie <small>(facultatif)</small></label>
          <select name="souscategorie" id="souscat-select">
            <option value="">Aucune</option>
            <?php foreach($souscats as $sc): ?>
              <option value="<?= $sc['id'] ?>" <?= $sc['id']==$prod['id_souscategorie']?'selected':'' ?>>
                <?= htmlspecialchars($sc['nom']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <div id="telephonie-fields" style="display:<?= strtolower($pdo->query("SELECT nom FROM categories WHERE id={$prod['id_categorie']}")->fetchColumn())==='téléphonie'?'block':'none' ?>;margin:8px 0;">
            <input type="text" name="imei"            value="<?= htmlspecialchars($prod['imei']??'') ?>" placeholder="IMEI">
            <input type="text" name="ecid"            value="<?= htmlspecialchars($prod['ecid']??'') ?>" placeholder="ECID">
            <input type="text" name="numero_de_serie" value="<?= htmlspecialchars($prod['numero_de_serie']??'') ?>" placeholder="N° de série">
          </div>

          <?php for($i=1;$i<=3;$i++): $col="photo$i"; ?>
            <p>Photo <?= $i ?> actuelle :</p>
            <?= $prod[$col] ? '<img src="'.htmlspecialchars($prod[$col]).'">' : '<span style="color:#888">Aucune</span>' ?>
            <input type="file" name="<?= $col ?>" accept="image/*">
          <?php endfor; ?>

          <p>QR Code actuel :</p>
          <?= $prod['qr_code'] ? '<img src="'.htmlspecialchars($prod['qr_code']).'">' : '<span style="color:#888">Aucun</span>' ?>

          <button type="submit" name="modifier_produit">Enregistrer</button>
        </form>
      <?php else: ?>
        <h2>Ajouter un produit</h2>
        <form method="post" enctype="multipart/form-data">
          <input type="text" name="nom_produit" placeholder="Nom" required>
          <input type="text" name="ean"          placeholder="EAN" required>
          <input type="text" name="nu"           placeholder="NU">
          <input type="number" name="quantite"   placeholder="Quantité" min="0" required>
          <input type="text" name="marque"       placeholder="Marque" required>
          <input type="text" name="reference"    placeholder="Référence" required>

          <label>Catégorie</label>
          <select name="categorie" id="cat-select" required>
            <option value="">Sélectionnez</option>
            <?php foreach($categories as $c): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nom']) ?></option>
            <?php endforeach; ?>
          </select>

          <label>Sous-catégorie <small>(facultatif)</small></label>
          <select name="souscategorie" id="souscat-select" disabled>
            <option value="">Aucune</option>
          </select>

          <div id="telephonie-fields" style="display:none;margin:8px 0;">
            <input type="text" name="imei"            placeholder="IMEI">
            <input type="text" name="ecid"            placeholder="ECID">
            <input type="text" name="numero_de_serie" placeholder="N° de série">
          </div>

          <p>Photo 1 : <input type="file" name="photo1" accept="image/*"></p>
          <p>Photo 2 : <input type="file" name="photo2" accept="image/*"></p>
          <p>Photo 3 : <input type="file" name="photo3" accept="image/*"></p>

          <button type="submit" name="ajouter_produit">Ajouter</button>
        </form>
      <?php endif; ?>
    </div>

    <!-- Tableau Produits -->
    <div style="flex:1;overflow-x:auto;">
      <form method="post" action="export_pdf.php" id="export-form">
        <button type="submit" name="export_pdf" style="margin-bottom:8px;">🖨️ Exporter en PDF</button>
        <table>
          <tr>
            <th><input type="checkbox" id="select-all"></th>
            <th>NOM</th><th>EAN</th><th>NU</th><th>QTE</th><th>MARQUE</th><th>REF</th>
            <th>IMEI</th><th>ECID</th><th>N° DE SERIE</th><th>CATEGORIE</th><th>SOUS-CATEGORIE</th>
            <th>PHOTOS</th><th>QR CODE</th><th>ACTIONS</th>
          </tr>
          <?php
          $sql="SELECT p.*,c.nom AS cat,s.nom AS souscat
                FROM produits p
                LEFT JOIN categories c ON p.id_categorie=c.id
                LEFT JOIN sous_categories s ON p.id_souscategorie=s.id
                WHERE 1";
          $params=[];
          if(!empty($_GET['filtre_categorie'])) {
            $sql.=" AND p.id_categorie=?";
            $params[]=(int)$_GET['filtre_categorie'];
          }
          if(!empty($_GET['recherche'])) {
            $sql.=" AND (p.nom LIKE ? OR p.ean LIKE ? OR p.nu LIKE ?)";
            $search="%".$_GET['recherche']."%";
            $params[]=$search; $params[]=$search; $params[]=$search;
          }
          $stmtP=$pdo->prepare($sql);
          $stmtP->execute($params);
          foreach($stmtP->fetchAll() as $r): ?>
            <tr>
              <td><input type="checkbox" name="ids[]" value="<?= $r['id'] ?>"></td>
              <td><?= htmlspecialchars($r['nom']) ?></td>
              <td><?= htmlspecialchars($r['ean']) ?></td>
              <td><?= htmlspecialchars($r['nu']) ?></td>
              <td>
                <form method="post" style="display:inline">
                  <button name="changer_quantite" value="moins">−</button>
                  <input type="hidden" name="id_produit" value="<?= $r['id'] ?>">
                </form>
                <form method="post" style="display:inline">
                  <input type="number" name="nouvelle_quantite" value="<?= $r['quantite'] ?>" style="width:40px">
                  <button name="modifier_quantite">✔</button>
                  <input type="hidden" name="id_produit" value="<?= $r['id'] ?>">
                </form>
                <form method="post" style="display:inline">
                  <button name="changer_quantite" value="plus">＋</button>
                  <input type="hidden" name="id_produit" value="<?= $r['id'] ?>">
                </form>
              </td>
              <td><?= htmlspecialchars($r['marque']) ?></td>
              <td><?= htmlspecialchars($r['reference']) ?></td>
              <td><?= htmlspecialchars($r['imei'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['ecid'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['numero_de_serie'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['cat']) ?></td>
              <td><?= htmlspecialchars($r['souscat'] ?? '') ?></td>
              <td>
                <?php for($i=1;$i<=3;$i++): $col="photo$i"; if(!empty($r[$col])): ?>
                  <a href="#zoom-<?= "{$r['id']}-$i" ?>"><img src="<?= $r[$col] ?>"></a>
                  <div class="overlay" id="zoom-<?= "{$r['id']}-$i" ?>">
                    <a href="#" class="close-btn">&times;</a>
                    <img src="<?= $r[$col] ?>">
                  </div>
                <?php endif; endfor; ?>
              </td>
              <td>
                <?php if(!empty($r['qr_code'])): ?>
                  <a href="#zoom-qr-<?= $r['id'] ?>"><img src="<?= $r['qr_code'] ?>"></a>
                  <div class="overlay" id="zoom-qr-<?= $r['id'] ?>">
                    <a href="#" class="close-btn">&times;</a>
                    <img src="<?= $r['qr_code'] ?>">
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <a href="admin.php?section=produits&edit_produit_id=<?= $r['id'] ?>">Modifier</a>
                <form method="post" style="display:inline" onsubmit="return confirm('Supprimer ?')">
                  <input type="hidden" name="supprimer_id" value="<?= $r['id'] ?>">
                  <button type="submit">Supprimer</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
      </form>
    </div>

  </div>

  <script>
    document.getElementById('select-all').addEventListener('change', function(){
      document.querySelectorAll('#export-form input[name="ids[]"]')
        .forEach(cb => cb.checked = this.checked);
    });
    document.getElementById('cat-select').addEventListener('change', function(){
      const id   = this.value,
            sous = document.getElementById('souscat-select'),
            tel  = document.getElementById('telephonie-fields');
      tel.style.display = this.options[this.selectedIndex].text.toLowerCase()==='téléphonie'?'block':'none';
      if (!id) {
        sous.innerHTML = '<option value="">Aucune</option>';
        sous.disabled = true;
        return;
      }
      sous.innerHTML = '<option>Chargement…</option>';
      sous.disabled = true;
      fetch('admin.php?ajax=souscats&categorie=' + id)
        .then(r => r.ok ? r.json() : Promise.reject())
        .then(data => {
          sous.innerHTML = '<option value="">Aucune</option>';
          data.forEach(sc => {
            const o = document.createElement('option');
            o.value = sc.id; o.textContent = sc.nom;
            sous.appendChild(o);
          });
          sous.disabled = false;
        })
        .catch(() => {
          sous.innerHTML = '<option value="">Erreur</option>';
        });
    });
  </script>

<?php elseif($section==='fournisseurs'): ?>

  <div class="section-container">
    <form method="post" class="form-left">
      <h2>Ajouter fournisseur</h2>
      <input type="text" name="nom_fournisseur" placeholder="Nom" required>
      <input type="text" name="adresse_fournisseur" placeholder="Adresse">
      <input type="text" name="tel_fournisseur" placeholder="Téléphone">
      <input type="email" name="email_fournisseur" placeholder="Email">
      <button type="submit" name="ajouter_fournisseur">Ajouter</button>
    </form>
    <table>
      <tr><th>Nom</th><th>Adresse</th><th>Téléphone</th><th>Email</th><th>Action</th></tr>
      <?php foreach($pdo->query("SELECT * FROM fournisseurs") as $f): ?>
        <tr>
          <td><?= htmlspecialchars($f['nom']) ?></td>
          <td><?= htmlspecialchars($f['adresse']) ?></td>
          <td><?= htmlspecialchars($f['telephone']) ?></td>
          <td><?= htmlspecialchars($f['email']) ?></td>
          <td>
            <form method="post" style="display:inline" onsubmit="return confirm('Supprimer ?')">
              <input type="hidden" name="supprimer_fournisseur_id" value="<?= $f['id'] ?>">
              <button type="submit">Supprimer</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>

<?php elseif($section==='clients'): ?>

  <div class="section-container">
    <form method="post" class="form-left">
      <h2>Ajouter client</h2>
      <input type="text" name="nom_client" placeholder="Nom" required>
      <input type="text" name="adresse_client" placeholder="Adresse">
      <input type="text" name="tel_client" placeholder="Téléphone">
      <input type="email" name="email_client" placeholder="Email">
      <button type="submit" name="ajouter_client">Ajouter</button>
    </form>
    <table>
      <tr><th>Nom</th><th>Adresse</th><th>Téléphone</th><th>Email</th><th>Action</th></tr>
      <?php foreach($pdo->query("SELECT * FROM clients") as $c): ?>
        <tr>
          <td><?= htmlspecialchars($c['nom']) ?></td>
          <td><?= htmlspecialchars($c['adresse']) ?></td>
          <td><?= htmlspecialchars($c['telephone']) ?></td>
          <td><?= htmlspecialchars($c['email']) ?></td>
          <td>
            <form method="post" style="display:inline" onsubmit="return confirm('Supprimer ?')">
              <input type="hidden" name="supprimer_client_id" value="<?= $c['id'] ?>">
              <button type="submit">Supprimer</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>

<?php elseif($section==='categories'): ?>

  <div class="section-container">
    <form method="post" class="form-left">
      <h2>Ajouter catégorie</h2>
      <input type="text" name="nom_categorie" placeholder="Nom" required>
      <button type="submit" name="ajouter_categorie">Ajouter</button>
    </form>
    <form method="post" class="form-left">
      <h2>Ajouter sous-catégorie</h2>
      <select name="categorie_parente" required>
        <option value="">Catégorie parente</option>
        <?php foreach($categories as $c): ?>
          <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nom']) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" name="nom_souscategorie" placeholder="Nom sous-catégorie" required>
      <button type="submit" name="ajouter_souscategorie">Ajouter</button>
    </form>
    <table>
      <tr><th>Catégorie</th><th>Action</th></tr>
      <?php foreach($pdo->query("SELECT * FROM categories") as $cat): ?>
        <tr>
          <td><?= htmlspecialchars($cat['nom']) ?></td>
          <td>
            <form method="post" style="display:inline" onsubmit="return confirm('Supprimer catégorie & produits ?')">
              <input type="hidden" name="supprimer_categorie_id" value="<?= $cat['id'] ?>">
              <button type="submit">Supprimer</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
    <table>
      <tr><th>Sous-catégorie</th><th>Catégorie parente</th><th>Action</th></tr>
      <?php
      $sql="SELECT s.id,s.nom AS sous,c.nom AS parente
            FROM sous_categories s
            JOIN categories c ON s.id_categorie=c.id";
      foreach($pdo->query($sql) as $sc): ?>
        <tr>
          <td><?= htmlspecialchars($sc['sous']) ?></td>
          <td><?= htmlspecialchars($sc['parente']) ?></td>
          <td>
            <form method="post" style="display:inline" onsubmit="return confirm('Supprimer sous-catégorie ?')">
              <input type="hidden" name="supprimer_souscategorie_id" value="<?= $sc['id'] ?>">
              <button type="submit">Supprimer</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>

<?php elseif($section==='utilisateurs'): ?>

  <div class="section-container">
    <form method="post" class="form-left">
      <h2>Ajouter utilisateur</h2>
      <?php if(!empty($errorUsers)): ?><p style="color:red"><?= htmlspecialchars($errorUsers) ?></p><?php endif; ?>
      <input type="text" name="username" placeholder="Identifiant" required>
      <input type="password" name="password" placeholder="Mot de passe" required>
      <select name="role" required>
        <option value="">Rôle</option>
        <option value="admin">Admin</option>
        <option value="client">Client</option>
      </select>
      <button type="submit" name="ajouter_utilisateur">Ajouter</button>
    </form>
    <table>
      <tr><th>Identifiant</th><th>Rôle</th><th>Action</th></tr>
      <?php foreach($pdo->query("SELECT id,username,role FROM users") as $u): ?>
        <tr>
          <td><?= htmlspecialchars($u['username']) ?></td>
          <td><?= htmlspecialchars($u['role']) ?></td>
          <td>
            <form method="post" style="display:inline" onsubmit="return confirm('Supprimer utilisateur ?')">
              <input type="hidden" name="supprimer_user_id" value="<?= $u['id'] ?>">
              <button type="submit">Supprimer</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>

<?php endif; ?>

</body>
</html>
