<?php
declare(strict_types=1);

// EXTRA-001 regression: source files must be clean UTF-8 without BOM.
//  - No UTF-8 BOM (a BOM in a view is echoed as U+FEFF into every page).
//  - No double encoding (UTF-8 read as cp1252 and written back as UTF-8):
//    "Código" -> "CÃ³digo", "—" -> "â€”", "™" -> "â„¢", "®" -> "Â®".
//  - No U+FFFD replacement characters and no invalid UTF-8.
// Scans app/, public/ and database/. The only allowed exceptions are files that
// contain mojibake on purpose, as the patterns they repair.
// COH-008: the population filter/column is "Población"; no "sub canal" (any
// spelling: "subcanal", "sub-canal", "Sub Canal") may remain in app/ or public/.
// Run: php tests/source_encoding.php

const SCANNED_DIRS = ['app', 'public', 'database'];
const RETIRED_LABEL_DIRS = ['app', 'public'];
const RETIRED_LABEL = '/sub[\s_-]*canal/iu';
const TEXT_EXTENSIONS = ['php', 'js', 'css', 'html', 'sql', 'json', 'md', 'csv', 'txt', 'htaccess'];
const ALLOWED_MOJIBAKE = [
    'app/Controllers/CohortController.php',              // normalizeText() repair map
    'database/migrations/008_fix_cohort_text_encoding.sql', // REPLACE() targets
];
// "Ã" or "Â" followed by a byte that cp1252 decoded as U+0080-U+00BF or as one of
// its punctuation characters, plus the 3-byte lead "â€"/"â„"/"â”" (dashes, quotes,
// ™, box drawing).
const MOJIBAKE = '/[ÃÂ][\x{0080}-\x{00BF}\x{0152}\x{0153}\x{0160}\x{0161}\x{0178}\x{017D}\x{017E}\x{0192}\x{02C6}\x{02DC}\x{2013}\x{2014}\x{2018}-\x{201E}\x{2020}-\x{2022}\x{2026}\x{2030}\x{2039}\x{203A}\x{20AC}\x{2122}]|â[€„”]/u';

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $message);
    }
}

// The detector itself must catch the known damage and leave clean Spanish alone.
foreach (['CÃ³digo', 'IdentificaciÃ³n', "Ã\u{0081}rea", 'â€”', 'BDPCâ„¢', 'CAIPCÂ®', 'â”€â”€'] as $broken) {
    check(preg_match(MOJIBAKE, $broken) === 1, "detector misses {$broken}");
}
foreach (['Código', 'Identificación', 'Área', 'Límite Admisión', 'Número', 'año', '—', '™', '®', '───'] as $clean) {
    check(preg_match(MOJIBAKE, $clean) === 0, "detector flags clean text {$clean}");
}
foreach (['Poblacion o sub canal', 'Población o Sub Canal', 'subcanal', 'sub-canal', 'SUB_CANAL'] as $retired) {
    check(preg_match(RETIRED_LABEL, $retired) === 1, "retired label detector misses {$retired}");
}
foreach (['Población', 'canal B2B', 'canales de medición'] as $allowed) {
    check(preg_match(RETIRED_LABEL, $allowed) === 0, "retired label detector flags {$allowed}");
}

$root = dirname(__DIR__);
$problems = [];
$scanned = 0;

foreach (SCANNED_DIRS as $dir) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $dir, FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        if (!$file->isFile() || !in_array(strtolower($file->getExtension()), TEXT_EXTENSIONS, true)) {
            continue;
        }
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
        $content = (string) file_get_contents($file->getPathname());
        $scanned++;

        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $problems[] = "{$relative}: UTF-8 BOM";
        }
        if (!mb_check_encoding($content, 'UTF-8')) {
            $problems[] = "{$relative}: invalid UTF-8";
            continue;
        }
        if (str_contains($content, "\u{FFFD}")) {
            $problems[] = "{$relative}: U+FFFD replacement character";
        }
        if (in_array($dir, RETIRED_LABEL_DIRS, true)) {
            foreach (preg_split('/\R/', $content) as $index => $line) {
                if (preg_match(RETIRED_LABEL, $line, $match) === 1) {
                    $problems[] = sprintf('%s:%d: retired label "%s" (COH-008: use "Población")', $relative, $index + 1, $match[0]);
                }
            }
        }
        if (!in_array($relative, ALLOWED_MOJIBAKE, true)) {
            foreach (preg_split('/\R/', $content) as $index => $line) {
                if (preg_match(MOJIBAKE, $line, $match) === 1) {
                    $problems[] = sprintf('%s:%d: double encoding "%s"', $relative, $index + 1, $match[0]);
                }
            }
        }
    }
}

check($scanned > 0, 'no files scanned');
check($problems === [], count($problems) . " encoding problem(s):\n  " . implode("\n  ", $problems));

echo "PASS: {$scanned} files in app/, public/, database/ are UTF-8 without BOM, double encoding or U+FFFD (allowed: " . implode(', ', ALLOWED_MOJIBAKE) . "); no \"sub canal\" in app/ or public/.\n";
