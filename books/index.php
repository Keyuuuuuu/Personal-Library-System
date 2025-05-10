<!-- books/index.php -->

<?php
$pageTitle = 'My Books';
require_once dirname(__FILE__) . '/../includes/header.php';

// 检查用户是否已登录
requireLogin();

// 获取当前用户ID
$userId = $_SESSION['user_id'];

// 处理分页
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12; // 每页显示的书籍数量
$offset = ($page - 1) * $limit;

// 处理搜索
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$searchCondition = '';
if (!empty($search)) {
    $searchCondition = " AND (b.title LIKE '%$search%' OR a.name LIKE '%$search%' OR b.genre LIKE '%$search%' OR b.isbn LIKE '%$search%')";
}

// 处理过滤
$genre = isset($_GET['genre']) ? sanitize($_GET['genre']) : '';
$genreCondition = '';
if (!empty($genre)) {
    $genreCondition = " AND b.genre = '$genre'";
}

// 查询总记录数
$totalQuery = "SELECT COUNT(*) as total FROM books b 
               LEFT JOIN authors a ON b.author_id = a.id 
               WHERE b.user_id = $userId $searchCondition $genreCondition";
$totalResult = fetchRow($totalQuery);
$total = $totalResult['total'];
$totalPages = ceil($total / $limit);

// 获取用户的所有图书
$booksQuery = "SELECT b.*, a.name as author_name 
               FROM books b 
               LEFT JOIN authors a ON b.author_id = a.id 
               WHERE b.user_id = $userId $searchCondition $genreCondition
               ORDER BY b.title ASC 
               LIMIT $offset, $limit";
$books = fetchAll($booksQuery);

// 获取所有类别用于过滤
$genresQuery = "SELECT DISTINCT genre FROM books WHERE user_id = $userId AND genre IS NOT NULL AND genre != '' ORDER BY genre ASC";
$genres = fetchAll($genresQuery);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>My Books</h1>
    <a href="<?php echo SITE_URL; ?>/books/add.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add New Book
    </a>
</div>

<!-- 搜索和过滤 -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="<?php echo SITE_URL; ?>/books/index.php" class="row g-3">
            <div class="col-md-6">
                <div class="input-group">
                    <input type="text" class="form-control" name="search" placeholder="Search by title, author, genre or ISBN" value="<?php echo $search; ?>">
                    <button class="btn btn-outline-primary" type="submit">Search</button>
                    <?php if (!empty($search) || !empty($genre)): ?>
                        <a href="<?php echo SITE_URL; ?>/books/index.php" class="btn btn-outline-secondary">Clear</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4">
                <select name="genre" class="form-select" onchange="this.form.submit()">
                    <option value="">All Genres</option>
                    <?php foreach ($genres as $g): ?>
                        <option value="<?php echo htmlspecialchars($g['genre']); ?>" <?php echo $genre == $g['genre'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($g['genre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <div class="input-group">
                    <span class="input-group-text">Total:</span>
                    <input type="text" class="form-control" value="<?php echo $total; ?>" readonly>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if (empty($books)): ?>
    <div class="alert alert-info">
        No books found. <?php echo empty($search) && empty($genre) ? 'Add some books to your collection.' : 'Try a different search or filter.'; ?>
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($books as $book): ?>
            <div class="col-md-3 col-sm-6 mb-4 book-item">
                <div class="card h-100 book-card">
                    <?php if ($book['available']): ?>
                        <div class="badge bg-success position-absolute top-0 end-0 m-2">Available</div>
                    <?php else: ?>
                        <div class="badge bg-danger position-absolute top-0 end-0 m-2">Borrowed</div>
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($book['title']); ?></h5>
                        <p class="card-text">
                            <strong>Author:</strong> <span class="book-author"><?php echo htmlspecialchars($book['author_name'] ?? 'Unknown'); ?></span><br>
                            <?php if (!empty($book['genre'])): ?>
                                <strong>Genre:</strong> <span class="book-genre"><?php echo htmlspecialchars($book['genre']); ?></span><br>
                            <?php endif; ?>
                            <?php if (!empty($book['publication_year'])): ?>
                                <strong>Year:</strong> <?php echo htmlspecialchars($book['publication_year']); ?><br>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="card-footer">
                        <div class="btn-group w-100">
                            <a href="<?php echo SITE_URL; ?>/books/view.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                            <a href="<?php echo SITE_URL; ?>/books/edit.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-outline-secondary">Edit</a>
                            <?php if ($book['available']): ?>
                                <a href="<?php echo SITE_URL; ?>/borrowings/add.php?book_id=<?php echo $book['id']; ?>" class="btn btn-sm btn-outline-success">Lend</a>
                            <?php else: ?>
                                <a href="<?php echo SITE_URL; ?>/borrowings/index.php?book_id=<?php echo $book['id']; ?>" class="btn btn-sm btn-outline-info">Borrowing</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- 分页 -->
    <?php if ($totalPages > 1): ?>
        <?php
        $url = SITE_URL . '/books/index.php';
        if (!empty($search)) {
            $url .= '?search=' . urlencode($search);
            if (!empty($genre)) {
                $url .= '&genre=' . urlencode($genre);
            }
        } elseif (!empty($genre)) {
            $url .= '?genre=' . urlencode($genre);
        } else {
            $url .= '?';
        }
        if (strpos($url, '?') !== false) {
            $url .= '&';
        } else {
            $url .= '?';
        }
        
        // 使用分页函数
        echo pagination($page, $totalPages, $url);
        ?>
    <?php endif; ?>
<?php endif; ?>

<?php
require_once dirname(__FILE__) . '/../includes/footer.php';
?>