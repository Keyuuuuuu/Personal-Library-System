<!-- authors/view.php -->

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
$pageTitle = $author['name'];

// 获取该作者的图书
$booksQuery = "SELECT * FROM books WHERE author_id = $authorId AND user_id = $userId ORDER BY title ASC";
$books = fetchAll($booksQuery);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?php echo htmlspecialchars($author['name']); ?></h1>
    <div>
        <a href="<?php echo SITE_URL; ?>/authors/edit.php?id=<?php echo $author['id']; ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit
        </a>
        <a href="<?php echo SITE_URL; ?>/authors/index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Authors
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Author Details</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($author['birth_date']) && $author['birth_date'] != '0000-00-00'): ?>
                    <p><strong>Birth Date:</strong> <?php echo date('F d, Y', strtotime($author['birth_date'])); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($author['death_date']) && $author['death_date'] != '0000-00-00'): ?>
                    <p><strong>Death Date:</strong> <?php echo date('F d, Y', strtotime($author['death_date'])); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($author['biography'])): ?>
                    <h5 class="mt-4">Biography</h5>
                    <p><?php echo nl2br(htmlspecialchars($author['biography'])); ?></p>
                <?php else: ?>
                    <p class="text-muted">No biography available.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Books by this Author</h5>
                <a href="<?php echo SITE_URL; ?>/books/add.php?author_id=<?php echo $author['id']; ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus"></i> Add Book for this Author
                </a>
            </div>
            
            <?php if (empty($books)): ?>
                <div class="card-body">
                    <p class="text-muted">No books found for this author.</p>
                </div>
            <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($books as $book): ?>
                        <a href="<?php echo SITE_URL; ?>/books/view.php?id=<?php echo $book['id']; ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1"><?php echo htmlspecialchars($book['title']); ?></h5>
                                <small class="text-<?php echo $book['available'] ? 'success' : 'danger'; ?>">
                                    <?php echo $book['available'] ? 'Available' : 'Borrowed'; ?>
                                </small>
                            </div>
                            <p class="mb-1">
                                <?php 
                                $details = [];
                                if (!empty($book['publication_year'])) $details[] = $book['publication_year'];
                                if (!empty($book['genre'])) $details[] = $book['genre'];
                                if (!empty($book['language'])) $details[] = $book['language'];
                                echo !empty($details) ? implode(' • ', $details) : 'No details available';
                                ?>
                            </p>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Statistics</h5>
            </div>
            <div class="card-body">
                <p><strong>Total Books:</strong> <?php echo count($books); ?></p>
                
                <?php
                // 计算可用和借出的书籍数量
                $availableCount = 0;
                $borrowedCount = 0;
                
                foreach ($books as $book) {
                    if ($book['available']) {
                        $availableCount++;
                    } else {
                        $borrowedCount++;
                    }
                }
                ?>
                
                <p><strong>Available Books:</strong> <?php echo $availableCount; ?></p>
                <p><strong>Borrowed Books:</strong> <?php echo $borrowedCount; ?></p>
                
                <?php if (!empty($books)): ?>
                    <?php
                    // 统计流派
                    $genres = [];
                    foreach ($books as $book) {
                        if (!empty($book['genre'])) {
                            if (isset($genres[$book['genre']])) {
                                $genres[$book['genre']]++;
                            } else {
                                $genres[$book['genre']] = 1;
                            }
                        }
                    }
                    
                    // 按数量排序
                    arsort($genres);
                    ?>
                    
                    <?php if (!empty($genres)): ?>
                        <h6 class="mt-4">Top Genres</h6>
                        <ul class="list-group list-group-flush">
                            <?php foreach (array_slice($genres, 0, 3) as $genre => $count): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo htmlspecialchars($genre); ?>
                                    <span class="badge bg-primary rounded-pill"><?php echo $count; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php endif; ?>
                
                <div class="mt-4">
                    <a href="<?php echo SITE_URL; ?>/authors/delete.php?id=<?php echo $author['id']; ?>" class="btn btn-danger confirm-delete">
                        <i class="fas fa-trash"></i> Delete Author
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once dirname(__FILE__) . '/../includes/footer.php';
?>