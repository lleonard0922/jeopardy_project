<?php
session_start();
require_once __DIR__ . '/includes-functions.php';
require_once __DIR__ . '/includes-questions.php';

if (empty($_SESSION['players'])) {
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['board'])) {
    build_board();
    assign_daily_double();
}

if (isset($_GET['pick']) && !$_SESSION['current_question'] && !$_SESSION['game_over']) {
    $qid = (int) $_GET['pick'];
    $q = get_question_by_id($qid);

    if ($q && !in_array($qid, $_SESSION['answered_cells'], true)) {
        $_SESSION['current_question'] = $q;
        $_SESSION['seen_ids'][] = $qid;
    }
}

if (is_board_complete() && !$_SESSION['game_over']) {
    $_SESSION['game_over'] = true;
    save_leaderboard();
}

$currentPlayer = get_current_player();
$currentQuestion = $_SESSION['current_question'];
$flashType = $_SESSION['flash_type'] ?? '';
$factFlash = $_SESSION['fact_flash'] ?? '';
$playerCount = count($_SESSION['players']);
$gameMode = $playerCount === 1 ? 'Single Player' : 'Multiplayer';
$winners = $_SESSION['game_over'] ? get_winners() : [];

$categories = ['Science', 'History', 'Literature', 'Technology', 'Sports'];
$rows = [100, 200, 300, 400];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Jeopardy Game</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-jeopardy">
<div class="container">

    <div class="topbar">
        <div>
            <h1 class="logo small">JEOPARDY!</h1>
            <div class="ai-badge"><?= h($gameMode) ?> | AI Difficulty: <?= h(ai_badge_text()) ?></div>
        </div>
        <div class="top-links">
            <button id="musicToggle" class="btn ghost" type="button">Music: On</button>
            <a class="btn ghost" href="leaderboard.php">Leaderboard</a>
            <a class="btn danger" href="reset.php">Reset</a>
        </div>
    </div>

    <div class="players-panel">
        <?php foreach ($_SESSION['players'] as $index => $player): ?>
            <div class="player-card <?= $index === $_SESSION['turn_index'] ? 'active' : '' ?>">
                <div class="player-name"><?= h($player['name']) ?></div>
                <div class="player-score">$<?= h($player['score']) ?></div>
                <div class="lifelines">
                    50:50 <?= $player['lifelines']['5050'] ?> |
                    Hint <?= $player['lifelines']['hint'] ?> |
                    Pass <?= $player['lifelines']['pass'] ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($_SESSION['flash']) && !$currentQuestion): ?>
        <div class="flash <?= h($flashType) ?> board-flash"><?= h($_SESSION['flash']) ?></div>
        <?php unset($_SESSION['flash'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <?php if (!empty($factFlash) && !$currentQuestion): ?>
        <div class="ai-fact-box">
            <div class="ai-fact-header">Fun Fact</div>
            <div class="ai-fact-body"><?= h($factFlash) ?></div>
        </div>
        <?php unset($_SESSION['fact_flash']); ?>
    <?php endif; ?>

    <?php if ($_SESSION['game_over']): ?>
        <div class="question-card reveal winner-screen">
            <?php if (count($winners) === 1): ?>
                <h2> Winner: <?= h($winners[0]['name']) ?></h2>
                <p class="winner-score">Final Score: $<?= h($winners[0]['score']) ?></p>
            <?php else: ?>
                <h2>It’s a Tie!</h2>
                <p class="winner-score">
                    <?php foreach ($winners as $winner): ?>
                        <?= h($winner['name']) ?> ($<?= h($winner['score']) ?>)
                    <?php endforeach; ?>
                </p>
            <?php endif; ?>

            <div class="final-standings">
                <h3>Final Standings</h3>
                <ul>
                    <?php
                    $sortedPlayers = $_SESSION['players'];
                    usort($sortedPlayers, fn($a, $b) => $b['score'] <=> $a['score']);
                    ?>
                    <?php foreach ($sortedPlayers as $player): ?>
                        <li><?= h($player['name']) ?> — $<?= h($player['score']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="links">
                <a class="btn primary" href="leaderboard.php">See Leaderboard</a>
                <a class="btn ghost" href="index.php">Play Again</a>
            </div>
        </div>

    <?php elseif ($currentQuestion): ?>
        <div class="question-card reveal clue-open">
            <h2><?= h($currentPlayer['name']) ?>’s Turn</h2>

            <?php if (is_daily_double($currentQuestion['id']) && empty($_SESSION['daily_double_hit'])): ?>
                <div class="daily-double-banner">DAILY DOUBLE!</div>
            <?php endif; ?>

            <div class="meta">
                <span><?= h($currentQuestion['category']) ?></span>
                <span>$<?= h($currentQuestion['value']) ?></span>
            </div>

            <p class="question-text"><?= h($currentQuestion['question']) ?></p>

            <?php if (!empty($_SESSION['flash']) && $currentQuestion): ?>
                <div class="flash <?= h($flashType) ?>"><?= h($_SESSION['flash']) ?></div>
                <?php unset($_SESSION['flash'], $_SESSION['flash_type']); ?>
            <?php endif; ?>

            <form method="post" action="answer.php" class="answer-form">
                <input type="text" name="answer" placeholder="Type your answer" required>

                <div class="lifeline-actions">
                    <button type="submit" name="action" value="answer" class="btn primary">Submit Answer</button>
                    <button type="submit" name="action" value="5050" class="btn warning">Use 50:50</button>
                    <button type="submit" name="action" value="hint" class="btn ghost">Use Hint</button>
                    <button type="submit" name="action" value="pass" class="btn danger">Pass</button>
                </div>
            </form>
        </div>

    <?php else: ?>
        <div class="board">
            <?php foreach ($categories as $category): ?>
                <div class="category"><?= h($category) ?></div>
            <?php endforeach; ?>

            <?php foreach ($rows as $row): ?>
                <?php foreach ($categories as $category): ?>
                    <?php
                    $qid = $_SESSION['board'][$category][$row] ?? null;
                    $answered = $qid && in_array($qid, $_SESSION['answered_cells'], true);
                    ?>
                    <div class="cell <?= $answered ? 'answered' : '' ?>">
                        <?php if ($qid && !$answered): ?>
                            <a href="game.php?pick=<?= h($qid) ?>">$<?= h($row) ?></a>
                        <?php else: ?>
                            <span>—</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<audio id="bgMusic" preload="auto">
    <source src="JTS.mp3" type="audio/mpeg">
</audio>
<audio id="correctSound" src="correct.mp3" preload="auto"></audio>
<audio id="wrongSound" src="wrong.mp3" preload="auto"></audio>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const flashType = "<?= h($flashType) ?>";
    const bgMusic = document.getElementById('bgMusic');
    const musicToggle = document.getElementById('musicToggle');
    const correctSound = document.getElementById('correctSound');
    const wrongSound = document.getElementById('wrongSound');

    let musicEnabled = localStorage.getItem('jeopardyMusicEnabled');
    musicEnabled = musicEnabled === null ? true : musicEnabled === 'true';

    let musicStarted = false;

    function updateMusicButton() {
        if (musicToggle) {
            musicToggle.textContent = musicEnabled ? 'Music: On' : 'Music: Off';
        }
    }

    function startMusic() {
        if (!bgMusic || !musicEnabled) return;

        bgMusic.volume = 0.35;
        bgMusic.loop = true;

        bgMusic.play().then(() => {
            musicStarted = true;
        }).catch((err) => {
            console.log('Background music blocked or failed:', err);
        });
    }

    function stopMusic() {
        if (!bgMusic) return;
        bgMusic.pause();
        bgMusic.currentTime = 0;
        musicStarted = false;
    }

    function tryStartFromInteraction() {
        if (musicEnabled && !musicStarted) {
            startMusic();
        }
    }

    updateMusicButton();

    if (musicEnabled) {
        startMusic();
    }

    document.addEventListener('click', tryStartFromInteraction);
    document.addEventListener('keydown', tryStartFromInteraction);

    if (musicToggle) {
        musicToggle.addEventListener('click', function () {
            musicEnabled = !musicEnabled;
            localStorage.setItem('jeopardyMusicEnabled', musicEnabled ? 'true' : 'false');
            updateMusicButton();

            if (musicEnabled) {
                startMusic();
            } else {
                stopMusic();
            }
        });
    }

    if (flashType === 'correct' && correctSound) {
        correctSound.currentTime = 0;
        correctSound.play().catch(() => {});
    }

    if (flashType === 'wrong' && wrongSound) {
        wrongSound.currentTime = 0;
        wrongSound.play().catch(() => {});
    }
});
</script>
</body>
</html>