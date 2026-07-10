<?php
declare(strict_types=1);

function clarity_load_config(): array
{
    $configFile = __DIR__ . '/config.php';
    $exampleConfigFile = __DIR__ . '/config.example.php';
    return file_exists($configFile) ? require $configFile : require $exampleConfigFile;
}

function clarity_load_json_file(string $path): array
{
    if ($path === '' || !file_exists($path)) {
        throw new RuntimeException('JSON payload file not found: ' . $path);
    }
    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new RuntimeException('Unable to read JSON payload file.');
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        throw new RuntimeException('Invalid JSON payload file.');
    }
    return $data;
}

function clarity_detect_http_status(array $headers): int
{
    foreach ($headers as $line) {
        if (preg_match('#^HTTP/\\S+\\s+(\\d{3})#', $line, $m)) {
            return (int)$m[1];
        }
    }
    return 200;
}

function clarity_load_remote_json(array $config): array
{
    $baseUrl = trim((string)($config['remote_json_url'] ?? ''));
    if ($baseUrl === '') {
        throw new RuntimeException('remote_json_url is empty.');
    }

    $query = $config['remote_query'] ?? [];
    if (!is_array($query)) {
        $query = [];
    }

    $url = $baseUrl;
    if ($query !== []) {
        $qs = http_build_query($query);
        $url .= (str_contains($baseUrl, '?') ? '&' : '?') . $qs;
    }

    $headers = [
        'Accept: application/json',
        'User-Agent: ClarityUxInsightsSkill/1.0',
    ];

    $bearer = trim((string)($config['remote_bearer_token'] ?? ''));
    if ($bearer !== '') {
        $headers[] = 'Authorization: Bearer ' . $bearer;
    }

    foreach (($config['remote_headers'] ?? []) as $header) {
        if (is_string($header) && trim($header) !== '') {
            $headers[] = $header;
        }
    }

    $method = strtoupper((string)($config['remote_method'] ?? 'GET'));
    $context = stream_context_create([
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'timeout' => 25,
            'ignore_errors' => true,
        ],
    ]);

    $raw = @file_get_contents($url, false, $context);
    if ($raw === false) {
        throw new RuntimeException('Unable to fetch remote JSON.');
    }

    $status = clarity_detect_http_status($http_response_header ?? []);
    if ($status >= 400) {
        throw new RuntimeException('Remote JSON API returned HTTP ' . $status);
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        throw new RuntimeException('Remote JSON API did not return valid JSON.');
    }

    return $data;
}

function clarity_ensure_array(mixed $value): array
{
    return is_array($value) ? array_values($value) : [];
}

function clarity_enrich_payload(array $payload, array $config): array
{
    $tz = new DateTimeZone((string)($config['timezone'] ?? 'Asia/Taipei'));
    $now = new DateTimeImmutable('now', $tz);

    $payload['dashboardTitle'] = (string)($config['dashboard_title'] ?? ($payload['dashboardTitle'] ?? '網站 UX 行為洞察儀表板'));
    $payload['site'] = (string)($config['site'] ?? ($payload['site'] ?? ''));
    $payload['projectId'] = (string)($config['project_id'] ?? ($payload['projectId'] ?? ''));
    $payload['status'] = (string)($payload['status'] ?? 'ok');
    $payload['statusLabel'] = (string)($payload['statusLabel'] ?? '資料已同步');
    $payload['statusMessage'] = (string)($payload['statusMessage'] ?? 'Clarity UX 行為資料已完成更新');
    $payload['rangeLabel'] = (string)($payload['rangeLabel'] ?? '近 7 天');
    $payload['summaryCards'] = clarity_ensure_array($payload['summaryCards'] ?? []);
    $payload['issueCards'] = clarity_ensure_array($payload['issueCards'] ?? []);
    $payload['charts'] = is_array($payload['charts'] ?? null) ? $payload['charts'] : [];
    $payload['problemPages'] = clarity_ensure_array($payload['problemPages'] ?? []);
    $payload['recordings'] = clarity_ensure_array($payload['recordings'] ?? []);
    $payload['segments'] = is_array($payload['segments'] ?? null) ? $payload['segments'] : ['devices' => [], 'sources' => [], 'landingPages' => []];
    $payload['recommendations'] = clarity_ensure_array($payload['recommendations'] ?? []);
    $payload['alerts'] = clarity_ensure_array($payload['alerts'] ?? []);
    $payload['refreshMeta'] = [
        'refreshedAtIso' => $now->format(DateTimeInterface::ATOM),
        'refreshedAtLabel' => $now->format('Y-m-d H:i:s'),
        'timezone' => $tz->getName(),
        'sourceMode' => (string)($config['mode'] ?? 'mock'),
        'dailySchedule' => sprintf('%02d:%02d', (int)($config['daily_refresh_hour'] ?? 8), (int)($config['daily_refresh_minute'] ?? 0)),
    ];
    return $payload;
}

function clarity_build_payload(array $config): array
{
    $mode = (string)($config['mode'] ?? 'mock');

    return match ($mode) {
        'mock' => clarity_enrich_payload(clarity_load_json_file(__DIR__ . '/examples/payload.example.json'), $config),
        'file' => clarity_enrich_payload(clarity_load_json_file((string)($config['payload_file'] ?? '')), $config),
        'remote_json' => clarity_enrich_payload(clarity_load_remote_json($config), $config),
        default => throw new RuntimeException('Unsupported mode: ' . $mode),
    };
}

function clarity_write_cache(array $payload, string $cacheFile): void
{
    $dir = dirname($cacheFile);
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        throw new RuntimeException('Failed to create cache dir: ' . $dir);
    }
    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('Failed to encode payload JSON');
    }
    if (file_put_contents($cacheFile, $json . PHP_EOL) === false) {
        throw new RuntimeException('Failed to write cache file: ' . $cacheFile);
    }
}

if (PHP_SAPI === 'cli') {
    try {
        $config = clarity_load_config();
        $payload = clarity_build_payload($config);
        $cacheFile = (string)($config['cache_file'] ?? (__DIR__ . '/data/dashboard-cache.json'));
        clarity_write_cache($payload, $cacheFile);
        echo "UPDATED {$cacheFile}\n";
        echo 'MODE ' . ($config['mode'] ?? 'mock') . "\n";
        echo 'REFRESHED ' . ($payload['refreshMeta']['refreshedAtLabel'] ?? '') . "\n";
    } catch (Throwable $e) {
        fwrite(STDERR, 'ERROR: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
}
