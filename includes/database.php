<?php
declare(strict_types=1);

/**
 * إنشاء اتصال بقاعدة البيانات
 * 
 * @return PDO كائن PDO للاتصال
 * @throws PDOException إذا فشل الاتصال
 */
function create_database_connection(): PDO {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_PERSISTENT         => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'"
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        // إعدادات إضافية
        $pdo->exec("SET time_zone = '+00:00'");
        $pdo->exec("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION'");
        
        return $pdo;
    } catch (PDOException $e) {
        log_error("Database connection failed: " . $e->getMessage());
        throw $e;
    }
}

/**
 * تهيئة جداول قاعدة البيانات إذا لم تكن موجودة
 * 
 * @param PDO $pdo كائن PDO للاتصال
 */
function initialize_database_tables(PDO $pdo): void {
    $tables = [
        'chats' => "
            CREATE TABLE IF NOT EXISTS chats (
                id INT AUTO_INCREMENT PRIMARY KEY,
                chat_id VARCHAR(64) NOT NULL UNIQUE,
                access_key VARCHAR(64) NOT NULL,
                one_time_token VARCHAR(64),
                creator_id VARCHAR(32) NOT NULL,
                created_at DATETIME NOT NULL,
                expires_at DATETIME,
                INDEX idx_chat_id (chat_id),
                INDEX idx_creator (creator_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        'messages' => "
            CREATE TABLE IF NOT EXISTS messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                chat_id VARCHAR(64) NOT NULL,
                sender_id VARCHAR(32) NOT NULL,
                content TEXT NOT NULL,
                is_temporary BOOLEAN NOT NULL DEFAULT FALSE,
                is_image BOOLEAN NOT NULL DEFAULT FALSE,
                created_at DATETIME NOT NULL,
                expires_at DATETIME,
                FOREIGN KEY (chat_id) REFERENCES chats(chat_id) ON DELETE CASCADE,
                INDEX idx_chat (chat_id),
                INDEX idx_sender (sender_id),
                INDEX idx_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        'message_views' => "
            CREATE TABLE IF NOT EXISTS message_views (
                id INT AUTO_INCREMENT PRIMARY KEY,
                message_id INT NOT NULL,
                user_id VARCHAR(32) NOT NULL,
                viewed_at DATETIME NOT NULL,
                FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
                UNIQUE KEY uniq_message_user (message_id, user_id),
                INDEX idx_message (message_id),
                INDEX idx_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        'certificates' => "
            CREATE TABLE IF NOT EXISTS certificates (
                id INT AUTO_INCREMENT PRIMARY KEY,
                cert_id VARCHAR(32) NOT NULL UNIQUE,
                user_id VARCHAR(32) NOT NULL,
                public_key TEXT NOT NULL,
                fingerprint VARCHAR(128) NOT NULL,
                created_at DATETIME NOT NULL,
                expires_at DATETIME NOT NULL,
                revoked_at DATETIME,
                INDEX idx_cert_id (cert_id),
                INDEX idx_user (user_id),
                INDEX idx_fingerprint (fingerprint)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        "
    ];

    try {
        $pdo->beginTransaction();
        
        foreach ($tables as $table => $sql) {
            $pdo->exec($sql);
        }
        
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        log_error("Failed to initialize database tables: " . $e->getMessage());
        throw $e;
    }
}

/**
 * نسخ احتياطي لقاعدة البيانات
 * 
 * @param PDO $pdo كائن PDO للاتصال
 * @param string $backupPath مسار حفظ النسخة الاحتياطية
 * @return bool صحيح إذا نجح النسخ الاحتياطي
 */
function backup_database(PDO $pdo, string $backupPath): bool {
    try {
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $output = '';
        
        foreach ($tables as $table) {
            // هيكل الجدول
            $output .= "--\n-- Structure for table `$table`\n--\n";
            $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
            $output .= $create['Create Table'] . ";\n\n";
            
            // بيانات الجدول
            $output .= "--\n-- Data for table `$table`\n--\n";
            $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($rows as $row) {
                $values = array_map(function($value) use ($pdo) {
                    return $value === null ? 'NULL' : $pdo->quote($value);
                }, $row);
                
                $output .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
            }
            
            $output .= "\n";
        }
        
        return file_put_contents($backupPath, $output) !== false;
    } catch (PDOException $e) {
        log_error("Database backup failed: " . $e->getMessage());
        return false;
    }
}

/**
 * تنفيذ استعلام مع معلمات مرتبطة بأمان
 * 
 * @param PDO $pdo كائن PDO للاتصال
 * @param string $sql نص الاستعلام
 * @param array $params معلمات الاستعلام
 * @return PDOStatement كائن النتيجة
 * @throws PDOException إذا فشل الاستعلام
 */
function execute_query(PDO $pdo, string $sql, array $params = []): PDOStatement {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        log_error("Query failed: " . $e->getMessage() . " [SQL: $sql]");
        throw $e;
    }
}

// إنشاء اتصال بقاعدة البيانات عند التحميل
try {
    $pdo = create_database_connection();
    
    // تهيئة الجداول إذا لم تكن موجودة (في بيئة التطوير فقط)
    if (APP_ENV === 'development') {
        initialize_database_tables($pdo);
    }
} catch (PDOException $e) {
    die("Failed to connect to database. Please try again later.");
}