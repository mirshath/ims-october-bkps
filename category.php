<?php
session_start();
ob_start();
include("database/connection.php");
include("includes/header.php");

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Categories - Library Management System</title>
    
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
        
        /* Action buttons styling */
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
        
        .btn-delete {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 12px;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-delete:hover {
            background-color: #c82333;
            color: white;
        }
        
        .table thead th {
            background-color: #f8f9fc;
            border-bottom: 2px solid #e3e6f0;
            color: #5a5c69;
            font-weight: 600;
        }
        
        /* DataTables customization */
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
        
        /* Action buttons container */
        .action-buttons {
            white-space: nowrap;
        }
        
        /* Category badge */
        .category-badge {
            background-color: #c9deff;
            color: #333232;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            display: inline-block;
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
                            </i>Book Category Management
                        </h4>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <div>
                                <!-- <i class="fas fa-table me-2"></i>
                                <strong>Category List</strong> -->
                            </div>
                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addnew">
                                <i class="fas fa-plus me-1"></i> Add New Category
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
                            
                            <div class="table-responsive col-8">
                                <table id="categoriesTable" class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-folder"></i> Category Name</th>
                                            <th><i class="fas fa-cogs"></i> Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                            $sql = "SELECT * FROM category ORDER BY name";
                                            $query = $conn->query($sql);
                                            if($query && $query->num_rows > 0){
                                                while($row = $query->fetch_assoc()){
                                                    echo "<tr>";
                                                    echo "<td><span class='category-badge'><i class='fas fa-tag me-1' style='font-size:auto;color: #dc3545;''></i>" . htmlspecialchars($row['name']) . "</span></td>";
                                                    echo "<td style='place-items: center;'>
                                                            <div class='action-buttons'>
                                                                <button class='btn-edit edit' data-id='".$row['id']."'>
                                                                    <i class='fas fa-edit'></i> Edit
                                                                </button>
                                                                <!--<button class='btn-delete delete' data-id='".$row['id']."'>
                                                                    <i class='fas fa-trash'></i> Delete
                                                                </button>-->
                                                            </div>
                                                           </td>";
                                                    echo "</tr>";
                                                }
                                            } else {
                                                echo "<tr><td colspan='2' class='text-center'>No categories found</td></tr>";
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

    <div class="modal fade" id="addnew" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>Add New Category
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="librarypro/category_add.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Category Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" placeholder="Enter category name" required>
                            <!-- <small class="text-muted">Example: Fiction, Non-Fiction, Science, History, etc.</small> -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" name="add">
                            <i class="fas fa-save"></i> Save Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="edit" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Edit Category
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="librarypro/category_edit.php">
                    <input type="hidden" id="cat_id" name="id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Category Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-success" name="edit">
                            <i class="fas fa-check-circle"></i> Update Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="delete" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-trash me-2"></i>Delete Category
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="librarypro/category_delete.php">
                    <input type="hidden" id="delete_cat_id" name="id">
                    <div class="modal-body text-center">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <p class="lead">Are you sure you want to delete this category?</p>
                        <h4 id="del_cat" class="text-danger"></h4>
                        <p class="text-muted">This action cannot be undone!</p>
                        <p class="text-warning small">
                            <i class="fas fa-info-circle"></i> Note: Books under this category will not be deleted, but will show no category.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-danger" name="delete">
                            <i class="fas fa-trash"></i> Confirm Delete
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
            $('#categoriesTable').DataTable({
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
                order: [[0, 'asc']]
            });
            
            // Edit button click
            $(document).on('click', '.edit', function(e){
                e.preventDefault();
                var id = $(this).data('id');
                getCategoryData(id, 'edit');
            });
            
            // Delete button click
            $(document).on('click', '.delete', function(e){
                e.preventDefault();
                var id = $(this).data('id');
                getCategoryData(id, 'delete');
            });
        });
        
        // Function to get category data via AJAX
        function getCategoryData(id, action){
            $.ajax({
                type: 'POST',
                url: 'category_row.php', // Points to root category_row.php directly
                data: {id: id},
                dataType: 'json',
                success: function(response){
                    if(response.error){
                        alert('Error: ' + response.error);
                        return;
                    }
                    
                    if(action === 'edit'){
                        $('#cat_id').val(response.id);
                        $('#edit_name').val(response.name);
                        var editModal = new bootstrap.Modal(document.getElementById('edit'));
                        editModal.show();
                    } else if(action === 'delete'){
                        $('#delete_cat_id').val(response.id);
                        $('#del_cat').text(response.name);
                        var deleteModal = new bootstrap.Modal(document.getElementById('delete'));
                        deleteModal.show();
                    }
                },
                error: function(xhr, status, error){
                    console.error('AJAX Error:', error);
                    alert('Error loading category data. Please try again.');
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