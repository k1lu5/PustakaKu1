<?php
$page_title = "Cart";
require_once '../config/init.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect("login.php");
}

$user_id = $_SESSION["user_id"];
$success_message = "";
$error_message = "";

// Process remove from cart
if (isset($_POST['remove_from_cart']) && isset($_POST['book_id'])) {
    $book_id = intval($_POST['book_id']);
    
    $sql = "DELETE FROM cart WHERE user_id = ? AND book_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $book_id);
    
    if ($stmt->execute()) {
        $success_message = "Book removed from cart successfully.";
    } else {
        $error_message = "Failed to remove book from cart. Please try again.";
    }
}

// Process clear cart
if (isset($_POST['clear_cart'])) {
    $sql = "DELETE FROM cart WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $success_message = "Cart cleared successfully.";
    } else {
        $error_message = "Failed to clear cart. Please try again.";
    }
}

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

// Build the SELECT query based on available columns
$select_fields = ['c.*', 'b.title', 'b.author', 'b.image'];

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

include '../includes/header.php';
?>

<div class="fade-in">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Your Cart</h1>
            <p class="text-muted mb-0"><?php echo count($cart_items); ?> item(s) in your cart</p>
        </div>
        <?php if (!empty($cart_items)): ?>
        <form method="POST" onsubmit="return confirm('Are you sure you want to clear your cart?');">
            <button type="submit" name="clear_cart" class="btn btn-outline-danger btn-sm">
                <i class="fas fa-trash me-1"></i>
                <span class="d-none d-sm-inline">Clear Cart</span>
            </button>
        </form>
        <?php endif; ?>
    </div>

    <!-- Alerts -->
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <!-- Cart Content -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($cart_items)): ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Your cart is empty.</p>
                    <a href="search.php" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Browse Books
                    </a>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($cart_items as $item): ?>
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="row g-0">
                                <div class="col-3 col-sm-2">
                                    <img src="../uploads/books/<?php echo $item['image']; ?>" 
                                         class="img-fluid h-100 w-100 rounded-start" 
                                         alt="<?php echo $item['title']; ?>" 
                                         style="object-fit: cover; min-height: 120px;"
                                         onerror="this.src='../assets/img/book-placeholder.png'">
                                </div>
                                <div class="col-9 col-sm-10">
                                    <div class="card-body p-3">
                                        <div class="row">
                                            <div class="col-12 col-md-8">
                                                <h6 class="card-title mb-2"><?php echo $item['title']; ?></h6>
                                                <div class="text-muted small">
                                                    <div class="mb-1">
                                                        <i class="fas fa-user me-1"></i>
                                                        <?php echo $item['author']; ?>
                                                    </div>
                                                    <?php if ($has_isbn && isset($item['isbn']) && !empty($item['isbn'])): ?>
                                                    <div class="mb-1">
                                                        <i class="fas fa-barcode me-1"></i>
                                                        <span class="d-inline d-sm-none"><?php echo substr($item['isbn'], 0, 10) . '...'; ?></span>
                                                        <span class="d-none d-sm-inline"><?php echo $item['isbn']; ?></span>
                                                    </div>
                                                    <?php endif; ?>
                                                    <?php if ($has_published_year && isset($item['published_year']) && !empty($item['published_year'])): ?>
                                                    <div class="mb-1">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        <?php echo $item['published_year']; ?>
                                                    </div>
                                                    <?php endif; ?>
                                                    <div class="mt-2">
                                                        <?php 
                                                        $is_available = $has_is_available ? $item['is_available'] : ($item['status'] == 'available');
                                                        ?>
                                                        <span class="badge <?php echo $is_available ? 'bg-success' : 'bg-danger'; ?>">
                                                            <i class="fas <?php echo $is_available ? 'fa-check' : 'fa-times'; ?> me-1"></i>
                                                            <?php echo $is_available ? 'Available' : 'Borrowed'; ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <div class="d-flex flex-column gap-2 h-100 justify-content-center">
                                                    <a href="book_detail.php?id=<?php echo $item['book_id']; ?>" 
                                                       class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-eye me-1"></i>View Details
                                                    </a>
                                                    <form method="POST" onsubmit="return confirm('Remove this book from your cart?');">
                                                        <input type="hidden" name="book_id" value="<?php echo $item['book_id']; ?>">
                                                        <button type="submit" name="remove_from_cart" class="btn btn-outline-danger btn-sm w-100">
                                                            <i class="fas fa-trash me-1"></i>Remove
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Checkout Button -->
                <div class="text-center mt-4">
                    <div class="d-grid gap-2 d-md-block">
                        <a href="checkout.php" class="btn btn-success btn-lg">
                            <i class="fas fa-check me-2"></i>Proceed to Checkout
                        </a>
                        <a href="search.php" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-plus me-2"></i>Add More Books
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
include '../includes/user_navbar.php';
include '../includes/footer.php'; 
?>
