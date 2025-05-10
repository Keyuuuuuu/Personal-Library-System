# Personal Library Management API

A RESTful API built with Node.js, Express, MySQL, and JWT authentication for managing your personal book collection.


## 📚 Project Overview

This project implements a comprehensive personal library management system where users can:

- Register, log in, and authenticate securely using JWT
- Manage authors and books in their library collection
- Track borrowing records including loans, due dates, and returns

## 🛠️ Technologies Used

- **Node.js** - Backend JavaScript runtime environment
- **Express** - Web framework for building RESTful APIs
- **MySQL** - Relational database for data storage
- **JWT** - JSON Web Tokens for secure authentication
- **bcryptjs** - Password hashing and comparison
- **dotenv** - Environment variable management

## 📂 Project Structure

```
personal-library-api/
  ├── node_modules/           # Project dependencies
  ├── src/
  │   ├── config/             # Configuration files (DB, JWT, etc.)
  │   ├── controllers/        # Controllers for handling API requests
  │   ├── middleware/         # Middleware (e.g., JWT authentication)
  │   ├── models/             # Database models for each entity
  │   ├── routes/             # API route definitions
  │   └── server.js           # Main entry point of the API
  ├── .env                    # Environment variables
  ├── package.json            # Project metadata and dependencies
  └── README.md               # Project overview and instructions
```

## 💾 Database Design

The database consists of four main tables:

| Table | Description |
|-------|-------------|
| **Users** | Stores user credentials and profile information |
| **Authors** | Contains author details (name, biography, dates) |
| **Books** | Stores book information linked to authors and users |
| **Borrowings** | Tracks book loans with borrower info and dates |

### Database Schema

```sql
CREATE TABLE users ( ... );
CREATE TABLE authors ( ... );
CREATE TABLE books ( ... );
CREATE TABLE borrowings ( ... );
```

## 🔌 API Endpoints

### Authentication
- `POST /api/auth/register` - User registration
- `POST /api/auth/login` - User login

### Users
- `GET /api/users/profile` - Get user profile details
- `PUT /api/users/profile` - Update user profile

### Authors
- `GET /api/authors` - Get all authors
- `POST /api/authors` - Add a new author
- `PUT /api/authors/:id` - Update author details
- `DELETE /api/authors/:id` - Delete an author

### Books
- `GET /api/books` - Get all books
- `POST /api/books` - Add a new book
- `PUT /api/books/:id` - Update book details
- `DELETE /api/books/:id` - Delete a book

### Borrowings
- `GET /api/borrowings` - Get all borrowings
- `POST /api/borrowings` - Add a new borrowing record
- `PUT /api/borrowings/:id` - Update borrowing details
- `DELETE /api/borrowings/:id` - Delete a borrowing record

## 🔐 JWT Authentication

- **Register**: Passwords are securely hashed before storage
- **Login**: JWT tokens are generated upon successful authentication
- **Middleware**: Protects routes by validating JWT tokens

## 🚀 Implementation Process

1. **Project Setup**
   - Initialized Node.js project
   - Installed dependencies (Express, MySQL2, bcryptjs, jsonwebtoken, dotenv)

2. **Database Configuration**
   - Set up MySQL connection pool
   - Created necessary database tables

3. **Authentication System**
   - Implemented user registration and login
   - Created JWT middleware for protected routes

4. **API Development**
   - Built RESTful endpoints for all entities
   - Implemented CRUD operations with proper validation

5. **Testing and Refinement**
   - Tested endpoints with Postman
   - Added error handling and edge case protection

## 🧩 Challenges and Solutions

| Challenge | Solution |
|-----------|----------|
| **Database Connection Issues** | Switched to MySQL X DevAPI for correct port configuration |
| **JWT Implementation** | Added token expiration handling and middleware protection |
| **Error Handling** | Implemented comprehensive error middleware for clear user feedback |

## ⚙️ Setup Instructions

### Prerequisites
- Node.js
- MySQL

### Installation Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-username/personal-library-api.git
   cd personal-library-api
   ```

2. **Install dependencies**
   ```bash
   npm install
   ```

3. **Configure environment variables**
   Create a `.env` file in the root directory:
   ```
   DB_HOST=localhost
   DB_USER=root
   DB_PASSWORD=your_password
   DB_NAME=personal_library
   JWT_SECRET=your_jwt_secret_key
   JWT_EXPIRES_IN=24h
   PORT=3000
   ```

4. **Start the server**
   ```bash
   npm run dev
   ```

5. **Test the API**
   Use Postman or any API testing tool to interact with the endpoints

## 📝 Conclusion

This project demonstrates the implementation of a RESTful API using Node.js, Express, MySQL, and JWT authentication. It showcases practical solutions for user authentication, relational data management, and error handling.

## 📄 License

[MIT](LICENSE)
