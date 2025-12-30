<?php
require 'config.php';

if (!is_logged_in()) header('Location: index.php');

// ADD TASK
if (isset($_POST['add_task']) && check_csrf($_POST['csrf'])) {
    $title = clean($_POST['title']);
    if (!empty($title)) {
        $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $title]);
    }
}

// DELETE TASK
if (isset($_POST['delete_task']) && check_csrf($_POST['csrf'])) {
    $task_id = (int)$_POST['task_id'];
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->execute([$task_id, $_SESSION['user_id']]);
}

// TOGGLE TASK
if (isset($_POST['toggle_task']) && check_csrf($_POST['csrf'])) {
    $task_id = (int)$_POST['task_id'];
    $stmt = $pdo->prepare("UPDATE tasks SET status = IF(status='completed', 'pending', 'completed') WHERE id = ? AND user_id = ?");
    $stmt->execute([$task_id, $_SESSION['user_id']]);
}

// EDIT TASK
if (isset($_POST['edit_task']) && check_csrf($_POST['csrf'])) {
    $task_id = (int)$_POST['task_id'];
    $title = clean($_POST['title']);
    if (!empty($title)) {
        $stmt = $pdo->prepare("UPDATE tasks SET title = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $task_id, $_SESSION['user_id']]);
    }
}

// GET TASKS
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$tasks = $stmt->fetchAll();

$total = count($tasks);
$completed = count(array_filter($tasks, fn($t) => $t['status'] == 'completed'));
$percent = $total > 0 ? round(($completed / $total) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Todo List</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="todo-app">
            <!-- Header -->
            <div class="header">
                <h1><i class="fas fa-tasks"></i> My Todo List</h1>
                <div class="user-info">
                    <span>Hello, <?= clean($_SESSION['username']) ?>!</span>
                    <a href="logout.php" class="logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats">
                <div class="stat">
                    <div class="stat-icon"><i class="fas fa-list"></i></div>
                    <div class="stat-number"><?= $total ?></div>
                    <div class="stat-label">Total</div>
                </div>
                <div class="stat">
                    <div class="stat-icon" style="background: #10b981;"><i class="fas fa-check"></i></div>
                    <div class="stat-number"><?= $completed ?></div>
                    <div class="stat-label">Done</div>
                </div>
                <div class="stat">
                    <div class="stat-icon" style="background: #f59e0b;"><i class="fas fa-clock"></i></div>
                    <div class="stat-number"><?= $total - $completed ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>

            <!-- Progress -->
            <div class="progress">
                <div class="progress-header">
                    <span>Progress</span>
                    <span><?= $percent ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $percent ?>%"></div>
                </div>
            </div>

            <!-- Add/Edit Task Form -->
            <form method="POST" class="add-form" id="taskForm">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="task_id" id="task_id" value="">
                <div class="input-group">
                    <input type="text" name="title" id="task_title" placeholder="What needs to be done?" required>
                    <div class="form-actions">
                        <button type="submit" name="add_task" id="add_button" class="btn-add">
                            <i class="fas fa-plus"></i> Add Task
                        </button>
                        <button type="submit" name="edit_task" id="edit_button" class="btn-edit" style="display: none;">
                            <i class="fas fa-save"></i> Update Task
                        </button>
                        <button type="button" id="cancel_button" class="btn-cancel" style="display: none;">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </div>
            </form>

            <!-- Tasks List -->
            <div class="tasks">
                <?php if (empty($tasks)): ?>
                    <div class="empty">
                        <i class="fas fa-clipboard-list"></i>
                        <h3>No tasks yet!</h3>
                        <p>Add your first task above</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($tasks as $task): ?>
                    <div class="task <?= $task['status'] ?>">
                        <!-- Toggle Complete -->
                        <form method="POST" class="task-form">
                            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                            <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                            <button type="submit" name="toggle_task" class="toggle" title="Toggle Complete">
                                <i class="fas fa-<?= $task['status'] == 'completed' ? 'check-circle' : 'circle' ?>"></i>
                            </button>
                        </form>
                        
                        <!-- Task Content -->
                        <div class="task-content">
                            <span class="task-title"><?= clean($task['title']) ?></span>
                            <div class="task-meta">
                                <span class="badge <?= $task['status'] ?>">
                                    <i class="fas fa-<?= $task['status'] == 'completed' ? 'check' : 'clock' ?>"></i>
                                    <?= ucfirst($task['status']) ?>
                                </span>
                                <span><i class="far fa-calendar"></i> <?= date('M d, Y', strtotime($task['created_at'])) ?></span>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div class="task-actions">
                            <button class="btn-edit-small edit-task" 
                                    data-id="<?= $task['id'] ?>" 
                                    data-title="<?= htmlspecialchars($task['title']) ?>"
                                    title="Edit Task">
                                <i class="fas fa-edit"></i>
                            </button>
                            
                            <form method="POST" class="task-form">
                                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                                <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                <button type="submit" name="delete_task" class="btn-delete" title="Delete Task" 
                                        onclick="return confirm('Are you sure you want to delete this task?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Security Footer -->
            <div class="security-footer">
                <i class="fas fa-shield-alt"></i>
                <span>Your tasks are securely stored. CSRF protection enabled.</span>
            </div>
        </div>
    </div>

    <script>
        // Edit Task functionality
        document.querySelectorAll('.edit-task').forEach(button => {
            button.addEventListener('click', function() {
                const taskId = this.getAttribute('data-id');
                const taskTitle = this.getAttribute('data-title');
                
                // Set form to edit mode
                document.getElementById('task_id').value = taskId;
                document.getElementById('task_title').value = taskTitle;
                document.getElementById('task_title').focus();
                
                // Show edit button, hide add button
                document.getElementById('add_button').style.display = 'none';
                document.getElementById('edit_button').style.display = 'flex';
                document.getElementById('cancel_button').style.display = 'flex';
                
                // Scroll to form
                document.getElementById('taskForm').scrollIntoView({ behavior: 'smooth' });
            });
        });
        
        // Cancel edit
        document.getElementById('cancel_button').addEventListener('click', function() {
            resetForm();
        });
        
        // Reset form after submission
        document.getElementById('taskForm').addEventListener('submit', function() {
            setTimeout(resetForm, 100);
        });
        
        function resetForm() {
            document.getElementById('task_id').value = '';
            document.getElementById('task_title').value = '';
            document.getElementById('add_button').style.display = 'flex';
            document.getElementById('edit_button').style.display = 'none';
            document.getElementById('cancel_button').style.display = 'none';
        }
        
        // Focus on input when page loads
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('task_title').focus();
        });
    </script>
</body>
</html>