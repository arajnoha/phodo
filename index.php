<?php
session_start();
$password = 'password';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['access']) && $_POST['access'] === $password) {
    $_SESSION['access'] = true;
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

$logged_in = isset($_SESSION['access']) && $_SESSION['access'] === true;
if ($logged_in) {
    date_default_timezone_set('Europe/Prague');
$filename = 'tasks.json';
$selected_date = $_GET['day'] ?? date('Y-m-d');
$today_formatted = date('d.m.Y', strtotime($selected_date));
$tasks = file_exists($filename) ? json_decode(file_get_contents($filename), true) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_done'])) {
        foreach ($tasks as &$task) {
            if ($task['id'] === $_POST['toggle_done']) {
                $task['done'] = !$task['done'];
                break;
            }
        }
    }
    if (isset($_POST['add_task']) && trim($_POST['title']) !== '') {
        $tasks[] = [
            'id' => uniqid(),
            'title' => htmlspecialchars($_POST['title']),
            'date' => date('d.m.Y', strtotime($_POST['date'] ?? $selected_date)),
            'done' => false,
        ];
    }
    if (isset($_POST['delete_task'])) {
        $tasks = array_filter($tasks, fn($t) => $t['id'] !== $_POST['delete_task']);
    }
    file_put_contents($filename, json_encode($tasks, JSON_PRETTY_PRINT));
    header("Location: index.php?day=" . $selected_date);
    exit;
}
$tasks_today = array_filter($tasks, fn($t) => $t['date'] === $today_formatted);
$prev_day = date('Y-m-d', strtotime("$selected_date -1 day"));
$next_day = date('Y-m-d', strtotime("$selected_date +1 day"));
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Phodo - <?= $today_formatted ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="neon.css">
</head>
<body>
    <?php if (!$logged_in): ?>
        <ul>
        <form method="post" class="login">
            <input type="password" name="access" placeholder="Insert password" required autofocus>
            <button type="submit">Log in</button>
        </form>
        </ul>
    <?php else: ?>
    <nav>
        <a href="?day=<?= $prev_day ?>">&lt; <?= date('d.m.', strtotime($prev_day)) ?></a>
        <a href="./">Today</a>
        <a href="?day=<?= $next_day ?>"><?= date('d.m.', strtotime($next_day)) ?> &gt;</a>
    </nav>
    <ul>
        <h3><?= $today_formatted ?></h3>
        <?php if (empty($tasks_today)): ?>
            <p>No tasks for this day.</p>
        <?php else: ?>
            <?php foreach ($tasks_today as $task): ?>
                <li class="<?= $task['done'] ? 'done' : '' ?>">
                    <form method="post" style="display:inline">
                        <input type="hidden" name="toggle_done" value="<?= $task['id'] ?>">
                        <button type="submit" class="done" title="done"></button>
                    </form>
                    <?= $task['done'] ? '<s>' . $task['title'] . '</s>' : $task['title'] ?>
                    <form method="post" style="display:inline" onsubmit="return confirm('Do you really want to delete this task?')">
                        <input type="hidden" name="delete_task" value="<?= $task['id'] ?>">
                        <button type="submit" class="delete" title="Delete task">🗑️</button>
                    </form>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>
        <form method="post">
            <input type="text" name="title" placeholder="Title of the new task" required autofocus>
            <button type="submit" name="add_task">Add</button>
    </form>
    </ul>
    <?php endif; ?>
</body>
</html>