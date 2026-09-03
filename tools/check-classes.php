<?php
/**
 * PSR-4 completeness gate: verifies every TRMEngine\* / NewCMS\* reference
 * found in vendor/trm sources resolves to a real PSR-4 file.
 *
 * Catches "secondary classes defined in a foreign file" (legacy classmap
 * style) that break under composer PSR-4 autoloading.
 *
 * Usage: php tools/check-classes.php <project-root-containing-vendor>
 */

$root = $argv[1] ?? null;
if (!$root || !is_dir($root . '/vendor/trm')) {
  fwrite(STDERR, "Usage: php tools/check-classes.php <root-with-vendor/trm>\n");
  exit(2);
}

$pkg = [
  'TRMEngine\\' => $root . '/vendor/trm/trmengine/',
  'NewCMS\\'    => $root . '/vendor/trm/newcms/',
];

$refs = [];
$defined = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/vendor/trm', FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
  if ($f->getExtension() !== 'php') continue;
  $src = file_get_contents($f->getPathname());
  preg_match_all('/((?:TRMEngine|NewCMS)(?:\\\\[A-Za-z0-9_]+)+)/', $src, $m);
  foreach ($m[1] as $c) {
    $c = trim($c, '\\');
    if (preg_match('/\\\\(Exceptions|Interfaces|Tests)$/', $c)) continue; // namespace-only mentions
    $refs[$c] = true;
  }
  foreach ($pkg as $prefix => $dir) {
    if (strpos($f->getPathname(), $dir) === 0) {
      $rel = substr($f->getPathname(), strlen($dir), -4);
      $defined[] = str_replace('/', '\\', $prefix . $rel);
    }
  }
}
$defined = array_unique($defined);

$real = [];
foreach (array_keys($refs) as $c) {
  $hasFile = false;
  foreach ($pkg as $prefix => $dir) {
    if (strpos($c, $prefix) === 0) {
      $hasFile = is_file($dir . str_replace('\\', '/', substr($c, strlen($prefix))) . '.php');
      break;
    }
  }
  if ($hasFile) continue;
  $isPrefix = false;
  foreach ($defined as $d) {
    if (strpos($d, $c . '\\') === 0) { $isPrefix = true; break; }
  }
  if (!$isPrefix) $real[] = $c;
}
sort($real);

if ($real) {
  echo "PSR-4 completeness check FAILED: ", count($real), " missing class file(s):\n";
  foreach ($real as $c) echo '  ', $c, "\n";
  exit(1);
}
echo "PSR-4 completeness check OK (refs: ", count($refs), ")\n";
