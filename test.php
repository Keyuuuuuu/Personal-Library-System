<?php
$pageTitle = 'System Test';
require_once 'includes/header.php';
?>

<h1>System Test</h1>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Configuration Test</h5>
    </div>
    <div class="card-body">
        <ul class="list-group">
            <li class="list-group-item d-flex justify-content-between align-items-center">
                PHP Version
                <span class="badge bg-primary"><?php echo phpversion(); ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                App Name
                <span class="badge bg-primary"><?php echo APP_NAME; ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                Site URL
                <span class="badge bg-primary"><?php echo SITE_URL; ?></span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center">
                Time Zone
                <span class="badge bg-primary"><?php echo date_default_timezone_get(); ?></span>
            </li>
        </ul>
    </div>
</div>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Database Connection Test</h5>
    </div>
    <div class="card-body">
        <?php
        try {
            $conn = getDBConnection();
            echo '<div class="alert alert-success">
                    <strong>Success!</strong> Database connection established successfully.
                  </div>';
            
            // 检查是否存在所需的表
            $tables = ['users', 'authors', 'books', 'borrowings'];
            $missingTables = [];
            
            foreach ($tables as $table) {
                $result = $conn->query("SHOW TABLES LIKE '$table'");
                if ($result->num_rows == 0) {
                    $missingTables[] = $table;
                }
            }
            
            if (!empty($missingTables)) {
                echo '<div class="alert alert-warning">
                        <strong>Warning!</strong> The following tables are missing: ' . implode(', ', $missingTables) . '
                      </div>';
                
                echo '<p>You need to create these tables before proceeding. Here are the SQL statements:</p>';
                
                echo '<div class="accordion" id="sqlAccordion">';
                
                // Users table
                echo '<div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUsers">
                                Create users table
                            </button>
                        </h2>
                        <div id="collapseUsers" class="accordion-collapse collapse" data-bs-parent="#sqlAccordion">
                            <div class="accordion-body">
                                <pre><code>CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  full_name VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);</code></pre>
                            </div>
                        </div>
                      </div>';
                
                // Authors table
                echo '<div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAuthors">
                                Create authors table
                            </button>
                        </h2>
                        <div id="collapseAuthors" class="accordion-collapse collapse" data-bs-parent="#sqlAccordion">
                            <div class="accordion-body">
                                <pre><code>CREATE TABLE authors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  birth_date DATE,
  death_date DATE,
  biography TEXT,
  user_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);</code></pre>
                            </div>
                        </div>
                      </div>';
                
                // Books table
                echo '<div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBooks">
                                Create books table
                            </button>
                        </h2>
                        <div id="collapseBooks" class="accordion-collapse collapse" data-bs-parent="#sqlAccordion">
                            <div class="accordion-body">
                                <pre><code>CREATE TABLE books (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  author_id INT,
  isbn VARCHAR(20),
  publication_year INT,
  publisher VARCHAR(100),
  genre VARCHAR(50),
  description TEXT,
  page_count INT,
  language VARCHAR(50),
  available BOOLEAN DEFAULT TRUE,
  user_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (author_id) REFERENCES authors(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);</code></pre>
                            </div>
                        </div>
                      </div>';
                
                // Borrowings table
                echo '<div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBorrowings">
                                Create borrowings table
                            </button>
                        </h2>
                        <div id="collapseBorrowings" class="accordion-collapse collapse" data-bs-parent="#sqlAccordion">
                            <div class="accordion-body">
                                <pre><code>CREATE TABLE borrowings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  book_id INT NOT NULL,
  borrower_name VARCHAR(100) NOT NULL,
  borrowed_date DATE NOT NULL,
  due_date DATE NOT NULL,
  returned_date DATE,
  notes TEXT,
  user_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (book_id) REFERENCES books(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);</code></pre>
                            </div>
                        </div>
                      </div>';
                
                echo '</div>'; // 关闭accordion
                
                echo '<div class="mt-3">
                        <a href="' . SITE_URL . '/test.php?create_tables=1" class="btn btn-primary">Create All Tables</a>
                      </div>';
                
                // 处理创建表请求
                if (isset($_GET['create_tables']) && $_GET['create_tables'] == 1) {
                    $conn->query("CREATE TABLE IF NOT EXISTS users (
                      id INT AUTO_INCREMENT PRIMARY KEY,
                      username VARCHAR(50) NOT NULL UNIQUE,
                      email VARCHAR(100) NOT NULL UNIQUE,
                      password VARCHAR(255) NOT NULL,
                      full_name VARCHAR(100),
                      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )");
                    
                    $conn->query("CREATE TABLE IF NOT EXISTS authors (
                      id INT AUTO_INCREMENT PRIMARY KEY,
                      name VARCHAR(100) NOT NULL,
                      birth_date DATE,
                      death_date DATE,
                      biography TEXT,
                      user_id INT,
                      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                      FOREIGN KEY (user_id) REFERENCES users(id)
                    )");
                    
                    $conn->query("CREATE TABLE IF NOT EXISTS books (
                      id INT AUTO_INCREMENT PRIMARY KEY,
                      title VARCHAR(255) NOT NULL,
                      author_id INT,
                      isbn VARCHAR(20),
                      publication_year INT,
                      publisher VARCHAR(100),
                      genre VARCHAR(50),
                      description TEXT,
                      page_count INT,
                      language VARCHAR(50),
                      available BOOLEAN DEFAULT TRUE,
                      user_id INT NOT NULL,
                      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                      FOREIGN KEY (author_id) REFERENCES authors(id),
                      FOREIGN KEY (user_id) REFERENCES users(id)
                    )");
                    
                    $conn->query("CREATE TABLE IF NOT EXISTS borrowings (
                      id INT AUTO_INCREMENT PRIMARY KEY,
                      book_id INT NOT NULL,
                      borrower_name VARCHAR(100) NOT NULL,
                      borrowed_date DATE NOT NULL,
                      due_date DATE NOT NULL,
                      returned_date DATE,
                      notes TEXT,
                      user_id INT NOT NULL,
                      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                      FOREIGN KEY (book_id) REFERENCES books(id),
                      FOREIGN KEY (user_id) REFERENCES users(id)
                    )");
                    
                    echo '<div class="alert alert-success mt-3">
                            <strong>Success!</strong> All tables have been created.
                          </div>';
                    
                    echo '<script>
                            setTimeout(function() {
                                window.location.href = "' . SITE_URL . '/test.php";
                            }, 2000);
                          </script>';
                }
            } else {
                echo '<div class="alert alert-success">
                        <strong>Success!</strong> All required tables exist in the database.
                      </div>';
            }
            
            $conn->close();
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">
                    <strong>Error!</strong> Failed to connect to database: ' . $e->getMessage() . '
                  </div>';
        }
        ?>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>