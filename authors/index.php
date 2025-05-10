<!-- authors/index.php -->

<?php
$pageTitle = 'Manage Authors';
require_once dirname(__FILE__) . '/../includes/header.php';

// 检查用户是否已登录
requireLogin();

// 获取当前用户ID
$userId = $_SESSION['user_id'];

// 处理分页
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; // 每页显示的作者数量
$offset = ($page - 1) * $limit;

// 处理搜索
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$searchCondition = '';
if (!empty($search)) {
    $searchCondition = " AND name LIKE '%$search%'";
}

// 查询总记录数
$totalQuery = "SELECT COUNT(*) as total FROM authors WHERE user_id = $userId $searchCondition";
$totalResult = fetchRow($totalQuery);
$total = $totalResult['total'];
$totalPages = ceil($total / $limit);

// 获取用户的所有作者
$authorsQuery = "SELECT * FROM authors 
                WHERE user_id = $userId $searchCondition
                ORDER BY name ASC 
                LIMIT $offset, $limit";
$authors = fetchAll($authorsQuery);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Manage Authors</h1>
    <a href="<?php echo SITE_URL; ?>/authors/add.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add New Author
    </a>
</div>

<!-- 搜索框 -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" action="<?php echo SITE_URL; ?>/authors/index.php" class="row g-3">
            <div class="col-md-10">
                <div class="input-group">
                    <input type="text" class="form-control" id="filterAuthors" name="search" placeholder="Search by author name" value="<?php echo $search; ?>">
                    <button class="btn btn-outline-primary" type="submit">Search</button>
                    <?php if (!empty($search)): ?>
                        <a href="<?php echo SITE_URL; ?>/authors/index.php" class="btn btn-outline-secondary">Clear</a>
                    <?php endif; ?>
                </div>
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

<?php if (empty($authors)): ?>
    <div class="alert alert-info">
        No authors found. <?php echo empty($search) ? 'Add some authors to your collection.' : 'Try a different search term.'; ?>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Name</th>
                    <th>Birth Date</th>
                    <th>Books</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($authors as $author): ?>
                    <tr class="author-row">
                        <td class="author-name"><?php echo htmlspecialchars($author['name']); ?></td>
                        <td>
                            <?php 
                            if (!empty($author['birth_date']) && $author['birth_date'] != '0000-00-00') {
                                echo date('M d, Y', strtotime($author['birth_date']));
                            } else {
                                echo 'Unknown';
                            }
                            ?>
                        </td>
                        <td>
                            <?php
                            $bookCountQuery = "SELECT COUNT(*) as count FROM books WHERE author_id = {$author['id']} AND user_id = $userId";
                            $bookCount = fetchRow($bookCountQuery);
                            echo $bookCount ? $bookCount['count'] : 0;
                            ?>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="<?php echo SITE_URL; ?>/authors/view.php?id=<?php echo $author['id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?php echo SITE_URL; ?>/authors/edit.php?id=<?php echo $author['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?php echo SITE_URL; ?>/authors/delete.php?id=<?php echo $author['id']; ?>" class="btn btn-sm btn-danger confirm-delete">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- 分页 -->
    <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo SITE_URL; ?>/authors/index.php?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Previous</a>
                </li>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="<?php echo SITE_URL; ?>/authors/index.php?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="<?php echo SITE_URL; ?>/authors/index.php?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Next</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<?php
require_once dirname(__FILE__) . '/../includes/footer.php';
?>