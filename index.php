<?php
$pageTitle = 'Home';
require_once 'includes/header.php';
?>

<div class="jumbotron bg-light p-5 rounded">
    <h1 class="display-4">Welcome to Personal Library System</h1>
    <p class="lead">Manage your book collection, track authors, and keep records of book borrowings all in one place.</p>
    
    <?php if (!isLoggedIn()): ?>
        <hr class="my-4">
        <p>Get started by creating an account or logging in.</p>
        <div class="d-flex gap-2">
            <a class="btn btn-primary btn-lg" href="<?php echo SITE_URL; ?>/auth/register.php" role="button">Register</a>
            <a class="btn btn-outline-primary btn-lg" href="<?php echo SITE_URL; ?>/auth/login.php" role="button">Login</a>
        </div>
    <?php else: ?>
        <hr class="my-4">
        <p>Explore your personal library or add new books to your collection.</p>
        <div class="d-flex gap-2">
            <a class="btn btn-primary btn-lg" href="<?php echo SITE_URL; ?>/books/index.php" role="button">View Books</a>
            <a class="btn btn-outline-primary btn-lg" href="<?php echo SITE_URL; ?>/books/add.php" role="button">Add New Book</a>
        </div>
    <?php endif; ?>
</div>

<?php if (isLoggedIn()): ?>
    <div class="row mt-5">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <i class="fas fa-book fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">Manage Books</h5>
                    <p class="card-text">Add, edit, and organize your book collection.</p>
                    <a href="<?php echo SITE_URL; ?>/books/index.php" class="btn btn-primary">Go to Books</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <i class="fas fa-user-edit fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">Manage Authors</h5>
                    <p class="card-text">Keep track of all your favorite authors.</p>
                    <a href="<?php echo SITE_URL; ?>/authors/index.php" class="btn btn-primary">Go to Authors</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <i class="fas fa-handshake fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">Track Borrowings</h5>
                    <p class="card-text">Record when you lend your books to others.</p>
                    <a href="<?php echo SITE_URL; ?>/borrowings/index.php" class="btn btn-primary">Go to Borrowings</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 最近添加的书籍 -->
    <div class="mt-5">
        <h2>Recently Added Books</h2>
        <hr>
        
        <div class="row">
            <?php
            // 获取最近添加的4本书
            $userId = $_SESSION['user_id'];
            $recentBooks = fetchAll("SELECT b.*, a.name as author_name 
                                    FROM books b 
                                    LEFT JOIN authors a ON b.author_id = a.id 
                                    WHERE b.user_id = $userId 
                                    ORDER BY b.created_at DESC 
                                    LIMIT 4");
            
            if (!empty($recentBooks)):
                foreach ($recentBooks as $book):
            ?>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="card h-100 book-card">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($book['title']); ?></h5>
                            <p class="card-text">
                                <strong>Author:</strong> <?php echo htmlspecialchars($book['author_name'] ?? 'Unknown'); ?><br>
                                <strong>Genre:</strong> <?php echo htmlspecialchars($book['genre'] ?? 'N/A'); ?>
                            </p>
                        </div>
                        <div class="card-footer">
                            <a href="<?php echo SITE_URL; ?>/books/view.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                        </div>
                    </div>
                </div>
            <?php 
                endforeach;
            else:
            ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        You haven't added any books yet. <a href="<?php echo SITE_URL; ?>/books/add.php">Add your first book</a> to get started.
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($recentBooks)): ?>
            <div class="text-center mt-3">
                <a href="<?php echo SITE_URL; ?>/books/index.php" class="btn btn-outline-primary">View All Books</a>
            </div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <!-- 功能介绍 -->
    <div class="row mt-5">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <i class="fas fa-book fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">Book Management</h5>
                    <p class="card-text">Create a digital catalog of your entire book collection with details like genre, ISBN, and more.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <i class="fas fa-user-edit fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">Author Tracking</h5>
                    <p class="card-text">Maintain information about your favorite authors and their published works in your collection.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <i class="fas fa-handshake fa-3x text-primary mb-3"></i>
                    <h5 class="card-title">Borrowing Records</h5>
                    <p class="card-text">Never lose track of borrowed books again. Record who borrowed what and when it's due back.</p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
require_once 'includes/footer.php';
?>