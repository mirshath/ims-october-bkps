<?php
session_start();
ob_start(); // Start output buffering

include("database/connection.php");
include("includes/header.php");

$session_role = $_SESSION['role'];
$disabled_m_d = ($session_role == 'manager' || $session_role == 'data_enter') ? 'disabled' : '';

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
}


// ---------------------------- allowed Redirections ---------------------------------------------------------------- 
// -------- Permission CHECKING TO REDIRECT TO HOME PAGE -------- 
require_once 'PermissionChecking.php';
// --------------------------------------------------------------------- 
// -------------------------------------------------------------------------------------------- 


// Function to get ALL tables from database
function getAllTables($conn)
{
    $tables = array();
    $result = mysqli_query($conn, "SHOW TABLES");

    if ($result) {
        while ($row = mysqli_fetch_array($result)) {
            $tableName = $row[0];
            $tables[] = $tableName;
        }
    }
    return $tables;
}

// Function to get count for a specific table
function getTableCount($conn, $tableName)
{
    $query = "SELECT COUNT(*) as count FROM `$tableName`";
    $result = mysqli_query($conn, $query);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        return $row['count'];
    }
    return 0;
}

// Function to format table name for display
function formatTableName($tableName)
{
    // Convert snake_case to Title Case with spaces
    $formatted = str_replace('_', ' ', $tableName);
    $formatted = ucwords($formatted);
    return $formatted;
}

// Get all tables and their counts
$allTables = getAllTables($conn);
$tableCounts = array();

foreach ($allTables as $table) {
    $count = getTableCount($conn, $table);
    $displayName = formatTableName($table);
    $tableCounts[$displayName] = $count;
}

// Sort tables alphabetically
ksort($tableCounts);

// Handle Excel Export - MUST be before any HTML output
if (isset($_POST['export_excel'])) {
    // Clear any previous output
    ob_clean();

    // Set headers for Excel file download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="database_tables_count_' . date('Y-m-d') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Output Excel content
    echo "Database Tables Count Report\n";
    echo "Generated on: " . date('Y-m-d H:i:s') . "\n\n";

    echo "Table Name\tOriginal Table Name\tRecord Count\tStatus\n";

    $totalRecords = 0;
    foreach ($allTables as $table) {
        $count = getTableCount($conn, $table);
        $totalRecords += $count;
        $status = $count == 0 ? 'Empty' : 'Has Data';

        echo formatTableName($table) . "\t";
        echo $table . "\t";
        echo $count . "\t";
        echo $status . "\n";
    }

    echo "\n\nSummary:\n";
    echo "Total Tables: " . count($allTables) . "\n";
    echo "Total Records: " . $totalRecords . "\n";
    echo "Average per Table: " . number_format($totalRecords / count($allTables), 1) . "\n";

    exit;
}

// Continue with normal page output
ob_end_flush(); // End output buffering and send output
?>

