<?php
/**
 * Content Draft Manager
 * ---------------------
 * Reusable Draft/Publish system for any content type.
 *
 * How it works:
 *  - Admin edits a record -> draft JSON is stored in content_drafts.
 *    The real table row is NOT modified.
 *  - Live site reads real tables directly -> 100% unchanged.
 *  - Preview mode (session or request-scoped) merges draft JSON over the
 *    real row so the admin sees exactly what they are about to publish.
 *  - "Publish" copies the draft fields into the real table columns, then
 *    deletes the draft row.
 *  - "Discard / Reset Draft" deletes the draft row; preview reverts to the
 *    published state.
 *
 * Safety:
 *  - No ALTER TABLE on existing content tables.
 *  - Real rows are never touched until an explicit Publish action.
 *  - Drafts are isolated in their own table.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/preview.php';

if (!function_exists('draft_ensure_table')) {
    function draft_ensure_table(?PDO $pdo = null): bool
    {
        static $checked = null;
        if ($checked !== null) {
            return $checked;
        }

        $pdo = $pdo ?: db();
        if (!$pdo) {
            $checked = false;
            return false;
        }

        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS content_drafts (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    content_type VARCHAR(80) NOT NULL,
                    entity_id BIGINT UNSIGNED NOT NULL,
                    draft_data LONGTEXT NOT NULL,
                    updated_by BIGINT UNSIGNED NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_content_draft (content_type, entity_id),
                    INDEX idx_content_drafts_type (content_type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            $checked = true;
            return true;
        } catch (Throwable $e) {
            $checked = false;
            return false;
        }
    }
}

if (!function_exists('draft_get_raw')) {
    /**
     * Always read draft JSON from DB (no preview-mode gate).
     * Used by publish / status / admin APIs.
     */
    function draft_get_raw(string $contentType, int $entityId): ?array
    {
        $pdo = db();
        if (!$pdo || !draft_ensure_table($pdo)) {
            return null;
        }

        $contentType = substr(trim($contentType), 0, 80);
        $entityId = max(0, $entityId);
        $stmt = $pdo->prepare('SELECT draft_data FROM content_drafts WHERE content_type = :ct AND entity_id = :eid LIMIT 1');
        $stmt->execute([
            ':ct' => $contentType,
            ':eid' => $entityId,
        ]);
        $row = $stmt->fetch();
        if (!$row || empty($row['draft_data'])) {
            return null;
        }

        $decoded = json_decode((string) $row['draft_data'], true);
        return is_array($decoded) ? $decoded : null;
    }
}

if (!function_exists('draft_save')) {
    /**
     * Save a draft for a content type + entity id.
     * Merges with any existing draft so partial updates do not wipe fields.
     *
     * @param string $contentType e.g. 'home_hero_video','home_slide','product','page','blog','category'
     * @param int $entityId The real table row id (0 for singletons).
     * @param array $data field=>value overrides
     * @param int|null $updatedBy admin user id.
     */
    function draft_save(string $contentType, int $entityId, array $data, ?int $updatedBy = null): bool
    {
        $pdo = db();
        if (!$pdo || !draft_ensure_table($pdo)) {
            return false;
        }

        $contentType = substr(trim($contentType), 0, 80);
        $entityId = max(0, $entityId);

        $existing = draft_get_raw($contentType, $entityId);
        if (is_array($existing) && $existing) {
            $data = array_merge($existing, $data);
        }

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return false;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO content_drafts (content_type, entity_id, draft_data, updated_by)
             VALUES (:ct, :eid, :data, :uby)
             ON DUPLICATE KEY UPDATE draft_data = VALUES(draft_data), updated_by = VALUES(updated_by), updated_at = NOW()'
        );

        return $stmt->execute([
            ':ct' => $contentType,
            ':eid' => $entityId,
            ':data' => $json,
            ':uby' => $updatedBy,
        ]);
    }
}

if (!function_exists('draft_get')) {
    /**
     * Get draft overrides only when preview mode is ON (for public/CMS reads).
     */
    function draft_get(string $contentType, int $entityId): ?array
    {
        if (!preview_mode_enabled()) {
            return null;
        }

        return draft_get_raw($contentType, $entityId);
    }
}

if (!function_exists('draft_has')) {
    /**
     * Check if a draft exists for a content type + entity id.
     */
    function draft_has(string $contentType, int $entityId): bool
    {
        $pdo = db();
        if (!$pdo || !draft_ensure_table($pdo)) {
            return false;
        }

        $entityId = max(0, $entityId);
        $count = (int) (db_fetch_value($pdo, 'SELECT COUNT(*) FROM content_drafts WHERE content_type = :ct AND entity_id = :eid', [
            ':ct' => $contentType,
            ':eid' => $entityId,
        ]) ?? 0);
        return $count > 0;
    }
}

