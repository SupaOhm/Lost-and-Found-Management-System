<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /Lost-Found/pages/login.php');
    exit();
}

// Initialize variables
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$location = isset($_GET['location']) ? $_GET['location'] : '';
$items = [];
$error = '';

// Debug: Log request parameters
error_log('Search Parameters - Query: ' . $searchQuery . ', Filter: ' . $filter . ', Category: ' . $category . ', Location: ' . $location);

try {
    // Close any open cursor first
    if (isset($stmt) && $stmt) {
        $stmt->closeCursor();
    }

    // Determine which stored procedure to call based on filters
    if (!empty($searchQuery) || !empty($category) || !empty($location)) {
        // Use advanced search with filters
        $stmt = $pdo->prepare("CALL SearchItems(?, ?, ?, ?)");
        $searchTerm = !empty($searchQuery) ? "%$searchQuery%" : null;
        $categoryFilter = !empty($category) ? $category : null;
        $locationFilter = !empty($location) ? "%$location%" : null;
        
        $stmt->execute([
            $searchTerm,
            $categoryFilter,
            $locationFilter,
            $filter === 'all' ? null : $filter
        ]);
    } else if ($filter !== 'all') {
        // Filter by type only
        $stmt = $pdo->prepare("CALL GetItemsByType(?)");
        $stmt->execute([$filter]);
    } else {
        // Get all active items
        $stmt = $pdo->query("CALL GetAllActiveItems()");
    }
    
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    
    // Get distinct categories for filter
    $categoryStmt = $pdo->query("CALL GetItemCategories()");
    $categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);
    $categoryStmt->closeCursor();
    
    // Debug: Log the number of items found
    error_log('Number of items found: ' . count($items));
    error_log('First item: ' . print_r(!empty($items) ? $items[0] : 'No items', true));

} catch (PDOException $e) {
    // Log detailed error information
    $errorDetails = 'Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
    error_log('Search Error: ' . $errorDetails);
    
    // For debugging - show detailed error to admin
    $isAdmin = true; // Set to true to see detailed errors
    $error = $isAdmin ? $errorDetails : 'An error occurred while searching. Please try again later.';
    
    // Log the last executed query if available
    if (isset($stmt)) {
        error_log('Last query: ' . $stmt->queryString);
        error_log('Query params: ' . print_r($stmt->debugDumpParams(), true));
    }
    
    // Log PDO error info
    if (isset($pdo)) {
        $errorInfo = $pdo->errorInfo();
        error_log('PDO Error Info: ' . print_r($errorInfo, true));
    }
}

