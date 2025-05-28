<?php
// Database migration script to update structure
require_once 'database.php';

echo "<h2>Database Migration Script</h2>";

try {
    // Check if books table exists and has the right structure
    $result = $conn->query("DESCRIBE books");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    echo "<h3>Current columns in books table:</h3>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>$column</li>";
    }
    echo "</ul>";
    
    // Add missing columns
    if (!in_array('isbn', $columns)) {
        $conn->query("ALTER TABLE books ADD COLUMN isbn VARCHAR(20) AFTER description");
        echo "<p>✅ Added isbn column</p>";
    }
    
    if (!in_array('published_year', $columns)) {
        $conn->query("ALTER TABLE books ADD COLUMN published_year INT AFTER isbn");
        echo "<p>✅ Added published_year column</p>";
    }
    
    if (!in_array('page_count', $columns)) {
        $conn->query("ALTER TABLE books ADD COLUMN page_count INT AFTER published_year");
        echo "<p>✅ Added page_count column</p>";
    }
    
    // Rename genre to category if needed
    if (in_array('genre', $columns) && !in_array('category', $columns)) {
        $conn->query("ALTER TABLE books CHANGE COLUMN genre category VARCHAR(50)");
        echo "<p>✅ Renamed genre to category</p>";
    }
    
    // Add is_available column and migrate from status
    if (!in_array('is_available', $columns)) {
        $conn->query("ALTER TABLE books ADD COLUMN is_available TINYINT(1) DEFAULT 1");
        echo "<p>✅ Added is_available column</p>";
        
        // Migrate data from status to is_available
        if (in_array('status', $columns)) {
            $conn->query("UPDATE books SET is_available = 1 WHERE status = 'available'");
            $conn->query("UPDATE books SET is_available = 0 WHERE status = 'borrowed'");
            echo "<p>✅ Migrated status data to is_available</p>";
            
            // Drop old status column
            $conn->query("ALTER TABLE books DROP COLUMN status");
            echo "<p>✅ Dropped old status column</p>";
        }
    }
    
    // Check if we have sample data
    $result = $conn->query("SELECT COUNT(*) as count FROM books");
    $count = $result->fetch_assoc()['count'];
    
    if ($count == 0) {
        // Insert sample data
        $sample_books = [
            ['Harry Potter and the Philosopher\'s Stone', 'J.K. Rowling', 'Fantasy', 'Harry Potter has never even heard of Hogwarts when the letters start dropping on the doormat at number four, Privet Drive.', '9780747532699', 1997, 223],
            ['To Kill a Mockingbird', 'Harper Lee', 'Fiction', 'The unforgettable novel of a childhood in a sleepy Southern town and the crisis of conscience that rocked it.', '9780061120084', 1960, 376],
            ['The Great Gatsby', 'F. Scott Fitzgerald', 'Classic', 'The story of the fabulously wealthy Jay Gatsby and his love for the beautiful Daisy Buchanan.', '9780743273565', 1925, 180],
            ['1984', 'George Orwell', 'Dystopian', 'Among the seminal texts of the 20th century, Nineteen Eighty-Four is a rare work that grows more haunting as its futuristic purgatory becomes more real.', '9780451524935', 1949, 328],
            ['Pride and Prejudice', 'Jane Austen', 'Romance', 'The story follows the main character, Elizabeth Bennet, as she deals with issues of manners, upbringing, morality, education, and marriage.', '9780141439518', 1813, 432],
            ['The Catcher in the Rye', 'J.D. Salinger', 'Fiction', 'The story of Holden Caulfield, a teenager who has been expelled from prep school.', '9780316769174', 1951, 277],
            ['Lord of the Flies', 'William Golding', 'Fiction', 'A group of British boys stranded on an uninhabited island and their disastrous attempt to govern themselves.', '9780571056866', 1954, 224],
            ['The Hobbit', 'J.R.R. Tolkien', 'Fantasy', 'Bilbo Baggins enjoys a comfortable, unambitious life until the wizard Gandalf chooses him to take part in an adventure.', '9780547928227', 1937, 366]
        ];
        
        $stmt = $conn->prepare("INSERT INTO books (title, author, category, description, isbn, published_year, page_count, image, is_available) VALUES (?, ?, ?, ?, ?, ?, ?, 'default.jpg', 1)");
        
        foreach ($sample_books as $book) {
            $stmt->bind_param("sssssii", $book[0], $book[1], $book[2], $book[3], $book[4], $book[5], $book[6]);
            $stmt->execute();
        }
        
        echo "<p>✅ Inserted sample books</p>";
    }
    
    echo "<h3>✅ Migration completed successfully!</h3>";
    echo "<p><a href='../user/login.php'>Go to User Login</a> | <a href='../admin/login.php'>Go to Admin Login</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

$conn->close();
?>
