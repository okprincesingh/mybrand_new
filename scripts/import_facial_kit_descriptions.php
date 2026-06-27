<?php
require __DIR__ . '/../includes/db.php';

$pdo = db();
if (!$pdo) {
    fwrite(STDERR, "DB connection failed.\n");
    exit(1);
}

$jsonPath = __DIR__ . '/facial_kit_descriptions.json';
if (!is_file($jsonPath)) {
    fwrite(STDERR, "JSON not found: {$jsonPath}\n");
    exit(1);
}

$data = json_decode((string) file_get_contents($jsonPath), true);
if (!is_array($data) || !isset($data['items']) || !is_array($data['items'])) {
    fwrite(STDERR, "Invalid JSON format.\n");
    exit(1);
}

function normalize_name(string $name): string
{
    $name = trim($name);
    $name = str_replace(["\xE2\x80\x93", "\xE2\x80\x94", '???', '???', '?', '?'], '-', $name);
    $name = str_replace(['_', '.docx', '.pdf'], ' ', $name);
    $name = preg_replace('/\s+/', ' ', $name) ?? $name;
    return strtolower(trim($name));
}

function clean_text(string $text): string
{
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace(['???', '???', '???', '???', '???', '??\x9d', '???'], ['-', '-', "'", "'", '"', '"', '...'], $text);
    $text = preg_replace('/\s+/', ' ', $text) ?? $text;
    return trim($text);
}

function split_step_chunks(string $raw): array
{
    $raw = clean_text($raw);
    if ($raw === '') return [];

    $parts = preg_split('/\bStep\s*:\s*/i', $raw);
    if (!$parts) return [];

    $chunks = [];
    foreach ($parts as $idx => $part) {
        $part = trim($part);
        if ($part === '') continue;

        if ($idx === 0) {
            // Intro before first step (product title/tagline etc.)
            $chunks[] = ['no' => 0, 'text' => $part];
            continue;
        }

        if (preg_match('/^(\d+)\s*[-?]?\s*(.*)$/s', $part, $m)) {
            $chunks[] = ['no' => (int)$m[1], 'text' => trim((string)$m[2])];
        } else {
            $chunks[] = ['no' => $idx, 'text' => $part];
        }
    }

    return $chunks;
}

function extract_between(string $text, string $startMarker, string $endMarker): string
{
    $startPos = stripos($text, $startMarker);
    if ($startPos === false) return '';

    $startPos += strlen($startMarker);
    $tail = substr($text, $startPos);
    if ($tail === false) return '';

    $endPos = stripos($tail, $endMarker);
    if ($endPos === false) return trim($tail);

    return trim(substr($tail, 0, $endPos));
}

function split_ingredients(string $ingredients): array
{
    $ingredients = trim($ingredients, " .\t\n\r\0\x0B");
    if ($ingredients === '') return [];

    $parts = preg_split('/\s*,\s*/', $ingredients) ?: [];
    $clean = [];
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p === '') continue;
        $p = preg_replace('/\s+/', ' ', $p) ?? $p;
        if ($p === '') continue;
        $clean[] = $p;
    }
    return $clean;
}

