<?php
$page_title = "Search Books";
require_once '../config/init.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect("login.php");
}

// Initialize variables
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$category = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$books = [];

// Get all categories for filter - check if category column exists
$sql = "SHOW COLUMNS FROM books LIKE 'category'";
$result = $conn->query($sql);
$has_category = $result->num_rows > 0;

if (!$has_category) {
    // Fallback to genre if category doesn't exist
    $sql = "SHOW COLUMNS FROM books LIKE 'genre'";
    $result = $conn->query($sql);
    $category_column = $result->num_rows > 0 ? 'genre' : 'category';
} else {
    $category_column = 'category';
}

// Get all categories for filter
$sql = "SELECT DISTINCT $category_column as category FROM books ORDER BY $category_column";
$result = $conn->query($sql);
$categories = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}

// Check if is_available column exists
$sql = "SHOW COLUMNS FROM books LIKE 'is_available'";
$result = $conn->query($sql);
$has_is_available = $result->num_rows > 0;

$status_column = $has_is_available ? 'is_available' : 'status';
$available_condition = $has_is_available ? 'is_available = 1' : "status = 'available'";

// Search books
if (!empty($search) || !empty($category)) {
    $sql = "SELECT * FROM books WHERE 1=1";
    $params = [];
    $types = "";
    
    if (!empty($search)) {
        $search_fields = ['title', 'author'];
        // Check if isbn column exists
        $sql_check = "SHOW COLUMNS FROM books LIKE 'isbn'";
        $result_check = $conn->query($sql_check);
        if ($result_check->num_rows > 0) {
            $search_fields[] = 'isbn';
        }
        
        $search_conditions = [];
        foreach ($search_fields as $field) {
            $search_conditions[] = "$field LIKE ?";
        }
        
        $sql .= " AND (" . implode(" OR ", $search_conditions) . ")";
        $search_param = "%$search%";
        foreach ($search_fields as $field) {
            $params[] = $search_param;
            $types .= "s";
        }
    }
    
    if (!empty($category)) {
        $sql .= " AND $category_column = ?";
        $params[] = $category;
        $types .= "s";
    }
    
    $sql .= " ORDER BY title";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
    }
} else {
    // Get all books if no search criteria
    $sql = "SELECT * FROM books ORDER BY title";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
    }
}

include '../includes/header.php';
?>

<div class="fade-in">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Discover Books</h1>
            <p class="text-muted mb-0">Find your next great read</p>
        </div>
    </div>

    <!-- Search Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="row g-3">
                    <div class="col-12 col-md-8">
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" 
                                   class="form-control" 
                                   placeholder="Search by title, author, or ISBN..." 
                                   name="search" 
                                   value="<?php echo $search; ?>">
                            <button class="btn btn-primary" type="submit">
                                Search
                            </button>
                        </div>
                    </div>
                    <div class="col-8 col-md-3">
                        <select class="form-select" name="category" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat; ?>" <?php echo $category == $cat ? 'selected' : ''; ?>>
                                    <?php echo $cat; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-4 col-md-1">
                        <a href="search.php" class="btn btn-outline-secondary w-100" title="Reset">
                            <i class="fas fa-undo"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Results -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-books me-2"></i>
                    <?php 
                    if (!empty($search) || !empty($category)) {
                        echo "Search Results";
                        if (!empty($search)) echo " for \"" . (strlen($search) > 20 ? substr($search, 0, 20) . '...' : $search) . "\"";
                        if (!empty($category)) echo " in $category";
                    } else {
                        echo "All Books";
                    }
                    ?>
                </h5>
                <span class="badge bg-light text-dark"><?php echo count($books); ?> books</span>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($books)): ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <p>No books found matching your criteria.</p>
                    <a href="search.php" class="btn btn-primary">Browse All Books</a>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($books as $book): ?>
                    <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                        <div class="card book-card h-100">
                            <div class="position-relative">
                                <img src="../uploads/books/<?php echo $book['image']; ?>" 
                                     class="book-image" 
                                     alt="<?php echo $book['title']; ?>" 
                                     onerror="this.src='../assets/img/book-placeholder.png'">
                                <div class="position-absolute top-0 end-0 m-2">
                                    <?php 
                                    $is_available = $has_is_available ? $book['is_available'] : ($book['status'] == 'available');
                                    ?>
                                    <span class="badge <?php echo $is_available ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $is_available ? 'Available' : 'Borrowed'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <h6 class="card-title text-truncate-2"><?php echo $book['title']; ?></h6>
                                <p class="card-text">
                                    <small class="text-muted d-block text-truncate"><?php echo $book['author']; ?></small>
                                    <small class="text-muted d-block text-truncate">
                                        <?php echo isset($book[$category_column]) ? $book[$category_column] : ''; ?>
                                    </small>
                                    <?php if (isset($book['published_year']) && !empty($book['published_year'])): ?>
                                    <small class="text-muted"><?php echo $book['published_year']; ?></small>
                                    <?php endif; ?>
                                </p>
                                <div class="mt-auto">
                                    <a href="book_detail.php?id=<?php echo $book['id']; ?>" 
                                       class="btn btn-primary btn-sm w-100">
                                        <i class="fas fa-eye me-1"></i>
                                        <span class="d-none d-sm-inline">View Details</span>
                                        <span class="d-sm-none">View</span>
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
