<!-- books/edit.php -->

<?php
$pageTitle = 'Edit Book';
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
    setMessage('You do not have permission to edit this book', 'danger');
    redirect('/books/index.php');
}

// 获取图书详情
$bookQuery = "SELECT * FROM books WHERE id = $bookId AND user_id = $userId";
$book = fetchRow($bookQuery);

if (!$book) {
    setMessage('Book not found', 'danger');
    redirect('/books/index.php');
}

// 获取所有作者用于下拉列表
$authorsQuery = "SELECT id, name FROM authors WHERE id IN (SELECT DISTINCT author_id FROM books WHERE user_id = $userId) OR id IN (SELECT id FROM authors WHERE user_id = $userId) ORDER BY name ASC";
$authors = fetchAll($authorsQuery);

$errors = [];

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取表单数据
    $title = sanitize($_POST['title']);
    $author_id = !empty($_POST['author_id']) ? (int)$_POST['author_id'] : null;
    $isbn = sanitize($_POST['isbn']);
    $publication_year = !empty($_POST['publication_year']) ? (int)$_POST['publication_year'] : null;
    $publisher = sanitize($_POST['publisher']);
    $genre = sanitize($_POST['genre']);
    $description = sanitize($_POST['description']);
    $page_count = !empty($_POST['page_count']) ? (int)$_POST['page_count'] : null;
    $language = sanitize($_POST['language']);
    $available = isset($_POST['available']) ? 1 : 0;
    
    // 验证输入
    if (empty($title)) {
        $errors[] = 'Title is required';
    }
    
    // 如果选择了"添加新作者"选项
    if ($author_id === -1) {
        $newAuthorName = sanitize($_POST['new_author_name'] ?? '');
        if (empty($newAuthorName)) {
            $errors[] = 'New author name is required';
        } else {
            // 添加新作者
            $authorData = [
                'name' => $newAuthorName,
                'user_id' => $userId
            ];
            
            $authorId = insert('authors', $authorData);
            if ($authorId) {
                $author_id = $authorId;
            } else {
                $errors[] = 'Failed to add new author';
            }
        }
    }
    
    // 如果没有错误，更新图书
    if (empty($errors)) {
        $bookData = [
            'title' => $title,
            'author_id' => $author_id,
            'isbn' => $isbn,
            'publication_year' => $publication_year,
            'publisher' => $publisher,
            'genre' => $genre,
            'description' => $description,
            'page_count' => $page_count,
            'language' => $language,
            'available' => $available
        ];
        
        // 过滤掉null值
        foreach ($bookData as $key => $value) {
            if ($value === null || $value === '') {
                unset($bookData[$key]);
            }
        }
        
        $result = update('books', $bookData, 'id = ? AND user_id = ?', 'ii', [$bookId, $userId]);
        
        if ($result) {
            setMessage('Book updated successfully!', 'success');
            redirect('/books/view.php?id=' . $bookId);
        } else {
            $errors[] = 'Failed to update book. Please try again.';
        }
    }
} else {
    // 预填表单
    $title = $book['title'];
    $author_id = $book['author_id'];
    $isbn = $book['isbn'];
    $publication_year = $book['publication_year'];
    $publisher = $book['publisher'];
    $genre = $book['genre'];
    $description = $book['description'];
    $page_count = $book['page_count'];
    $language = $book['language'];
    $available = $book['available'];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Edit Book: <?php echo htmlspecialchars($book['title']); ?></h1>
    <a href="<?php echo SITE_URL; ?>/books/view.php?id=<?php echo $bookId; ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Back to Book
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
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $bookId; ?>">
            <div class="row">
                <!-- 基本信息 -->
                <div class="col-md-6">
                    <h3>Basic Information</h3>
                    <div class="mb-3">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="author_id" class="form-label">Author</label>
                        <select class="form-select" id="author_id" name="author_id" onchange="toggleNewAuthorField()">
                            <option value="">Select Author</option>
                            <?php foreach ($authors as $author): ?>
                                <option value="<?php echo $author['id']; ?>" <?php echo $author_id == $author['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($author['name']); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="-1">+ Add New Author</option>
                        </select>
                    </div>

                    <div class="mb-3" id="new_author_div" style="display: none;">
                        <label for="new_author_name" class="form-label">New Author Name</label>
                        <input type="text" class="form-control" id="new_author_name" name="new_author_name" value="">
                    </div>

                    <div class="mb-3">
                        <label for="isbn" class="form-label">ISBN</label>
                        <input type="text" class="form-control" id="isbn" name="isbn" value="<?php echo htmlspecialchars($isbn); ?>">
                        <div class="invalid-feedback" id="isbnFeedback">
                            Invalid ISBN format. Please enter a valid 10 or 13-digit ISBN.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="genre" class="form-label">Genre</label>
                        <input type="text" class="form-control" id="genre" name="genre" value="<?php echo htmlspecialchars($genre); ?>" list="genreList">
                        <datalist id="genreList">
                            <option value="Fiction">
                            <option value="Non-fiction">
                            <option value="Science Fiction">
                            <option value="Fantasy">
                            <option value="Mystery">
                            <option value="Thriller">
                            <option value="Romance">
                            <option value="Biography">
                            <option value="History">
                            <option value="Self-help">
                            <option value="Business">
                            <option value="Travel">
                            <option value="Science">
                            <option value="Poetry">
                            <option value="Children's">
                            <option value="Young Adult">
                        </datalist>
                    </div>
                </div>

                <!-- 详细信息 -->
                <div class="col-md-6">
                    <h3>Additional Details</h3>
                    <div class="mb-3">
                        <label for="publication_year" class="form-label">Publication Year</label>
                        <select class="form-select" id="publication_year" name="publication_year">
                            <option value="">Select Year</option>
                            <?php 
                            $currentYear = date('Y');
                            for ($year = $currentYear; $year >= 1800; $year--): 
                            ?>
                                <option value="<?php echo $year; ?>" <?php echo $publication_year == $year ? 'selected' : ''; ?>><?php echo $year; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="publisher" class="form-label">Publisher</label>
                        <input type="text" class="form-control" id="publisher" name="publisher" value="<?php echo htmlspecialchars($publisher); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="page_count" class="form-label">Page Count</label>
                        <input type="number" class="form-control" id="page_count" name="page_count" value="<?php echo htmlspecialchars($page_count); ?>" min="1">
                    </div>

                    <div class="mb-3">
                        <label for="language" class="form-label">Language</label>
                        <input type="text" class="form-control" id="language" name="language" value="<?php echo htmlspecialchars($language); ?>" list="languageList">
                        <datalist id="languageList">
                            <option value="English">
                            <option value="Spanish">
                            <option value="French">
                            <option value="German">
                            <option value="Chinese">
                            <option value="Japanese">
                            <option value="Russian">
                            <option value="Arabic">
                            <option value="Portuguese">
                            <option value="Italian">
                        </datalist>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="available" name="available" <?php echo $available ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="available">Available for borrowing</label>
                    </div>
                </div>

                <!-- 描述 -->
                <div class="col-12 mt-3">
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="5"><?php echo htmlspecialchars($description); ?></textarea>
                    </div>
                </div>

                <div class="col-12 mt-3">
                    <hr>
                    <button type="submit" class="btn btn-primary">Update Book</button>
                    <a href="<?php echo SITE_URL; ?>/books/view.php?id=<?php echo $bookId; ?>" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
require_once dirname(__FILE__) . '/../includes/footer.php';
?>