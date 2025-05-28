<?php
$page_title = "Checkout";
require_once '../config/init.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect("login.php");
}

$user_id = $_SESSION["user_id"];
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
$has_page_count = in_array('page_count', $columns);
$has_category = in_array('category', $columns);
$category_column = $has_category ? 'category' : 'genre';

// Build the SELECT query based on available columns
$select_fields = ['c.*', 'b.title', 'b.author', 'b.image', 'b.id as book_id'];

if ($has_is_available) {
    $select_fields[] = 'b.is_available';
} else {
    $select_fields[] = 'b.status';
}

if ($has_isbn) {
    $select_fields[] = 'b.isbn';
}

if ($has_published_year) {
    $select_fields[] = 'b.published_year';
}

if ($has_page_count) {
    $select_fields[] = 'b.page_count';
}

if ($has_category) {
    $select_fields[] = 'b.category';
} else {
    $select_fields[] = 'b.genre';
}

// Get cart items
$sql = "SELECT " . implode(', ', $select_fields) . "
        FROM cart c 
        JOIN books b ON c.book_id = b.id 
        WHERE c.user_id = ? 
        ORDER BY c.added_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cart_items = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $cart_items[] = $row;
    }
}

// Check if cart is empty
if (empty($cart_items)) {
    redirect("cart.php");
}

// Check if any book is not available
foreach ($cart_items as $item) {
    $is_available = $has_is_available ? $item['is_available'] : (isset($item['status']) ? ($item['status'] == 'available') : true);
    if (!$is_available) {
        $error_message = "Some books in your cart are not available. Please remove them before proceeding.";
        break;
    }
}

// Process checkout
if ($_SERVER["REQUEST_METHOD"] == "POST" && empty($error_message)) {
    $pickup_location = sanitize($_POST["pickup_location"]);
    
    if (empty($pickup_location)) {
        $error_message = "Pickup location is required.";
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert borrowings
            foreach ($cart_items as $item) {
                $book_id = $item['book_id'];
                
                // Insert borrowing
                $sql = "INSERT INTO borrowings (user_id, book_id, pickup_location) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iis", $user_id, $book_id, $pickup_location);
                $stmt->execute();
                
                // Update book status
                if ($has_is_available) {
                    $sql = "UPDATE books SET is_available = 0 WHERE id = ?";
                } else {
                    $sql = "UPDATE books SET status = 'borrowed' WHERE id = ?";
                }
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $book_id);
                $stmt->execute();
            }
            
            // Clear cart
            $sql = "DELETE FROM cart WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            
            // Redirect to borrowings page
            redirect("borrowings.php?success=checkout");
        } catch (Exception $e) {
            // Rollback transaction
            $conn->rollback();
            $error_message = "Checkout failed. Please try again.";
        }
    }
}

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Checkout</h1>
    <a href="cart.php" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left"></i> Back to Cart
    </a>
</div>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger"><?php echo $error_message; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Books to Borrow</h5>
            </div>
            <div class="card-body">
                <?php foreach ($cart_items as $item): ?>
                <div class="d-flex mb-3 pb-3 border-bottom">
                    <img src="../uploads/books/<?php echo $item['image']; ?>" class="img-thumbnail me-3" alt="<?php echo $item['title']; ?>" style="width: 80px; height: 100px; object-fit: cover;" onerror="this.src='../assets/img/book-placeholder.png'">
                    <div>
                        <h6 class="mb-1"><?php echo $item['title']; ?></h6>
                        <p class="text-muted mb-1"><?php echo $item['author']; ?></p>
                        <?php if ($has_isbn && isset($item['isbn']) && !empty($item['isbn'])): ?>
                        <p class="text-muted mb-1"><small>ISBN: <?php echo $item['isbn']; ?></small></p>
                        <?php endif; ?>
                        <?php 
                        $is_available = $has_is_available ? $item['is_available'] : (isset($item['status']) ? ($item['status'] == 'available') : true);
                        ?>
                        <span class="badge <?php echo $is_available ? 'bg-success' : 'bg-danger'; ?>">
                            <?php echo $is_available ? 'Available' : 'Borrowed'; ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Borrowing Details</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="mb-3">
                        <label for="pickup_location" class="form-label">Pickup Location</label>
                        <textarea class="form-control" id="pickup_location" name="pickup_location" rows="3" required></textarea>
                        <small class="text-muted">Enter the address where you want to pick up the books.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Borrowing Period</label>
                        <p class="text-muted">Books can be borrowed for a maximum of 3 days.</p>
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100" <?php echo !empty($error_message) ? 'disabled' : ''; ?>>
                        <i class="fas fa-check"></i> Confirm Borrowing
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php 
include '../includes/user_navbar.php';
include '../includes/footer.php'; 
?>
