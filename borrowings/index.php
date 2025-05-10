<!-- borrowings/index.php -->

<?php
$pageTitle = 'Borrowings';
require_once dirname(__FILE__) . '/../includes/header.php';

// 检查用户是否已登录
requireLogin();

// 获取当前用户ID
$userId = $_SESSION['user_id'];

// 处理过滤器
$filter = isset($_GET['filter']) ? sanitize($_GET['filter']) : 'all';
$bookId = isset($_GET['book_id']) ? (int)$_GET['book_id'] : 0;

// 根据过滤器构建查询条件
$filterCondition = '';
switch ($filter) {
    case 'current':
        $filterCondition = ' AND returned_date IS NULL';
        break;
    case 'returned':
        $filterCondition = ' AND returned_date IS NOT NULL';
        break;
    case 'overdue':
        $filterCondition = ' AND returned_date IS NULL AND due_date < CURDATE()';
        break;
    default:
        $filterCondition = '';
}

// 如果指定了特定书籍，添加额外的过滤条件
$bookCondition = '';
if ($bookId > 0) {
    $bookCondition = " AND b.book_id = $bookId";
    
    // 获取书籍信息
    $bookQuery = "SELECT title FROM books WHERE id = $bookId AND user_id = $userId";
    $book = fetchRow($bookQuery);
    
    if ($book) {
        $pageTitle = 'Borrowings for "' . $book['title'] . '"';
    }
}

// 构建查询
$borrowingsQuery = "SELECT b.*, bk.title as book_title, bk.isbn
                  FROM borrowings b
                  JOIN books bk ON b.book_id = bk.id
                  WHERE b.user_id = $userId $filterCondition $bookCondition
                  ORDER BY b.borrowed_date DESC";
$borrowings = fetchAll($borrowingsQuery);

// 获取统计数据
$statsQuery = "SELECT 
              COUNT(*) as total,
              SUM(CASE WHEN returned_date IS NULL THEN 1 ELSE 0 END) as current,
              SUM(CASE WHEN returned_date IS NOT NULL THEN 1 ELSE 0 END) as returned,
              SUM(CASE WHEN returned_date IS NULL AND due_date < CURDATE() THEN 1 ELSE 0 END) as overdue
              FROM borrowings 
              WHERE user_id = $userId";
$stats = fetchRow($statsQuery);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?php echo $pageTitle; ?></h1>
    <a href="<?php echo SITE_URL; ?>/books/index.php" class="btn btn-primary">
        <i class="fas fa-book"></i> Browse Books to Lend
    </a>
</div>

<!-- 过滤器 -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="btn-group" role="group" aria-label="Borrowing filters">
                    <a href="<?php echo SITE_URL; ?>/borrowings/index.php<?php echo $bookId ? '?book_id=' . $bookId : ''; ?>" class="btn btn-outline-primary <?php echo $filter === 'all' ? 'active' : ''; ?>">
                        All <span class="badge bg-secondary"><?php echo $stats['total']; ?></span>
                    </a>
                    <a href="<?php echo SITE_URL; ?>/borrowings/index.php?filter=current<?php echo $bookId ? '&book_id=' . $bookId : ''; ?>" class="btn btn-outline-primary <?php echo $filter === 'current' ? 'active' : ''; ?>">
                        Current <span class="badge bg-secondary"><?php echo $stats['current']; ?></span>
                    </a>
                    <a href="<?php echo SITE_URL; ?>/borrowings/index.php?filter=returned<?php echo $bookId ? '&book_id=' . $bookId : ''; ?>" class="btn btn-outline-primary <?php echo $filter === 'returned' ? 'active' : ''; ?>">
                        Returned <span class="badge bg-secondary"><?php echo $stats['returned']; ?></span>
                    </a>
                    <a href="<?php echo SITE_URL; ?>/borrowings/index.php?filter=overdue<?php echo $bookId ? '&book_id=' . $bookId : ''; ?>" class="btn btn-outline-primary <?php echo $filter === 'overdue' ? 'active' : ''; ?>">
                        Overdue <span class="badge bg-danger"><?php echo $stats['overdue']; ?></span>
                    </a>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <?php if ($bookId): ?>
                    <a href="<?php echo SITE_URL; ?>/borrowings/index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear Book Filter
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (empty($borrowings)): ?>
    <div class="alert alert-info">
        No borrowing records found.
        <?php if ($filter !== 'all' || $bookId): ?>
            Try changing your filters or <a href="<?php echo SITE_URL; ?>/borrowings/index.php">view all borrowings</a>.
        <?php else: ?>
            Start by <a href="<?php echo SITE_URL; ?>/books/index.php">lending a book</a>.
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Book</th>
                    <th>Borrower</th>
                    <th>Borrowed Date</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($borrowings as $borrowing): ?>
                    <tr>
                        <td>
                            <a href="<?php echo SITE_URL; ?>/books/view.php?id=<?php echo $borrowing['book_id']; ?>">
                                <?php echo htmlspecialchars($borrowing['book_title']); ?>
                            </a>
                            <?php if (!empty($borrowing['isbn'])): ?>
                                <br><small class="text-muted">ISBN: <?php echo htmlspecialchars($borrowing['isbn']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($borrowing['borrower_name']); ?></td>
                        <td><?php echo formatDate($borrowing['borrowed_date']); ?></td>
                        <td>
                            <?php echo formatDate($borrowing['due_date']); ?>
                            <?php if (empty($borrowing['returned_date']) && strtotime($borrowing['due_date']) < time()): ?>
                                <span class="badge bg-danger">Overdue</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (empty($borrowing['returned_date'])): ?>
                                <span class="badge bg-warning">Borrowed</span>
                            <?php else: ?>
                                <span class="badge bg-success">Returned on <?php echo formatDate($borrowing['returned_date']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (empty($borrowing['returned_date'])): ?>
                                <a href="<?php echo SITE_URL; ?>/borrowings/return.php?id=<?php echo $borrowing['id']; ?>" class="btn btn-sm btn-success">
                                    <i class="fas fa-check"></i> Mark Returned
                                </a>
                            <?php endif; ?>
                            <a href="<?php echo SITE_URL; ?>/borrowings/edit.php?id=<?php echo $borrowing['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="<?php echo SITE_URL; ?>/borrowings/delete.php?id=<?php echo $borrowing['id']; ?>" class="btn btn-sm btn-danger confirm-delete">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php
require_once dirname(__FILE__) . '/../includes/footer.php';
?>