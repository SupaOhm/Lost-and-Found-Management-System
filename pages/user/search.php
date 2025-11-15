<?php
session_start();
require_once '../../config/userconfig.php';
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
// Option to exclude current user's reports
$excludeMy = isset($_GET['exclude_my']) && $_GET['exclude_my'] === '1';

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

    // If requested, remove items reported by the current user
    if ($excludeMy && isset($_SESSION['user_id']) && !empty($items)) {
        $filtered = [];
        foreach ($items as $it) {
            $ownerId = null;
            if (isset($it['user_id'])) {
                $ownerId = $it['user_id'];
            } else {
                // Try to fetch owner id from the appropriate table if not present
                try {
                    if (isset($it['type']) && $it['type'] === 'lost') {
                        $q = $pdo->prepare('SELECT user_id FROM LostItem WHERE lost_id = ?');
                        $q->execute([$it['id']]);
                        $row = $q->fetch(PDO::FETCH_ASSOC);
                        $q->closeCursor();
                        $ownerId = $row['user_id'] ?? null;
                    } elseif (isset($it['type']) && $it['type'] === 'found') {
                        $q = $pdo->prepare('SELECT user_id FROM FoundItem WHERE found_id = ?');
                        $q->execute([$it['id']]);
                        $row = $q->fetch(PDO::FETCH_ASSOC);
                        $q->closeCursor();
                        $ownerId = $row['user_id'] ?? null;
                    }
                } catch (PDOException $e) {
                    // If lookup fails, keep the item (fail-open)
                    $ownerId = null;
                }
            }

            if ($ownerId === null || $ownerId != $_SESSION['user_id']) {
                $filtered[] = $it;
            }
        }
        $items = $filtered;
    }
    
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
    <?php include 'includes/header.php'; ?>

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
                    <input type="hidden" name="exclude_my" id="excludeMyInput" value="<?php echo $excludeMy ? '1' : ''; ?>">
                    <input type="hidden" name="view_mode" id="viewModeInput" value="<?php echo isset($_GET['view_mode']) ? htmlspecialchars($_GET['view_mode']) : 'list'; ?>">
                </div>
            </form>
        </div>

        <div class="d-flex gap-3 mb-4 flex-wrap justify-content-center align-items-center">
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
            <div class="d-flex justify-content-between mb-2 list-controls align-items-center">
                <div>
                    <button id="viewToggleBtn" type="button" class="btn btn-outline-secondary btn-sm view-btn" title="Toggle View">
                        <i id="viewToggleIcon" class="bi bi-list"></i>
                    </button>
                </div>
                <button id="excludeBtn" class="exclude-toggle btn btn-sm <?php echo $excludeMy ? 'btn-primary active' : 'btn-outline-secondary'; ?>" data-exclude="1">
                    <?php echo $excludeMy ? '<i class="bi bi-eye-fill"></i> Showing: Others' : '<i class="bi bi-eye-slash"></i> Hide My Reports'; ?>
                </button>
            </div>
            <div class="scrollable-box" id="searchItems">
                <div id="itemsContainer" class="<?php echo $viewMode === 'grid' ? 'grid-view' : 'vertical-list'; ?>">
                <?php 
                foreach ($items as $item): 
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
                    $itemDate = date('M j, Y', strtotime($item['item_date']));
                    $createdAt = date('M j, Y g:i A', strtotime($item['created_at']));
                    $typeBadgeClass = $item['type'] === 'lost' ? 'item-type-lost' : ($item['type'] === 'found' ? 'item-type-found' : 'bg-secondary');
                ?>
                <div class="report-card d-flex align-items-start position-relative mb-3 item-card">
                    <div class="item-type-badge <?php echo $typeBadgeClass; ?>">
                        <?php echo ucfirst($item['type']); ?>
                    </div>
                    <div class="item-image">
                        <i class="bi bi-collection"></i>
                    </div>
                    <div class="item-details">
                        <h5 class="item-title mb-1"><?php echo htmlspecialchars($item['item_name']); ?></h5>
                        <div class="item-meta mb-2">
                            <div><i class="bi bi-calendar3 me-1"></i> <?php echo $createdAt; ?></div>
                            <div><i class="bi bi-tag-fill me-1"></i> Category: <?php echo htmlspecialchars($item['category']); ?></div>
                            <div><i class="bi bi-geo-alt-fill me-1"></i> Location: <?php echo htmlspecialchars($item['location']); ?></div>
                            <div><i class="bi bi-calendar-event me-1"></i> <?php echo $item['type'] === 'lost' ? 'Lost' : 'Found'; ?> on: <?php echo $itemDate; ?></div>
                        </div>
                        <?php if (!empty($item['description'])): ?>
                            <div class="text-truncate" style="max-width: 350px;" title="<?php echo htmlspecialchars($item['description']); ?>">
                                <i class="bi bi-chat-square-text text-muted me-1"></i>
                                <?php 
                                $shortDesc = strlen($item['description']) > 80 ? substr($item['description'],0,77).'...' : $item['description'];
                                echo htmlspecialchars($shortDesc);
                                ?>
                            </div>
                        <?php endif; ?>
                        <div class="mt-3 d-flex justify-content-end align-items-center gap-2">
                            <a href="item_detail.php?type=<?php echo htmlspecialchars($item['type']); ?>&id=<?php echo htmlspecialchars($item['id']); ?>" 
                               class="btn btn-mark-text btn-sm">
                                <i class="bi bi-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
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
            // Single view toggle
            const viewToggleBtn = document.getElementById('viewToggleBtn');
            const viewToggleIcon = document.getElementById('viewToggleIcon');
            const itemsContainer = document.getElementById('itemsContainer');
            const viewModeInput = document.getElementById('viewModeInput');
            let isListView = (viewModeInput.value === 'list');
            function setViewMode(listView) {
                isListView = listView;
                if (isListView) {
                    itemsContainer.classList.remove('grid-view');
                    itemsContainer.classList.add('vertical-list');
                    viewToggleIcon.classList.remove('bi-grid-3x3-gap');
                    viewToggleIcon.classList.add('bi-list');
                    viewModeInput.value = 'list';
                } else {
                    itemsContainer.classList.remove('vertical-list');
                    itemsContainer.classList.add('grid-view');
                    viewToggleIcon.classList.remove('bi-list');
                    viewToggleIcon.classList.add('bi-grid-3x3-gap');
                    viewModeInput.value = 'grid';
                }
            }
            setViewMode(isListView);
            viewToggleBtn.addEventListener('click', function() {
                setViewMode(!isListView);
            });
            // Filter buttons
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
            // Exclude my reports toggle
            const excludeBtn = document.getElementById('excludeBtn');
            const excludeInput = document.getElementById('excludeMyInput');
            if (excludeBtn && excludeInput) {
                excludeBtn.addEventListener('click', function() {
                    const isActive = this.classList.toggle('active');
                    excludeInput.value = isActive ? '1' : '';
                    this.textContent = isActive ? 'Showing: Others' : 'Hide My Reports';
                    document.getElementById('searchForm').submit();
                });
            }
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

    <style>
        .scrollable-box {
            max-height: 500px;
            overflow-y: auto;
            margin-bottom: 1rem;
            padding-right: 0.5rem;
        }
        .scrollable-box::-webkit-scrollbar {
            width: 6px;
        }
        .scrollable-box::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        .scrollable-box::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        .scrollable-box::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        .report-card {
            background: white;
            border-radius: 8px;
            padding: 0.5rem 0.75rem; /* even smaller vertical padding */
            margin-bottom: 0.6rem; /* tighter gap */
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
            min-height: 56px;
            font-size: 1rem;
        }
        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .item-image {
            width: 38px; /* smaller image */
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            border-radius: 8px;
            margin-right: 0.6rem;
            font-size: 1.1rem;
            color: #6c757d;
            box-shadow: 0 2px 8px rgba(16,24,40,0.04);
        }
        .item-details {
            flex: 1;
        }
        .item-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.1rem;
            font-size: 1.08rem;
            line-height: 1.3;
        }
        .item-meta {
            font-size: 0.97rem;
            color: #495057;
            margin-bottom: 0.18rem;
            line-height: 1.4;
        }
        .item-type-badge {
            position: absolute;
            top: 6px;
            right: 6px;
            padding: .14rem .45rem;
            border-radius: 999px;
            font-weight:700;
            color:#fff;
            font-size: .72rem;
            box-shadow: 0 6px 18px rgba(0,0,0,0.08);
        }
        .item-type-lost { background: linear-gradient(135deg,#e53935,#ff6b6b); }
        .item-type-found { background: linear-gradient(135deg,#28a745,#20c997); }
        .btn-mark-text {
            background: linear-gradient(135deg,#28a745,#20c997);
            border: none;
            color: #fff;
            padding: .28rem 0.6rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            box-shadow: 0 6px 18px rgba(32,201,151,0.12);
            font-size: 0.85rem;
            font-weight: 500;
            white-space: nowrap;
        }
        .btn-mark-text:hover {
            background: linear-gradient(135deg,#228236,#1aa179);
            color: #fff;
            text-decoration: none;
        }
        .btn-mark-text i { font-size: 0.85rem; line-height: 1; }
        .text-truncate {
            max-width: 300px !important;
            font-size: 0.97rem;
            color: #212529;
            line-height: 1.4;
        }
        .view-toggle .view-btn {
            margin-left: 2px;
            margin-right: 2px;
        }
        #itemsContainer.vertical-list {
            display: block;
        }
        #itemsContainer.grid-view {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 0.8rem;
        }
        #itemsContainer.grid-view .item-card {
            flex-direction: column !important;
            min-height: 180px;
            align-items: flex-start !important;
        }
        #itemsContainer.grid-view .item-image {
            margin-right: 0;
            margin-bottom: 0.5rem;
        }
        #itemsContainer.grid-view .item-details {
            width: 100%;
        }
        .view-btn {
            min-width: 36px;
        }
    </style>
</body>
</html>
