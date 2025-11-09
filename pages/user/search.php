<?php
session_start();
require_once '../../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /Lost-Found/pages/login.php');
    exit();
}

// Initialize variables
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$items = [];

try {
    // Include the database configuration
    require_once '../../config/db.php';
    
    // Base query for both lost and found items
    $query = "SELECT 'lost' as type, item_id, user_id, item_name, category, description, 
                     date_lost as item_date, location, image_path, status, created_at 
              FROM lost_items 
              WHERE status = 'pending'
              
              UNION ALL
              
              SELECT 'found' as type, item_id, user_id, item_name, category, description, 
                     found_date as item_date, location, image_path, status, created_at 
              FROM found_items 
              WHERE status = 'available'
              
              ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $allItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Apply filters
    $items = array_filter($allItems, function($item) use ($filter, $searchQuery) {
        // Apply type filter
        if ($filter !== 'all' && $item['type'] !== $filter) {
            return false;
        }
        
        // Apply search query
        if (!empty($searchQuery)) {
            $searchableText = strtolower($item['item_name'] . ' ' . $item['description'] . ' ' . $item['category'] . ' ' . $item['location']);
            return (strpos($searchableText, strtolower($searchQuery)) !== false);
        }
        
        return true;
    });
    
} catch (PDOException $e) {
    error_log('Database Error: ' . $e->getMessage());
    $error = 'An error occurred while loading items. Please try again later.';
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
    <link rel="stylesheet" href="/Lost-Found/assets/style.css">
    <style>
        .search-input-group {
            position: relative;
            max-width: 700px;
        }
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 1.1rem;
        }
        #searchInput {
            padding-left: 45px;
            border-radius: 8px;
            height: 50px;
            font-size: 1rem;
            border: 1px solid #dee2e6;
        }
        .filter-button {
            padding: 0.5rem 1.5rem;
            border: 1px solid #dee2e6;
            background: white;
            border-radius: 50px;
            font-weight: 500;
            color: #495057;
            transition: all 0.2s;
        }
        .filter-button:hover, .filter-button.active {
            background-color: #4a6cf7;
            color: white;
            border-color: #4a6cf7;
        }
        .item-card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
        }
        .item-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        .item-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        .item-type {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .item-type.lost {
            background-color: #fff3cd;
            color: #856404;
        }
        .item-type.found {
            background-color: #d4edda;
            color: #155724;
        }
        .item-category {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .item-date {
            color: #6c757d;
            font-size: 0.85rem;
        }
        .item-location {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .btn-view-details {
            background-color: #4a6cf7;
            color: white;
            font-weight: 500;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            border: none;
            transition: background-color 0.2s;
        }
        .btn-view-details:hover {
            background-color: #3a5bd9;
            color: white;
        }
        .no-items-icon {
            font-size: 5rem;
            color: #dee2e6;
            margin-bottom: 1.5rem;
        }
    </style>
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
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                            <li><a class="dropdown-item" href="my_claims.php"><i class="bi bi-card-checklist me-2"></i>My Claims</a></li>
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

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php elseif (empty($items)): ?>
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
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4" id="itemGrid">
                <?php foreach ($items as $item): ?>
                    <div class="col">
                        <div class="card item-card h-100">
                            <div class="position-relative">
                                <?php if (!empty($item['image_path'])): ?>
                                    <img src="/Lost-Found/<?php echo htmlspecialchars($item['image_path']); ?>" class="card-img-top item-image" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
                                <?php else: ?>
                                    <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                        <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                                    </div>
                                <?php endif; ?>
                                <span class="item-type <?php echo $item['type']; ?>">
                                    <?php echo ucfirst($item['type']); ?>
                                </span>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title mb-1"><?php echo htmlspecialchars($item['item_name']); ?></h5>
                                <div class="item-category">
                                    <i class="bi bi-tag-fill me-1"></i> 
                                    <?php echo htmlspecialchars($item['category']); ?>
                                </div>
                                <p class="card-text flex-grow-1">
                                    <?php 
                                    $description = $item['description'];
                                    if (strlen($description) > 100) {
                                        $description = substr($description, 0, 100) . '...';
                                    }
                                    echo htmlspecialchars($description);
                                    ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <div>
                                        <div class="item-location">
                                            <i class="bi bi-geo-alt-fill me-1"></i> 
                                            <?php echo htmlspecialchars($item['location']); ?>
                                        </div>
                                        <div class="item-date">
                                            <i class="bi bi-calendar-event me-1"></i> 
                                            <?php echo date('M j, Y', strtotime($item['item_date'])); ?>
                                        </div>
                                    </div>
                                    <a href="item_detail.php?type=<?php echo $item['type']; ?>&id=<?php echo $item['item_id']; ?>" class="btn btn-view-details">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
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
