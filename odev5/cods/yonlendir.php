<?php
if(isset($_GET["link"]))
    $link = $_GET["link"];
else
    $link="main";

switch ($link) {
    case "product":
        $page = "product.php";
        break;
    case "aboutus":
    case "ref":
    case "contact":
    case "main":
        $page = "site.php";
        break;
    case "ekle":
        $page="siteEkle.php";
        break;
    case "kaydet":
        $page="siteKaydet.php";
        break;
         case "sil":
        $page = "siteSil.php";
        break;
    case "duzenle":
        $page = "siteDuzenle.php";
        break;
    case "guncelle":
        $page = "siteGuncelle.php";
        break;
    default:    
        $page = "site.php";
        break;
} 