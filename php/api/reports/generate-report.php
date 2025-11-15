<?php
/**
 * Generate Student Progress Report
 * Giảng viên bấm "Check Report" → AI phân tích và tạo báo cáo
 * 
 * POST /api/reports/generate-report.php
 * Body: { student_id, period: 'last_7_days' }
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';

// Simple response helper
function jsonResponse($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['student_id'])) {
    jsonResponse(false, 'Missing student_id', null, 400);
}

$studentId = (int)$input['student_id'];
$period = $input['period'] ?? 'last_7_days';

// Parse period
switch ($period) {
    case 'last_7_days':
        $days = 7;
        break;
    case 'last_30_days':
        $days = 30;
        break;
    case 'all_time':
        $days = 365;
        break;
    default:
        $days = 7;
}

// Connect database
try {
    $db = getDBConnection();
} catch (Exception $e) {
    jsonResponse(false, 'Database error', null, 500);
}

try {
    // Get student info
    $stmt = $db->prepare("
        SELECT u.display_name, sp.* 
        FROM users u
        LEFT JOIN student_profiles sp ON u.id = sp.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        jsonResponse(false, 'Student not found', null, 404);
    }
    
    // Get search history
    $stmt = $db->prepare("
        SELECT * FROM search_logs 
        WHERE user_id = ? 
          AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ORDER BY created_at DESC
    ");
    $stmt->execute([$studentId, $days]);
    $searches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get paper interactions
    $stmt = $db->prepare("
        SELECT * FROM paper_interactions 
        WHERE user_id = ? 
          AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ORDER BY created_at DESC
    ");
    $stmt->execute([$studentId, $days]);
    $papers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get team activities (if in a project)
    $stmt = $db->prepare("
        SELECT * FROM team_activity_feed 
        WHERE user_id = ? 
          AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $stmt->execute([$studentId, $days]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Call MegaLLM AI to generate report
    $aiReport = generateAIReport($student, $searches, $papers, $activities, $period);
    
    // Save report to database
    $reportId = saveReport($db, $studentId, $aiReport, $period);
    
    // Return report
    jsonResponse(true, 'Report generated successfully', [
        'report_id' => $reportId,
        'student' => [
            'id' => $studentId,
            'name' => $student['display_name'],
            'major' => $student['major'],
            'university' => $student['university']
        ],
        'period' => $period,
        'activity_stats' => [
            'searches_count' => count($searches),
            'papers_viewed' => count(array_filter($papers, fn($p) => $p['interaction_type'] === 'view')),
            'papers_saved' => count(array_filter($papers, fn($p) => $p['interaction_type'] === 'save')),
            'total_time_spent' => array_sum(array_column($papers, 'time_spent'))
        ],
        'report' => $aiReport,
        'raw_data' => [
            'searches' => $searches,
            'papers' => $papers,
            'activities' => $activities
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Generate report error: ' . $e->getMessage());
    jsonResponse(false, 'Error generating report: ' . $e->getMessage(), null, 500);
}

/**
 * Call MegaLLM to generate AI report
 */
function generateAIReport($student, $searches, $papers, $activities, $period) {
    $apiKey = 'sk-mega-a871069e3800ca98042da57b6a019814e9bd173a42a5870412b88895d52eea5e';
    $baseURL = 'https://ai.megallm.io/v1';
    
    // Build comprehensive prompt
    $prompt = buildReportPrompt($student, $searches, $papers, $activities, $period);
    
    // Call Claude Opus 4.1 for deep analysis
    $ch = curl_init($baseURL . '/chat/completions');
    
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'claude-opus-4-1-20250805',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an AI research supervisor. Analyze student progress and generate comprehensive report in JSON format with Vietnamese text.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.3,
            'max_tokens' => 4000
        ])
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log('MegaLLM API error: ' . $response);
        return generateFallbackReport($student, $searches, $papers);
    }
    
    $result = json_decode($response, true);
    $aiContent = $result['choices'][0]['message']['content'] ?? '';
    
    // Extract JSON from AI response
    if (preg_match('/\{[\s\S]*\}/', $aiContent, $matches)) {
        $reportData = json_decode($matches[0], true);
        if ($reportData) {
            return $reportData;
        }
    }
    
    // Fallback nếu không parse được JSON
    return [
        'summary' => $aiContent,
        'ai_raw_response' => $aiContent
    ];
}