<!-- Page Wrapper -->
<div id="wrapper">
    <?php include("nav.php"); ?>
    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">
        <!-- Main Content -->
        <div id="content">
            <!-- Topbar -->
            <?php include("includes/topnav.php"); ?>
            <!-- End of Topbar -->
            <!-- Begin Page Content -->
            <div class="container">

                <!-- Page Heading -->
                <div class="d-sm-flex align-items-center justify-content-between mb-4">
                    <h4 class="h4 mb-0 text-gray-800">Database Tables Count</h4>
                    <div>
                        <form method="POST" style="display: inline;">
                            <button type="submit" name="export_excel" class="btn btn-success">
                                <i class="fas fa-file-excel"></i> Export to Excel
                            </button>
                        </form>
                        <span class="badge bg-primary ms-2"><?php echo count($allTables); ?> Tables</span>
                    </div>
                </div>

                <!-- Export Section -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card border-left-success">
                            <div class="card-body">
                                <h5 class="card-title text-success">
                                    <i class="fas fa-file-excel"></i> Export Data
                                </h5>
                                <p class="card-text">
                                    Download complete database table counts in Excel format for reporting and analysis.
                                </p>
                                <form method="POST" class="d-inline">
                                    <button type="submit" name="export_excel" class="btn btn-success">
                                        <i class="fas fa-download"></i> Download Excel Report
                                    </button>
                                </form>
                                <small class="text-muted ms-2">Includes all table names, counts, and summary statistics</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add Form -->
                <div class="row mb-5">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex align-items-center justify-content-between" style="height: 60px;">
                                <div class="d-flex align-items-center">
                                    <span class="bg-dark text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;">
                                        <i class="fas fa-database"></i>
                                    </span> &nbsp;&nbsp;&nbsp;&nbsp;
                                    <h6 class="mb-0 me-2">All Tables Counts (<?php echo count($allTables); ?> Tables)</h6>
                                </div>
                                <form method="POST" class="mb-0">
                                    <button type="submit" name="export_excel" class="btn btn-sm btn-success">
                                        <i class="fas fa-file-excel"></i> Export Excel
                                    </button>
                                </form>
                            </div>

                            <div class="card-body">
                                <!-- Search and Filter -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <input type="text" id="tableSearch" class="form-control" placeholder="Search tables...">
                                    </div>
                                    <div class="col-md-3">
                                        <select id="countFilter" class="form-control">
                                            <option value="all">All Tables</option>
                                            <option value="empty">Empty Tables (0 records)</option>
                                            <option value="has_data">Tables with Data</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <button class="btn btn-outline-primary w-100" onclick="refreshCounts()">
                                            <i class="fas fa-sync-alt"></i> Refresh
                                        </button>
                                    </div>
                                </div>

                                <div class="row" id="tablesContainer">
                                    <?php
                                    $counter = 0;
                                    $totalRecords = 0;
                                    $emptyTables = 0;
                                    foreach ($tableCounts as $tableName => $count):
                                        $counter++;
                                        $totalRecords += $count;
                                        if ($count == 0) $emptyTables++;
                                        // Color coding
                                        $bgColor = $count == 0 ? 'bg-warning' : 'bg-success';
                                        $textColor = $count == 0 ? 'text-warning' : 'text-success';
                                        $borderColor = $count == 0 ? 'border-left-warning' : 'border-left-primary';
                                    ?>
                                        <div class="col-xl-3 col-md-4 col-sm-6 mb-4 table-card"
                                            data-name="<?php echo strtolower($tableName); ?>"
                                            data-count="<?php echo $count; ?>">
                                            <div class="card <?php echo $borderColor; ?> shadow h-100 py-2">
                                                <div class="card-body">
                                                    <div class="row no-gutters align-items-center">
                                                        <div class="col mr-2">
                                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                                <?php echo $tableName; ?>
                                                            </div>
                                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                                <?php echo number_format($count); ?> records
                                                            </div>
                                                            <small class="text-muted">
                                                                <?php
                                                                $originalName = array_search($tableName, array_flip($tableCounts));
                                                                echo $originalName ?: strtolower(str_replace(' ', '_', $tableName));
                                                                ?>
                                                            </small>
                                                        </div>
                                                        <div class="col-auto">
                                                            <i class="fas fa-table fa-2x <?php echo $textColor; ?>"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    <?php
                                        // Add clearfix for responsive layout
                                        if ($counter % 4 == 0) {
                                            echo '<div class="w-100"></div>';
                                        }
                                    endforeach;
                                    ?>
                                </div>

                                <!-- Summary Statistics -->
                                <div class="row mt-4">
                                    <div class="col-md-3">
                                        <div class="card bg-gradient-success text-white shadow">
                                            <div class="card-body text-center">
                                                <h4><?php echo count($allTables); ?></h4>
                                                <p>Total Tables</p>
                                                <i class="fas fa-table fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-gradient-success text-white shadow">
                                            <div class="card-body text-center">
                                                <h4><?php echo number_format($totalRecords); ?></h4>
                                                <p>Total Records</p>
                                                <i class="fas fa-database fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-gradient-info text-white shadow">
                                            <div class="card-body text-center">
                                                <h4><?php echo number_format($totalRecords / count($allTables), 1); ?></h4>
                                                <p>Average per Table</p>
                                                <i class="fas fa-chart-bar fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="card bg-gradient-warning text-white shadow">
                                            <div class="card-body text-center">
                                                <h4><?php echo $emptyTables; ?></h4>
                                                <p>Empty Tables</p>
                                                <i class="fas fa-exclamation-triangle fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>


                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- JavaScript for Search and Filter -->
    <script>
        function refreshCounts() {
            location.reload();
        }

        // Search functionality
        document.getElementById('tableSearch').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const cards = document.querySelectorAll('.table-card');

            cards.forEach(card => {
                const tableName = card.getAttribute('data-name');
                if (tableName.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Filter functionality
        document.getElementById('countFilter').addEventListener('change', function() {
            const filter = this.value;
            const cards = document.querySelectorAll('.table-card');

            cards.forEach(card => {
                const count = parseInt(card.getAttribute('data-count'));

                switch (filter) {
                    case 'empty':
                        card.style.display = count === 0 ? 'block' : 'none';
                        break;
                    case 'has_data':
                        card.style.display = count > 0 ? 'block' : 'none';
                        break;
                    default:
                        card.style.display = 'block';
                }
            });
        });
    </script>

    </body>

    </html>