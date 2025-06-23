<?php
// Activer l’affichage des erreurs pour debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['username'], $_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// -- Chargement de Dompdf (Composer ou manuel) --
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/dompdf/autoload.inc.php')) {
    require __DIR__ . '/dompdf/autoload.inc.php';
} elseif (file_exists(__DIR__ . '/dompdf/src/Autoloader.php')) {
    require __DIR__ . '/dompdf/src/Autoloader.php';
    Dompdf\Autoloader::register();
} else {
    die('Erreur : Impossible de trouver l’autoloader de Dompdf. Vérifiez votre installation.');
}

use Dompdf\Dompdf;

// Connexion PDO
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=nuratecstock;charset=utf8",
        "nura_user",            // remplace « root »
        "Nura1939@",            // votre mot de passe
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Erreur BDD : " . $e->getMessage());
}


// Récupérer les IDs sélectionnés
$ids = $_POST['ids'] ?? [];
if (empty($ids)) {
    header('Location: admin.php?section=produits');
    exit;
}

// Préparer la requête
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$sql = "
    SELECT
      nom, ean, nu, quantite, marque, reference,
      imei, ecid, numero_de_serie,
      (SELECT nom FROM categories WHERE id = p.id_categorie)      AS categorie,
      (SELECT nom FROM sous_categories WHERE id = p.id_souscategorie) AS souscategorie
    FROM produits p
    WHERE id IN ($placeholders)
    ORDER BY nom
";
$stmt = $pdo->prepare($sql);
$stmt->execute($ids);
$rows = $stmt->fetchAll();

// Construire le HTML du PDF
$html = '
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><style>
  body{font-family:DejaVu Sans,sans-serif;font-size:12px;margin:0;padding:0;}
  h1{text-align:center;margin-bottom:20px;}
  table{width:100%;border-collapse:collapse;}
  th,td{border:1px solid #444;padding:6px;text-align:center;}
  th{background:#eee;}
</style></head>
<body>
  <h1>Produits sélectionnés</h1>
  <table>
    <tr>
      <th>Nom</th><th>EAN</th><th>NU</th><th>Qté</th><th>Marque</th><th>Réf</th>
      <th>IMEI</th><th>ECID</th><th>N° de série</th><th>Catégorie</th><th>Sous-catégorie</th>
    </tr>';
foreach ($rows as $r) {
    $html .= '<tr>'
      .'<td>'.htmlspecialchars($r['nom']).'</td>'
      .'<td>'.htmlspecialchars($r['ean']).'</td>'
      .'<td>'.htmlspecialchars($r['nu']).'</td>'
      .'<td>'.htmlspecialchars($r['quantite']).'</td>'
      .'<td>'.htmlspecialchars($r['marque']).'</td>'
      .'<td>'.htmlspecialchars($r['reference']).'</td>'
      .'<td>'.htmlspecialchars($r['imei']).'</td>'
      .'<td>'.htmlspecialchars($r['ecid']).'</td>'
      .'<td>'.htmlspecialchars($r['numero_de_serie']).'</td>'
      .'<td>'.htmlspecialchars($r['categorie']).'</td>'
      .'<td>'.htmlspecialchars($r['souscategorie']).'</td>'
    .'</tr>';
}
$html .= '
  </table>
</body>
</html>';

// Générer le PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// Nettoyage du buffer et envoi des headers PDF
if (ob_get_length()) {
    ob_end_clean();
}
header('Content-Type: application/pdf');
header('Cache-Control: no-cache, must-revalidate');

// Affichage inline (pour forcer le téléchargement, passez "Attachment" => true)
$dompdf->stream("produits_selectionnes.pdf", ["Attachment" => false]);
exit;
