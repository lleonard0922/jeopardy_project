<?php
session_start();
require_once __DIR__ . '/includes-functions.php';

$leaders = get_leaderboard();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Jeopardy Leaderboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-jeopardy">
<div class="container setup-card">
    <h1 class="logo">Leaderboard</h1>

    <table class="leaderboard-table">
        <tr>
            <th>Rank</th>
            <th>Name</th>
            <th>Score</th>
            <th>Date</th>
        </tr>
        <?php if (empty($leaders)): ?>
            <tr><td colspan="4">No scores yet.</td></tr>
        <?php else: ?>
            <?php foreach ($leaders as $i => $entry): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= h($entry['name']) ?></td>
                    <td>$<?= h($entry['score']) ?></td>
                    <td><?= h($entry['date']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </table>

    <div class="links">
        <a href="index.php">New Game</a>
        <a href="game.php">Back to Game</a>
    </div>
</div>
</body>
</html>