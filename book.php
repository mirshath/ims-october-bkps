<?php
session_start();
ob_start();

// Database connection with path array
$paths = [
    __DIR__ . '/../database/connection.php',
    __DIR__ . '/database/connection.php',
    '../database/connection.php',
    'database/connection.php'
];

$conn = null;
foreach ($paths as $path) {
    if (file_exists($path)) {
        include($path);
        break;
    }
}

if (!isset($conn) || !$conn) {
    die("Database connection failed. Please check configuration.");
}

include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit();
}

$catid = 0;
$where = '';
if(isset($_GET['category'])){
    $catid = (int)$_GET['category'];
    $where = 'WHERE books.category_id = '.$catid;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book List - Library Management System</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
        body {
            background-color: #f8f9fc;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.35rem;
        }
        
        .btn-primary {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        
        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
        }
        
        .btn-edit {
            background-color: #042d5c;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 12px;
            margin-right: 5px;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-edit:hover {
            background-color: #0a3d7a;
            color: white;
        }
        
        .btn-edit-disabled {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 12px;
            margin-right: 5px;
            display: inline-block;
            cursor: not-allowed;
            opacity: 0.65;
        }
        
        .btn-inactivate {
            background-color: #ffc107;
            color: #000;
            border: none;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 12px;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-inactivate:hover {
            background-color: #e0a800;
            color: #000;
        }
        
        .btn-inactivate-disabled {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 12px;
            display: inline-block;
            cursor: not-allowed;
            opacity: 0.65;
        }
        
        .btn-activate {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 12px;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-activate:hover {
            background-color: #218838;
            color: white;
        }
        
        .status-available {
            background-color: #353434;
            color: greenyellow;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
            width: 100%;
            text-align: center;
        }
        
        .status-borrowed {
            background-color: #dc3545;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
            text-align: center;
            min-width: 100px;
        }
        
        .status-inactive {
            background-color: #6c757d;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
            text-align: center;
            min-width: 100px;
        }
        
        .table thead th {
            background-color: #f8f9fc;
            border-bottom: 2px solid #e3e6f0;
            color: #5a5c69;
            font-weight: 600;
            text-align: Center;
            font-size: 14px;
        }

        .table tbody tr {

            font-size: 13px;
        }
        
        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #d1d3e2;
            border-radius: 0.35rem;
            padding: 0.375rem 0.75rem;
            margin: 0 0.5rem;
            width: auto;
            display: inline-block;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            background-size: 1.2em;
            padding-right: 2rem !important;
        }
        
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #d1d3e2;
            border-radius: 0.35rem;
            padding: 0.375rem 0.75rem;
            margin-left: 0.5rem;
        }
        
        .modal-header {
            background-color: #dc3545;
            color: white;
            border-radius: 10px 10px 0 0;
        }
        
        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        .action-buttons {
            white-space: nowrap;
        }
        
        .inactive-row {
            background-color: #f8f9fa !important;
            opacity: 0.7;
        }
        
        .inactive-row td {
            color: #6c757d !important;
        }
        
        .reason-cell {
            max-width: 200px;
            font-size: 12px;
            color: #6c757d;
        }
        
        .reason-text {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .modal-header-inactive {
            background-color: #ffc107;
            color: #000;
        }
        
        .modal-header-active {
            background-color: #28a745;
            color: white;
        }
        
        .modal-header-edit {
            background-color: #042d5c;
            color: white;
        }
        
        .modal-header-add {
            background-color: #dc3545;
            color: white;
        }
        
        .badge-borrowed {
            background-color: #dc3545;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            margin-left: 5px;
        }
    </style>
</head>

<body>
    <div id="wrapper">
        <?php include("nav.php"); ?>
        
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include("includes/topnav.php"); ?>

                <div class="container-fluid py-4">
                    
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h4 class="h4 mb-0 text-gray-800">
                            Book Inventory
                        </h4>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <!-- <strong>Book Catalogue</strong> -->
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addnew">
                                <i class="fas fa-plus me-1"></i> Add New Book
                            </button>
                        </div>
                        <div class="card-body">
                            
                            <?php if(isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Error!</strong> <?php echo htmlspecialchars($_SESSION['error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php unset($_SESSION['error']); endif; ?>
                            
                            <?php if(isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Success!</strong> <?php echo htmlspecialchars($_SESSION['success']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php unset($_SESSION['success']); endif; ?>
                            
                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text text-white" style="background-color: #dc3545">
                                            <i class="fas fa-filter"></i>
                                        </span>
                                        <label class="input-group-text" style="background-color: #042d5c; color: white;" >Category Filter:</label>
                                        <select class="form-select" id="select_category">
                                            <option value="0">All Categories</option>
                                            <?php
                                                $sql = "SELECT * FROM category ORDER BY name";
                                                $query = $conn->query($sql);
                                                while($catrow = $query->fetch_assoc()){
                                                    $selected = ($catid == $catrow['id']) ? " selected" : "";
                                                    echo "<option value='".$catrow['id']."'".$selected.">".htmlspecialchars($catrow['name'])."</option>";
                                                }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table id="booksTable" class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>Accession No.</th>
                                            <th>Calling No.</th>
                                            <th>Category</th>
                                            <th>Author</th>
                                            <th>Title</th>
                                            <th>Publish Year</th>
                                            <th>Status</th>
                                            <th>Inactivation Reason</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                            $sql = "SELECT books.*, category.name AS category_name, books.id AS bookid 
                                                    FROM books 
                                                    LEFT JOIN category ON category.id = books.category_id 
                                                    $where 
                                                    ORDER BY books.id DESC";
                                            $query = $conn->query($sql);
                                            if($query && $query->num_rows > 0){
                                                while($row = $query->fetch_assoc()){
                                                    $row_class = ($row['is_inactive'] == 1) ? 'inactive-row' : '';
                                                    $is_borrowed = ($row['status'] == 1);
                                                    
                                                    // Status display
                                                    if($row['is_inactive'] == 1) {
                                                        $status_class = 'status-inactive';
                                                        $status_text = 'Inactive';
                                                    } elseif($is_borrowed) {
                                                        $status_class = 'status-borrowed';
                                                        $status_text = 'Borrowed';
                                                    } else {
                                                        $status_class = 'status-available';
                                                        $status_text = 'Available';
                                                    }
                                                    
                                                    // Inactivation reason display
                                                    $reason_display = '';
                                                    if($row['is_inactive'] == 1 && !empty($row['inactivation_reason'])) {
                                                        $reason_display = '<span class="reason-text" title="' . htmlspecialchars($row['inactivation_reason']) . '">' 
                                                                        . htmlspecialchars($row['inactivation_reason']) . '</span>';
                                                        if(strlen($row['inactivation_reason']) > 50) {
                                                            $reason_display = '<span class="reason-text" title="' . htmlspecialchars($row['inactivation_reason']) . '">' 
                                                                            . htmlspecialchars(substr($row['inactivation_reason'], 0, 50)) . '...</span>';
                                                        }
                                                    } else {
                                                        $reason_display = '<span class="text-muted" style="font-size: 11px;">—</span>';
                                                    }
                                                    
                                                    echo "<tr class='$row_class'>";
                                                    echo "<td>" . htmlspecialchars($row['accession_number']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['calling_number']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['category_name']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['author']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['publish_date']) . "</td>";
                                                    echo "<td><span class='$status_class'>$status_text</span></td>";
                                                    echo "<td class='reason-cell'>$reason_display</td>";
                                                    echo "<td>
                                                            <div class='action-buttons'>";
                                                            
                                                    // Button logic based on status
                                                    if($row['is_inactive'] == 0) {
                                                        // Book is Active (Available or Borrowed)
                                                        if($is_borrowed) {
                                                            // Status: Borrowed - Both buttons disabled
                                                            echo "<button class='btn-edit-disabled' disabled title='Cannot edit a borrowed book'>
                                                                            <i class='fas fa-edit'></i> Edit
                                                                        </button>";
                                                            echo "<button class='btn-inactivate-disabled' disabled title='Cannot inactivate a borrowed book'>
                                                                            <i class='fas fa-ban'></i> Inactivate
                                                                        </button>";
                                                        } else {
                                                            // Status: Available - Both buttons active
                                                            echo "<button class='btn-edit edit' data-id='".$row['bookid']."'>
                                                                            <i class='fas fa-edit'></i> Edit
                                                                        </button>";
                                                            echo "<button class='btn-inactivate inactivate' data-id='".$row['bookid']."' data-title='".htmlspecialchars($row['title'])."'>
                                                                            <i class='fas fa-ban'></i> Inactivate
                                                                        </button>";
                                                        }
                                                    } else {
                                                        // Status: Inactive - Edit disabled, Activate active
                                                        echo "<button class='btn-edit-disabled' disabled title='Cannot edit an inactive book'>
                                                                        <i class='fas fa-edit'></i> Edit
                                                                    </button>";
                                                        echo "<button class='btn-activate activate' data-id='".$row['bookid']."' data-title='".htmlspecialchars($row['title'])."'>
                                                                        <i class='fas fa-check'></i> Activate
                                                                    </button>";
                                                    }
                                                    
                                                    echo "      </div>
                                                           </td>";
                                                    echo "</tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='9' class='text-center'>No books found</td></tr>";
                                            }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Book Modal -->
    <div class="modal fade" id="addnew" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header modal-header-add">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>Add New Book
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="librarypro/book_add.php">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Accession Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="accession_number" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Calling Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="calling_number" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select" name="category" required>
                                    <option value="">- Select Category -</option>
                                    <?php
                                        $sql = "SELECT * FROM category ORDER BY name";
                                        $query = $conn->query($sql);
                                        while($crow = $query->fetch_assoc()){
                                            echo "<option value='".$crow['id']."'>".htmlspecialchars($crow['name'])."</option>";
                                        }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Author</label>
                            <input type="text" class="form-control" name="author">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Book Title <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="title" rows="2" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Publish Year</label>
                            <input type="number" class="form-control" name="publish_date" placeholder="YYYY">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" name="add">
                            <i class="fas fa-save"></i> Save Book
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Book Modal -->
    <div class="modal fade" id="edit" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header modal-header-edit">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Edit Book
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="librarypro/book_edit.php" id="editForm">
                    <input type="hidden" id="book_id" name="id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Accession Number</label>
                                <input type="text" class="form-control" id="edit_accession_number" name="accession_number">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Calling Number</label>
                                <input type="text" class="form-control" id="edit_calling_number" name="calling_number">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" id="edit_category" name="category">
                                <option value="">Select Category</option>
                                <?php
                                    $sql = "SELECT * FROM category ORDER BY name";
                                    $query = $conn->query($sql);
                                    while($crow = $query->fetch_assoc()){
                                        echo "<option value='".$crow['id']."'>".htmlspecialchars($crow['name'])."</option>";
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Author</label>
                            <input type="text" class="form-control" id="edit_author" name="author">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Book Title</label>
                            <textarea class="form-control" id="edit_title" name="title" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Publish Year</label>
                            <input type="text" class="form-control" id="edit_publish_date" name="publish_date">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-success" name="edit">
                            <i class="fas fa-check-circle"></i> Update Book
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Inactivate Book Modal -->
    <div class="modal fade" id="inactivateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header modal-header-inactive">
                    <h5 class="modal-title">
                        <i class="fas fa-ban me-2"></i>Inactivate Book
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="librarypro/book_inactivate.php">
                    <input type="hidden" id="inactivate_book_id" name="id">
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Warning:</strong> Inactivating a book will make it unavailable for borrowing.
                        </div>
                        <p class="lead">Are you sure you want to inactivate this book?</p>
                        <h4 id="inactivate_book_title" class="text-warning"></h4>
                        <div class="mb-3 mt-3">
                            <label class="form-label">Reason for Inactivation <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="inactivation_reason" rows="3" required placeholder="Please provide a reason for inactivating this book..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-warning" name="inactivate">
                            <i class="fas fa-ban"></i> Inactivate Book
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Activate Book Confirmation Modal -->
    <div class="modal fade" id="activateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header modal-header-active">
                    <h5 class="modal-title">
                        <i class="fas fa-check-circle me-2"></i>Activate Book
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="librarypro/book_activate.php">
                    <input type="hidden" id="activate_book_id" name="id">
                    <div class="modal-body text-center">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <p class="lead">Are you sure you want to activate this book?</p>
                        <h4 id="activate_book_title" class="text-success"></h4>
                        <p class="text-muted">This will make the book available in the inventory again.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-success" name="activate">
                            <i class="fas fa-check"></i> Activate Book
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#booksTable').DataTable({
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                language: {
                    search: "<i class='fas fa-search'></i> Search:",
                    lengthMenu: "Show _MENU_ entries",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    emptyTable: "No data available in table",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                },
                order: [[0, 'desc']],
                columnDefs: [
                    { orderable: false, targets: [7, 8] } // Disable sorting on Reason and Actions columns
                ]
            });
            
            // Category filter
            $('#select_category').change(function(){
                var value = $(this).val();
                if(value == 0){
                    window.location = 'book.php';
                } else {
                    window.location = 'book.php?category=' + value;
                }
            });
            
            // Edit button click - Available for all books
            $(document).on('click', '.edit', function(e){
                e.preventDefault();
                var id = $(this).data('id');
                getBookData(id, 'edit');
            });
            
            // Inactivate button click
            $(document).on('click', '.inactivate', function(e){
                e.preventDefault();
                var id = $(this).data('id');
                var title = $(this).data('title');
                $('#inactivate_book_id').val(id);
                $('#inactivate_book_title').text(title);
                var inactivateModal = new bootstrap.Modal(document.getElementById('inactivateModal'));
                inactivateModal.show();
            });
            
            // Activate button click
            $(document).on('click', '.activate', function(e){
                e.preventDefault();
                var id = $(this).data('id');
                var title = $(this).data('title');
                $('#activate_book_id').val(id);
                $('#activate_book_title').text(title);
                var activateModal = new bootstrap.Modal(document.getElementById('activateModal'));
                activateModal.show();
            });
        });
        
        // Function to get book data via AJAX
        function getBookData(id, action){
            $.ajax({
                type: 'POST',
                url: 'book_row.php',
                data: {id: id},
                dataType: 'json',
                success: function(response){
                    if(response.error){
                        alert('Error: ' + response.error);
                        return;
                    }
                    
                    if(action === 'edit'){
                        // Populate edit form
                        $('#book_id').val(response.bookid);
                        $('#edit_accession_number').val(response.accession_number);
                        $('#edit_calling_number').val(response.calling_number);
                        $('#edit_category').val(response.category_id);
                        $('#edit_author').val(response.author);
                        $('#edit_title').val(response.title);
                        $('#edit_publish_date').val(response.publish_date);
                        // Show edit modal
                        var editModal = new bootstrap.Modal(document.getElementById('edit'));
                        editModal.show();
                    }
                },
                error: function(xhr, status, error){
                    console.error('AJAX Error:', error);
                    console.error('Response Text:', xhr.responseText);
                    alert('Error loading book data. Please try again.');
                }
            });
        }
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    </script>
</body>
</html>