<?php
session_start();
require_once __DIR__ . '/includes-functions.php';
require_once __DIR__ . '/includes-questions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $players = array_filter($_POST['players'] ?? [], fn($p) => trim($p) !== '');

    if (count($players) >= 1) {
        init_game_state($players);
        header('Location: game.php');
        exit;
    }

    $error = 'Please enter at least 1 player name.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Jeopardy Setup</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-jeopardy">
    <div class="container setup-card">
        <h1 class="logo">JEOPARDY!</h1>
        <p class="subtitle">PHP Single Player & Multiplayer Edition</p>

        <?php if (!empty($error)): ?>
            <div class="error"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="post" class="setup-form">
            <label>Player 1</label>
            <input type="text" name="players[]" required>

            <label>Player 2 (optional)</label>
            <input type="text" name="players[]">

            <label>Player 3 (optional)</label>
            <input type="text" name="players[]">

            <label>Player 4 (optional)</label>
            <input type="text" name="players[]">

            <button type="submit" class="btn primary">Start Game</button>
        </form>

        <div class="links">
            <a href="leaderboard.php">View Leaderboard</a>
        </div>
    </div>
</body>
</html>