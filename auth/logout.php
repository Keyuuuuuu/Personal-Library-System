<!-- auth/logout.php -->

<?php
require_once dirname(__FILE__) . '/../includes/config.php';
require_once dirname(__FILE__) . '/../includes/functions.php';

// 清除所有会话变量
$_SESSION = array();

// 如果需要，销毁会话 cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 销毁会话
session_destroy();

// 重定向到登录页面
setMessage('You have been logged out successfully.', 'info');
redirect('/auth/login.php');
?>