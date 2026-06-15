<?php
/**
 * ╔══════════════════════════════════════════════════════════════════════════╗
 * ║           FILE MANAGER ULTIMATE - ALL IN ONE (File-Based Storage)        ║
 * ║                                                                          ║
 * ║  NO DATABASE! File-based storage di folder tersembunyi .fm_data/        ║
 * ║                                                                          ║
 * ║  Features:                                                              ║
 * ║  ✅ File Manager (Upload/Delete/Rename)                                ║
 * ║  ✅ Admin Panel (Settings via UI)                                       ║
 * ║  ✅ Telegram Notifications                                             ║
 * ║  ✅ Security Monitor (Detect deleted & recreated files)                ║
 * ║  ✅ Audit Logging                                                       ║
 * ║  ✅ File-Based Storage (No Database!)                                  ║
 * ║  ✅ Hidden Folder (.fm_data/)                                          ║
 * ║                                                                          ║
 * ║  Storage Location:                                                      ║
 * ║  .fm_data/settings.json      → Settings                                ║
 * ║  .fm_data/logs.txt           → Activity logs                           ║
 * ║  .fm_data/alerts.json        → Security alerts                         ║
 * ║  .fm_data/critical_files.json → File tracking                          ║
 * ║                                                                          ║
 * ║  Access:                                                                ║
 * ║  Browser: http://domain.com/fm.php                                     ║
 * ║  Cron:    php /var/www/html/fm.php monitor                             ║
 * ║                                                                          ║
 * ║  Cron Setup:                                                            ║
 * ║  */5 * * * * php /var/www/html/fm.php monitor                          ║
 * ║                                                                          ║
 * ║  Version: 5.0 ALL-IN-ONE (File-Based, No Database)                    ║
 * ╚══════════════════════════════════════════════════════════════════════════╝
 */

session_start();

// ==================== KONFIGURASI ====================

$data_dir = __DIR__ . '/.fm_data';
$uploads_dir = __DIR__ . '/uploads';
$settings_file = $data_dir . '/settings.json';
$logs_file = $data_dir . '/logs.txt';
$alerts_file = $data_dir . '/alerts.json';
$critical_files_file = $data_dir . '/critical_files.json';

// ==================== INIT DATA FOLDER ====================

function init_data_folder() {
    global $data_dir, $settings_file;
    
    if (!is_dir($data_dir)) {
        mkdir($data_dir, 0700, true);
        chmod($data_dir, 0700);
    }
    
    if (!is_dir(__DIR__ . '/uploads')) {
        mkdir(__DIR__ . '/uploads', 0755, true);
    }
    
    if (!file_exists($settings_file)) {
        $defaults = [
            'admin_password' => 'admin123',
            'enable_telegram' => '0',
            'telegram_token' => '',
            'telegram_chat_id' => '',
            'site_title' => 'File Manager',
            'max_file_size' => '10485760',
            'allowed_extensions' => 'jpg,jpeg,png,gif,pdf,doc,docx,txt,zip',
            'enable_logging' => '1',
        ];
        file_put_contents($settings_file, json_encode($defaults, JSON_PRETTY_PRINT));
        chmod($settings_file, 0600);
    }
}

init_data_folder();

// ==================== SETTINGS ====================

function get_settings() {
    global $settings_file;
    if (!file_exists($settings_file)) return [];
    $content = file_get_contents($settings_file);
    return json_decode($content, true) ?: [];
}

function get_setting($key, $default = null) {
    $settings = get_settings();
    return $settings[$key] ?? $default;
}

function set_setting($key, $value) {
    global $settings_file;
    $settings = get_settings();
    $settings[$key] = $value;
    file_put_contents($settings_file, json_encode($settings, JSON_PRETTY_PRINT));
    chmod($settings_file, 0600);
    return true;
}

// ==================== LOGGING ====================

function log_action($action, $filename = '', $status = 'INFO', $details = '') {
    global $logs_file;
    if (!get_setting('enable_logging', '1')) return;
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = get_client_ip();
    $username = $_SESSION['username'] ?? 'ANONYMOUS';
    $log_line = "[$timestamp] $username | $action | $filename | $status | IP: $ip | $details\n";
    
    file_put_contents($logs_file, $log_line, FILE_APPEND);
    chmod($logs_file, 0600);
}

