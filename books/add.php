<!-- books/add.php -->

<?php
$pageTitle = 'Add New Book';
require_once dirname(__FILE__) . '/../includes/header.php';

// 检查用户是否已登录
requireLogin();

// 获取当前用户ID
$userId = $_SESSION['user_id'];

// 获取所有作者用于下拉列表
$authorsQuery = "SELECT id, name FROM authors WHERE id IN (SELECT DISTINCT author_id FROM books WHERE user_id = $userId) OR id IN (SELECT id FROM authors WHERE user_id = $userId) ORDER BY name ASC";
$authors = fetchAll($authorsQuery);

$errors = [];
$book = [
    'title' => '',
    'author_id' => '',
    'isbn' => '',
    'publication_year' => '',
    'publisher' => '',
    'genre' => '',
    'description' => '',
    'page_count' => '',
    'language' => 'English', // 默认语言
    'available' => true
];

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取表单数据
    $book['title'] = sanitize($_POST['title']);
    $book['author_id'] = !empty($_POST['author_id']) ? (int)$_POST['author_id'] : null;
    $book['isbn'] = sanitize($_POST['isbn']);
    $book['publication_year'] = !empty($_POST['publication_year']) ? (int)$_POST['publication_year'] : null;
    $book['publisher'] = sanitize($_POST['publisher']);
    $book['genre'] = sanitize($_POST['genre']);
    $book['description'] = sanitize($_POST['description']);
    $book['page_count'] = !empty($_POST['page_count']) ? (int)$_POST['page_count'] : null;
    $book['language'] = sanitize($_POST['language']);
    $book['available'] = isset($_POST['available']) ? true : false;
    
    // 验证输入
    if (empty($book['title'])) {
        $errors[] = 'Title is required';
    }
    
    // 如果选择了"添加新作者"选项
    $newAuthorName = sanitize($_POST['new_author_name'] ?? '');
    if ($book['author_id'] === -1 && !empty($newAuthorName)) {
        // 先添加新作者
        $authorData = [
            'name' => $newAuthorName,
            'user_id' => $userId
        ];
        
        $conn = getDBConnection();
        $stmt = $conn->prepare("INSERT INTO authors (name, user_id) VALUES (?, ?)");
        $stmt->bind_param("si", $authorData['name'], $authorData['user_id']);
        
        if ($stmt->execute()) {
            $book['author_id'] = $conn->insert_id;
        } else {
            $errors[] = 'Failed to add new author';
        }
        
        $stmt->close();
        $conn->close();
    } elseif ($book['author_id'] === -1 && empty($newAuthorName)) {
        $errors[] = 'New author name is required';
    }
    
    // 如果没有错误，添加图书
    if (empty($errors)) {
        $bookData = [
            'title' => $book['title'],
            'author_id' => $book['author_id'],
            'isbn' => $book['isbn'],
            'publication_year' => $book['publication_year'],
            'publisher' => $book['publisher'],
            'genre' => $book['genre'],
            'description' => $book['description'],
            'page_count' => $book['page_count'],
            'language' => $book['language'],
            'available' => $book['available'] ? 1 : 0,
            'user_id' => $userId
        ];
        
        // 过滤掉null值
        foreach ($bookData as $key => $value) {
            if ($value === null || $value === '') {
                unset($bookData[$key]);
            }
        }
        
        $insertId = insert('books', $bookData);
        
        if ($insertId) {
            setMessage('Book added successfully!', 'success');
            redirect('/books/view.php?id=' . $insertId);
        } else {
            $errors[] = 'Failed to add book. Please try again.';
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Add New Book</h1>
    <a href="<?php echo SITE_URL; ?>/books/index.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Back to Books
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
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="row">
                <!-- 基本信息 -->
                <div class="col-md-6">
                    <h3>Basic Information</h3>
                    <div class="mb-3">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo $book['title']; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="author_id" class="form-label">Author</label>
                        <select class="form-select" id="author_id" name="author_id" onchange="toggleNewAuthorField()">
                            <option value="">Select Author</option>
                            <?php foreach ($authors as $author): ?>
                                <option value="<?php echo $author['id']; ?>" <?php echo $book['author_id'] == $author['id'] ? 'selected' : ''; ?>>
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
                        <input type="text" class="form-control" id="isbn" name="isbn" value="<?php echo $book['isbn']; ?>">
                        <div class="invalid-feedback" id="isbnFeedback">
                            Invalid ISBN format. Please enter a valid 10 or 13-digit ISBN.
                        </div>
                        <div class="form-text">Enter ISBN-10 or ISBN-13 format (with or without hyphens)</div>
                    </div>

                    <div class="mb-3">
                        <label for="genre" class="form-label">Genre</label>
                        <input type="text" class="form-control" id="genre" name="genre" value="<?php echo $book['genre']; ?>" list="genreList">
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
                            <!-- JavaScript will populate years -->
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="publisher" class="form-label">Publisher</label>
                        <input type="text" class="form-control" id="publisher" name="publisher" value="<?php echo $book['publisher']; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="page_count" class="form-label">Page Count</label>
                        <input type="number" class="form-control" id="page_count" name="page_count" value="<?php echo $book['page_count']; ?>" min="1">
                    </div>

                    <div class="mb-3">
                        <label for="language" class="form-label">Language</label>
                        <input type="text" class="form-control" id="language" name="language" value="<?php echo $book['language']; ?>" list="languageList">
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
                        <input type="checkbox" class="form-check-input" id="available" name="available" <?php echo $book['available'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="available">Available for borrowing</label>
                    </div>
                </div>

                <!-- 描述 -->
                <div class="col-12 mt-3">
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="5"><?php echo $book['description']; ?></textarea>
                    </div>
                </div>

                <div class="col-12 mt-3">
                    <hr>
                    <button type="submit" class="btn btn-primary">Add Book</button>
                    <a href="<?php echo SITE_URL; ?>/books/index.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
require_once dirname(__FILE__) . '/../includes/footer.php';
?>