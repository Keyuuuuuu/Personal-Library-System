<!-- borrowings/edit.php -->

<?php
$pageTitle = 'Edit Borrowing';
require_once dirname(__FILE__) . '/../includes/header.php';

// 检查用户是否已登录
requireLogin();

// 获取借阅记录ID
$borrowingId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($borrowingId <= 0) {
    setMessage('Invalid borrowing ID', 'danger');
    redirect('/borrowings/index.php');
}

// 获取当前用户ID
$userId = $_SESSION['user_id'];

// 获取借阅记录信息
$borrowingQuery = "SELECT b.*, bk.title as book_title 
                 FROM borrowings b
                 JOIN books bk ON b.book_id = bk.id
                 WHERE b.id = $borrowingId AND b.user_id = $userId";
$borrowing = fetchRow($borrowingQuery);

if (!$borrowing) {
    setMessage('Borrowing record not found', 'danger');
    redirect('/borrowings/index.php');
}

$errors = [];

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取表单数据
    $borrowerName = sanitize($_POST['borrower_name']);
    $borrowedDate = sanitize($_POST['borrowed_date']);
    $dueDate = sanitize($_POST['due_date']);
    $returnedDate = !empty($_POST['returned_date']) ? sanitize($_POST['returned_date']) : null;
    $notes = sanitize($_POST['notes']);
    
    // 验证输入
    if (empty($borrowerName)) {
        $errors[] = 'Borrower name is required';
    }
    
    if (empty($borrowedDate)) {
        $errors[] = 'Borrowed date is required';
    }
    
    if (empty($dueDate)) {
        $errors[] = 'Due date is required';
    }
    
    // 验证日期
    if (!empty($borrowedDate) && !empty($dueDate)) {
        $borrowedDateObj = new DateTime($borrowedDate);
        $dueDateObj = new DateTime($dueDate);
        
        if ($dueDateObj <= $borrowedDateObj) {
            $errors[] = 'Due date must be later than the borrowed date';
        }
    }
    
    if (!empty($returnedDate) && !empty($borrowedDate)) {
        $borrowedDateObj = new DateTime($borrowedDate);
        $returnedDateObj = new DateTime($returnedDate);
        
        if ($returnedDateObj < $borrowedDateObj) {
            $errors[] = 'Return date cannot be earlier than the borrowed date';
        }
    }
    
    // 如果没有错误，更新借阅记录
    if (empty($errors)) {
        // 开始事务
        $conn = getDBConnection();
        $conn->begin_transaction();
        
        try {
            // 更新借阅记录
            $stmt = $conn->prepare("UPDATE borrowings SET borrower_name = ?, borrowed_date = ?, due_date = ?, returned_date = ?, notes = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("sssssii", $borrowerName, $borrowedDate, $dueDate, $returnedDate, $notes, $borrowingId, $userId);
            $stmt->execute();
            $stmt->close();
            
            // 更新图书状态
            $available = is_null($returnedDate) ? 0 : 1;
            $stmt = $conn->prepare("UPDATE books SET available = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("iii", $available, $borrowing['book_id'], $userId);
            $stmt->execute();
            $stmt->close();
            
            // 提交事务
            $conn->commit();
            
            setMessage('Borrowing record updated successfully!', 'success');
            redirect('/borrowings/index.php');
        } catch (Exception $e) {
            // 回滚事务
            $conn->rollback();
            $errors[] = 'Failed to update borrowing record: ' . $e->getMessage();
        }
        
        $conn->close();
    }
} else {
    // 预填表单
    $borrowerName = $borrowing['borrower_name'];
    $borrowedDate = $borrowing['borrowed_date'];
    $dueDate = $borrowing['due_date'];
    $returnedDate = $borrowing['returned_date'];
    $notes = $borrowing['notes'];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Edit Borrowing</h1>
    <a href="<?php echo SITE_URL; ?>/borrowings/index.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Back to Borrowings
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
    <div class="card-header">
        <h5 class="mb-0">Book: <?php echo htmlspecialchars($borrowing['book_title']); ?></h5>
    </div>
    <div class="card-body">
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $borrowingId; ?>">
            <div class="mb-3">
                <label for="borrower_name" class="form-label">Borrower Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="borrower_name" name="borrower_name" value="<?php echo htmlspecialchars($borrowerName); ?>" required>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="borrowed_date" class="form-label">Borrowed Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="borrowed_date" name="borrowed_date" value="<?php echo $borrowedDate; ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="due_date" name="due_date" value="<?php echo $dueDate; ?>" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="returned_date" class="form-label">Return Date</label>
                <input type="date" class="form-control" id="returned_date" name="returned_date" value="<?php echo $returnedDate; ?>">
                <div class="form-text">Leave empty if the book has not been returned yet.</div>
            </div>
            
            <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($notes); ?></textarea>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" class="btn btn-primary">Update Borrowing</button>
                <a href="<?php echo SITE_URL; ?>/borrowings/index.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
require_once dirname(__FILE__) . '/../includes/footer.php';
?>