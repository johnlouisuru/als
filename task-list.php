<?php
require_once "db-config/security.php";

// If logged in but profile incomplete, redirect to complete profile
if (!isLoggedIn()) {
    header('Location: login');
    exit;
}


$type = $_GET['type'] ?? '';
$id   = (int) ($_GET['id'] ?? 0);

if (!in_array($type, ['assignment', 'quiz']) || !$id) {
    die('Invalid request');
}

if ($type === 'assignment') {
    $stmt = $pdo->prepare("
        SELECT a.*, t.topic_name, CONCAT(u.lastname, ' ', u.firstname) AS teacher_name
        FROM assignments a
        LEFT JOIN topics t ON a.topic_id = t.id
        LEFT JOIN teachers u ON a.teacher_id = u.id
        WHERE a.id = ?
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT q.*, t.topic_name, CONCAT(u.lastname, ' ', u.firstname) AS teacher_name
        FROM quizzes q
        LEFT JOIN topics t ON q.topic_id = t.id
        LEFT JOIN teachers u ON q.teacher_id = u.id
        WHERE q.id = ?
    ");
}

$stmt->execute([$id]);
$task = $stmt->fetch(PDO::FETCH_ASSOC);

$task_points = $task['points'];

if (!$task) {
    die('Task not found');
}

$studentId = $_SESSION['user_id'];

if ($type === 'assignment') {
    $workStmt = $pdo->prepare("
        SELECT *
        FROM assignment_work_attachment
        WHERE assignment_id = ? AND student_id = ?
        LIMIT 1
    ");
} else {
    $workStmt = $pdo->prepare("
        SELECT *
        FROM quiz_work_attachment
        WHERE quiz_id = ? AND student_id = ?
        LIMIT 1
    ");
}

$workStmt->execute([$id, $studentId]);
$submission = $workStmt->fetch(PDO::FETCH_ASSOC);


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_ENV['PAGE_HEADER'] ?></title>
    <link rel="apple-touch-icon" sizes="76x76" href="<?=$_ENV['PAGE_ICON']?>">
    <link rel="icon" type="image/png" href="<?=$_ENV['PAGE_ICON']?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- This is the Google Classroom Style  -->
     <link rel="stylesheet" href="css/classroom.css">
    <!-- This is the Google Classroom Style  -->
</head>
<body>
    <!-- Top Bar -->
    <?php 
        require_once "bars/topbar.php";
    ?>


    <!-- Content Area -->
    <div class="content-area" id="contentArea">
        <a href="classwork" class="btn btn-secondary w-100" type="button">Back to ClassWork</a>
        <!-- Newsfeed Section -->
        <div class="container mt-4">

            <h3><?= htmlspecialchars($task['title']) ?></h3>

            <p class="text-muted">
                <?= htmlspecialchars($task['topic_name'] ?? '') ?>
                • <?= htmlspecialchars($task['teacher_name']) ?> <br>
                <?php $dueDate = $task['due_date']; $today = strtotime(date('Y-m-d')); 
                // Determine status 
                switch (true) { case ($dueDate === null): 
                // No due date 
                        break; 
                case (strtotime($dueDate) < $today): ?> 
                    <span class="text text-danger"> Ended last: [<?= date('M d Y', strtotime($dueDate)) ?>] </span> 
                    <?php break; 
                case (strtotime($dueDate) >= $today): ?> 
                    <span class="text text-info"> Due On: [<?= date('M d Y', strtotime($dueDate)) ?>] </span> 
                    <?php break; } 
                ?>
                
            </p>

            <p><?= nl2br(htmlspecialchars($task['description'])) ?></p>
            <p class="text-muted"><?= $task['points'] !== null ? 'Points: '.htmlspecialchars($task['points']) : '' ?></p>

            <?php if ($task['file_path']): ?>
                <button class="btn btn-outline-primary btn-sm view-attachment-btn"
                        data-file="<?= htmlspecialchars('../teacher/'.$task['file_path']) ?>"
                        data-title="Task Attachment">
                    <i class="bi bi-paperclip"></i> View Attachment
                </button>
            <?php endif; ?>

            
         <?php if ($submission): ?>   
            
            <?php else: ?>
                <hr>
            <button class="btn btn-primary w-100"
                    data-bs-toggle="modal"
                    data-bs-target="#addWorkModal">
                <i class="bi bi-plus-lg"></i> Add Work
            </button>
                <hr />
        <?php endif; ?>
            <!-- <button class="btn btn-success w-100"
                    data-bs-toggle="modal"
                    data-bs-target="#addWorkModal">
                <i class="bi bi-check-lg"></i> Mark as Done
            </button> -->
        </div>

        <hr>

        <h5>Your Work</h5>

        <?php if ($submission): ?>

            <?php if ($submission['file_path']): ?>
                <button class="btn btn-outline-secondary btn-sm view-attachment-btn"
                        data-file="<?= htmlspecialchars($submission['file_path']) ?>"
                        data-title="Your Submitted Work">
                    <i class="bi bi-eye"></i> View Attachment
                </button>
            <?php endif; ?>

            <?php if (!empty($submission['comment'])): ?>
                <p class="mt-2">
                    <strong>Your Note:</strong><br>
                    <?= nl2br(htmlspecialchars($submission['comment'])) ?>
                </p>
            <?php endif; ?>

            <span class="text text-secondary text-muted">Teacher's Response will be displayed here:</span>    
            <?php if ($submission['gained_score'] != null || $submission['teacher_comment'] != null): ?>
                    <p class="mt-2">
                        <strong>Score:</strong>
                        <?= nl2br(htmlspecialchars($submission['gained_score'])) ?>
                        <br />
                        <strong>Teacher Comment: </strong><?= htmlspecialchars($submission['teacher_comment']) ?><br>
                    </p>
                    <?php else : ?>
                        <hr />

                    <button class="btn btn-warning mt-2"
                            data-bs-toggle="modal"
                            data-bs-target="#addWorkModal">
                        <i class="bi bi-pencil"></i> Update Work
                    </button>
                        <hr />
                    <button class="btn btn-danger mt-2 ms-2"
                        id="deleteWorkBtn"
                        data-type="<?= $type ?>"
                        data-task-id="<?= $id ?>">
                        <i class="bi bi-trash"></i> Delete Work
                    </button>
            <?php endif; ?>

            

            


        <?php else: ?>

            <p class="text-muted">No submission yet.</p>

        <?php endif; ?>



    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            /* ==========================
            SUBMIT WORK
            ========================== */
            document.getElementById('submitWorkBtn').addEventListener('click', function () {
                const isUpdate = <?= $submission ? 'true' : 'false' ?>;
                const fileInput = document.getElementById('workFile');
                const comment   = document.getElementById('workComment').value;

                const errorEl   = document.getElementById('workError');
                const successEl = document.getElementById('workSuccess');

                errorEl.classList.add('d-none');
                successEl.classList.add('d-none');

                if (!fileInput.files.length) {
                    errorEl.textContent = 'Please attach a file.';
                    errorEl.classList.remove('d-none');
                    return;
                }

                const formData = new FormData();
                formData.append('type', '<?= $type ?>');
                formData.append('is_update', isUpdate ? 1 : 0);
                formData.append('task_id', '<?= $id ?>');
                formData.append('file', fileInput.files[0]);
                formData.append('comment', comment);
                this.disabled = true;
                this.textContent = 'Uploading...';
                fetch('process_attach_file_to_work.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(r => {
                    if (!r.success) {
                        errorEl.textContent = r.message;
                        errorEl.classList.remove('d-none');
                        return;
                    }

                    successEl.textContent = r.message;
                    successEl.classList.remove('d-none');

                    setTimeout(() => location.reload(), 1200);
                })
                .catch(() => {
                    errorEl.textContent = 'Upload failed. Please try again.';
                    errorEl.classList.remove('d-none');
                    this.disabled = false;
                });
            });


            /* ==========================
            ATTACHMENT VIEWER
            ========================== */
            document.addEventListener('click', function (e) {

                const btn = e.target.closest('.view-attachment-btn');
                if (!btn) return;

                const file  = btn.dataset.file;
                const title = btn.dataset.title || 'Attachment';

                const modalBody  = document.getElementById('attachmentModalBody');
                const modalTitle = document.getElementById('attachmentModalTitle');

                modalTitle.textContent = title;
                modalBody.innerHTML = '';

                const ext = file.split('.').pop().toLowerCase();

                if (['jpg','jpeg','png','gif','webp'].includes(ext)) {
                    modalBody.innerHTML = `
                        <img src="${file}" class="img-fluid rounded">
                    `;
                }
                else if (ext === 'pdf') {
                    modalBody.innerHTML = `
                        <iframe src="${file}" style="width:100%; height:80vh; border:none;"></iframe>
                    `;
                }
                else {
                    modalBody.innerHTML = `<p class="text-danger">Unsupported file</p>`;
                }

                new bootstrap.Modal(document.getElementById('attachmentModal')).show();
            });

            const deleteBtn = document.getElementById('deleteWorkBtn');

        if (deleteBtn) {
            deleteBtn.addEventListener('click', () => {
                new bootstrap.Modal(
                    document.getElementById('deleteWorkModal')
                ).show();
            });
        }

        document.getElementById('confirmDeleteWorkBtn')
                    .addEventListener('click', function () {
                const errorEl   = document.getElementById('deleteError');
                const successEl = document.getElementById('deleteSuccess');

                this.disabled = true;
                this.textContent = 'Deleting...';

                fetch('process_delete_work.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        type: deleteBtn.dataset.type,
                        task_id: deleteBtn.dataset.taskId
                    })
                })
                .then(res => res.json())
                .then(r => {
                    if (!r.success) {
                        errorEl.textContent = r.message;
                        errorEl.classList.remove('d-none');

                        this.disabled = false;
                        this.textContent = 'Yes, Delete';
                        //alert(r.message);
                        //return;
                    } else {
                        
                        successEl.textContent = r.message;
                        successEl.classList.remove('d-none');
                        //return;
                    }

                    setTimeout(() => location.reload(), 1200);
                });
            });

        });
        
        
        </script>




    <div class="modal fade" id="addWorkModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Submit Work</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div class="alert alert-danger d-none" id="workError"></div>
                <div class="alert alert-success d-none" id="workSuccess"></div>

                <div class="mb-3">
                <label class="form-label">Attach File (PDF or Image)</label>
                <input type="file" class="form-control" id="workFile">
                </div>

                <div class="mb-3">
                <label class="form-label">Comment (Optional)</label>
                <textarea class="form-control" id="workComment"><?= $submission['comment'] ?? '' ?></textarea>
                </div>

            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="submitWorkBtn">Submit</button>
            </div>

            </div>
        </div>
    </div>

    <!-- Attachment Viewer Modal -->
    <div class="modal fade" id="attachmentModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="attachmentModalTitle">Attachment</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body text-center" id="attachmentModalBody">
                <!-- Dynamic content -->
            </div>

            </div>
        </div>
    </div>
        <!-- Delete Confirmation for Student's Submitted Work  -->
    <div class="modal fade" id="deleteWorkModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title text-danger">Delete Submission</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-danger d-none" id="deleteError"></div>
                    <div class="alert alert-success d-none" id="deleteSuccess"></div>
                    <p class="mb-0">
                        Are you sure you want to delete your submitted work?
                        <br><strong>This action cannot be undone.</strong>
                    </p>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-danger" id="confirmDeleteWorkBtn">Yes, Delete</button>
                </div>

            </div>
        </div>
    </div>



</body>
</html>