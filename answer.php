<?php
session_start();
require_once __DIR__ . '/includes-functions.php';
require_once __DIR__ . '/includes-questions.php';

if (empty($_SESSION['players']) || empty($_SESSION['current_question'])) {
    header('Location: game.php');
    exit;
}

$action = $_POST['action'] ?? 'answer';
$player = get_current_player();
$q = $_SESSION['current_question'];

if ($action === '5050') {
    if ($player['lifelines']['5050'] > 0) {
        $player['lifelines']['5050']--;
        $player['penalties'] += 10;
        update_current_player($player);

        $_SESSION['flash'] = '50:50 used. Penalty: -10 at scoring judgment.';
        $_SESSION['flash_type'] = 'neutral';
    } else {
        $_SESSION['flash'] = '50:50 already used.';
        $_SESSION['flash_type'] = 'neutral';
    }

    header('Location: game.php');
    exit;
}

if ($action === 'hint') {
    if ($player['lifelines']['hint'] > 0) {
        $player['lifelines']['hint']--;
        $player['penalties'] += 5;
        update_current_player($player);

        $_SESSION['flash'] = 'Hint: ' . $q['hint'] . ' | Penalty: -5';
        $_SESSION['flash_type'] = 'neutral';
    } else {
        $_SESSION['flash'] = 'Hint already used.';
        $_SESSION['flash_type'] = 'neutral';
    }

    header('Location: game.php');
    exit;
}

if ($action === 'pass') {
    if ($player['lifelines']['pass'] > 0) {
        $player['lifelines']['pass']--;
        $player['score'] -= 15;
        $player['penalties'] = 0;

        update_current_player($player);

        $_SESSION['answered_cells'][] = $q['id'];
        $_SESSION['current_question'] = null;
        $_SESSION['flash'] = 'Question passed. Penalty: -15.';
        $_SESSION['flash_type'] = 'wrong';
        $_SESSION['fact_flash'] = $q['fact'] ?? '';

        if (is_daily_double($q['id'])) {
            $_SESSION['daily_double_hit'] = true;
        }

        update_ai_difficulty(false);
        next_turn();
    } else {
        $_SESSION['flash'] = 'Pass already used.';
        $_SESSION['flash_type'] = 'neutral';
    }

    header('Location: game.php');
    exit;
}

$userAnswer = normalize_answer($_POST['answer'] ?? '');
$correctAnswer = normalize_answer($q['answer']);
$isCorrect = $userAnswer === $correctAnswer;

$baseValue = $q['value'];

if (is_daily_double($q['id'])) {
    $baseValue *= 2;
    $_SESSION['daily_double_hit'] = true;
}

$scoreChange = $baseValue - $player['penalties'];
if ($scoreChange < 0) {
    $scoreChange = 0;
}

if ($isCorrect) {
    $player['score'] += $scoreChange;
    $_SESSION['flash'] = 'Correct! +' . $scoreChange;
    $_SESSION['flash_type'] = 'correct';
} else {
    $player['score'] -= $baseValue;
    $_SESSION['flash'] = 'Incorrect. Correct answer: ' . $q['answer'] . ' | -' . $baseValue;
    $_SESSION['flash_type'] = 'wrong';
}

$player['penalties'] = 0;
update_current_player($player);

$_SESSION['answered_cells'][] = $q['id'];
$_SESSION['current_question'] = null;
$_SESSION['fact_flash'] = $q['fact'] ?? '';

update_ai_difficulty($isCorrect);
next_turn();

if (is_board_complete()) {
    $_SESSION['game_over'] = true;
    save_leaderboard();
}

header('Location: game.php');
exit;