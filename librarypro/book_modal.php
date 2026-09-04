<?php
// Safety Check: If the connection doesn't exist yet, look for it and include it.
if (!isset($conn)) {
    $paths = [
        __DIR__ . '/../database/connection.php',
        __DIR__ . '/database/connection.php',
        '../database/connection.php',
        'database/connection.php'
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            include($path);
            break;
        }
    }
}
?>

<div class="modal fade" id="addnew" tabindex="-1" role="dialog" aria-labelledby="borrowModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="borrowModalLabel">
                    <i class="fa fa-book mr-2"></i><b>Borrow Books</b>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form class="form-horizontal" method="POST" action="librarypro/borrow_add.php">
                <div class="modal-body p-4" style="font-size: 0.875rem;">
                    
                    <div class="form-group row align-items-center mb-3">
                        <label for="student" class="col-sm-4 font-weight-bold text-gray-700 m-0">Student ID</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control form-control-sm border-left-primary" id="student" name="student" placeholder="e.g. STU-1002" required>
                        </div>
                    </div>

                    <div class="form-group row align-items-center mb-3">
                        <label for="accession_number" class="col-sm-4 font-weight-bold text-gray-700 m-0">Accession Number</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control form-control-sm border-left-info" id="accession_number" name="accession_number[]" placeholder="Enter Accession Number" required>
                        </div>
                    </div>

                    <div id="append-div"></div>

                    <div class="form-group row mt-3">
                        <div class="col-sm-8 offset-sm-4">
                            <button type="button" class="btn btn-outline-info btn-xs rounded-pill" id="append">
                                <i class="fa fa-plus fa-xs mr-1"></i> Add Another Book Field
                            </button>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light border-top-0">
                    <button type="button" class="btn btn-secondary btn-sm rounded-sm px-3" data-dismiss="modal">
                        <i class="fa fa-close mr-1"></i> Close
                    </button>
                    <button type="submit" class="btn btn-primary btn-sm rounded-sm px-3 shadow" name="add">
                        <i class="fa fa-save mr-1"></i> Save Transaction
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="edit" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="editModalLabel">
                    <i class="fa fa-edit mr-2"></i><b>Edit Book Information</b>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form class="form-horizontal" method="POST" action="book_edit.php">
                <input type="hidden" class="bookid" name="id">
                <div class="modal-body p-4" style="font-size: 0.875rem;">

                    <div class="form-group row align-items-center mb-3">
                        <label for="edit_accession_number" class="col-sm-4 font-weight-bold text-gray-700 m-0">Accession Number</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control form-control-sm" id="edit_accession_number" name="accession_number" required>
                        </div>
                    </div>

                    <div class="form-group row align-items-center mb-3">
                        <label for="edit_calling_number" class="col-sm-4 font-weight-bold text-gray-700 m-0">Calling Number</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control form-control-sm" id="edit_calling_number" name="calling_number" required>
                        </div>
                    </div>

                    <div class="form-group row align-items-center mb-3">
                        <label for="edit_category" class="col-sm-4 font-weight-bold text-gray-700 m-0">Category</label>
                        <div class="col-sm-8">
                            <select class="form-control form-control-sm" name="category" id="edit_category" required>
                                <option value="" selected id="catselect">- Select -</option>
                                <?php
                                if (isset($conn) && $conn) {
                                    $sql = "SELECT * FROM category";
                                    $query = $conn->query($sql);
                                    while($crow = $query->fetch_assoc()){
                                        echo "<option value='".$crow['id']."'>".$crow['name']."</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group row align-items-center mb-3">
                        <label for="edit_author" class="col-sm-4 font-weight-bold text-gray-700 m-0">Author</label>
                        <div class="col-sm-8">
                            <input type="text" class="form-control form-control-sm" id="edit_author" name="author">
                        </div>
                    </div>

                    <div class="form-group row mb-3">
                        <label for="edit_title" class="col-sm-4 font-weight-bold text-gray-700 m-0">Book Title</label>
                        <div class="col-sm-8">
                            <textarea class="form-control form-control-sm" name="title" id="edit_title" rows="2" required></textarea>
                        </div>
                    </div>

                    <div class="form-group row align-items-center mb-3">
                        <label for="edit_publish_date" class="col-sm-4 font-weight-bold text-gray-700 m-0">Publish Year</label>
                        <div class="col-sm-8">
                            <input type="number" class="form-control form-control-sm" id="edit_publish_date" name="publish_date">
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light border-top-0">
                    <button type="button" class="btn btn-secondary btn-sm rounded-sm px-3" data-dismiss="modal">
                        <i class="fa fa-close mr-1"></i> Close
                    </button>
                    <button type="submit" class="btn btn-success btn-sm rounded-sm px-3 shadow" name="edit">
                        <i class="fa fa-check-square-o mr-1"></i> Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="delete" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="fa fa-trash mr-2"></i><b>Deleting Book Profile...</b>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form class="form-horizontal" method="POST" action="book_delete.php">
                <input type="hidden" class="bookid" name="id">
                <div class="modal-body p-4 text-center">
                    <div class="text-danger mb-3">
                        <i class="fa fa-exclamation-triangle fa-3x"></i>
                    </div>
                    <p class="text-gray-600 font-weight-bold m-0" style="font-size: 0.9rem;">Are you sure you want to permanently delete this book entry?</p>
                    <h4 id="del_book" class="text-gray-900 font-weight-bold mt-2 mb-0"></h4>
                </div>
                <div class="modal-footer bg-light border-top-0 justify-content-center">
                    <button type="button" class="btn btn-secondary btn-sm rounded-sm px-4 mx-1" data-dismiss="modal">
                        <i class="fa fa-close mr-1"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-danger btn-sm rounded-sm px-4 mx-1 shadow" name="delete">
                        <i class="fa fa-trash mr-1"></i> Delete Permanent
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>