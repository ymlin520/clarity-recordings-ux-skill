<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/refresh-dashboard.php';

$config = clarity_load_config();
$allowOrigin = $config['cors_allow_origin'] ?? '*';
header('Access-Control-Allow-Origin: ' . $allowOrigin);
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    $expected = trim((string)($config['access_token'] ?? ''));
    if ($expected !== '') {
        $provided = (string)($_GET['access_token'] ?? '');
        if (!hash_equals($expected, $provided)) {
            throw new RuntimeException('Unauthorized', 401);
        }
    }

    $cacheFile = (string)($config['cache_file'] ?? (__DIR__ . '/data/dashboard-cache.json'));
    if (!is_file($cacheFile)) {
        if (!empty($config['allow_mock_fallback'])) {
            $payload = clarity_build_payload($config);
            clarity_write_cache($payload, $cacheFile);
        } else {
            throw new RuntimeException('Cache file not found: ' . $cacheFile, 500);
        }
    }

    $raw = file_get_contents($cacheFile);
    if ($raw === false) {
        throw new RuntimeException('Unable to read cache file.', 500);
    }

    echo $raw;
} catch (Throwable $e) {
    http_response_code((int)($e->getCode() >= 400 ? $e->getCode() : 500));
    echo json_encode([
        'status' => 'error',
        'statusLabel' => '載入失敗',
        'statusMessage' => $e->getMessage(),
        'site' => $config['site'] ?? '',
        'projectId' => $config['project_id'] ?? '',
        'rangeLabel' => '未知',
        'summaryCards' => [],
        'issueCards' => [],
        'charts' => [
            'labels' => [],
            'sessions' => [],
            'rageClicks' => [],
            'deadClicks' => [],
            'quickBacks' => [],
            'scrollDepth' => [],
        ],
        'problemPages' => [],
        'recordings' => [],
        'segments' => [
            'devices' => [],
            'sources' => [],
            'landingPages' => [],
        ],
        'recommendations' => [],
        'alerts' => [
            [
                'tone' => 'warn',
                'message' => 'API 錯誤：' . $e->getMessage(),
            ],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}