// Function to highlight search terms in text
function highlightSearchTerms($text, $searchQuery) {
    if (empty($searchQuery)) return $text;
    $searchTerms = explode(' ', $searchQuery);
    foreach ($searchTerms as $term) {
        $term = trim($term);
        if (!empty($term)) {
            $text = preg_replace("/($term)/i", '<span class="highlight">$1</span>', $text);
        }
    }
    return $text;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Items - Lost&Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/style.css">
    
</head>
<body>
    <header class="app-header">
        <div class="container py-3">
            <div class="d-flex justify-content-between align-items-center">
                <a href="userdash.php" class="logo">
                    <i class="bi bi-search-heart logo-icon"></i>
                    Lost&Found
                </a>
                <div class="d-flex align-items-center gap-3">
                    <a href="userdash.php" class="dashboard-btn d-flex align-items-center gap-2">
                        <i class="bi bi-house-door-fill"></i> Dashboard
                    </a>
                    <div class="dropdown">
                        <div class="profile-icon" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                            <li><a class="dropdown-item" href="userprofile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                            <li><a class="dropdown-item" href="claim.php"><i class="bi bi-card-checklist me-2"></i>My Claims & Reports</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="container py-5">
        <div class="text-center mb-5">
            <h2 class="section-title">Browse Lost & Found Items</h2>
            <p class="text-muted">Search through our database to find your lost item or check if someone found your belongings</p>
        </div>

        <div class="mb-5">
            <form id="searchForm" method="GET" action="search.php" class="position-relative">
                <div class="search-input-group position-relative mx-auto">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" 
                           class="form-control" 
                           id="searchInput" 
                           name="q" 
                           value="<?php echo htmlspecialchars($searchQuery); ?>" 
                           placeholder="Search by item name, description, category, or location..." 
                           aria-label="Search">
                    <input type="hidden" name="filter" id="filterInput" value="<?php echo htmlspecialchars($filter); ?>">
                </div>
            </form>
        </div>

        <div class="d-flex gap-3 mb-5 flex-wrap justify-content-center">
            <button class="filter-button <?php echo $filter === 'all' ? 'active' : ''; ?>" data-filter="all">All Items</button>
            <button class="filter-button <?php echo $filter === 'lost' ? 'active' : ''; ?>" data-filter="lost">Lost Items</button>
            <button class="filter-button <?php echo $filter === 'found' ? 'active' : ''; ?>" data-filter="found">Found Items</button>
        </div>
        <!-- 
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <h4>Error Details:</h4>
                <pre><?php echo htmlspecialchars($error); ?></pre>
                <p class="mt-2">Please check the following:</p>
                <ul>
                    <li>Are you logged in? (User ID: <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Not logged in'; ?>)</li>
                    <li>Is the database connected? <?php echo isset($pdo) ? 'Yes' : 'No'; ?></li>
                    <li>Number of items found: <?php echo is_array($items) ? count($items) : '0 (items is not an array)'; ?></li>
                </ul>
            </div>
        <?php endif; ?>
        -->

        <?php if (is_array($items) && !empty($items)): // Show items if we have them ?>
            <div class="list-group mb-4">
                <?php 
                foreach ($items as $item): 
                    // Ensure all required fields have values
                    $item = array_merge([
                        'type' => 'unknown',
                        'id' => 0,
                        'item_name' => 'Untitled Item',
                        'description' => 'No description available',
                        'category' => 'Uncategorized',
                        'location' => 'Location not specified',
                        'item_date' => date('Y-m-d'),
                        'created_at' => date('Y-m-d H:i:s')
                    ], $item);
                    
                    // Format dates
                    $itemDate = date('M j, Y', strtotime($item['item_date']));
                    $createdAt = date('M j, Y g:i A', strtotime($item['created_at']));
                ?>
                    <div class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1">
                                <span class="badge bg-<?php echo $item['type'] === 'lost' ? 'danger' : 'success'; ?> me-2">
                                    <?php echo ucfirst(htmlspecialchars($item['type'])); ?>
                                </span>
                                <?php echo htmlspecialchars($item['item_name']); ?>
                            </h5>
                            <small class="text-muted">Reported on <?php echo $createdAt; ?></small>
                        </div>
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <div class="me-3">
                                <p class="mb-1">
                                    <i class="bi bi-tag-fill text-muted me-1"></i>
                                    <span class="text-muted">Category:</span> 
                                    <?php echo htmlspecialchars($item['category']); ?>
                                </p>
                                <p class="mb-1">
                                    <i class="bi bi-geo-alt-fill text-muted me-1"></i>
                                    <span class="text-muted">Location:</span> 
                                    <?php echo htmlspecialchars($item['location']); ?>
                                </p>
                                <p class="mb-1">
                                    <i class="bi bi-calendar-event text-muted me-1"></i>
                                    <span class="text-muted">
                                        <?php echo $item['type'] === 'lost' ? 'Lost' : 'Found'; ?> on:
                                    </span> 
                                    <?php echo $itemDate; ?>
                                </p>
                                <?php if (!empty($item['description'])): ?>
                                    <p class="mb-0 mt-2">
                                        <i class="bi bi-chat-square-text text-muted me-1"></i>
                                        <?php echo htmlspecialchars($item['description']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <a href="item_detail.php?type=<?php echo htmlspecialchars($item['type']); ?>&id=<?php echo htmlspecialchars($item['id']); ?>" 
                               class="btn btn-sm btn-outline-primary">
                                View Details
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: // Show no items message ?>
            <div id="noItemsMessage" class="text-center mt-5">
                <div class="py-5">
                    <i class="bi bi-search no-items-icon"></i>
                    <h4 class="text-muted">No items found</h4>
                    <p class="text-muted mb-4">
                        <?php 
                        if (!empty($searchQuery)) {
                            echo 'No items match your search criteria. Try different keywords or filters.';
                        } else if ($filter !== 'all') {
                            echo 'No ' . htmlspecialchars($filter) . ' items found. Check back later or try a different filter.';
                        } else {
                            echo 'No items have been reported yet. Check back later or report a lost/found item.';
                        }
                        ?>
                    </p>
                    <div class="d-flex gap-3 justify-content-center">
                        <a href="lost.php" class="btn btn-form-submit">Report Lost Item</a>
                        <a href="found.php" class="btn btn-outline-primary">Report Found Item</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle filter button clicks
            const filterButtons = document.querySelectorAll('.filter-button');
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Update active state
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Update hidden input and submit form
                    const filter = this.getAttribute('data-filter');
                    document.getElementById('filterInput').value = filter;
                    document.getElementById('searchForm').submit();
                });
            });
            
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Auto-submit search form when typing stops (with debounce)
            let searchTimer;
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(() => {
                        document.getElementById('searchForm').submit();
                    }, 500);
                });
            }
        });
    </script>
</body>
</html>