function get_logs($limit = 100) {
    global $logs_file;
    if (!file_exists($logs_file)) return [];
    $lines = file($logs_file, FILE_IGNORE_NEW_LINES);
    return array_slice($lines, -$limit);
}

// ==================== ALERTS ====================

function add_alert($alert_type, $filename, $original_path, $new_path = null, $message = '', $severity = 'WARNING') {
    global $alerts_file;
    
    $alerts = [];
    if (file_exists($alerts_file)) {
        $content = file_get_contents($alerts_file);
        $alerts = json_decode($content, true) ?: [];
    }
    
    $alert = [
        'timestamp' => date('Y-m-d H:i:s'),
        'alert_type' => $alert_type,
        'filename' => $filename,
        'original_path' => $original_path,
        'new_path' => $new_path,
        'message' => $message,
        'severity' => $severity,
    ];
    
    $alerts[] = $alert;
    if (count($alerts) > 1000) {
        $alerts = array_slice($alerts, -1000);
    }
    
    file_put_contents($alerts_file, json_encode($alerts, JSON_PRETTY_PRINT));
    chmod($alerts_file, 0600);
}

function get_alerts($limit = 50) {
    global $alerts_file;
    if (!file_exists($alerts_file)) return [];
    $content = file_get_contents($alerts_file);
    $alerts = json_decode($content, true) ?: [];
    return array_slice($alerts, -$limit);
}

// ==================== CRITICAL FILES ====================

function get_critical_files() {
    global $critical_files_file;
    if (!file_exists($critical_files_file)) return [];
    $content = file_get_contents($critical_files_file);
    return json_decode($content, true) ?: [];
}

function update_critical_file($filename, $data) {
    global $critical_files_file;
    $files = get_critical_files();
    $files[$filename] = $data;
    file_put_contents($critical_files_file, json_encode($files, JSON_PRETTY_PRINT));
    chmod($critical_files_file, 0600);
}

function get_critical_file($filename) {
    $files = get_critical_files();
    return $files[$filename] ?? null;
}

// ==================== TELEGRAM ====================

function send_telegram($message, $type = 'info') {
    $enabled = get_setting('enable_telegram', '0');
    $token = get_setting('telegram_token', '');
    $chat_id = get_setting('telegram_chat_id', '');
    
    if (!$enabled || !$token || !$chat_id) return false;
    
    $emoji = [
        'upload' => '📤',
        'delete' => '🗑️',
        'login' => '🔓',
        'deleted' => '🚨',
        'recreated' => '⚠️',
        'suspicious_copy' => '🔴',
    ];
    
    $prefix = $emoji[$type] ?? '📢';
    $formatted = "$prefix *FILE MANAGER ALERT*\n\n" . htmlspecialchars($message);
    
    $url = "https://api.telegram.org/bot$token/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $formatted,
        'parse_mode' => 'HTML',
    ];
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query($data),
            'timeout' => 5,
        ]
    ];
    
    $context = stream_context_create($options);
    return @file_get_contents($url, false, $context) !== false;
}

// ==================== HELPERS ====================

function get_client_ip() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) return $_SERVER['HTTP_CF_CONNECTING_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return $_SERVER['HTTP_X_FORWARDED_FOR'];
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}

function format_bytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}

function get_file_hash($filepath) {
    return file_exists($filepath) ? md5_file($filepath) : null;
}

function validate_path($path) {
    global $uploads_dir;
    $real_path = realpath($path);
    $base_real = realpath($uploads_dir);
    if (!$real_path || !$base_real) return null;
    if (strpos($real_path, $base_real) !== 0) return null;
    return $real_path;
}

