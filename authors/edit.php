<!-- authors/edit.php -->

<?php
require_once dirname(__FILE__) . '/../includes/header.php';

// 检查用户是否已登录
requireLogin();

// 获取作者ID
$authorId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($authorId <= 0) {
    setMessage('Invalid author ID', 'danger');
    redirect('/authors/index.php');
}

// 获取当前用户ID
$userId = $_SESSION['user_id'];

// 获取作者详情
$authorQuery = "SELECT * FROM authors WHERE id = $authorId AND user_id = $userId";
$author = fetchRow($authorQuery);

if (!$author) {
    setMessage('Author not found', 'danger');
    redirect('/authors/index.php');
}

// 设置页面标题
$pageTitle = 'Edit Author: ' . $author['name'];

$errors = [];

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取表单数据
    $name = sanitize($_POST['name']);
    $birth_date = !empty($_POST['birth_date']) ? $_POST['birth_date'] : null;
    $death_date = !empty($_POST['death_date']) ? $_POST['death_date'] : null;
    $biography = sanitize($_POST['biography']);
    
    // 验证输入
    if (empty($name)) {
        $errors[] = 'Author name is required';
    }
    
    // 验证日期
    if (!empty($birth_date) && !empty($death_date)) {
        $birthDate = new DateTime($birth_date);
        $deathDate = new DateTime($death_date);
        
        if ($birthDate > $deathDate) {
            $errors[] = 'Birth date cannot be later than death date';
        }
    }
    
    // 如果没有错误，更新作者
    if (empty($errors)) {
        $conn = getDBConnection();
        
        // 准备SQL语句
        $sql = "UPDATE authors SET name = ?, birth_date = ?, death_date = ?, biography = ? WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        
        // 绑定参数
        $stmt->bind_param("ssssii", $name, $birth_date, $death_date, $biography, $authorId, $userId);
        
        // 执行更新
        if ($stmt->execute()) {
            setMessage('Author updated successfully!', 'success');
            redirect('/authors/view.php?id=' . $authorId);
        } else {
            $errors[] = 'Failed to update author. Please try again.';
        }
        
        $stmt->close();
        $conn->close();
    }
} else {
    // 预填表单
    $name = $author['name'];
    $birth_date = $author['birth_date'];
    $death_date = $author['death_date'];
    $biography = $author['biography'];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Edit Author: <?php echo htmlspecialchars($author['name']); ?></h1>
    <a href="<?php echo SITE_URL; ?>/authors/view.php?id=<?php echo $author['id']; ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Back to Author
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $author['id']; ?>">
            <div class="mb-3">
                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="birth_date" class="form-label">Birth Date</label>
                    <input type="date" class="form-control" id="birth_date" name="birth_date" value="<?php echo $birth_date; ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="death_date" class="form-label">Death Date</label>
                    <input type="date" class="form-control" id="death_date" name="death_date" value="<?php echo $death_date; ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label for="biography" class="form-label">Biography</label>
                <textarea class="form-control" id="biography" name="biography" rows="5"><?php echo htmlspecialchars($biography); ?></textarea>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" class="btn btn-primary">Update Author</button>
                <a href="<?php echo SITE_URL; ?>/authors/view.php?id=<?php echo $author['id']; ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
require_once dirname(__FILE__) . '/../includes/footer.php';
?>