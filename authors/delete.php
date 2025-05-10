<!-- authors/delete.php -->

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

// 检查作者是否属于当前用户
$authorQuery = "SELECT * FROM authors WHERE id = $authorId AND user_id = $userId";
$author = fetchRow($authorQuery);

if (!$author) {
    setMessage('Author not found', 'danger');
    redirect('/authors/index.php');
}

// 检查是否有关联的图书
$booksQuery = "SELECT COUNT(*) as count FROM books WHERE author_id = $authorId AND user_id = $userId";
$booksCount = fetchRow($booksQuery);

if ($booksCount['count'] > 0) {
    setMessage('Cannot delete author with associated books. Please delete or update those books first.', 'danger');
    redirect('/authors/view.php?id=' . $authorId);
}

// 如果用户确认删除（通过POST请求）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
    $conn = getDBConnection();
    
    // 准备SQL语句
    $sql = "DELETE FROM authors WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    
    // 绑定参数
    $stmt->bind_param("ii", $authorId, $userId);
    
    // 执行删除
    if ($stmt->execute()) {
        setMessage('Author deleted successfully!', 'success');
        redirect('/authors/index.php');
    } else {
        setMessage('Failed to delete author. Please try again.', 'danger');
        redirect('/authors/view.php?id=' . $authorId);
    }
    
    $stmt->close();
    $conn->close();
} else {
    // 显示确认页面
    $pageTitle = 'Delete Author';
}
?>

<div class="row">
    <div class="col-md-6 offset-md-3">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h4 class="mb-0">Confirm Deletion</h4>
            </div>
            <div class="card-body">
                <p>Are you sure you want to delete the author "<strong><?php echo htmlspecialchars($author['name']); ?></strong>"?</p>
                <p><strong>This action cannot be undone.</strong></p>
                
                <form method="post" action="<?php echo SITE_URL; ?>/authors/delete.php?id=<?php echo $authorId; ?>">
                    <input type="hidden" name="confirm_delete" value="yes">
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-danger">Delete</button>
                        <a href="<?php echo SITE_URL; ?>/authors/view.php?id=<?php echo $authorId; ?>" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once dirname(__FILE__) . '/../includes/footer.php';
?>