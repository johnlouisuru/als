<?php
require_once "db-config/security.php";

// Handle success/error messages
$success_message = '';
$error_message = '';

// $is_active_button = 'active';

if (isset($_GET['success'])) {
    $success_message = 'Post created successfully!';
}

if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}

// If logged in but profile incomplete, redirect to complete profile
if (isLoggedIn() && !isProfileComplete()) {
    header('Location: complete-profile');
    exit;
}

// If logged in but profile incomplete, redirect to complete profile
if (!isLoggedIn()) {
    header('Location: logout/');
    exit;
}

    $query_topics = "SELECT * FROM topics WHERE is_active = 1";
    $topics = secure_query_no_params($pdo, $query_topics);
    
    require_once "reusable-query/get-assignments-topic-teacher.php";
    require_once "reusable-query/get-quizzes-topic-teacher.php";

    $classwork = [];

    /* Normalize assignments */
    foreach ($assignments as $a) {
        $a['type'] = 'assignment';
        $classwork[] = $a;
    }

    /* Normalize quizzes */
    foreach ($quizzes as $q) {
        $q['type'] = 'quiz';
        $classwork[] = $q;
    }

    /* Sort by newest */
    usort($classwork, function ($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });


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
        <!-- Facebook Group–Style Cover Header -->
        <div class="group-cover mb-4"> 
            <!-- style="background-image: url('assets/img/cover.jpg');" -->
             <!-- <img src="<?= $_SESSION["profile_picture"] ?>"/> -->

            <div class="group-cover-content  w-100">
                <h1><?= $_ENV['PAGE_HEADER'] ?></h1>
        <hr />
        <h5>TOPICS:</h5>
             <?php if ($topics && $topics->rowCount() > 0): ?>
                    <?php foreach ($topics as $topic): ?>

                        <!-- <h5>Topics:</h5> -->
                <div class="d-flex align-items-center">
                    <!-- Topic name -->
                    <p class="mb-0 fw-semibold flex-grow-1 text-truncate"
                    id="topicText-<?= $topic['id'] ?>">
                        <?= htmlspecialchars($topic['topic_name']) ?>
                    </p>

                    

                </div>

                <!-- END of Topics -->

                    <?php endforeach; ?>
                <?php else: ?>
                    
                <?php endif; ?>

            </div>

        </div>

        <!-- Newsfeed Section -->

        <!-- ClassWork Section -->
        <div id="classwork">
            <div class="section-title">To Do</div>

            <?php if (!empty($classwork)): ?>
                <?php foreach ($classwork as $item): ?>

                    <?php
                        $title       = htmlspecialchars($item['title']);
                        $topic       = $item['topic_name'] ?? '';
                        $teacher     = htmlspecialchars($item['teacher_name']);
                        $points      = $item['points'] ?? 0;
                        $file        = $item['file_path'] ?? '';
                        $dueDate     = $item['due_date'];
                        $postedDays  = floor((time() - strtotime($item['created_at'])) / 86400);

                        $icon = $item['type'] === 'quiz'
                            ? 'bi-patch-question'
                            : 'bi-file-earmark-text';

                        $dueBadgeClass = '';
                        if ($dueDate) {
                            $daysLeft = (strtotime($dueDate) - time()) / 86400;
                            $dueBadgeClass = $daysLeft <= 1 ? 'urgent' : 'upcoming';
                        }
                    ?>
            <a href="task-list?type=<?= $item['type'] ?>&id=<?= $item['id'] ?>"
                class="text-decoration-none text-dark">
                    <div class="assignment-card todo">
                        <div class="assignment-header">

                            <div class="assignment-icon">
                                <i class="bi <?= $icon ?>"></i>
                            </div>

                            <div class="assignment-info">
                                <div class="assignment-title">
                                    <?= $title ?>
                                </div>

                                <div class="assignment-meta">
                                    <?= $topic ?>
                                    <?= $topic ? '•' : '' ?>
                                    <?= ucfirst($item['type']) ?>
                                    • Posted <?= $postedDays ?> day<?= $postedDays != 1 ? 's' : '' ?> ago
                                </div>

                                <?php if ($dueDate): ?>
                                    <div class="due-badge <?= $dueBadgeClass ?>">
                                        Due: <?= date('M d Y', strtotime($dueDate)) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                </a>

                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted">No assignments or quizzes available.</p>
            <?php endif; ?>
        </div>


  



    <!-- Bottom Navigation -->
    <div class="bottom-nav">
        <a href="classroom" class="nav-item" >
            <i class="bi bi-chat-left-text"></i>
            <span>Newsfeed</span>
        </a>
        <a href="#" class="nav-item active" >
            <i class="bi bi-journal-text"></i>
            <span>ClassWork</span>
        </a>
        <a href="class-student" class="nav-item" >
            <i class="bi bi-people-fill"></i>
            <span>Students</span>
        </a>
    </div>
    <?php 
        // require_once "bars/bottom-bar.php";
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
   

</body>
</html>