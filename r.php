<?php
/**
 * XNEWS - Reklam Tıklama Sayaci + Yonlendirici
 * Kullanim: /r.php?id=REKLAM_ID
 * Tiklamayi sayar ve reklamin hedef_url'sine yonlendirir.
 */
define('XNEWS', true);
require __DIR__ . '/baglan.php';

$id = (int)($_GET['id'] ?? 0);
if ($id < 1) { http_response_code(400); die('Gecersiz istek.'); }

$stmt = $db->prepare("SELECT id, hedef_url, aktif FROM " . DB_PREFIX . "ads WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$reklam = $stmt->fetch();

if (!$reklam || !$reklam['aktif'] || empty($reklam['hedef_url'])) {
    http_response_code(404);
    die('Reklam bulunamadı veya aktif degil.');
}

// Bot filtresi (basit)
$ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
$bot_mu = preg_match('#bot|crawler|spider|slurp|facebookexternalhit|headless#i', $ua);

// Aynı IP 30 saniye icinde tekrar sayilmasin
$ip = istemci_ip();
$anahtar = 'r_' . $id . '_' . md5($ip);
$simdi = time();

if (!$bot_mu && (empty($_SESSION[$anahtar]) || $_SESSION[$anahtar] < $simdi - 30)) {
    try {
        $db->prepare("UPDATE " . DB_PREFIX . "ads SET tiklanma = tiklanma + 1 WHERE id = ?")->execute([$id]);
        $_SESSION[$anahtar] = $simdi;
    } catch (Throwable $e) { /* sessiz */ }
}

// URL guvenlik: sadece http(s) sema
$hedef = $reklam['hedef_url'];
if (!preg_match('#^https?://#i', $hedef)) {
    http_response_code(400);
    die('Gecersiz hedef URL.');
}

header('Location: ' . $hedef, true, 302);
exit;
