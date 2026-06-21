<?php
// backend/public/api.php
require_once 'db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$action = $_GET['action'] ?? '';
$pdo = Database::connect();

try {
    switch ($action) {
        case 'collect':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid method');
            }
            $input = json_decode(file_get_contents('php://input'), true);

            // 获取真实 IP
            $ip = $_SERVER['REMOTE_ADDR'];
            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
            } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
                $ip = $_SERVER['HTTP_X_REAL_IP'];
            }
            $ip = trim($ip);

            // 服务端 IP 定位（如果客户端没有提供）
            $country = $input['country'] ?? '';
            $city = $input['city'] ?? '';
            $isp = $input['isp'] ?? '';

            if (empty($country) && empty($city)) {
                // 尝试服务端获取 IP 定位
                $geoUrl = "http://ip-api.com/json/{$ip}?lang=zh-CN";
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 3,
                        'ignore_errors' => true
                    ]
                ]);
                $geoJson = @file_get_contents($geoUrl, false, $context);
                if ($geoJson) {
                    $geoData = json_decode($geoJson, true);
                    if ($geoData && $geoData['status'] === 'success') {
                        $country = $geoData['country'] ?? '';
                        $city = $geoData['city'] ?? '';
                        $isp = $geoData['isp'] ?? '';
                    }
                }
            }

            // 补全服务端信息
            $data = [
                ':ip' => $ip,
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                ':country' => $country,
                ':city' => $city,
                ':isp' => $isp,

                ':browser' => $input['browser'] ?? '未知',
                ':browser_version' => $input['browser_version'] ?? '',
                ':os' => $input['os'] ?? '未知',
                ':os_version' => $input['os_version'] ?? '',
                ':device_type' => $input['device_type'] ?? '桌面设备',

                ':screen_width' => $input['screen_width'] ?? 0,
                ':screen_height' => $input['screen_height'] ?? 0,
                ':window_width' => $input['window_width'] ?? 0,
                ':window_height' => $input['window_height'] ?? 0,

                ':language' => $input['language'] ?? '',
                ':timezone' => $input['timezone'] ?? '',
                ':platform' => $input['platform'] ?? '',
                ':cookie_enabled' => isset($input['cookie_enabled']) ? ($input['cookie_enabled'] ? 1 : 0) : 0,

                ':touch_points' => $input['touch_points'] ?? 0,
                ':device_memory' => $input['device_memory'] ?? 0,
                ':cpu_cores' => $input['cpu_cores'] ?? 0,
                ':connection_type' => $input['connection_type'] ?? '',

                ':referrer' => $input['referrer'] ?? '',
                ':remark' => ''
            ];

            $sql = "INSERT INTO visitors (
                ip, user_agent, country, city, isp,
                browser, browser_version, os, os_version, device_type,
                screen_width, screen_height, window_width, window_height,
                language, timezone, platform, cookie_enabled,
                touch_points, device_memory, cpu_cores, connection_type,
                referrer, remark
            ) VALUES (
                :ip, :user_agent, :country, :city, :isp,
                :browser, :browser_version, :os, :os_version, :device_type,
                :screen_width, :screen_height, :window_width, :window_height,
                :language, :timezone, :platform, :cookie_enabled,
                :touch_points, :device_memory, :cpu_cores, :connection_type,
                :referrer, :remark
            )";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);

            echo json_encode(['status' => 'success', 'id' => $pdo->lastInsertId()]);
            break;

        case 'list':
            $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
            $limit = 20;
            $offset = ($page - 1) * $limit;
            $search = $_GET['search'] ?? '';

            $where = "WHERE 1=1";
            $params = [];

            if ($search) {
                $where .= " AND (ip LIKE :search OR remark LIKE :search OR city LIKE :search)";
                $params[':search'] = "%$search%";
            }

            // count
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM visitors $where");
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            // data
            $stmt = $pdo->prepare("SELECT * FROM visitors $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
            $stmt->execute($params);
            $list = $stmt->fetchAll();

            echo json_encode([
                'status' => 'success',
                'data' => $list,
                'total' => $total,
                'page' => $page,
                'pages' => ceil($total / $limit)
            ]);
            break;

        case 'remark':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid method');
            }
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? 0;
            $remark = $input['remark'] ?? '';

            if (!$id)
                throw new Exception('ID required');

            $stmt = $pdo->prepare("UPDATE visitors SET remark = :remark WHERE id = :id");
            $stmt->execute([':remark' => $remark, ':id' => $id]);

            echo json_encode(['status' => 'success']);
            break;

        case 'stats':
            $today = date('Y-m-d');
            $todayStmt = $pdo->prepare("SELECT COUNT(*) FROM visitors WHERE DATE(created_at) = :today");
            $todayStmt->execute([':today' => $today]);
            $todayCount = $todayStmt->fetchColumn();

            $totalStmt = $pdo->query("SELECT COUNT(*) FROM visitors");
            $totalCount = $totalStmt->fetchColumn();

            echo json_encode([
                'status' => 'success',
                'total' => $totalCount,
                'today' => $todayCount
            ]);
            break;

        case 'report_scripts':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid method');
            }
            $input = json_decode(file_get_contents('php://input'), true);
            $visitorId = $input['visitor_id'] ?? 0;
            $scripts = $input['scripts'] ?? [];

            if (!is_array($scripts) || empty($scripts)) {
                echo json_encode(['status' => 'success', 'count' => 0]);
                break;
            }

            $inserted = 0;
            foreach ($scripts as $script) {
                $scriptHash = $script['script_hash'] ?? '';
                $scriptUrl = $script['script_url'] ?? '';
                $scriptDomain = $script['script_domain'] ?? '';
                $scriptType = $script['script_type'] ?? 'other';
                $loadTime = $script['load_time'] ?? 0;
                $startAfterConsent = $script['start_after_consent'] ?? 0;
                $collectFields = is_array($script['collect_fields'] ?? null)
                    ? json_encode($script['collect_fields'])
                    : ($script['collect_fields'] ?? '[]');
                $pageUrl = $script['page_url'] ?? '';

                $checkStmt = $pdo->prepare("SELECT id, status FROM third_party_scripts WHERE script_hash = :hash");
                $checkStmt->execute([':hash' => $scriptHash]);
                $existing = $checkStmt->fetch();

                $scriptId = null;
                $scriptStatus = 'pending';
                if ($existing) {
                    $scriptId = $existing['id'];
                    $scriptStatus = $existing['status'];
                } else {
                    $riskLevel = 'medium';
                    if ($scriptType === 'ad_pixel') {
                        $riskLevel = 'high';
                    } elseif ($scriptType === 'customer_service') {
                        $riskLevel = 'high';
                    } elseif ($scriptType === 'analytics') {
                        $riskLevel = 'low';
                    }

                    $insertStmt = $pdo->prepare("INSERT INTO third_party_scripts (
                        script_hash, script_url, script_domain, script_name,
                        script_type, status, description, collect_fields, risk_level
                    ) VALUES (
                        :hash, :url, :domain, :name,
                        :type, 'pending', :desc, :fields, :risk
                    )");
                    $insertStmt->execute([
                        ':hash' => $scriptHash,
                        ':url' => $scriptUrl,
                        ':domain' => $scriptDomain,
                        ':name' => $scriptDomain,
                        ':type' => $scriptType,
                        ':desc' => '自动检测发现的第三方脚本',
                        ':fields' => $collectFields,
                        ':risk' => $riskLevel
                    ]);
                    $scriptId = $pdo->lastInsertId();
                }

                $monitorStatus = in_array($scriptStatus, ['approved', 'observed']) ? 'formal' : 'pre_check';

                $detStmt = $pdo->prepare("INSERT INTO script_detections (
                    visitor_id, script_id, script_url, script_hash,
                    load_time, start_after_consent, collect_fields, page_url,
                    monitor_status
                ) VALUES (
                    :visitor_id, :script_id, :script_url, :script_hash,
                    :load_time, :start_after_consent, :collect_fields, :page_url,
                    :monitor_status
                )");
                $detStmt->execute([
                    ':visitor_id' => $visitorId,
                    ':script_id' => $scriptId,
                    ':script_url' => $scriptUrl,
                    ':script_hash' => $scriptHash,
                    ':load_time' => $loadTime,
                    ':start_after_consent' => $startAfterConsent,
                    ':collect_fields' => $collectFields,
                    ':page_url' => $pageUrl,
                    ':monitor_status' => $monitorStatus
                ]);
                $inserted++;
            }

            echo json_encode(['status' => 'success', 'count' => $inserted]);
            break;

        case 'script_list':
            $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
            $offset = ($page - 1) * $limit;
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            $type = $_GET['type'] ?? '';

            $where = "WHERE 1=1";
            $params = [];

            if ($search) {
                $where .= " AND (script_name LIKE :search OR script_domain LIKE :search OR script_url LIKE :search)";
                $params[':search'] = "%$search%";
            }
            if ($status) {
                $where .= " AND status = :status";
                $params[':status'] = $status;
            }
            if ($type) {
                $where .= " AND script_type = :type";
                $params[':type'] = $type;
            }

            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM third_party_scripts $where");
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT * FROM third_party_scripts $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
            $stmt->execute($params);
            $list = $stmt->fetchAll();

            foreach ($list as &$item) {
                if (!empty($item['collect_fields'])) {
                    $item['collect_fields'] = json_decode($item['collect_fields'], true) ?: [];
                } else {
                    $item['collect_fields'] = [];
                }
                $formalStmt = $pdo->prepare("SELECT COUNT(*) FROM script_detections WHERE script_id = :id AND monitor_status = 'formal'");
                $formalStmt->execute([':id' => $item['id']]);
                $item['formal_count'] = $formalStmt->fetchColumn();

                $preCheckStmt = $pdo->prepare("SELECT COUNT(*) FROM script_detections WHERE script_id = :id AND monitor_status = 'pre_check'");
                $preCheckStmt->execute([':id' => $item['id']]);
                $item['precheck_count'] = $preCheckStmt->fetchColumn();

                $item['detection_count'] = $item['formal_count'];
            }

            echo json_encode([
                'status' => 'success',
                'data' => $list,
                'total' => $total,
                'page' => $page,
                'pages' => ceil($total / $limit)
            ]);
            break;

        case 'script_detail':
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            if (!$id) throw new Exception('ID required');

            $stmt = $pdo->prepare("SELECT * FROM third_party_scripts WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $script = $stmt->fetch();

            if (!$script) throw new Exception('脚本不存在');

            if (!empty($script['collect_fields'])) {
                $script['collect_fields'] = json_decode($script['collect_fields'], true) ?: [];
            } else {
                $script['collect_fields'] = [];
            }

            $detStmt = $pdo->prepare("SELECT d.*, v.ip FROM script_detections d
                LEFT JOIN visitors v ON d.visitor_id = v.id
                WHERE d.script_id = :id
                ORDER BY d.detected_at DESC
                LIMIT 20");
            $detStmt->execute([':id' => $id]);
            $detections = $detStmt->fetchAll();

            foreach ($detections as &$det) {
                if (!empty($det['collect_fields'])) {
                    $det['collect_fields'] = json_decode($det['collect_fields'], true) ?: [];
                } else {
                    $det['collect_fields'] = [];
                }
            }

            echo json_encode([
                'status' => 'success',
                'script' => $script,
                'detections' => $detections
            ]);
            break;

        case 'script_status':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid method');
            }
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? 0;
            $status = $input['status'] ?? '';
            $description = $input['description'] ?? null;

            if (!$id) throw new Exception('ID required');
            if (!in_array($status, ['pending', 'approved', 'observed', 'disabled'])) {
                throw new Exception('无效的状态');
            }

            $updateFields = ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
            $params = [':id' => $id, ':status' => $status, ':updated_at' => date('Y-m-d H:i:s')];

            if ($description !== null) {
                $updateFields['description'] = $description;
                $params[':description'] = $description;
            }

            $setParts = [];
            foreach (array_keys($updateFields) as $field) {
                $setParts[] = "$field = :$field";
            }

            $stmt = $pdo->prepare("UPDATE third_party_scripts SET " . implode(', ', $setParts) . " WHERE id = :id");
            $stmt->execute($params);

            echo json_encode(['status' => 'success']);
            break;

        case 'script_stats':
            $totalStmt = $pdo->query("SELECT COUNT(*) FROM third_party_scripts");
            $total = $totalStmt->fetchColumn();

            $pendingStmt = $pdo->query("SELECT COUNT(*) FROM third_party_scripts WHERE status = 'pending'");
            $pending = $pendingStmt->fetchColumn();

            $approvedStmt = $pdo->query("SELECT COUNT(*) FROM third_party_scripts WHERE status = 'approved'");
            $approved = $approvedStmt->fetchColumn();

            $observedStmt = $pdo->query("SELECT COUNT(*) FROM third_party_scripts WHERE status = 'observed'");
            $observed = $observedStmt->fetchColumn();

            $disabledStmt = $pdo->query("SELECT COUNT(*) FROM third_party_scripts WHERE status = 'disabled'");
            $disabled = $disabledStmt->fetchColumn();

            $highRiskStmt = $pdo->query("SELECT COUNT(*) FROM third_party_scripts WHERE risk_level IN ('high', 'critical') AND status != 'disabled'");
            $highRisk = $highRiskStmt->fetchColumn();

            $todayStmt = $pdo->prepare("SELECT COUNT(*) FROM script_detections WHERE DATE(detected_at) = :today AND monitor_status = 'formal'");
            $todayStmt->execute([':today' => date('Y-m-d')]);
            $todayDetections = $todayStmt->fetchColumn();

            $todayPrecheckStmt = $pdo->prepare("SELECT COUNT(*) FROM script_detections WHERE DATE(detected_at) = :today AND monitor_status = 'pre_check'");
            $todayPrecheckStmt->execute([':today' => date('Y-m-d')]);
            $todayPrecheck = $todayPrecheckStmt->fetchColumn();

            echo json_encode([
                'status' => 'success',
                'total' => $total,
                'pending' => $pending,
                'approved' => $approved,
                'observed' => $observed,
                'disabled' => $disabled,
                'high_risk' => $highRisk,
                'today_detections' => $todayDetections,
                'today_precheck' => $todayPrecheck
            ]);
            break;

        default:
            echo json_encode(['status' => 'error', 'message' => '未知操作']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
