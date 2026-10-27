<?php
// router.php untuk Wasmer Edge Laravel
$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

// Jika file statis yang diminta benar-benar ada di folder public, akses langsung
if ($uri !== '/' && file_exists(__DIR__.'/public'.$uri)) {
    return false;
}

// Alihkan semua request lainnya ke public/index.php
require_once __DIR__.'/public/index.php';