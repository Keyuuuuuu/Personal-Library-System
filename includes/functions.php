<!-- includes/functions.php -->
 
<?php
require_once 'db.php';

/**
 * 安全处理输入
 * @param string $data 输入数据
 * @return string 处理后的数据
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * 检查用户是否已登录
 * @return bool 已登录返回true，否则返回false
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * 跳转到指定页面
 * @param string $location 目标URL
 * @return void
 */
function redirect($location) {
    header("Location: " . SITE_URL . $location);
    exit;
}

/**
 * 如果用户未登录，重定向到登录页面
 * @return void
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['message'] = "Please login first";
        $_SESSION['message_type'] = "warning";
        redirect('/auth/login.php');
    }
}

/**
 * 设置提示消息
 * @param string $message 消息内容
 * @param string $type 消息类型 (success, info, warning, danger)
 * @return void
 */
function setMessage($message, $type = 'info') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

/**
 * 显示提示消息
 * @return string 消息HTML代码
 */
function displayMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'] ?? 'info';
        
        $html = '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
        $html .= $message;
        $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        $html .= '</div>';
        
        // 清除会话中的消息
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        
        return $html;
    }
    
    return '';
}

/**
 * 生成分页链接
 * @param int $page 当前页码
 * @param int $totalPages 总页数
 * @param string $url 基础URL
 * @return string 分页HTML代码
 */
function pagination($page, $totalPages, $url) {
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // 上一页链接
    if ($page > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $url . 'page=' . ($page - 1) . '">Previous</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a></li>';
    }
    
    // 页码链接
    $startPage = max(1, $page - 2);
    $endPage = min($totalPages, $page + 2);
    
    if ($startPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $url . 'page=1">1</a></li>';
        if ($startPage > 2) {
            $html .= '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
        }
    }
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        if ($i == $page) {
            $html .= '<li class="page-item active" aria-current="page"><a class="page-link" href="#">' . $i . '</a></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . $url . 'page=' . $i . '">' . $i . '</a></li>';
        }
    }
    
    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . $url . 'page=' . $totalPages . '">' . $totalPages . '</a></li>';
    }
    
    // 下一页链接
    if ($page < $totalPages) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $url . 'page=' . ($page + 1) . '">Next</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true">Next</a></li>';
    }
    
    $html .= '</ul></nav>';
    
    return $html;
}

/**
 * 格式化日期显示
 * @param string $date 日期字符串
 * @param string $format 格式
 * @return string 格式化后的日期
 */
function formatDate($date, $format = 'Y-m-d') {
    if (empty($date) || $date == '0000-00-00') {
        return '';
    }
    return date($format, strtotime($date));
}

/**
 * 获取当前用户信息
 * @return array|null 用户信息
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $userId = $_SESSION['user_id'];
    $sql = "SELECT * FROM users WHERE id = $userId";
    return fetchRow($sql);
}

/**
 * 检查用户是否是图书的拥有者
 * @param int $bookId 图书ID
 * @return bool 是拥有者返回true，否则返回false
 */
function isBookOwner($bookId) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $userId = $_SESSION['user_id'];
    $sql = "SELECT 1 FROM books WHERE id = $bookId AND user_id = $userId";
    $result = query($sql);
    
    return $result && $result->num_rows > 0;
}
?>