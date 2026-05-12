<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$pollId   = isset($input['poll_id'])   ? (int)$input['poll_id']   : 0;
$optionId = isset($input['option_id']) ? (int)$input['option_id'] : 0;

if (!$pollId || !$optionId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'داتای نادروست']);
    exit;
}

$pdo    = getDB();
$userIP = getUserIP();

// پشتڕاستکردنەوەی بوونی ڕاپرسی
$stmt = $pdo->prepare("SELECT id FROM polls WHERE id = ? AND is_active = 1");
$stmt->execute([$pollId]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'ڕاپرسییەکە نییە یان داخراوە']);
    exit;
}

// پشتڕاستکردنەوەی بوونی هەڵبژاردن
$stmt = $pdo->prepare("SELECT id FROM options WHERE id = ? AND poll_id = ?");
$stmt->execute([$optionId, $pollId]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'هەڵبژاردنەکە نییە']);
    exit;
}

// چاودێری دەنگی دووبارە
$stmt = $pdo->prepare("SELECT id FROM votes WHERE poll_id = ? AND ip_address = ?");
$stmt->execute([$pollId, $userIP]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'پێشتر دەنگت داوە', 'already_voted' => true]);
    exit;
}

// تۆماری دەنگ
try {
    $stmt = $pdo->prepare("
        INSERT INTO votes (poll_id, option_id, ip_address, user_agent, voter_name, comment) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $userAgent  = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
    $voterName  = isset($_SESSION['voter_name']) ? $_SESSION['voter_name'] : null;
    $comment    = isset($input['comment']) ? trim(mb_substr($input['comment'], 0, 300)) : null;
    $comment    = ($comment === '') ? null : htmlspecialchars($comment, ENT_QUOTES, 'UTF-8');
    $stmt->execute([$pollId, $optionId, $userIP, $userAgent, $voterName, $comment]);
} catch (PDOException $e) {
    // دووبارە بوونی unique key
    if ($e->getCode() == 23000) {
        echo json_encode(['success' => false, 'message' => 'پێشتر دەنگت داوە', 'already_voted' => true]);
        exit;
    }
    echo json_encode(['success' => false, 'message' => 'کێشەی سیستەم']);
    exit;
}

// ئامادەکردنی نێتیجەی نوێ
$stmt = $pdo->prepare("
    SELECT o.id, o.option_text, o.option_icon, COUNT(v.id) as vote_count
    FROM options o
    LEFT JOIN votes v ON o.id = v.option_id
    WHERE o.poll_id = ?
    GROUP BY o.id
    ORDER BY o.sort_order
");
$stmt->execute([$pollId]);
$options   = $stmt->fetchAll();
$totalVotes = 0;
foreach ($options as $opt) { $totalVotes += $opt['vote_count']; }

$results = [];
foreach ($options as $opt) {
    $percentage = $totalVotes > 0 ? round(($opt['vote_count'] / $totalVotes) * 100) : 0;
    $results[] = [
        'id'          => $opt['id'],
        'vote_count'  => (int)$opt['vote_count'],
        'percentage'  => $percentage,
    ];
}

echo json_encode([
    'success'     => true,
    'message'     => 'دەنگەکەت تۆمار کرا! سوپاس 🎉',
    'total_votes' => $totalVotes,
    'results'     => $results,
    'voted_for'   => $optionId,
]);
