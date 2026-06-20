<?php
// backend/public/db.php

class Database
{
    private static $pdo;

    public static function connect()
    {
        if (self::$pdo === null) {
            try {
                // 确保数据目录存在
                $dbPath = '/var/www/html/data/visitors.sqlite';
                $dir = dirname($dbPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }

                self::$pdo = new PDO("sqlite:" . $dbPath);
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                // 初始化表结构
                self::initTable();

            } catch (PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }
        return self::$pdo;
    }

    private static function initTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS visitors (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip TEXT,
            country TEXT,
            city TEXT,
            isp TEXT,
            user_agent TEXT,
            
            browser TEXT,
            browser_version TEXT,
            os TEXT,
            os_version TEXT,
            device_type TEXT,
            
            screen_width INTEGER,
            screen_height INTEGER,
            window_width INTEGER,
            window_height INTEGER,
            
            language TEXT,
            timezone TEXT,
            platform TEXT,
            cookie_enabled INTEGER,
            
            touch_points INTEGER,
            device_memory REAL,
            cpu_cores INTEGER,
            connection_type TEXT,
            
            referrer TEXT,
            remark TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        self::$pdo->exec($sql);

        $sql2 = "CREATE TABLE IF NOT EXISTS third_party_scripts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            script_hash TEXT UNIQUE,
            script_url TEXT,
            script_domain TEXT,
            script_name TEXT,
            script_type TEXT DEFAULT 'other',
            status TEXT DEFAULT 'pending',
            description TEXT,
            collect_fields TEXT,
            risk_level TEXT DEFAULT 'medium',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        self::$pdo->exec($sql2);

        $sql3 = "CREATE TABLE IF NOT EXISTS script_detections (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            visitor_id INTEGER,
            script_id INTEGER,
            script_url TEXT,
            script_hash TEXT,
            load_time REAL,
            start_after_consent INTEGER DEFAULT 0,
            collect_fields TEXT,
            page_url TEXT,
            detected_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        self::$pdo->exec($sql3);

        // 检查是否需要插入演示数据
        $count = self::$pdo->query("SELECT COUNT(*) FROM visitors")->fetchColumn();
        if ($count == 0) {
            self::seedData();
        }

        $scriptCount = self::$pdo->query("SELECT COUNT(*) FROM third_party_scripts")->fetchColumn();
        if ($scriptCount == 0) {
            self::seedScriptData();
        }
    }

    private static function seedData()
    {
        $stmt = self::$pdo->prepare("INSERT INTO visitors (
            ip, country, city, isp, user_agent, browser, os, screen_width, screen_height, remark, created_at
        ) VALUES (
            :ip, :country, :city, :isp, :user_agent, :browser, :os, :screen_width, :screen_height, :remark, :created_at
        )");

        $demos = [
            [
                ':ip' => '192.168.1.101',
                ':country' => 'China',
                ':city' => 'Shanghai',
                ':isp' => 'China Telecom',
                ':user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)...',
                ':browser' => 'Chrome',
                ':os' => 'Mac OS X',
                ':screen_width' => 1920,
                ':screen_height' => 1080,
                ':remark' => '测试数据 A',
                ':created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
            ],
            [
                ':ip' => '10.0.0.5',
                ':country' => 'China',
                ':city' => 'Beijing',
                ':isp' => 'China Unicom',
                ':user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_6 like Mac OS X)...',
                ':browser' => 'Safari',
                ':os' => 'iOS',
                ':screen_width' => 390,
                ':screen_height' => 844,
                ':remark' => '测试数据 B - 手机端',
                ':created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
            ]
        ];

        foreach ($demos as $demo) {
            $stmt->execute($demo);
        }
    }

    private static function seedScriptData()
    {
        $stmt = self::$pdo->prepare("INSERT INTO third_party_scripts (
            script_hash, script_url, script_domain, script_name, script_type,
            status, description, collect_fields, risk_level, created_at
        ) VALUES (
            :script_hash, :script_url, :script_domain, :script_name, :script_type,
            :status, :description, :collect_fields, :risk_level, :created_at
        )");

        $demos = [
            [
                ':script_hash' => 'hash_google_analytics_001',
                ':script_url' => 'https://www.google-analytics.com/analytics.js',
                ':script_domain' => 'google-analytics.com',
                ':script_name' => 'Google Analytics',
                ':script_type' => 'analytics',
                ':status' => 'approved',
                ':description' => '谷歌网站统计分析服务',
                ':collect_fields' => json_encode(['页面URL', '停留时间', '点击事件', '设备信息', '地理位置']),
                ':risk_level' => 'low',
                ':created_at' => date('Y-m-d H:i:s', strtotime('-30 days'))
            ],
            [
                ':script_hash' => 'hash_baidu_tongji_002',
                ':script_url' => 'https://hm.baidu.com/hm.js',
                ':script_domain' => 'hm.baidu.com',
                ':script_name' => '百度统计',
                ':script_type' => 'analytics',
                ':status' => 'pending',
                ':description' => '百度网站流量统计服务 - 待审核',
                ':collect_fields' => json_encode(['页面访问', '点击热力图', '搜索关键词', '访客画像']),
                ':risk_level' => 'medium',
                ':created_at' => date('Y-m-d H:i:s', strtotime('-3 days'))
            ],
            [
                ':script_hash' => 'hash_meiqia_003',
                ':script_url' => 'https://static.meiqia.com/dist/meiqia.js',
                ':script_domain' => 'meiqia.com',
                ':script_name' => '美洽客服',
                ':script_type' => 'customer_service',
                ':status' => 'observed',
                ':description' => '在线客服聊天插件 - 观察中',
                ':collect_fields' => json_encode(['访客信息', '聊天记录', '访问轨迹', '联系方式']),
                ':risk_level' => 'high',
                ':created_at' => date('Y-m-d H:i:s', strtotime('-7 days'))
            ],
            [
                ':script_hash' => 'hash_adpixel_004',
                ':script_url' => 'https://pixel.example.com/track.js',
                ':script_domain' => 'pixel.example.com',
                ':script_name' => '未知广告追踪像素',
                ':script_type' => 'ad_pixel',
                ':status' => 'disabled',
                ':description' => '未经授权的广告追踪像素 - 已禁用',
                ':collect_fields' => json_encode(['浏览行为', '点击转化', '设备指纹', '跨站追踪']),
                ':risk_level' => 'critical',
                ':created_at' => date('Y-m-d H:i:s', strtotime('-1 days'))
            ],
            [
                ':script_hash' => 'hash_51la_005',
                ':script_url' => 'https://js.users.51.la/1234567.js',
                ':script_domain' => '51.la',
                ':script_name' => '51.la 统计',
                ':script_type' => 'analytics',
                ':status' => 'pending',
                ':description' => '我要啦网站统计 - 新发现',
                ':collect_fields' => json_encode(['访问量统计', '来路分析', '关键词分析', '访客系统']),
                ':risk_level' => 'medium',
                ':created_at' => date('Y-m-d H:i:s', strtotime('-12 hours'))
            ]
        ];

        foreach ($demos as $demo) {
            $stmt->execute($demo);
        }

        $detStmt = self::$pdo->prepare("INSERT INTO script_detections (
            visitor_id, script_id, script_url, script_hash, load_time,
            start_after_consent, collect_fields, page_url, detected_at
        ) VALUES (
            :visitor_id, :script_id, :script_url, :script_hash, :load_time,
            :start_after_consent, :collect_fields, :page_url, :detected_at
        )");

        $detections = [
            [
                ':visitor_id' => 1,
                ':script_id' => 1,
                ':script_url' => 'https://www.google-analytics.com/analytics.js',
                ':script_hash' => 'hash_google_analytics_001',
                ':load_time' => 45.2,
                ':start_after_consent' => 1,
                ':collect_fields' => json_encode(['页面URL', '停留时间']),
                ':page_url' => 'https://example.com/',
                ':detected_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
            ],
            [
                ':visitor_id' => 1,
                ':script_id' => 4,
                ':script_url' => 'https://pixel.example.com/track.js',
                ':script_hash' => 'hash_adpixel_004',
                ':load_time' => 128.5,
                ':start_after_consent' => 0,
                ':collect_fields' => json_encode(['浏览行为', '设备指纹']),
                ':page_url' => 'https://example.com/',
                ':detected_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
            ],
            [
                ':visitor_id' => 2,
                ':script_id' => 2,
                ':script_url' => 'https://hm.baidu.com/hm.js',
                ':script_hash' => 'hash_baidu_tongji_002',
                ':load_time' => 62.8,
                ':start_after_consent' => 0,
                ':collect_fields' => json_encode(['页面访问', '点击热力图']),
                ':page_url' => 'https://example.com/product',
                ':detected_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
            ]
        ];

        foreach ($detections as $det) {
            $detStmt->execute($det);
        }
    }
}
