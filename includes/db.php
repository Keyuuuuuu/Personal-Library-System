<!-- includes/db.php -->

<?php
require_once 'config.php';

/**
 * 获取数据库连接
 * @return mysqli|false 数据库连接对象或失败时返回false
 */
function getDBConnection() {
    static $conn = null;
    
    // 如果已经有连接，尝试ping测试连接是否有效，无效则重新连接
    if ($conn !== null) {
        try {
            if ($conn->ping()) {
                return $conn;
            }
        } catch (Exception $e) {
            // 连接已失效，继续创建新连接
        }
    }
    
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // 检查连接
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }
        
        // 设置字符集
        $conn->set_charset("utf8mb4");
        
        // 设置较长的超时时间（可选）
        $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 300);
        
        return $conn;
    } catch (Exception $e) {
        // 记录错误并返回false
        error_log("Database connection error: " . $e->getMessage());
        return false;
    }
}

/**
 * 执行查询语句
 * @param string $sql SQL查询语句
 * @return mysqli_result|bool 查询结果对象或失败时返回false
 */
function query($sql) {
    try {
        $conn = getDBConnection();
        
        if (!$conn) {
            throw new Exception("Could not connect to database");
        }
        
        $result = $conn->query($sql);
        
        if (!$result) {
            throw new Exception("SQL query error: " . $conn->error);
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("Query error: " . $e->getMessage());
        echo "Database error occurred. Please try again later.";
        return false;
    }
}

/**
 * 预处理执行SQL语句（防注入）
 * @param string $sql 预处理SQL语句
 * @param string $types 参数类型
 * @param array $params 参数数组
 * @return mysqli_stmt|bool 成功返回预处理语句对象，失败返回false
 */
function preparedQuery($sql, $types = "", $params = []) {
    $conn = getDBConnection();
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo "Prepared statement error: " . $conn->error;
        $conn->close();
        return false;
    }
    
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    
    $result = $stmt->execute();
    
    if (!$result) {
        echo "Prepared statement execution error: " . $stmt->error;
    }
    
    $conn->close();
    return $stmt;
}

/**
 * 获取单行数据
 * @param string $sql SQL查询语句
 * @return array|null 查询结果行或没有结果时返回null
 */
function fetchRow($sql) {
    $result = query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $result->free();
        return $row;
    }
    
    return null;
}

/**
 * 获取多行数据
 * @param string $sql SQL查询语句
 * @return array 查询结果行数组
 */
function fetchAll($sql) {
    $result = query($sql);
    $rows = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $result->free();
    }
    
    return $rows;
}

/**
 * 插入数据并返回插入ID
 * @param string $table 表名
 * @param array $data 待插入的数据数组
 * @return int|bool 成功返回插入ID，失败返回false
 */
function insert($table, $data) {
    $conn = getDBConnection();
    
    $columns = implode(", ", array_keys($data));
    $placeholders = implode(", ", array_fill(0, count($data), "?"));
    $types = "";
    $values = [];
    
    foreach ($data as $value) {
        if (is_int($value)) {
            $types .= "i";
        } elseif (is_float($value)) {
            $types .= "d";
        } elseif (is_string($value)) {
            $types .= "s";
        } else {
            $types .= "s";
        }
        $values[] = $value;
    }
    
    $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo "Prepared statement error: " . $conn->error;
        $conn->close();
        return false;
    }
    
    $stmt->bind_param($types, ...$values);
    $result = $stmt->execute();
    
    if (!$result) {
        echo "Prepared statement execution error: " . $stmt->error;
        $stmt->close();
        $conn->close();
        return false;
    }
    
    $insertId = $conn->insert_id;
    $stmt->close();
    $conn->close();
    
    return $insertId;
}

/**
 * 更新数据
 * @param string $table 表名
 * @param array $data 待更新的数据数组
 * @param string $where 条件
 * @param string $whereTypes 条件参数类型
 * @param array $whereParams 条件参数数组
 * @return bool 成功返回true，失败返回false
 */
function update($table, $data, $where, $whereTypes = "", $whereParams = []) {
    $conn = getDBConnection();
    
    $setClause = [];
    $types = "";
    $values = [];
    
    foreach ($data as $column => $value) {
        $setClause[] = "$column = ?";
        if (is_int($value)) {
            $types .= "i";
        } elseif (is_float($value)) {
            $types .= "d";
        } elseif (is_string($value)) {
            $types .= "s";
        } else {
            $types .= "s";
        }
        $values[] = $value;
    }
    
    $setClauseStr = implode(", ", $setClause);
    $sql = "UPDATE $table SET $setClauseStr WHERE $where";
    
    // 合并数据参数和条件参数
    $allTypes = $types . $whereTypes;
    $allParams = array_merge($values, $whereParams);
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo "Prepared statement error: " . $conn->error;
        $conn->close();
        return false;
    }
    
    $stmt->bind_param($allTypes, ...$allParams);
    $result = $stmt->execute();
    
    if (!$result) {
        echo "Prepared statement execution error: " . $stmt->error;
    }
    
    $affectedRows = $stmt->affected_rows;
    $stmt->close();
    $conn->close();
    
    return $affectedRows > 0;
}

/**
 * 删除数据
 * @param string $table 表名
 * @param string $where 条件
 * @param string $types 参数类型
 * @param array $params 参数数组
 * @return bool 成功返回true，失败返回false
 */
function delete($table, $where, $types = "", $params = []) {
    $conn = getDBConnection();
    
    $sql = "DELETE FROM $table WHERE $where";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo "Prepared statement error: " . $conn->error;
        $conn->close();
        return false;
    }
    
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    
    $result = $stmt->execute();
    
    if (!$result) {
        echo "Prepared statement execution error: " . $stmt->error;
    }
    
    $affectedRows = $stmt->affected_rows;
    $stmt->close();
    $conn->close();
    
    return $affectedRows > 0;
}
?>