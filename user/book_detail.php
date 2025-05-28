<?php
$page_title = "Book Detail";
require_once '../config/init.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect("login.php");
}

// Check if book ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect("search.php");
}

$book_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
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

// Get book details
$sql = "SELECT * FROM books WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    redirect("search.php");
}

$book = $result->fetch_assoc();

// Check if book is in user's cart
$sql = "SELECT id FROM cart WHERE user_id = ? AND book_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $book_id);
$stmt->execute();
$result = $stmt->get_result();
$in_cart = ($result->num_rows > 0);

// Check if user has borrowed this book
$sql = "SELECT id FROM borrowings WHERE user_id = ? AND book_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $book_id);
$stmt->execute();
$result = $stmt->get_result();
$has_borrowed = ($result->num_rows > 0);

// Get book reviews
$sql = "SELECT r.*, u.name as user_name FROM reviews r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.book_id = ? 
        ORDER BY r.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();
$reviews = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
}

// Calculate average rating
$avg_rating = 0;
if (!empty($reviews)) {
    $total_rating = 0;
    foreach ($reviews as $review) {
        $total_rating += $review['rating'];
    }
    $avg_rating = round($total_rating / count($reviews), 1);
}

// Process add to cart
if (isset($_POST['add_to_cart'])) {
    // Check if book is available
    $is_available = $has_is_available ? $book['is_available'] : ($book['status'] == 'available');
    
    if (!$is_available) {
        $error_message = "This book is currently borrowed and cannot be added to cart.";
    } else {
        // Check if user already has 2 books in cart
        $sql = "SELECT COUNT(*) as count FROM cart WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $cart_count = $result->fetch_assoc()['count'];
        
        if ($cart_count >= 2) {
            $error_message = "You can only have a maximum of 2 books in your cart.";
        } else {
            // Add book to cart
            $sql = "INSERT INTO cart (user_id, book_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE added_at = CURRENT_TIMESTAMP";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $user_id, $book_id);
            
            if ($stmt->execute()) {
                $success_message = "Book added to cart successfully.";
                $in_cart = true;
            } else {
                $error_message = "Failed to add book to cart. Please try again.";
            }
        }
    }
}

// Process remove from cart
if (isset($_POST['remove_from_cart'])) {
    $sql = "DELETE FROM cart WHERE user_id = ? AND book_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $book_id);
    
    if ($stmt->execute()) {
        $success_message = "Book removed from cart successfully.";
        $in_cart = false;
    } else {
        $error_message = "Failed to remove book from cart. Please try again.";
    }
}

// Process add review
if (isset($_POST['add_review'])) {
    $rating = intval($_POST['rating']);
    $comment = sanitize($_POST['comment']);
    
    // Validate input
    if ($rating < 1 || $rating > 5) {
        $error_message = "Rating must be between 1 and 5.";
    } elseif (empty($comment)) {
        $error_message = "Comment is required.";
    } else {
        // Check if user has already reviewed this book
        $sql = "SELECT id FROM reviews WHERE user_id = ? AND book_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $book_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing review
            $review_id = $result->fetch_assoc()['id'];
            $sql = "UPDATE reviews SET rating = ?, comment = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isi", $rating, $comment, $review_id);
        } else {
            // Insert new review
            $sql = "INSERT INTO reviews (user_id, book_id, rating, comment) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiis", $user_id, $book_id, $rating, $comment);
        }
        
        if ($stmt->execute()) {
            $success_message = "Review submitted successfully.";
            // Refresh the page to show the new review
            redirect("book_detail.php?id=$book_id&success=review_added");
        } else {
            $error_message = "Failed to submit review. Please try again.";
        }
    }
}

// Check for success message from URL
if (isset($_GET['success']) && $_GET['success'] == 'review_added') {
    $success_message = "Review submitted successfully.";
}

// Get book availability status
$is_available = $has_is_available ? $book['is_available'] : (isset($book['status']) ? ($book['status'] == 'available') : true);

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Book Detail</h1>
    <a href="javascript:history.back()" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left"></i>
        <span class="d-none d-sm-inline ms-1">Back</span>
    </a>