function is_safe_file($filename) {
    $allowed = array_map('trim', explode(',', get_setting('allowed_extensions', '')));
    $denied = ['exe', 'bat', 'cmd', 'php', 'sh', 'jar'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return !in_array($ext, $denied) && in_array($ext, $allowed);
}

// ==================== SECURITY MONITOR ====================

function monitor_files() {
    global $uploads_dir;
    
    echo "🔒 Security Monitor - " . date('Y-m-d H:i:s') . "\n";
    
    $monitored = ['fm.php'];
    $parent_dir = dirname($uploads_dir);
    
    foreach ($monitored as $filename) {
        $filepath = $parent_dir . DIRECTORY_SEPARATOR . $filename;
        $record = get_critical_file($filename);
        
        if (file_exists($filepath)) {
            $hash = get_file_hash($filepath);
            if ($record && $record['status'] === 'MISSING') {
                echo "✓ FILE RESTORED: $filename\n";
                update_critical_file($filename, [
                    'status' => 'OK',
                    'path' => $filepath,
                    'hash' => $hash,
                    'last_seen' => date('Y-m-d H:i:s'),
                ]);
                add_alert('recreated', $filename, $record['path'], $filepath, "File restored", 'WARNING');
                send_telegram("✓ File $filename restored at $filepath", 'recreated');
            } else {
                if (!$record) {
                    update_critical_file($filename, [
                        'status' => 'OK',
                        'path' => $filepath,
                        'hash' => $hash,
                        'last_seen' => date('Y-m-d H:i:s'),
                    ]);
                }
            }
        } else {
            if ($record && $record['status'] === 'OK') {
                echo "✗ FILE DELETED: $filename\n";
                update_critical_file($filename, [
                    'status' => 'MISSING',
                    'path' => $record['path'],
                    'hash' => $record['hash'],
                    'last_seen' => date('Y-m-d H:i:s'),
                ]);
                add_alert('deleted', $filename, $record['path'], null, "File deleted", 'CRITICAL');
                send_telegram("🚨 CRITICAL FILE DELETED: $filename\nPath: {$record['path']}", 'deleted');
            }
        }
    }
    
    echo "Scanning for suspicious copies...\n";
    
    foreach ($monitored as $filename) {
        $filepath = $parent_dir . DIRECTORY_SEPARATOR . $filename;
        if (!file_exists($filepath)) continue;
        
        $original_hash = get_file_hash($filepath);
        $dirs = ['/var/www/html', '/tmp', '/home', '/var/tmp'];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) continue;
            
            try {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::SELF_FIRST
                );
                
                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->getFilename() === $filename) {
                        $found_path = $file->getRealPath();
                        if (realpath($found_path) === realpath($filepath)) continue;
                        
                        $found_hash = get_file_hash($found_path);
                        if ($found_hash === $original_hash) {
                            echo "⚠️  SUSPICIOUS COPY: $found_path\n";
                            add_alert('suspicious_copy', $filename, $filepath, $found_path, "Suspicious copy found", 'CRITICAL');
                            send_telegram("🔴 SUSPICIOUS COPY FOUND\nFile: $filename\nPath: $found_path", 'suspicious_copy');
                        }
                    }
                }
            } catch (Exception $e) {
                // Skip
            }
        }
    }
    
    echo "✅ Monitor completed\n";
}

// ==================== AUTH ====================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $pass = get_setting('admin_password', 'admin123');
    $input = $_POST['password'] ?? '';
    
    if ($pass === $input) {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = 'admin';
        $_SESSION['login_time'] = time();
        log_action('LOGIN', '', 'SUCCESS', '');
        send_telegram('Admin login dari IP ' . get_client_ip(), 'login');
        header('Location: ?');
        exit;
    } else {
        $error = '❌ Password salah!';
        log_action('LOGIN', '', 'FAILED', '');
    }
}

