<?php
$pageTitle = 'My Profile';
require_once dirname(__FILE__) . '/../includes/header.php';

// 检查用户是否已登录
requireLogin();

// 获取当前用户信息
$userId = $_SESSION['user_id'];
$userQuery = "SELECT * FROM users WHERE id = $userId";
$user = fetchRow($userQuery);

if (!$user) {
    setMessage('User not found', 'danger');
    redirect('/index.php');
}

// 获取用户统计数据
$statsQuery = "SELECT 
              (SELECT COUNT(*) FROM books WHERE user_id = $userId) as total_books,
              (SELECT COUNT(*) FROM authors WHERE user_id = $userId) as total_authors,
              (SELECT COUNT(*) FROM borrowings WHERE user_id = $userId) as total_borrowings,
              (SELECT COUNT(*) FROM borrowings WHERE user_id = $userId AND returned_date IS NULL) as current_borrowings,
              (SELECT COUNT(DISTINCT author_id) FROM books WHERE user_id = $userId AND author_id IS NOT NULL) as authors_with_books";
$stats = fetchRow($statsQuery);

$errors = [];
$success = false;

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        // 获取表单数据
        $fullName = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        
        // 验证输入
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        // 如果邮箱发生变化，检查是否已存在
        if ($email !== $user['email']) {
            $conn = getDBConnection();
            
            $stmt = $conn->prepare("SELECT 1 FROM users WHERE email = ? AND id != ?");
            $stmt->bind_param("si", $email, $userId);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $errors[] = 'Email already exists';
            }
            
            $stmt->close();
            $conn->close();
        }
        
        // 如果没有错误，更新资料
        if (empty($errors)) {
            $userData = [
                'full_name' => $fullName,
                'email' => $email
            ];
            
            $result = update('users', $userData, 'id = ?', 'i', [$userId]);
            
            if ($result) {
                $success = true;
                setMessage('Profile updated successfully!', 'success');
                
                // 更新会话中的用户名（如果有变化）
                if ($email !== $user['email']) {
                    // 重新获取用户信息
                    $user = fetchRow("SELECT * FROM users WHERE id = $userId");
                }
            } else {
                $errors[] = 'Failed to update profile. Please try again.';
            }
        }
    } elseif ($action === 'change_password') {
        // 获取表单数据
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        // 验证输入
        if (empty($currentPassword)) {
            $errors[] = 'Current password is required';
        }
        
        if (empty($newPassword)) {
            $errors[] = 'New password is required';
        } elseif (strlen($newPassword) < 6) {
            $errors[] = 'New password must be at least 6 characters';
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'New passwords do not match';
        }
        
        // 验证当前密码
        if (!password_verify($currentPassword, $user['password'])) {
            $errors[] = 'Current password is incorrect';
        }
        
        // 如果没有错误，更新密码
        if (empty($errors)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            $userData = [
                'password' => $hashedPassword
            ];
            
            $result = update('users', $userData, 'id = ?', 'i', [$userId]);
            
            if ($result) {
                $success = true;
                setMessage('Password changed successfully!', 'success');
            } else {
                $errors[] = 'Failed to change password. Please try again.';
            }
        }
    }
    
    // 重定向以避免表单重提交
    if ($success) {
        redirect('/users/profile.php');
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>My Profile</h1>
    <a href="<?php echo SITE_URL; ?>/index.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Back to Home
    </a>
</div>

<div class="row">
    <div class="col-md-4">
        <!-- 用户信息卡片 -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Account Information</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <i class="fas fa-user-circle fa-5x text-primary"></i>
                </div>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                <p><strong>Full Name:</strong> <?php echo htmlspecialchars($user['full_name'] ?? 'Not set'); ?></p>
                <p><strong>Member Since:</strong> <?php echo formatDate($user['created_at'], 'F d, Y'); ?></p>
            </div>
        </div>
        
        <!-- 统计数据卡片 -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Library Statistics</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <h3 class="text-primary"><?php echo $stats['total_books']; ?></h3>
                        <p>Books</p>
                    </div>
                    <div class="col-6 mb-3">
                        <h3 class="text-primary"><?php echo $stats['total_authors']; ?></h3>
                        <p>Authors</p>
                    </div>
                    <div class="col-6">
                        <h3 class="text-primary"><?php echo $stats['total_borrowings']; ?></h3>
                        <p>Total Loans</p>
                    </div>
                    <div class="col-6">
                        <h3 class="text-primary"><?php echo $stats['current_borrowings']; ?></h3>
                        <p>Current Loans</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <!-- 编辑资料表单 -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Edit Profile</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errors) && isset($_POST['action']) && $_POST['action'] === 'update_profile'): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                        <div class="form-text">Username cannot be changed.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- 修改密码表单 -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Change Password</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errors) && isset($_POST['action']) && $_POST['action'] === 'change_password'): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <div class="form-text">Password must be at least 6 characters long.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once dirname(__FILE__) . '/../includes/footer.php';
?>