</div>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success"><?php echo $success_message; ?></div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger"><?php echo $error_message; ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-12 col-md-4 col-lg-3 text-center mb-3 mb-md-0">
                <img src="../uploads/books/<?php echo $book['image']; ?>" 
                     class="img-fluid rounded" 
                     alt="<?php echo $book['title']; ?>" 
                     style="max-height: 300px; width: 100%; object-fit: cover;" 
                     onerror="this.src='../assets/img/book-placeholder.png'">
            </div>
            <div class="col-12 col-md-8 col-lg-9">
                <h2 class="h4 mb-3"><?php echo $book['title']; ?></h2>
                
                <div class="mb-3">
                    <span class="badge <?php echo $is_available ? 'bg-success' : 'bg-danger'; ?> mb-2">
                        <?php echo $is_available ? 'Available' : 'Borrowed'; ?>
                    </span>
                    
                    <?php if ($avg_rating > 0): ?>
                    <div class="mb-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?php if ($i <= round($avg_rating)): ?>
                                <i class="fas fa-star text-warning"></i>
                            <?php else: ?>
                                <i class="far fa-star text-warning"></i>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <span class="ms-1">(<?php echo $avg_rating; ?>/5 - <?php echo count($reviews); ?> reviews)</span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-12 col-sm-6">
                            <p class="mb-2"><strong>Author:</strong> <?php echo $book['author']; ?></p>
                            <?php if (isset($book[$category_column]) && !empty($book[$category_column])): ?>
                            <p class="mb-2"><strong><?php echo $has_category ? 'Category' : 'Genre'; ?>:</strong> <?php echo $book[$category_column]; ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-12 col-sm-6">
                            <?php if ($has_isbn && isset($book['isbn']) && !empty($book['isbn'])): ?>
                            <p class="mb-2"><strong>ISBN:</strong> 
                                <span class="d-inline d-sm-none"><?php echo substr($book['isbn'], 0, 10) . '...'; ?></span>
                                <span class="d-none d-sm-inline"><?php echo $book['isbn']; ?></span>
                            </p>
                            <?php endif; ?>
                            
                            <?php if ($has_published_year && isset($book['published_year']) && !empty($book['published_year'])): ?>
                            <p class="mb-2"><strong>Published:</strong> <?php echo $book['published_year']; ?></p>
                            <?php endif; ?>
                            
                            <?php if ($has_page_count && isset($book['page_count']) && !empty($book['page_count'])): ?>
                            <p class="mb-2"><strong>Pages:</strong> <?php echo $book['page_count']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <h5>Description</h5>
                    <p class="text-justify"><?php echo nl2br($book['description']); ?></p>
                </div>
                
                <div class="d-flex gap-2 flex-column flex-sm-row">
                    <?php if ($in_cart): ?>
                        <form method="POST">
                            <button type="submit" name="remove_from_cart" class="btn btn-danger w-100">
                                <i class="fas fa-trash"></i> Remove from Cart
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST">
                            <button type="submit" name="add_to_cart" class="btn btn-primary w-100" <?php echo !$is_available ? 'disabled' : ''; ?>>
                                <i class="fas fa-cart-plus"></i> Add to Cart
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Reviews</h5>
    </div>
    <div class="card-body">
        <?php if ($has_borrowed): ?>
        <div class="mb-4">
            <h6>Write a Review</h6>
            <form method="POST">
                <div class="mb-3">
                    <label for="rating" class="form-label">Rating</label>
                    <div class="rating">
                        <div class="d-flex flex-wrap gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="rating" id="rating1" value="1" required>
                                <label class="form-check-label" for="rating1">1 ⭐</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="rating" id="rating2" value="2">
                                <label class="form-check-label" for="rating2">2 ⭐</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="rating" id="rating3" value="3">
                                <label class="form-check-label" for="rating3">3 ⭐</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="rating" id="rating4" value="4">
                                <label class="form-check-label" for="rating4">4 ⭐</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="rating" id="rating5" value="5">
                                <label class="form-check-label" for="rating5">5 ⭐</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="comment" class="form-label">Comment</label>
                    <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                </div>
                <button type="submit" name="add_review" class="btn btn-primary">Submit Review</button>
            </form>
        </div>
        <?php endif; ?>
        
        <?php if (empty($reviews)): ?>
            <p class="text-center">No reviews yet. Be the first to review this book!</p>
        <?php else: ?>
            <?php foreach ($reviews as $review): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-column flex-sm-row">
                        <h6 class="mb-0"><?php echo $review['user_name']; ?></h6>
                        <small class="text-muted"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></small>
                    </div>
                    <div class="mb-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?php if ($i <= $review['rating']): ?>
                                <i class="fas fa-star text-warning"></i>
                            <?php else: ?>
                                <i class="far fa-star text-warning"></i>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>
                    <p class="mb-0"><?php echo nl2br($review['comment']); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php 
include '../includes/user_navbar.php';
include '../includes/footer.php'; 
?>