if (!function_exists('draft_discard')) {
    /**
     * Discard / reset a draft. Preview reverts to the published state.
     */
    function draft_discard(string $contentType, int $entityId): bool
    {
        $pdo = db();
        if (!$pdo || !draft_ensure_table($pdo)) {
            return false;
        }

        $entityId = max(0, $entityId);
        $stmt = $pdo->prepare('DELETE FROM content_drafts WHERE content_type = :ct AND entity_id = :eid');
        return $stmt->execute([
            ':ct' => $contentType,
            ':eid' => $entityId,
        ]);
    }
}

if (!function_exists('draft_publish')) {
    /**
     * Publish a draft: copy draft fields into the real table row, then delete the draft.
     *
     * @param string $contentType
     * @param int $entityId
     * @param callable $publishFn fn(\PDO $pdo, int $entityId, array $draftData): bool
     */
    function draft_publish(string $contentType, int $entityId, callable $publishFn): bool
    {
        $pdo = db();
        if (!$pdo || !draft_ensure_table($pdo)) {
            return false;
        }

        $entityId = max(0, $entityId);
        // Always read raw draft — publish must work even when session preview is off.
        $draftData = draft_get_raw($contentType, $entityId);
        if ($draftData === null) {
            return false;
        }

        if (!$publishFn($pdo, $entityId, $draftData)) {
            return false;
        }

        return draft_discard($contentType, $entityId);
    }
}

if (!function_exists('draft_list_types')) {
    /**
     * List all content types that currently have drafts (for showing draft badges).
     */
    function draft_list_types(): array
    {
        $pdo = db();
        if (!$pdo || !draft_ensure_table($pdo)) {
            return [];
        }

        $rows = db_fetch_all($pdo, 'SELECT content_type, entity_id, updated_at FROM content_drafts ORDER BY updated_at DESC');
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'content_type' => (string) ($row['content_type'] ?? ''),
                'entity_id' => (int) ($row['entity_id'] ?? 0),
                'updated_at' => (string) ($row['updated_at'] ?? ''),
            ];
        }
        return $out;
    }
}

if (!function_exists('draft_merge_row')) {
    /**
     * Merge a draft over a real published row when preview mode is active.
     * Returns the merged array; if no draft exists (or preview is off), returns the original row.
     */
    function draft_merge_row(array $row, string $contentType, int $entityId): array
    {
        $draft = draft_get($contentType, $entityId);
        if (!is_array($draft) || !$draft) {
            return $row;
        }

        return array_merge($row, $draft);
    }
}

if (!function_exists('draft_merge_rows')) {
    /**
     * Merge drafts over a list of rows (each row must contain 'id').
     */
    function draft_merge_rows(array $rows, string $contentType): array
    {
        if (!preview_mode_enabled()) {
            return $rows;
        }

        $out = [];
        foreach ($rows as $row) {
            $entityId = (int) ($row['id'] ?? 0);
            $out[] = draft_merge_row((array) $row, $contentType, $entityId);
        }
        return $out;
    }
}

if (!function_exists('draft_normalize_form_data')) {
    /**
     * Map form field names (existing_*, file inputs) onto real table column names.
     *
     * @param string $contentType
     * @param array $raw Form/API payload
     * @param array $columns Allowed real columns for this content type
     * @param array $aliases Map of form field name => column name
     * @return array Clean column=>value map
     */
    function draft_normalize_form_data(string $contentType, array $raw, array $columns, array $aliases = []): array
    {
        $mapped = $raw;

        foreach ($aliases as $from => $to) {
            if (array_key_exists($from, $raw) && !array_key_exists($to, $mapped)) {
                $mapped[$to] = $raw[$from];
            }
            // Prefer alias value when both exist and column is empty
            if (array_key_exists($from, $raw) && array_key_exists($to, $mapped)) {
                $colVal = is_string($mapped[$to] ?? null) ? trim((string) $mapped[$to]) : $mapped[$to];
                $aliasVal = $raw[$from];
                if (($colVal === '' || $colVal === null) && $aliasVal !== '' && $aliasVal !== null) {
                    $mapped[$to] = $aliasVal;
                }
            }
        }

        // Common convention: existing_<column> -> <column>
        foreach ($columns as $col) {
            $existingKey = 'existing_' . $col;
            if (array_key_exists($existingKey, $raw)) {
                $colVal = is_string($mapped[$col] ?? null) ? trim((string) $mapped[$col]) : ($mapped[$col] ?? null);
                $existingVal = $raw[$existingKey];
                if (!array_key_exists($col, $mapped) || $colVal === '' || $colVal === null) {
                    if ($existingVal !== '' && $existingVal !== null) {
                        $mapped[$col] = $existingVal;
                    }
                }
            }
        }

        $clean = [];
        foreach ($columns as $col) {
            if (array_key_exists($col, $mapped)) {
                $val = $mapped[$col];
                if (is_bool($val)) {
                    $clean[$col] = $val ? '1' : '0';
                } elseif (is_array($val)) {
                    continue;
                } else {
                    $clean[$col] = (string) $val;
                }
            }
        }

        return $clean;
    }
}
