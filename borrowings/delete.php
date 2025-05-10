<!-- borrowings/delete.php -->

<?php
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

// 如果用户确认删除
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
    // 开始事务
    $conn = getDBConnection();
    $conn->begin_transaction();
    
    try {
        // 删除借阅记录
        $stmt = $conn->prepare("DELETE FROM borrowings WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $borrowingId, $userId);
        $stmt->execute();
        $stmt->close();
        
        // 如果书籍没有被归还（currently borrowed），更新图书状态为可用
        if (empty($borrowing['returned_date'])) {
            $stmt = $conn->prepare("UPDATE books SET available = 1 WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $borrowing['book_id'], $userId);
            $stmt->execute();
            $stmt->close();
        }
        
        // 提交事务
        $conn->commit();
        
        setMessage('Borrowing record deleted successfully', 'success');
        redirect('/borrowings/index.php');
    } catch (Exception $e) {
        // 回滚事务
        $conn->rollback();
        setMessage('Failed to delete borrowing record: ' . $e->getMessage(), 'danger');
        redirect('/borrowings/index.php');
    }
    
    $conn->close();
} else {
    // 显示确认页面
    $pageTitle = 'Delete Borrowing Record';
}
?>

<div class="row">
    <div class="col-md-6 offset-md-3">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h4 class="mb-0">Confirm Deletion</h4>
            </div>
            <div class="card-body">
                <p>Are you sure you want to delete this borrowing record?</p>
                
                <div class="mb-3">
                    <strong>Book:</strong> <?php echo htmlspecialchars($borrowing['book_title']); ?><br>
                    <strong>Borrower:</strong> <?php echo htmlspecialchars($borrowing['borrower_name']); ?><br>
                    <strong>Borrowed Date:</strong> <?php echo formatDate($borrowing['borrowed_date']); ?><br>
                    <strong>Due Date:</strong> <?php echo formatDate($borrowing['due_date']); ?><br>
                    <?php if (!empty($borrowing['returned_date'])): ?>
                        <strong>Returned Date:</strong> <?php echo formatDate($borrowing['returned_date']); ?>
                    <?php else: ?>
                        <strong>Status:</strong> <span class="text-danger">Currently borrowed</span>
                    <?php endif; ?>
                </div>
                
                <p><strong>This action cannot be undone.</strong></p>
                
                <?php if (empty($borrowing['returned_date'])): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> Warning: This book is currently marked as borrowed. Deleting this record will mark the book as available again.
                    </div>
                <?php endif; ?>
                
                <form method="post" action="<?php echo SITE_URL; ?>/borrowings/delete.php?id=<?php echo $borrowingId; ?>">
                    <input type="hidden" name="confirm_delete" value="yes">
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-danger">Delete</button>
                        <a href="<?php echo SITE_URL; ?>/borrowings/index.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once dirname(__FILE__) . '/../includes/footer.php';
?>