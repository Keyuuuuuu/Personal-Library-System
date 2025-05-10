<!-- authors/add.php -->

<?php
$pageTitle = 'Add New Author';
require_once dirname(__FILE__) . '/../includes/header.php';

// 检查用户是否已登录
requireLogin();

// 获取当前用户ID
$userId = $_SESSION['user_id'];

$errors = [];
$author = [
    'name' => '',
    'birth_date' => '',
    'death_date' => '',
    'biography' => ''
];

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取表单数据
    $author['name'] = sanitize($_POST['name']);
    $author['birth_date'] = !empty($_POST['birth_date']) ? $_POST['birth_date'] : null;
    $author['death_date'] = !empty($_POST['death_date']) ? $_POST['death_date'] : null;
    $author['biography'] = sanitize($_POST['biography']);
    
    // 验证输入
    if (empty($author['name'])) {
        $errors[] = 'Author name is required';
    }
    
    // 验证日期
    if (!empty($author['birth_date']) && !empty($author['death_date'])) {
        $birthDate = new DateTime($author['birth_date']);
        $deathDate = new DateTime($author['death_date']);
        
        if ($birthDate > $deathDate) {
            $errors[] = 'Birth date cannot be later than death date';
        }
    }
    
    // 如果没有错误，添加作者
    if (empty($errors)) {
        $conn = getDBConnection();
        
        // 准备SQL语句
        $sql = "INSERT INTO authors (name, birth_date, death_date, biography, user_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        // 绑定参数
        $stmt->bind_param("ssssi", 
            $author['name'],
            $author['birth_date'],
            $author['death_date'],
            $author['biography'],
            $userId
        );
        
        // 执行插入
        if ($stmt->execute()) {
            $authorId = $conn->insert_id;
            setMessage('Author added successfully!', 'success');
            redirect('/authors/view.php?id=' . $authorId);
        } else {
            $errors[] = 'Failed to add author. Please try again.';
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Add New Author</h1>
    <a href="<?php echo SITE_URL; ?>/authors/index.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Back to Authors
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
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="mb-3">
                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo $author['name']; ?>" required>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="birth_date" class="form-label">Birth Date</label>
                    <input type="date" class="form-control" id="birth_date" name="birth_date" value="<?php echo $author['birth_date']; ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="death_date" class="form-label">Death Date</label>
                    <input type="date" class="form-control" id="death_date" name="death_date" value="<?php echo $author['death_date']; ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label for="biography" class="form-label">Biography</label>
                <textarea class="form-control" id="biography" name="biography" rows="5"><?php echo $author['biography']; ?></textarea>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" class="btn btn-primary">Add Author</button>
                <a href="<?php echo SITE_URL; ?>/authors/index.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
require_once dirname(__FILE__) . '/../includes/footer.php';
?>