
<?php
// Submission File Service - Kelompok 4
// Port: 8105

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$rid = $_SERVER['HTTP_X_REQUEST_ID'] ?? 'req-' . uniqid();

$dataFile = __DIR__ . '/data/submissions.json';
$uploadDir = __DIR__ . '/uploads';

// ---------- RESPONSE ----------
function successResponse($data, $status = 200) {
    global $rid;

    http_response_code($status);
    echo json_encode([
        'success' => true,
        'data' => $data,
        'meta' => ['request_id' => $rid]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function errorResponse($status, $code, $message) {
    global $rid;

    http_response_code($status);
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => $code,
            'message' => $message
        ],
        'meta' => ['request_id' => $rid]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------- SERVICE CONFIGURATION ----------
function getServices() {
    $file = __DIR__ . '/services.json';
    $services = json_decode(file_get_contents($file), true);

    return is_array($services) ? $services : [];
}

// ---------- READ AUTHORIZATION ----------
function getAuthHeader() {
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if ($auth === '' && function_exists('getallheaders')) {
        foreach (getallheaders() as $key => $value) {
            if (strtolower($key) === 'authorization') {
                $auth = $value;
            }
        }
    }

    return $auth;
}

// ---------- CALL OTHER SERVICE ----------
function callService($url, $method, $headers = [], $body = null) {
    $ch = curl_init($url);

    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 3,
        CURLOPT_CONNECTTIMEOUT => 3
    ];

    if ($body !== null) {
        $options[CURLOPT_POSTFIELDS] = $body;
    }

    curl_setopt_array($ch, $options);

    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_errno($ch);

    curl_close($ch);

    return [
        'status' => $status,
        'error' => $error,
        'body' => json_decode((string) $response, true)
    ];
}

// ---------- AUTHENTICATION ----------
function checkAuth() {
    $auth = getAuthHeader();

    if (!preg_match('/^Bearer\s+\S+$/i', $auth)) {
        errorResponse(401, 'unauthorized', 'Token tidak ada atau tidak valid');
    }

    $services = getServices();

    if (empty($services['auth'])) {
        errorResponse(503, 'service_unavailable', 'Alamat Authentication belum diatur');
    }

    $result = callService(
        $services['auth'] . '/api/auth/validate',
        'POST',
        [
            'Authorization: ' . $auth,
            'Content-Type: application/json',
            'X-Request-ID: ' . $GLOBALS['rid']
        ],
        json_encode(['token' => trim(substr($auth, 7))])
    );

    if ($result['error'] !== 0 || $result['status'] === 0) {
        errorResponse(503, 'auth_unavailable', 'Authentication Service tidak dapat dihubungi');
    }

    if ($result['status'] === 401 || $result['status'] === 403) {
        errorResponse(401, 'unauthorized', 'Token tidak valid');
    }

    if ($result['status'] !== 200 ||
        ($result['body']['data']['valid'] ?? false) !== true) {
        errorResponse(401, 'unauthorized', 'Token tidak valid');
    }

    $user = $result['body']['data']['user'] ?? null;

    if (!is_array($user) || empty($user['id'])) {
        errorResponse(502, 'auth_bad_response', 'Data pengguna dari Authentication tidak lengkap');
    }

    return $user;
}

// ---------- CHECK ASSIGNMENT ----------
function checkAssignment($assignmentId) {
    $services = getServices();

    if (empty($services['assignment'])) {
        errorResponse(503, 'service_unavailable', 'Alamat Assignment belum diatur');
    }

    $result = callService(
        $services['assignment'] . '/api/assignments/' . rawurlencode($assignmentId),
        'GET',
        [
            'Authorization: ' . getAuthHeader(),
            'X-Request-ID: ' . $GLOBALS['rid']
        ]
    );

    if ($result['error'] !== 0 || $result['status'] === 0) {
        errorResponse(503, 'assignment_unavailable', 'Assignment Service tidak dapat dihubungi');
    }

    if ($result['status'] === 404) {
        errorResponse(404, 'assignment_not_found', 'Tugas tidak ditemukan');
    }

    if ($result['status'] === 401) {
        errorResponse(401, 'unauthorized', 'Token tidak valid');
    }

    if ($result['status'] !== 200 ||
        ($result['body']['success'] ?? false) !== true) {
        errorResponse(502, 'assignment_bad_response', 'Respons Assignment tidak sesuai');
    }

    return $result['body']['data'];
}

// ---------- JSON STORAGE ----------
function readSubmissions() {
    global $dataFile;

    if (!file_exists($dataFile)) {
        if (!is_dir(dirname($dataFile))) {
            mkdir(dirname($dataFile), 0775, true);
        }

        file_put_contents($dataFile, json_encode([
            'seq' => 0,
            'items' => []
        ], JSON_PRETTY_PRINT));
    }

    $store = json_decode(file_get_contents($dataFile), true);

    if (!is_array($store) || !isset($store['items'])) {
        return ['seq' => 0, 'items' => []];
    }

    return $store;
}

function saveSubmissions($store) {
    global $dataFile;

    file_put_contents(
        $dataFile,
        json_encode($store, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
}

// ---------- HEALTH CHECK ----------
if ($path === '/health' && $method === 'GET') {
    successResponse([
        'service' => 'submission',
        'status' => 'healthy'
    ]);
}

// ---------- GET SUBMISSIONS ----------
if ($path === '/api/submissions' && $method === 'GET') {
    checkAuth();

    $assignmentId = trim($_GET['assignment_id'] ?? '');

    if ($assignmentId === '') {
        errorResponse(400, 'validation_error', 'assignment_id wajib diisi');
    }

    $store = readSubmissions();

    $items = array_values(array_filter(
        $store['items'],
        function ($item) use ($assignmentId) {
            return $item['assignment_id'] === $assignmentId;
        }
    ));

    successResponse([
        'submissions' => $items,
        'total' => count($items)
    ]);
}

// ---------- CREATE SUBMISSION ----------
if ($path === '/api/submissions' && $method === 'POST') {
    $user = checkAuth();

    $assignmentId = trim($_POST['assignment_id'] ?? '');

    if ($assignmentId === '') {
        errorResponse(400, 'validation_error', 'assignment_id wajib diisi');
    }

    if (!isset($_FILES['file'])) {
        errorResponse(400, 'validation_error', 'File tugas wajib diunggah');
    }

    $file = $_FILES['file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        errorResponse(400, 'upload_error', 'File gagal diunggah');
    }

    // Maksimal ukuran file 10 MB
    if ($file['size'] > 10 * 1024 * 1024) {
        errorResponse(413, 'file_too_large', 'Ukuran file maksimal 10 MB');
    }

    // Format file yang diperbolehkan
    $originalName = basename($file['name']);
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    $allowed = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip'];

    if (!in_array($extension, $allowed, true)) {
        errorResponse(415, 'unsupported_media_type', 'Format file tidak didukung');
    }

    // Periksa apakah tugas benar-benar tersedia
    $assignment = checkAssignment($assignmentId);

    global $uploadDir;

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $store = readSubmissions();
    $store['seq']++;

    $submissionId = sprintf('sub-%03d', $store['seq']);
    $savedName = $submissionId . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $destination = $uploadDir . '/' . $savedName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        errorResponse(500, 'upload_failed', 'File tidak dapat disimpan');
    }

    $submission = [
        'id' => $submissionId,
        'assignment_id' => $assignmentId,
        'user_id' => (string) $user['id'],
        'file_name' => $originalName,
        'file_path' => 'uploads/' . $savedName,
        'status' => 'submitted',
        'submitted_at' => gmdate('c')
    ];

    $store['items'][] = $submission;
    saveSubmissions($store);

    // Kirim notifikasi setelah submission berhasil disimpan
    $services = getServices();

    if (!empty($services['notification'])) {
        $notification = [
            'user_id' => (string) $user['id'],
            'message' => 'Submission berhasil diterima',
            'type' => 'submission',
            'assignment_id' => $assignmentId,
            'submission_id' => $submissionId
        ];

        callService(
            $services['notification'] . '/api/notifications',
            'POST',
            [
                'Authorization: ' . getAuthHeader(),
                'Content-Type: application/json',
                'X-Request-ID: ' . $GLOBALS['rid']
            ],
            json_encode($notification)
        );
    }

    successResponse($submission, 201);
}

// ---------- DELETE SUBMISSION ----------
if (preg_match('#^/api/submissions/([A-Za-z0-9_-]+)$#', $path, $matches)
    && $method === 'DELETE') {

    checkAuth();

    $id = $matches[1];
    $store = readSubmissions();
    $found = false;

    foreach ($store['items'] as $index => $item) {
        if ($item['id'] === $id) {
            $filePath = __DIR__ . '/' . $item['file_path'];

            if (is_file($filePath)) {
                unlink($filePath);
            }

            unset($store['items'][$index]);
            $found = true;
            break;
        }
    }

    if (!$found) {
        errorResponse(404, 'submission_not_found', 'Submission tidak ditemukan');
    }

    $store['items'] = array_values($store['items']);
    saveSubmissions($store);

    successResponse([
        'message' => 'Submission berhasil dihapus',
        'id' => $id
    ]);
}

// ---------- UNKNOWN ENDPOINT ----------
errorResponse(404, 'not_found', 'Endpoint tidak ditemukan');
