<!-- books/view.php -->

<?php
$pageTitle = 'Book Details';
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

// 获取图书详情
$bookQuery = "SELECT b.*, a.name as author_name 
              FROM books b 
              LEFT JOIN authors a ON b.author_id = a.id 
              WHERE b.id = $bookId AND b.user_id = $userId";
$book = fetchRow($bookQuery);

if (!$book) {
    setMessage('Book not found', 'danger');
    redirect('/books/index.php');
}

// 设置页面标题
$pageTitle = $book['title'];

// 获取该书的借阅记录
$borrowingsQuery = "SELECT * FROM borrowings WHERE book_id = $bookId AND user_id = $userId ORDER BY borrowed_date DESC";
$borrowings = fetchAll($borrowingsQuery);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?php echo htmlspecialchars($book['title']); ?></h1>
    <div>
        <a href="<?php echo SITE_URL; ?>/books/edit.php?id=<?php echo $book['id']; ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit
        </a>
        <a href="<?php echo SITE_URL; ?>/books/index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Books
        </a>
    </div>
</div>

<div class="row book-details">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Book Details</h5>
                <span class="badge <?php echo $book['available'] ? 'bg-success' : 'bg-danger'; ?> py-2 px-3">
                    <?php echo $book['available'] ? 'Available' : 'Borrowed'; ?>
                </span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Author:</strong> 
                            <?php if (!empty($book['author_id'])): ?>
                                <a href="<?php echo SITE_URL; ?>/authors/view.php?id=<?php echo $book['author_id']; ?>">
                                    <?php echo htmlspecialchars($book['author_name']); ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">Unknown</span>
                            <?php endif; ?>
                        </p>
                        
                        <?php if (!empty($book['isbn'])): ?>
                            <p><strong>ISBN:</strong> <?php echo htmlspecialchars($book['isbn']); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($book['publisher'])): ?>
                            <p><strong>Publisher:</strong> <?php echo htmlspecialchars($book['publisher']); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($book['publication_year'])): ?>
                            <p><strong>Publication Year:</strong> <?php echo htmlspecialchars($book['publication_year']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-6">
                        <?php if (!empty($book['genre'])): ?>
                            <p><strong>Genre:</strong> <?php echo htmlspecialchars($book['genre']); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($book['language'])): ?>
                            <p><strong>Language:</strong> <?php echo htmlspecialchars($book['language']); ?></p>
                        <?php endif; ?>
                        
                        <?php if (!empty($book['page_count'])): ?>
                            <p><strong>Pages:</strong> <?php echo htmlspecialchars($book['page_count']); ?></p>
                        <?php endif; ?>
                        
                        <p><strong>Added on:</strong> <?php echo formatDate($book['created_at']); ?></p>
                    </div>
                </div>
                
                <?php if (!empty($book['description'])): ?>
                    <div class="mt-3">
                        <h5>Description</h5>
                        <p><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>
                    </div>
                <?php endif; ?>
                
                <div class="mt-4">
                    <?php if ($book['available']): ?>
                        <a href="<?php echo SITE_URL; ?>/borrowings/add.php?book_id=<?php echo $book['id']; ?>" class="btn btn-success">
                            <i class="fas fa-hand-holding"></i> Lend Book
                        </a>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>/borrowings/index.php?book_id=<?php echo $book['id']; ?>" class="btn btn-info">
                            <i class="fas fa-info-circle"></i> View Borrowing Details
                        </a>
                    <?php endif; ?>
                    
                    <a href="<?php echo SITE_URL; ?>/books/delete.php?id=<?php echo $book['id']; ?>" class="btn btn-danger confirm-delete">
                        <i class="fas fa-trash"></i> Delete Book
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <!-- 借阅历史 -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Borrowing History</h5>
            </div>
            
            <?php if (empty($borrowings)): ?>
                <div class="card-body">
                    <p class="text-muted">No borrowing records found for this book.</p>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($borrowings as $borrowing): ?>
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?php echo htmlspecialchars($borrowing['borrower_name']); ?></h6>
                                <small class="text-<?php echo empty($borrowing['returned_date']) ? 'danger' : 'success'; ?>">
                                    <?php echo empty($borrowing['returned_date']) ? 'Not Returned' : 'Returned'; ?>
                                </small>
                            </div>
                            <p class="mb-1">
                                <small>Borrowed: <?php echo formatDate($borrowing['borrowed_date']); ?></small><br>
                                <small>Due: <?php echo formatDate($borrowing['due_date']); ?></small>
                                
                                <?php if (!empty($borrowing['returned_date'])): ?>
                                    <br><small>Returned: <?php echo formatDate($borrowing['returned_date']); ?></small>
                                <?php endif; ?>
                            </p>
                            
                            <?php if (!empty($borrowing['notes'])): ?>
                                <small class="text-muted"><?php echo htmlspecialchars($borrowing['notes']); ?></small>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="card-footer">
                <a href="<?php echo SITE_URL; ?>/borrowings/index.php?book_id=<?php echo $book['id']; ?>" class="btn btn-sm btn-outline-primary">
                    View All Borrowings
                </a>
            </div>
        </div>
        
        <!-- 相关书籍 -->
        <?php if (!empty($book['author_id'])): ?>
            <?php
            $relatedBooksQuery = "SELECT * FROM books 
                                 WHERE author_id = {$book['author_id']} 
                                 AND id != $bookId 
                                 AND user_id = $userId 
                                 LIMIT 5";
            $relatedBooks = fetchAll($relatedBooksQuery);
            
            if (!empty($relatedBooks)):
            ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">More Books by <?php echo htmlspecialchars($book['author_name']); ?></h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php foreach ($relatedBooks as $relatedBook): ?>
                            <a href="<?php echo SITE_URL; ?>/books/view.php?id=<?php echo $relatedBook['id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($relatedBook['title']); ?></h6>
                                    <small><?php echo htmlspecialchars($relatedBook['publication_year'] ?? ''); ?></small>
                                </div>
                                <small class="text-muted"><?php echo htmlspecialchars($relatedBook['genre'] ?? ''); ?></small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
require_once dirname(__FILE__) . '/../includes/footer.php';
?>