<?php
$page_title = "Dashboard";
require_once '../config/init.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect("login.php");
}

// Get recently added books
$sql = "SELECT * FROM books ORDER BY created_at DESC LIMIT 8";
$result = $conn->query($sql);
$recent_books = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recent_books[] = $row;
    }
}

// Get user's active borrowings (exclude canceled and returned)
$user_id = $_SESSION["user_id"];
$sql = "SELECT b.*, bk.title, bk.image FROM borrowings b 
        JOIN books bk ON b.book_id = bk.id 
        WHERE b.user_id = ? AND b.status IN ('pending', 'picked_up')
        ORDER BY b.borrow_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$active_borrowings = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $active_borrowings[] = $row;
    }
}

include '../includes/header.php';
?>

<div class="fade-in">
    <!-- Welcome Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Welcome back!</h1>
            <p class="text-muted mb-0"><?php echo $_SESSION["user_name"]; ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="search.php" class="btn btn-primary btn-sm">
                <i class="fas fa-search"></i>
                <span class="d-none d-sm-inline ms-1">Browse</span>
            </a>
        </div>
    </div>

    <!-- Active Borrowings -->
    <?php if (!empty($active_borrowings)): ?>
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-book-reader me-2"></i>
                    Your Active Borrowings
                </h5>
                <span class="badge bg-light text-dark"><?php echo count($active_borrowings); ?></span>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <?php foreach ($active_borrowings as $borrowing): ?>
                <div class="col-12 col-sm-6 col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <div class="row g-0 h-100">
                            <div class="col-4">
                                <img src="../uploads/books/<?php echo $borrowing['image']; ?>" 
                                     class="img-fluid h-100 w-100 rounded-start" 
                                     alt="<?php echo $borrowing['title']; ?>" 
                                     style="object-fit: cover; min-height: 120px;"
                                     onerror="this.src='../assets/img/book-placeholder.png'">
                            </div>
                            <div class="col-8">
                                <div class="card-body p-3 d-flex flex-column h-100">
                                    <h6 class="card-title mb-2 text-truncate-2"><?php echo $borrowing['title']; ?></h6>
                                    <div class="mb-2">
                                        <?php if ($borrowing['status'] == 'pending'): ?>
                                            <span class="badge bg-warning">
                                                <i class="fas fa-clock me-1"></i>Pending Pickup
                                            </span>
                                        <?php elseif ($borrowing['status'] == 'picked_up'): ?>
                                            <span class="badge bg-info">
                                                <i class="fas fa-book me-1"></i>Borrowed
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-auto">
                                        <a href="borrowing_detail.php?id=<?php echo $borrowing['id']; ?>" 
                                           class="btn btn-primary btn-sm w-100">
                                            <i class="fas fa-eye me-1"></i>View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-3">
                <a href="borrowings.php" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-list me-1"></i>View All Borrowings
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recently Added Books -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-star me-2"></i>
                    Recently Added Books
                </h5>
                <a href="search.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($recent_books)): ?>
                <div class="empty-state">
                    <i class="fas fa-book"></i>
                    <p>No books available at the moment.</p>
                    <a href="search.php" class="btn btn-primary">Browse Library</a>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($recent_books as $book): ?>
                    <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                        <div class="card book-card h-100">
                            <img src="../uploads/books/<?php echo $book['image']; ?>" 
                                 class="book-image" 
                                 alt="<?php echo $book['title']; ?>" 
                                 onerror="this.src='../assets/img/book-placeholder.png'">
                            <div class="card-body">
                                <h6 class="card-title text-truncate-2"><?php echo $book['title']; ?></h6>
                                <p class="card-text">
                                    <small class="text-muted d-block text-truncate"><?php echo $book['author']; ?></small>
                                    <?php if (isset($book['published_year']) && !empty($book['published_year'])): ?>
                                    <small class="text-muted"><?php echo $book['published_year']; ?></small>
                                    <?php endif; ?>
                                </p>
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge <?php echo $book['is_available'] ? 'bg-success' : 'bg-danger'; ?>">
                                            <?php echo $book['is_available'] ? 'Available' : 'Borrowed'; ?>
                                        </span>
                                    </div>
                                    <a href="book_detail.php?id=<?php echo $book['id']; ?>" 
                                       class="btn btn-primary btn-sm w-100">
                                        <i class="fas fa-eye me-1"></i>
                                        <span class="d-none d-sm-inline">View</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
include '../includes/user_navbar.php';
include '../includes/footer.php'; 
?>
