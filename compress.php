<?php
if (!isset($argv[1])) {
    echo "Usage: php ${argv[0]} [filename]\n";
    exit(1);
}
$filename = $argv[1];
if (!file_exists($filename)) {
    echo "ERROR: file '$filename' not found\n";
    exit(1);
}
$content = file_get_contents($filename);
$before = strlen($content);
if (strpos($content, '__halt_compiler();')) {
    $action = 'uncompressed';
    $content = explode('__halt_compiler();', $content)[1];
    $content = gzinflate($content);
    $after = strlen($content);
} else {
    $action = 'compressed';
    $content = gzdeflate($content);
    $start = <<<'EOF'
$f = fopen(__FILE__, 'r');
fseek($f, __COMPILER_HALT_OFFSET__);
$t = tmpfile();
$u = stream_get_meta_data($t)['uri'];
fwrite($t, gzinflate(stream_get_contents($f)));
include($u);
fclose($t);
__halt_compiler();
EOF;
    $content = '<?php ' . str_replace([' ', "\n"], '', $start) . $content;
}
$after = strlen($content);
file_put_contents($filename, $content);
$percentage = (int) (($after * 100) / $before);
$before = (int) ($before / 1024);
$after = (int) ($after / 1024);
echo "$action '$filename' from $before to $after kbyte ($percentage%)\n";