/**
 * Build prompt for AI report generation
 */
function buildReportPrompt($student, $searches, $papers, $activities, $period) {
    $prompt = "=== STUDENT RESEARCH PROGRESS ANALYSIS ===\n\n";
    
    $prompt .= "Student: {$student['display_name']}\n";
    $prompt .= "Major: {$student['major']}\n";
    $prompt .= "University: {$student['university']}\n";
    $prompt .= "Period: $period\n\n";
    
    $prompt .= "=== SEARCH HISTORY (" . count($searches) . " searches) ===\n";
    foreach (array_slice($searches, 0, 20) as $i => $search) {
        $prompt .= ($i + 1) . ". \"{$search['query']}\" - {$search['created_at']}\n";
    }
    
    $prompt .= "\n=== PAPERS INTERACTED (" . count($papers) . " papers) ===\n";
    foreach (array_slice($papers, 0, 15) as $i => $paper) {
        $prompt .= ($i + 1) . ". \"{$paper['paper_title']}\"\n";
        $prompt .= "   Authors: {$paper['paper_authors']}, Year: {$paper['paper_year']}\n";
        $prompt .= "   Action: {$paper['interaction_type']}, Time: {$paper['time_spent']}s\n";
    }
    
    $prompt .= "\n=== PROVIDE ANALYSIS (JSON FORMAT) ===\n";
    $prompt .= "Return JSON with:\n";
    $prompt .= "{\n";
    $prompt .= '  "summary": "Tổng quan về tiến độ và hướng nghiên cứu",'."\n";
    $prompt .= '  "research_focus": "Student đang tập trung vào chủ đề gì?",'."\n";
    $prompt .= '  "strengths": ["điểm mạnh 1", "điểm mạnh 2"],'."\n";
    $prompt .= '  "concerns": [{"severity": "high/medium/low", "issue": "", "recommendation": ""}],'."\n";
    $prompt .= '  "knowledge_gaps": [{"gap": "", "priority": "high/medium/low", "papers_missing": []}],'."\n";
    $prompt .= '  "warnings": [{"type": "methodology/direction", "message": "", "action": ""}],'."\n";
    $prompt .= '  "must_read_papers": [{"title": "", "reason": "", "priority": "critical/high/medium"}],'."\n";
    $prompt .= '  "progress_score": 0-100,'."\n";
    $prompt .= '  "next_steps": ["bước 1", "bước 2"]'."\n";
    $prompt .= "}\n";
    
    return $prompt;
}

/**
 * Fallback report nếu AI fail
 */
function generateFallbackReport($student, $searches, $papers) {
    return [
        'summary' => 'AI report generation failed. Basic stats only.',
        'activity_stats' => [
            'searches' => count($searches),
            'papers' => count($papers)
        ],
        'error' => 'AI API failed'
    ];
}

/**
 * Save report to database
 */
function saveReport($db, $studentId, $report, $period) {
    $stmt = $db->prepare("
        INSERT INTO supervisor_reports 
        (student_id, report_type, time_period_end, summary, research_focus, 
         strengths, concerns, recommendations, overall_score, ai_model)
        VALUES (?, 'progress', NOW(), ?, ?, ?, ?, ?, ?, 'claude-opus-4-1-20250805')
    ");
    
    $stmt->execute([
        $studentId,
        $report['summary'] ?? '',
        $report['research_focus'] ?? '',
        json_encode($report['strengths'] ?? []),
        json_encode($report['concerns'] ?? []),
        json_encode($report['next_steps'] ?? []),
        $report['progress_score'] ?? 0
    ]);
    
    return $db->lastInsertId();
}

