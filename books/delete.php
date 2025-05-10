<!-- books/delete.php -->

<?php
require_once dirname(__FILE__) . '/../includes/header.php';

// 检查用户是否已登录
requireLogin();

// 获取图书ID
$bookId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($bookId <= 0) {
    setMessage('Invalid book ID', 'danger');
    redirect('/books/index.php');
}

// 获取当前用户ID
$userId = $_SESSION['user_id'];

// 检查图书是否属于当前用户
if (!isBookOwner($bookId)) {
    setMessage('You do not have permission to delete this book', 'danger');
    redirect('/books/index.php');
}

// 获取图书信息
$bookQuery = "SELECT title FROM books WHERE id = $bookId AND user_id = $userId";
$book = fetchRow($bookQuery);

if (!$book) {
    setMessage('Book not found', 'danger');
    redirect('/books/index.php');
}

// 检查是否有关联的借阅记录
$borrowingsQuery = "SELECT COUNT(*) as count FROM borrowings WHERE book_id = $bookId AND returned_date IS NULL";
$borrowingsCount = fetchRow($borrowingsQuery);

if ($borrowingsCount && $borrowingsCount['count'] > 0) {
    setMessage('Cannot delete book that is currently borrowed. Please mark it as returned first.', 'danger');
    redirect('/books/view.php?id=' . $bookId);
}

// 如果用户确认删除
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
    // 先删除相关的借阅记录
    delete('borrowings', 'book_id = ?', 'i', [$bookId]);
    
    // 然后删除图书
    $result = delete('books', 'id = ? AND user_id = ?', 'ii', [$bookId, $userId]);
    
    if ($result) {
        setMessage('Book deleted successfully', 'success');
        redirect('/books/index.php');
    } else {
        setMessage('Failed to delete book', 'danger');
        redirect('/books/view.php?id=' . $bookId);
    }
} else {
    // 显示确认页面
    $pageTitle = 'Delete Book';
}
?>

<div class="row">
    <div class="col-md-6 offset-md-3">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h4 class="mb-0">Confirm Deletion</h4>
            </div>
            <div class="card-body">
                <p>Are you sure you want to delete the book "<strong><?php echo htmlspecialchars($book['title']); ?></strong>"?</p>
                <p><strong>This action cannot be undone.</strong> All borrowing records associated with this book will also be deleted.</p>
                
                <form method="post" action="<?php echo SITE_URL; ?>/books/delete.php?id=<?php echo $bookId; ?>">
                    <input type="hidden" name="confirm_delete" value="yes">
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-danger">Delete</button>
                        <a href="<?php echo SITE_URL; ?>/books/view.php?id=<?php echo $bookId; ?>" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once dirname(__FILE__) . '/../includes/footer.php';
?>