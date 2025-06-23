<?php
// logout.php
session_start();
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit();



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