function format_description_html(string $raw): string
{
    $raw = clean_text($raw);
    if ($raw === '') {
        return '<h3>Description</h3><p>No description available.</p>';
    }

    $chunks = split_step_chunks($raw);
    if (!$chunks) {
        return '<h3>Description</h3><p>' . htmlspecialchars($raw, ENT_QUOTES, 'UTF-8') . '</p>';
    }

    $directions = [];
    $ingredientMap = [];
    $stepDetails = [];

    foreach ($chunks as $chunk) {
        $no = (int)$chunk['no'];
        $txt = clean_text((string)$chunk['text']);
        if ($txt === '') continue;

        $descPart = $txt;
        $ingPart = '';
        $dirPart = '';

        if (stripos($txt, 'INGREDIENTS:') !== false) {
            $descPart = trim((string)preg_split('/INGREDIENTS\s*:/i', $txt, 2)[0]);
            $ingPart = extract_between($txt, 'INGREDIENTS:', 'DIRECTION FOR USE:');
        }

        if (stripos($txt, 'DIRECTION FOR USE:') !== false) {
            $dirPart = extract_between($txt, 'DIRECTION FOR USE:', 'Step:');
        }

        if ($dirPart !== '') {
            if ($no > 0) {
                $directions[] = '<li><strong>Step ' . $no . ':</strong> ' . htmlspecialchars($dirPart, ENT_QUOTES, 'UTF-8') . '</li>';
            } else {
                $directions[] = '<li>' . htmlspecialchars($dirPart, ENT_QUOTES, 'UTF-8') . '</li>';
            }
        }

        foreach (split_ingredients($ingPart) as $ing) {
            $k = strtolower($ing);
            if (!isset($ingredientMap[$k])) {
                $ingredientMap[$k] = $ing;
            }
        }

        if ($no > 0) {
            $stepDetails[] = '<li><strong>Step ' . $no . ':</strong> ' . htmlspecialchars($descPart, ENT_QUOTES, 'UTF-8') . '</li>';
        }
    }

    if (!$directions) {
        // Fallback: preserve all step text so no data is lost.
        foreach ($chunks as $chunk) {
            if ((int)$chunk['no'] <= 0) continue;
            $directions[] = '<li><strong>Step ' . (int)$chunk['no'] . ':</strong> ' . htmlspecialchars(clean_text((string)$chunk['text']), ENT_QUOTES, 'UTF-8') . '</li>';
        }
    }

    $ingredientHtml = '';
    foreach (array_slice(array_values($ingredientMap), 0, 40) as $ing) {
        $ingredientHtml .= '<li>' . htmlspecialchars($ing, ENT_QUOTES, 'UTF-8') . '</li>';
    }

    $html = '';
    $html .= '<h3>Description</h3>';
    if ($stepDetails) {
        $html .= '<ul class="desc-steps">' . implode('', $stepDetails) . '</ul>';
    }

    $html .= '<h4>Directions To Use</h4>';
    $html .= '<ul class="desc-directions">' . implode('', $directions) . '</ul>';

    $html .= '<h4>Key Ingredients</h4>';
    $html .= '<p><em>*This Product is Non-Vegan Formulation*</em></p>';
    $html .= '<p><strong>Key Ingredients:</strong></p>';
    if ($ingredientHtml !== '') {
        $html .= '<ul class="desc-ingredients">' . $ingredientHtml . '</ul>';
    } else {
        $html .= '<p>No ingredient list available.</p>';
    }

    $html .= '<h4>Important Considerations</h4>';
    $html .= '<p><strong>Note:</strong> Use only as directed. Avoid contact with eyes. If irritation occurs, discontinue use and consult a physician.</p>';

    return $html;
}

$stmtProducts = $pdo->query("SELECT p.id, p.name FROM products p JOIN categories c ON c.id=p.category_id JOIN categories pc ON pc.id=c.parent_id WHERE c.name='Facials Kits' AND pc.name='Skin Care'");
$products = $stmtProducts ? $stmtProducts->fetchAll(PDO::FETCH_ASSOC) : [];
$map = [];
foreach ($products as $p) {
    $map[normalize_name((string)$p['name'])] = (int)$p['id'];
}

$update = $pdo->prepare('UPDATE products SET description = :description WHERE id = :id');

$updated = 0;
$notMatched = [];
$emptyText = [];

$pdo->beginTransaction();
try {
    foreach ($data['items'] as $item) {
        $stem = (string)($item['stem'] ?? '');
        $desc = trim((string)($item['description'] ?? ''));
        $file = (string)($item['file'] ?? $stem);

        if ($desc === '') {
            $emptyText[] = $file;
            continue;
        }

        $key = normalize_name($stem);
        if (!isset($map[$key])) {
            $notMatched[] = $file;
            continue;
        }

        $formatted = format_description_html($desc);

        $update->execute([
            ':description' => $formatted,
            ':id' => $map[$key],
        ]);
        $updated += $update->rowCount();
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'Failed: ' . $e->getMessage() . "\n");
    exit(1);
}

echo "Done\n";
echo "Products in scope: " . count($products) . "\n";
echo "JSON items: " . count($data['items']) . "\n";
echo "Updated: {$updated}\n";
echo "Not matched: " . count($notMatched) . "\n";
foreach ($notMatched as $f) {
    echo "  - {$f}\n";
}
echo "Empty text: " . count($emptyText) . "\n";
foreach ($emptyText as $f) {
    echo "  - {$f}\n";
}
?>
