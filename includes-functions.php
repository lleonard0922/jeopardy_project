<?php

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function normalize_answer($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s\$_]/', '', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    return $text;
}

function init_game_state($players) {
    $_SESSION['players'] = [];

    foreach ($players as $name) {
        $_SESSION['players'][] = [
            'name' => trim($name),
            'score' => 0,
            'lifelines' => [
                '5050' => 1,
                'hint' => 1,
                'pass' => 1,
            ],
            'penalties' => 0,
        ];
    }

    $_SESSION['turn_index'] = 0;
    $_SESSION['answered_cells'] = [];
    $_SESSION['current_question'] = null;
    $_SESSION['board'] = [];
    $_SESSION['game_over'] = false;

    $_SESSION['ai_diff'] = 2;
    $_SESSION['seen_ids'] = [];
    $_SESSION['recent'] = [];

    $_SESSION['daily_double_id'] = null;
    $_SESSION['daily_double_hit'] = false;

    $_SESSION['flash'] = null;
    $_SESSION['flash_type'] = null;
    $_SESSION['fact_flash'] = null;

    build_board();
    assign_daily_double();
}

function build_board() {
    global $QUESTION_BANK;

    $categories = ['Science', 'History', 'Literature', 'Technology', 'Sports'];
    $rows = [100, 200, 300, 400];

    $board = [];

    foreach ($categories as $category) {
        foreach ($rows as $value) {
            $match = null;

            foreach ($QUESTION_BANK as $q) {
                if ($q['category'] === $category && $q['value'] === $value) {
                    $match = $q;
                    break;
                }
            }

            if ($match) {
                $board[$category][$value] = $match['id'];
            }
        }
    }

    $_SESSION['board'] = $board;
}

function assign_daily_double() {
    $allQuestionIds = [];

    foreach ($_SESSION['board'] as $category => $rows) {
        foreach ($rows as $value => $qid) {
            $allQuestionIds[] = $qid;
        }
    }

    if (!empty($allQuestionIds)) {
        $_SESSION['daily_double_id'] = $allQuestionIds[array_rand($allQuestionIds)];
    }
}

function is_daily_double($questionId) {
    return isset($_SESSION['daily_double_id']) && $_SESSION['daily_double_id'] == $questionId;
}

function get_question_by_id($id) {
    global $QUESTION_BANK;

    foreach ($QUESTION_BANK as $q) {
        if ($q['id'] == $id) {
            return $q;
        }
    }

    return null;
}

function get_current_player() {
    return $_SESSION['players'][$_SESSION['turn_index']];
}

function update_current_player($player) {
    $_SESSION['players'][$_SESSION['turn_index']] = $player;
}

function next_turn() {
    $count = count($_SESSION['players']);

    if ($count > 0) {
        $_SESSION['turn_index'] = ($_SESSION['turn_index'] + 1) % $count;
    }
}

function is_board_complete() {
    return count($_SESSION['answered_cells']) >= 20;
}

function update_ai_difficulty($wasCorrect) {
    $_SESSION['recent'][] = $wasCorrect ? 1 : 0;
    $_SESSION['recent'] = array_slice($_SESSION['recent'], -3);

    $correct = array_sum($_SESSION['recent']);
    $wrong = count($_SESSION['recent']) - $correct;

    if ($correct >= 2) {
        $_SESSION['ai_diff'] = min(3, $_SESSION['ai_diff'] + 1);
    } elseif ($wrong >= 2) {
        $_SESSION['ai_diff'] = max(1, $_SESSION['ai_diff'] - 1);
    }
}

function ai_badge_text() {
    return match ($_SESSION['ai_diff']) {
        1 => 'Easy',
        2 => 'Medium',
        3 => 'Hard',
        default => 'Medium',
    };
}

function get_winners() {
    if (empty($_SESSION['players'])) {
        return [];
    }

    $players = $_SESSION['players'];
    $topScore = max(array_column($players, 'score'));

    return array_values(array_filter($players, function ($player) use ($topScore) {
        return $player['score'] === $topScore;
    }));
}

function save_leaderboard() {
    $file = __DIR__ . '/leaderboard.json';
    $existing = [];

    if (file_exists($file)) {
        $json = file_get_contents($file);
        $existing = json_decode($json, true) ?: [];
    }

    foreach ($_SESSION['players'] as $player) {
        $existing[] = [
            'name' => $player['name'],
            'score' => $player['score'],
            'date' => date('Y-m-d H:i:s'),
        ];
    }

    usort($existing, function ($a, $b) {
        return $b['score'] <=> $a['score'];
    });

    $existing = array_slice($existing, 0, 15);

    file_put_contents($file, json_encode($existing, JSON_PRETTY_PRINT));
}

function get_leaderboard() {
    $file = __DIR__ . '/leaderboard.json';

    if (!file_exists($file)) {
        return [];
    }

    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function get_random_unused_question_by_difficulty() {
    global $QUESTION_BANK;

    $filtered = array_filter($QUESTION_BANK, function ($q) {
        return $q['difficulty'] == $_SESSION['ai_diff']
            && !in_array($q['id'], $_SESSION['seen_ids'], true);
    });

    if (empty($filtered)) {
        $filtered = array_filter($QUESTION_BANK, function ($q) {
            return !in_array($q['id'], $_SESSION['seen_ids'], true);
        });
    }

    if (empty($filtered)) {
        return null;
    }

    $filtered = array_values($filtered);
    return $filtered[rand(0, count($filtered) - 1)];
}