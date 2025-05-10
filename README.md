# Personal Library System


## 📚 Project Overview

The Personal Library System is a browser-based web application for managing personal book collections. Built with PHP and MySQL, this system features a responsive Bootstrap interface that allows users to catalog books, maintain author information, and track borrowing records. The application provides secure user authentication and comprehensive CRUD operations, enabling efficient library management through any web browser.

## ✨ Features

- **User Authentication** - Secure registration, login, and session management
- **Book Management** - Add, view, edit, and delete books with detailed information
- **Author Management** - Maintain author profiles with biographical information
- **Borrowing Tracker** - Record book loans, due dates, and returns
- **Search & Filter** - Quickly locate books and authors by keywords
- **Pagination** - Navigate through large collections with ease
- **Responsive Design** - Mobile-friendly interface that works on any device

## 🛠️ Technologies Used

- **PHP** - Core server-side programming
- **MySQL** - Relational database system
- **HTML5 & CSS3** - Markup and styling
- **Bootstrap 5** - Responsive UI framework
- **JavaScript (ES6)** - Enhanced interactivity
- **Apache/Nginx** - HTTP server environment

## 💾 Database Schema

The application uses four interconnected tables for data management:

### Users Table
```sql
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  full_name VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Authors Table
```sql
CREATE TABLE authors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  birth_date DATE,
  death_date DATE,
  biography TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Books Table
```sql
CREATE TABLE books (
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
);
```

### Borrowings Table
```sql
CREATE TABLE borrowings (
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
);
```

## 📂 Project Structure

```
personal-library-system/
  ├── index.php                # Main entry point
  ├── assets/                  # Static resources
  │   ├── css/                 # Stylesheets
  │   ├── js/                  # JavaScript files
  │   └── images/              # Images and icons
  ├── includes/                # Common components
  │   ├── config.php           # Configuration settings
  │   ├── db.php               # Database connection
  │   ├── header.php           # Common header
  │   ├── footer.php           # Common footer
  │   └── auth_check.php       # Authentication verification
  ├── auth/                    # Authentication pages
  │   ├── login.php            # User login
  │   ├── register.php         # User registration
  │   └── logout.php           # Logout handler
  ├── books/                   # Book management
  │   ├── list.php             # Book listing
  │   ├── add.php              # Add new book
  │   ├── edit.php             # Edit book details
  │   ├── view.php             # Book details view
  │   └── delete.php           # Delete book handler
  ├── authors/                 # Author management
  │   ├── list.php             # Author listing
  │   ├── add.php              # Add new author
  │   ├── edit.php             # Edit author info
  │   └── delete.php           # Delete author handler
  ├── borrowings/              # Borrowing management
  │   ├── list.php             # Borrowings list
  │   ├── add.php              # New borrowing record
  │   ├── return.php           # Return book handler
  │   └── delete.php           # Delete borrowing record
  └── users/                   # User profile management
      └── profile.php          # User profile page
```

## 🚀 Implementation Highlights

The development followed a structured approach:

1. **Requirement Analysis** - Defined core features and user stories
2. **Database Design** - Created relational schema with foreign key relationships
3. **Project Structure** - Organized files for separation of concerns
4. **Authentication System** - Implemented secure user registration and login
5. **Core CRUD Features** - Built management interfaces for books, authors, and borrowings
6. **Bootstrap UI** - Created responsive, mobile-friendly interface
7. **Security Measures** - Applied input validation, prepared statements, and password hashing
8. **Pagination & Search** - Added filters and page navigation for improved usability

## 🛡️ Security Features

- Password hashing using PHP's `password_hash()`
- Prepared statements to prevent SQL injection
- Input validation on all forms
- XSS protection with `htmlspecialchars()`
- Session security with regenerated IDs
- Access control for authenticated users

## ⚙️ Setup Instructions

### Prerequisites
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx web server

### Installation

1. **Set up environment**
   - Install XAMPP, MAMP, or equivalent PHP/MySQL environment

2. **Get the code**
   ```bash
   git clone https://github.com/your-username/personal-library-system.git
   ```

3. **Create database**
   - Create a new MySQL database named `personal_library`
   - Import the SQL schema (or create tables using the SQL provided above)

4. **Configure the application**
   - Edit `includes/config.php` with your database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'personal_library');
   ```

5. **Launch the application**
   - Access via browser: `http://localhost/personal-library-system/`
   - Register a new user account
   - Start managing your library!

## 💡 Key Challenges & Solutions

| Challenge | Solution |
|-----------|----------|
| **Relational Integrity** | Foreign key constraints and cascading rules |
| **User Authentication** | Centralized session management |
| **Data Security** | Input validation and output sanitization |
| **Search with Pagination** | Dynamic SQL queries with parameter retention |
| **Mobile Responsiveness** | Bootstrap responsive classes and media queries |

## 📋 License

[MIT](LICENSE)
