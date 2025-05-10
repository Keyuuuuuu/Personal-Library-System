<!-- borrowings/add.php -->

<?php
$pageTitle = 'Lend a Book';
require_once dirname(__FILE__) . '/../includes/header.php';

// 检查用户是否已登录
requireLogin();

// 获取当前用户ID
$userId = $_SESSION['user_id'];

// 获取图书ID（如果有的话）
$bookId = isset($_GET['book_id']) ? (int)$_GET['book_id'] : 0;

// 如果提供了图书ID，检查图书是否存在且属于当前用户
$book = null;
if ($bookId > 0) {
    $bookQuery = "SELECT * FROM books WHERE id = $bookId AND user_id = $userId";
    $book = fetchRow($bookQuery);
    
    if (!$book) {
        setMessage('Book not found', 'danger');
        redirect('/books/index.php');
    }
    
    // 检查图书是否可借出
    if (!$book['available']) {
        setMessage('This book is already borrowed', 'warning');
        redirect('/books/view.php?id=' . $bookId);
    }
} else {
    // 获取可借出的图书列表
    $availableBooksQuery = "SELECT id, title FROM books WHERE user_id = $userId AND available = 1 ORDER BY title ASC";
    $availableBooks = fetchAll($availableBooksQuery);
    
    if (empty($availableBooks)) {
        setMessage('You have no available books to lend', 'warning');
        redirect('/books/index.php');
    }
}

$errors = [];
$borrowing = [
    'book_id' => $bookId,
    'borrower_name' => '',
    'borrowed_date' => date('Y-m-d'),
    'due_date' => date('Y-m-d', strtotime('+2 weeks')),
    'notes' => ''
];

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取表单数据
    $borrowing['book_id'] = isset($_POST['book_id']) ? (int)$_POST['book_id'] : $bookId;
    $borrowing['borrower_name'] = sanitize($_POST['borrower_name']);
    $borrowing['borrowed_date'] = sanitize($_POST['borrowed_date']);
    $borrowing['due_date'] = sanitize($_POST['due_date']);
    $borrowing['notes'] = sanitize($_POST['notes']);
    
    // 验证输入
    if (empty($borrowing['book_id'])) {
        $errors[] = 'Please select a book';
    } else {
        // 检查所选图书是否存在且可借出
        $bookCheckQuery = "SELECT available FROM books WHERE id = {$borrowing['book_id']} AND user_id = $userId";
        $bookCheck = fetchRow($bookCheckQuery);
        
        if (!$bookCheck) {
            $errors[] = 'Selected book does not exist';
        } elseif (!$bookCheck['available']) {
            $errors[] = 'Selected book is already borrowed';
        }
    }
    
    if (empty($borrowing['borrower_name'])) {
        $errors[] = 'Borrower name is required';
    }
    
    if (empty($borrowing['borrowed_date'])) {
        $errors[] = 'Borrowed date is required';
    }
    
    if (empty($borrowing['due_date'])) {
        $errors[] = 'Due date is required';
    }
    
    // 验证日期
    if (!empty($borrowing['borrowed_date']) && !empty($borrowing['due_date'])) {
        $borrowedDate = new DateTime($borrowing['borrowed_date']);
        $dueDate = new DateTime($borrowing['due_date']);
        
        if ($dueDate <= $borrowedDate) {
            $errors[] = 'Due date must be later than the borrowed date';
        }
    }
    
    // 如果没有错误，创建借阅记录
    if (empty($errors)) {
        // 开始事务
        $conn = getDBConnection();
        $conn->begin_transaction();
        
        try {
            // 插入借阅记录
            $borrowingData = [
                'book_id' => $borrowing['book_id'],
                'borrower_name' => $borrowing['borrower_name'],
                'borrowed_date' => $borrowing['borrowed_date'],
                'due_date' => $borrowing['due_date'],
                'notes' => $borrowing['notes'],
                'user_id' => $userId
            ];
            
            $stmt = $conn->prepare("INSERT INTO borrowings (book_id, borrower_name, borrowed_date, due_date, notes, user_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssi", 
                $borrowingData['book_id'], 
                $borrowingData['borrower_name'], 
                $borrowingData['borrowed_date'], 
                $borrowingData['due_date'], 
                $borrowingData['notes'], 
                $borrowingData['user_id']
            );
            
            $stmt->execute();
            $borrowingId = $conn->insert_id;
            $stmt->close();
            
            // 更新图书状态为不可用
            $stmt = $conn->prepare("UPDATE books SET available = 0 WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $borrowing['book_id'], $userId);
            $stmt->execute();
            $stmt->close();
            
            // 提交事务
            $conn->commit();
            
            setMessage('Book has been lent successfully!', 'success');
            redirect('/borrowings/index.php');
        } catch (Exception $e) {
            // 回滚事务
            $conn->rollback();
            $errors[] = 'Failed to lend book: ' . $e->getMessage();
        }
        
        $conn->close();
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Lend a Book</h1>
    <a href="<?php echo SITE_URL; ?>/borrowings/index.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Back to Borrowings
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . ($bookId ? '?book_id=' . $bookId : ''); ?>" id="borrowForm">
            <div class="mb-3">
                <label for="book_id" class="form-label">Book <span class="text-danger">*</span></label>
                <?php if ($book): ?>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($book['title']); ?>" readonly>
                    <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                <?php else: ?>
                    <select class="form-select" id="book_id" name="book_id" required>
                        <option value="">Select a book</option>
                        <?php foreach ($availableBooks as $availableBook): ?>
                            <option value="<?php echo $availableBook['id']; ?>" <?php echo $borrowing['book_id'] == $availableBook['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($availableBook['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
            
            <div class="mb-3">
                <label for="borrower_name" class="form-label">Borrower Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="borrower_name" name="borrower_name" value="<?php echo htmlspecialchars($borrowing['borrower_name']); ?>" required>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="borrowed_date" class="form-label">Borrowed Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="borrowed_date" name="borrowed_date" value="<?php echo $borrowing['borrowed_date']; ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="due_date" class="form-label">Due Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="due_date" name="due_date" value="<?php echo $borrowing['due_date']; ?>" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($borrowing['notes']); ?></textarea>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" class="btn btn-primary">Lend Book</button>
                <a href="<?php echo SITE_URL; ?>/borrowings/index.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
require_once dirname(__FILE__) . '/../includes/footer.php';
?>