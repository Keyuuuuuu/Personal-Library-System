<!-- borrowings/return.php -->

<?php
$pageTitle = 'Return Book';
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

// 如果已归还，重定向回列表
if (!empty($borrowing['returned_date'])) {
    setMessage('This book has already been returned', 'info');
    redirect('/borrowings/index.php');
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取归还日期
    $returnedDate = isset($_POST['returned_date']) && !empty($_POST['returned_date']) 
                  ? sanitize($_POST['returned_date']) 
                  : date('Y-m-d'); // 默认今天
    
    // 验证归还日期不早于借出日期
    if (strtotime($returnedDate) < strtotime($borrowing['borrowed_date'])) {
        setMessage('Return date cannot be earlier than the borrowed date', 'danger');
    } else {
        // 开始事务
        $conn = getDBConnection();
        $conn->begin_transaction();
        
        try {
            // 更新借阅记录的归还日期
            $stmt = $conn->prepare("UPDATE borrowings SET returned_date = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("sii", $returnedDate, $borrowingId, $userId);
            $stmt->execute();
            $stmt->close();
            
            // 更新图书状态为可用
            $stmt = $conn->prepare("UPDATE books SET available = 1 WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $borrowing['book_id'], $userId);
            $stmt->execute();
            $stmt->close();
            
            // 提交事务
            $conn->commit();
            
            setMessage('Book has been marked as returned successfully!', 'success');
            redirect('/borrowings/index.php');
        } catch (Exception $e) {
            // 回滚事务
            $conn->rollback();
            setMessage('Failed to mark book as returned: ' . $e->getMessage(), 'danger');
        }
        
        $conn->close();
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Return Book</h1>
    <a href="<?php echo SITE_URL; ?>/borrowings/index.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Back to Borrowings
    </a>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Borrowing Details</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Book:</strong> <?php echo htmlspecialchars($borrowing['book_title']); ?></p>
                <p><strong>Borrower:</strong> <?php echo htmlspecialchars($borrowing['borrower_name']); ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Borrowed Date:</strong> <?php echo formatDate($borrowing['borrowed_date']); ?></p>
                <p><strong>Due Date:</strong> <?php echo formatDate($borrowing['due_date']); ?></p>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Return Information</h5>
    </div>
    <div class="card-body">
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $borrowingId; ?>">
            <div class="mb-3">
                <label for="returned_date" class="form-label">Return Date</label>
                <input type="date" class="form-control" id="returned_date" name="returned_date" value="<?php echo date('Y-m-d'); ?>">
                <div class="form-text">Leave as today's date or select when the book was actually returned.</div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" class="btn btn-success">Mark as Returned</button>
                <a href="<?php echo SITE_URL; ?>/borrowings/index.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
require_once dirname(__FILE__) . '/../includes/footer.php';
?>