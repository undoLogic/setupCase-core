<?php
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$domain   = $_SERVER['HTTP_HOST'];
$path     = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$basePath = preg_replace('#/init-web/5\.x$#', '', $path);

$url = $protocol . $domain . $basePath . '/sourceFiles/CodeBlocks';
header('Location: ' . $url);
exit;