if (isset($_GET['logout'])) {
    log_action('LOGOUT', '', 'SUCCESS', '');
    session_destroy();
    header('Location: ?');
    exit;
}

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    $site_title = get_setting('site_title', 'File Manager');
    ?>
    <!DOCTYPE html>
    <html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title><?php echo htmlspecialchars($site_title); ?></title><style>*{margin:0;padding:0;box-sizing:border-box}body{background:linear-gradient(135deg,#667eea,#764ba2);min-height:100vh;display:flex;justify-content:center;align-items:center;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif}.login-box{background:white;padding:40px;border-radius:10px;box-shadow:0 10px 30px rgba(0,0,0,0.3);width:100%;max-width:400px}.login-box h1{text-align:center;margin-bottom:30px;color:#333}.form-group{margin-bottom:20px}.form-group label{display:block;margin-bottom:8px;color:#333;font-weight:500}.form-group input{width:100%;padding:12px;border:1px solid #ddd;border-radius:5px}.form-group input:focus{outline:0;border-color:#667eea;box-shadow:0 0 0 2px rgba(102,126,234,0.1)}.btn-login{width:100%;padding:12px;background:linear-gradient(135deg,#667eea,#764ba2);color:white;border:0;border-radius:5px;font-size:16px;font-weight:600;cursor:pointer}.btn-login:hover{opacity:0.9}.alert{padding:12px;background:#f8d7da;color:#721c24;border-radius:5px;margin-bottom:20px}.info{padding:12px;background:#e7f3ff;color:#0056b3;border-radius:5px;margin-top:20px;font-size:12px}</style></head><body><div class="login-box"><h1>🔐 <?php echo htmlspecialchars($site_title); ?></h1><?php if(isset($error)):?><div class="alert"><?php echo htmlspecialchars($error);?></div><?php endif;?><form method="POST"><div class="form-group"><label>Password:</label><input type="password" name="password" required autofocus></div><button type="submit" name="login" class="btn-login">Login</button></form><div class="info">📝 Default: <strong>admin123</strong><br>Ubah di Settings!</div></div></body></html>
    <?php
    exit;
}

// ==================== FILE OPERATIONS ====================

if (!is_dir($uploads_dir)) mkdir($uploads_dir, 0755, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $filename = basename($file['name']);
    
    if (is_safe_file($filename) && $file['size'] <= get_setting('max_file_size')) {
        $target = $uploads_dir . DIRECTORY_SEPARATOR . $filename;
        if (move_uploaded_file($file['tmp_name'], $target)) {
            chmod($target, 0644);
            $success = '✅ File berhasil diupload!';
            log_action('UPLOAD', $filename, 'SUCCESS', '');
            send_telegram("📤 Upload: $filename", 'upload');
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['file'])) {
    $file_path = validate_path($uploads_dir . '/' . $_GET['file']);
    if ($file_path && file_exists($file_path)) {
        $backup_dir = $uploads_dir . '/_backups';
        if (!is_dir($backup_dir)) mkdir($backup_dir, 0700, true);
        
        $backup_path = $backup_dir . '/' . date('Y-m-d_H-i-s') . '_' . basename($file_path);
        
        if (is_dir($file_path)) {
            exec("cp -r " . escapeshellarg($file_path) . " " . escapeshellarg($backup_path));
            exec("rm -rf " . escapeshellarg($file_path));
        } else {
            copy($file_path, $backup_path);
            unlink($file_path);
        }
        
        $success = '✅ File dihapus (backup tersimpan)!';
        log_action('DELETE', basename($file_path), 'SUCCESS', '');
        send_telegram("🗑️ Delete: " . basename($file_path), 'delete');
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'rename' && isset($_POST['old_name']) && isset($_POST['new_name'])) {
    $old_path = validate_path($uploads_dir . '/' . $_POST['old_name']);
    $new_name = basename($_POST['new_name']);
    
    if ($old_path && file_exists($old_path) && is_safe_file($new_name)) {
        $new_path = $uploads_dir . DIRECTORY_SEPARATOR . $new_name;
        if (!file_exists($new_path) && rename($old_path, $new_path)) {
            $success = '✅ File direname!';
            log_action('RENAME', $_POST['old_name'], 'SUCCESS', "→ $new_name");
        }
    }
}

if (isset($_GET['download']) && isset($_GET['file'])) {
    $file_path = validate_path($uploads_dir . '/' . $_GET['file']);
    if ($file_path && is_file($file_path)) {
        log_action('DOWNLOAD', basename($file_path), 'SUCCESS', '');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
        readfile($file_path);
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'mkdir' && isset($_POST['folder_name'])) {
    $folder_name = basename($_POST['folder_name']);
    $folder_path = $uploads_dir . DIRECTORY_SEPARATOR . $folder_name;
    if (!file_exists($folder_path) && mkdir($folder_path, 0755)) {
        $success = '✅ Folder dibuat!';
        log_action('MKDIR', $folder_name, 'SUCCESS', '');
    }
}

if (isset($_GET['page']) && $_GET['page'] === 'settings') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
        if (!empty($_POST['new_password'])) {
            set_setting('admin_password', $_POST['new_password']);
            $success = '✅ Password berhasil diubah!';
        }
        
        set_setting('telegram_token', $_POST['telegram_token'] ?? '');
        set_setting('telegram_chat_id', $_POST['telegram_chat_id'] ?? '');
        set_setting('enable_telegram', isset($_POST['enable_telegram']) ? '1' : '0');
        set_setting('site_title', $_POST['site_title'] ?? 'File Manager');
        set_setting('max_file_size', $_POST['max_file_size'] ?? '10485760');
        set_setting('allowed_extensions', $_POST['allowed_extensions'] ?? '');
        set_setting('enable_logging', isset($_POST['enable_logging']) ? '1' : '0');
    }
    
    $site_title = get_setting('site_title', 'File Manager');
    $telegram_token = get_setting('telegram_token', '');
    $telegram_chat_id = get_setting('telegram_chat_id', '');
    $enable_telegram = get_setting('enable_telegram', '0');
    $max_file_size = get_setting('max_file_size', '10485760');
    $allowed_extensions = get_setting('allowed_extensions', '');
    $enable_logging = get_setting('enable_logging', '1');
    ?>
    <!DOCTYPE html>
    <html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Settings</title><style>*{margin:0;padding:0;box-sizing:border-box}body{background:#f5f5f5;padding:20px;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif}.container{max-width:900px;margin:0 auto;background:white;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1)}.header{background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:20px 25px;display:flex;justify-content:space-between;align-items:center}.content{padding:25px}.form-section{margin-bottom:30px;padding-bottom:30px;border-bottom:1px solid #eee}.form-section h2{font-size:18px;margin-bottom:20px;color:#333}.form-group{margin-bottom:15px}.form-group label{display:block;margin-bottom:5px;color:#555;font-weight:500}.form-group input,.form-group textarea{width:100%;padding:10px;border:1px solid #ddd;border-radius:5px;font-size:14px}.btn-save{background:#667eea;color:white;padding:12px 30px;border:0;border-radius:5px;cursor:pointer;font-size:14px;font-weight:600}.btn-save:hover{background:#764ba2}.btn-back{background:#6c757d;color:white;padding:8px 15px;border:0;border-radius:5px;cursor:pointer;text-decoration:none}.alert-success{padding:15px;background:#d4edda;color:#155724;border:1px solid #c3e6cb;border-radius:5px;margin-bottom:20px}.help-text{font-size:12px;color:#999;margin-top:5px}</style></head><body><div class="container"><div class="header"><h1>⚙️ Settings</h1><a href="?" class="btn-back">← Kembali</a></div><div class="content"><?php if(isset($success)):?><div class="alert-success"><?php echo htmlspecialchars($success);?></div><?php endif;?><form method="POST"><div class="form-section"><h2>🌐 General</h2><div class="form-group"><label>Site Title:</label><input type="text" name="site_title" value="<?php echo htmlspecialchars($site_title);?>"></div><div class="form-group"><label>Max File Size (bytes):</label><input type="number" name="max_file_size" value="<?php echo htmlspecialchars($max_file_size);?>"><div class="help-text">10MB=10485760, 50MB=52428800</div></div><div class="form-group"><label>Allowed Extensions:</label><textarea name="allowed_extensions" placeholder="jpg,png,pdf,doc,docx,txt,zip"><?php echo htmlspecialchars($allowed_extensions);?></textarea></div><div class="form-group"><label><input type="checkbox" name="enable_logging" <?php echo $enable_logging?'checked':'';?>> Enable Logging</label></div></div><div class="form-section"><h2>🔐 Security</h2><div class="form-group"><label>Admin Password Baru:</label><input type="password" name="new_password" placeholder="Biarkan kosong jika tidak ingin ubah"></div></div><div class="form-section"><h2>🤖 Telegram</h2><div class="form-group"><label><input type="checkbox" name="enable_telegram" <?php echo $enable_telegram?'checked':'';?>> Enable Telegram</label></div><div class="form-group"><label>Bot Token:</label><input type="text" name="telegram_token" value="<?php echo htmlspecialchars($telegram_token);?>" placeholder="123456:ABC-DEF..."></div><div class="form-group"><label>Chat ID:</label><input type="text" name="telegram_chat_id" value="<?php echo htmlspecialchars($telegram_chat_id);?>" placeholder="123456789"></div></div><button type="submit" name="update_settings" class="btn-save">💾 Save</button></form></div></div></body></html>
    <?php
    exit;
}

$current_dir = isset($_GET['dir']) ? validate_path($uploads_dir . '/' . $_GET['dir']) : $uploads_dir;
if (!$current_dir) $current_dir = $uploads_dir;

$items = scandir($current_dir);
sort($items);

$site_title = get_setting('site_title', 'File Manager');
$relative_dir = str_replace($uploads_dir, '', $current_dir);
?>

<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title><?php echo htmlspecialchars($site_title);?></title><style>*{margin:0;padding:0;box-sizing:border-box}body{background:#f5f5f5;padding:20px;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif}.container{max-width:1100px;margin:0 auto;background:white;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1)}.header{background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:20px 25px;display:flex;justify-content:space-between;align-items:center}.header h1{font-size:24px}.header-actions a{background:rgba(255,255,255,0.2);color:white;border:1px solid white;padding:8px 15px;margin-left:10px;border-radius:5px;text-decoration:none}.header-actions a:hover{background:rgba(255,255,255,0.3)}.breadcrumb{background:#f9f9f9;padding:15px 25px;border-bottom:1px solid #eee;font-size:14px}.breadcrumb a{color:#667eea;text-decoration:none;margin:0 5px}.breadcrumb span{color:#999;margin:0 5px}.messages{padding:20px 25px}.alert{padding:15px;background:#d4edda;color:#155724;border:1px solid #c3e6cb;border-radius:5px;margin-bottom:15px}.content{padding:25px}.toolbar{display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap}.toolbar button,.toolbar label{padding:10px 15px;background:#667eea;color:white;border:0;border-radius:5px;cursor:pointer}.toolbar button:hover,.toolbar label:hover{background:#764ba2}#file_input{display:none}table{width:100%;border-collapse:collapse}table th{background:#f5f5f5;padding:12px;text-align:left;border-bottom:2px solid #ddd;font-weight:600}table td{padding:12px;border-bottom:1px solid #ddd}table tr:hover{background:#f9f9f9}.action-btn{padding:6px 10px;margin:0 3px;border:0;border-radius:3px;cursor:pointer;text-decoration:none;display:inline-block}.btn-download{background:#28a745;color:white}.btn-delete{background:#dc3545;color:white}.btn-rename{background:#ffc107;color:#333}.modal{display:none;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.4)}.modal-content{background:white;margin:15% auto;padding:20px;border-radius:5px;width:300px}.modal-content input{width:100%;padding:8px;margin:10px 0;border:1px solid #ddd;border-radius:3px}.modal-content button{padding:8px 15px;background:#667eea;color:white;border:0;border-radius:3px;cursor:pointer;margin-right:10px}</style></head><body><div class="container"><div class="header"><h1>📁 <?php echo htmlspecialchars($site_title);?></h1><div class="header-actions"><a href="?page=settings">⚙️ Settings</a><a href="?logout=true">🚪 Logout</a></div></div><div class="breadcrumb"><a href="?">Home</a> <?php $parts=explode(DIRECTORY_SEPARATOR,trim($relative_dir,DIRECTORY_SEPARATOR));$path='';foreach($parts as $part){if($part){$path.=$part.'/';echo '<span>›</span><a href="?dir='.urlencode(trim($path,'/')).'">'.$part.'</a>';}}?></div><?php if(isset($success)):?><div class="messages"><div class="alert"><?php echo htmlspecialchars($success);?></div></div><?php endif;?><div class="content"><div class="toolbar"><label for="file_input">📤 Upload</label><input type="file" id="file_input"><button onclick="showMkdir()">📁 Folder</button></div><form id="upload_form" method="POST" enctype="multipart/form-data" style="display:none"><input type="file" id="upload_file" name="file"></form><?php if(count($items)>2):?><table><thead><tr><th>Nama</th><th>Tipe</th><th>Ukuran</th><th>Modified</th><th>Aksi</th></tr></thead><tbody><?php foreach($items as $item){if($item=='.'||$item=='..'||strpos($item,'_backups')===0)continue;$item_path=$current_dir.DIRECTORY_SEPARATOR.$item;$is_dir=is_dir($item_path);$rel_item_path=trim(str_replace($uploads_dir,'',$current_dir).'/'.$item,'/');$size=$is_dir?'-':round(filesize($item_path)/1024,2).' KB';$type=$is_dir?'Folder':strtoupper(pathinfo($item,PATHINFO_EXTENSION));$modified=date('d/m/Y H:i',filemtime($item_path));?><tr><td><?php if($is_dir):?><a href="?dir=<?php echo urlencode($rel_item_path);?>" style="text-decoration:none;color:#667eea;font-weight:500">📁 <?php echo htmlspecialchars($item);?></a><?php else:?>📄 <?php echo htmlspecialchars($item);?><?php endif;?></td><td><?php echo $type;?></td><td><?php echo $size;?></td><td><?php echo $modified;?></td><td><?php if(!$is_dir):?><a href="?download=true&file=<?php echo urlencode($item);?>&dir=<?php echo urlencode(str_replace($uploads_dir,'',$current_dir));?>" class="action-btn btn-download">⬇️</a><?php endif;?><button class="action-btn btn-rename" onclick="showRename('<?php echo htmlspecialchars($item);?>')">✏️</button><a href="?action=delete&file=<?php echo urlencode($item);?>&dir=<?php echo urlencode(str_replace($uploads_dir,'',$current_dir));?>" class="action-btn btn-delete" onclick="return confirm('Hapus?')">🗑️</a></td></tr><?php }?></tbody></table><?php else:?><div style="text-align:center;padding:40px;color:#999"><p>📭 Folder kosong</p></div><?php endif;?></div></div></div><div id="mkdir_modal" class="modal"><div class="modal-content"><h3>Buat Folder</h3><form method="POST" action="?action=mkdir&dir=<?php echo urlencode(str_replace($uploads_dir,'',$current_dir));?>"><input type="text" name="folder_name" placeholder="Nama" required><button type="submit">Buat</button><button type="button" onclick="closeMkdir()">Batal</button></form></div></div><div id="rename_modal" class="modal"><div class="modal-content"><h3>Rename</h3><form method="POST" action="?action=rename&dir=<?php echo urlencode(str_replace($uploads_dir,'',$current_dir));?>"><input type="hidden" id="rename_old" name="old_name"><input type="text" id="rename_new" name="new_name" required><button type="submit">Rename</button><button type="button" onclick="closeRename()">Batal</button></form></div></div><script>document.getElementById('file_input').addEventListener('change',function(){document.getElementById('upload_file').files=this.files;document.getElementById('upload_form').submit()});function showMkdir(){document.getElementById('mkdir_modal').style.display='block'}function closeMkdir(){document.getElementById('mkdir_modal').style.display='none'}function showRename(name){document.getElementById('rename_old').value=name;document.getElementById('rename_new').value=name;document.getElementById('rename_modal').style.display='block';document.getElementById('rename_new').select()}function closeRename(){document.getElementById('rename_modal').style.display='none'}window.onclick=function(e){if(e.target.id==='mkdir_modal')closeMkdir();if(e.target.id==='rename_modal')closeRename()}</script></body></html>

<?php
if (php_sapi_name() === 'cli') {
    $arg = $argv[1] ?? '';
    if ($arg === 'monitor') {
        monitor_files();
    } else {
        echo "File Manager - CLI\n";
        echo "Usage: php fm.php monitor\n";
    }
}
