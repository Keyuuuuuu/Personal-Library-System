// Script.js

// 页面加载完成后执行
document.addEventListener('DOMContentLoaded', function () {
    // 初始化Bootstrap工具提示
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // 初始化Bootstrap弹出框
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // 确认删除操作
    document.querySelectorAll('.confirm-delete').forEach(function (element) {
        element.addEventListener('click', function (e) {
            if (!confirm('Are you sure you want to delete? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // 图书过滤功能
    const filterInput = document.getElementById('filterBooks');
    if (filterInput) {
        filterInput.addEventListener('keyup', function () {
            const filterValue = this.value.toLowerCase();
            const bookCards = document.querySelectorAll('.book-item');

            bookCards.forEach(function (card) {
                const title = card.querySelector('.card-title').textContent.toLowerCase();
                const author = card.querySelector('.book-author') ?
                    card.querySelector('.book-author').textContent.toLowerCase() : '';
                const genre = card.querySelector('.book-genre') ?
                    card.querySelector('.book-genre').textContent.toLowerCase() : '';

                if (title.includes(filterValue) || author.includes(filterValue) || genre.includes(filterValue)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }

    // 作者过滤功能
    const filterAuthorsInput = document.getElementById('filterAuthors');
    if (filterAuthorsInput) {
        filterAuthorsInput.addEventListener('keyup', function () {
            const filterValue = this.value.toLowerCase();
            const authorRows = document.querySelectorAll('.author-row');

            authorRows.forEach(function (row) {
                const name = row.querySelector('.author-name').textContent.toLowerCase();

                if (name.includes(filterValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // 借阅日期和归还日期验证
    const borrowForm = document.getElementById('borrowForm');
    if (borrowForm) {
        borrowForm.addEventListener('submit', function (e) {
            const borrowedDate = new Date(document.getElementById('borrowed_date').value);
            const dueDate = new Date(document.getElementById('due_date').value);

            if (dueDate <= borrowedDate) {
                e.preventDefault();
                alert('Due date must be later than the borrowed date!');
            }
        });
    }

    // ISBN验证
    const isbnInput = document.getElementById('isbn');
    if (isbnInput) {
        isbnInput.addEventListener('blur', function () {
            const isbn = this.value.replace(/[-\s]/g, '');
            let valid = false;

            if (isbn.length === 10) {
                valid = validateISBN10(isbn);
            } else if (isbn.length === 13) {
                valid = validateISBN13(isbn);
            }

            if (!valid && isbn !== '') {
                this.classList.add('is-invalid');
                document.getElementById('isbnFeedback').style.display = 'block';
            } else {
                this.classList.remove('is-invalid');
                document.getElementById('isbnFeedback').style.display = 'none';
            }
        });
    }

    // ISBN-10验证函数
    function validateISBN10(isbn) {
        let sum = 0;
        for (let i = 0; i < 9; i++) {
            sum += (10 - i) * parseInt(isbn.charAt(i));
        }

        const lastChar = isbn.charAt(9);
        const lastDigit = (lastChar === 'X' || lastChar === 'x') ? 10 : parseInt(lastChar);

        sum += lastDigit;
        return sum % 11 === 0;
    }

    // ISBN-13验证函数
    function validateISBN13(isbn) {
        let sum = 0;
        for (let i = 0; i < 12; i++) {
            sum += (i % 2 === 0) ? parseInt(isbn.charAt(i)) : parseInt(isbn.charAt(i)) * 3;
        }

        const check = (10 - (sum % 10)) % 10;
        return check === parseInt(isbn.charAt(12));
    }

    // 添加新作者字段切换
    window.toggleNewAuthorField = function () {
        const authorSelect = document.getElementById('author_id');
        const newAuthorDiv = document.getElementById('new_author_div');

        if (authorSelect && newAuthorDiv) {
            if (authorSelect.value === '-1') {
                newAuthorDiv.style.display = 'block';
            } else {
                newAuthorDiv.style.display = 'none';
            }
        }
    };

    // 初始化调用一次
    if (document.getElementById('author_id')) {
        toggleNewAuthorField();
    }

    // 初始化年份选择器
    const yearSelect = document.getElementById('publication_year');
    if (yearSelect && yearSelect.options.length <= 1) {
        const currentYear = new Date().getFullYear();
        for (let year = currentYear; year >= 1800; year--) {
            const option = document.createElement('option');
            option.value = year;
            option.textContent = year;
            yearSelect.appendChild(option);
        }
    }
});