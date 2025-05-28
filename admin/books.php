<?php
$page_title = "Manage Books";
require_once '../config/init.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect("login.php");
}

$success_message = "";
$error_message = "";

// Check which columns exist in books table
$sql = "SHOW COLUMNS FROM books";
$result = $conn->query($sql);
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}

$has_is_available = in_array('is_available', $columns);
$has_isbn = in_array('isbn', $columns);
$has_published_year = in_array('published_year', $columns);
$has_category = in_array('category', $columns);
$category_column = $has_category ? 'category' : 'genre';

// Process delete book
if (isset($_POST['delete_book']) && isset($_POST['book_id'])) {
    $book_id = intval($_POST['book_id']);
    
    // Check if book is borrowed
    if ($has_is_available) {
        $sql = "SELECT is_available FROM books WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $book = $result->fetch_assoc();
        $is_available = $book['is_available'];
    } else {
        $sql = "SELECT status FROM books WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $book = $result->fetch_assoc();
        $is_available = ($book['status'] == 'available');
    }
    
    if (!$is_available) {
        $error_message = "Cannot delete book that is currently borrowed.";
    } else {
        // Delete book
        $sql = "DELETE FROM books WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $book_id);
        
        if ($stmt->execute()) {
            $success_message = "Book deleted successfully.";
        } else {
            $error_message = "Failed to delete book. Please try again.";
        }
    }
}

// Get all books
$sql = "SELECT * FROM books ORDER BY title";
$result = $conn->query($sql);
$books = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
}

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Manage Books</h1>
    <a href="add_book.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add New Book
    </a>
</div>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success"><?php echo $success_message; ?></div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger"><?php echo $error_message; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <?php if (empty($books)): ?>
            <p class="text-center">No books available.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th><?php echo $has_category ? 'Category' : 'Genre'; ?></th>
                            <?php if ($has_isbn): ?>
                            <th>ISBN</th>
                            <?php endif; ?>
                            <?php if ($has_published_year): ?>
                            <th>Published</th>
                            <?php endif; ?>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($books as $book): ?>
                        <tr>
                            <td>
                                <img src="../uploads/books/<?php echo $book['image']; ?>" class="img-thumbnail" alt="<?php echo $book['title']; ?>" style="width: 50px; height: 70px; object-fit: cover;" onerror="this.src='../assets/img/book-placeholder.png'">
                            </td>
                            <td><?php echo $book['title']; ?></td>
                            <td><?php echo $book['author']; ?></td>
                            <td><?php echo isset($book[$category_column]) ? $book[$category_column] : '-'; ?></td>
                            <?php if ($has_isbn): ?>
                            <td><?php echo isset($book['isbn']) ? $book['isbn'] : '-'; ?></td>
                            <?php endif; ?>
                            <?php if ($has_published_year): ?>
                            <td><?php echo isset($book['published_year']) ? $book['published_year'] : '-'; ?></td>
                            <?php endif; ?>
                            <td>
                                <?php 
                                $is_available = $has_is_available ? $book['is_available'] : (isset($book['status']) ? ($book['status'] == 'available') : true);
                                ?>
                                <span class="badge <?php echo $is_available ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $is_available ? 'Available' : 'Borrowed'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="edit_book.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this book?');">
                                        <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                        <button type="submit" name="delete_book" class="btn btn-sm btn-danger" <?php echo !$is_available ? 'disabled' : ''; ?>>
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
include '../includes/admin_navbar.php';
include '../includes/footer.php'; 
?>
