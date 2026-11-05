<?php

/**
 * =========================================================
 * SSRINI HANDCRAFTS
 * ADMIN ABOUT SECTION & HONORS MANAGEMENT
 * =========================================================
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdminLogin();

$pageTitle = 'About Ssrini & Honors';

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$successMessage = null;
$errorMessage = null;

if (isset($_SESSION['about_success'])) {
    $successMessage = (string) $_SESSION['about_success'];
    unset($_SESSION['about_success']);
}
if (isset($_SESSION['about_error'])) {
    $errorMessage = (string) $_SESSION['about_error'];
    unset($_SESSION['about_error']);
}

// ---------------------------------------------------------
// PRESERVED DEFAULTS (Used if DB has not yet been populated)
// ---------------------------------------------------------
$defaultStory = [
    'hero_image'          => 'assets/hero_brand_founder.jpg?v=2',
    'main_heading'        => 'Where Every Piece Tells A Story',
    'story_para_1'        => "Welcome to Ssrini, the brand of Adrish Creative. Our story began in Kolkata in 2004 with an institute dedicated to the art of handicrafts. By 2021, we expanded, opening our first manufacturing unit to create and share the beautiful work of Bengal's artisans with the world.",
    'story_para_2'        => "We specialize in a wide range of handcrafted items—from designer kurtis and blouses to handmade jewellery and home decor. Our mission is to empower women artisans in Bengal, giving them a global platform for their talent. Every product you purchase directly supports this community and helps preserve the rich legacy of Bengal's handicraft.",
    'story_para_3'        => "Each piece is a work of art, made with care and delivered right to your doorstep.",
    'story_subtext'       => "Discover authentic, handcrafted products from anywhere in India and soon, around the world.",
    'stat_1_num'          => '20+',
    'stat_1_text'         => 'years of heritage',
    'stat_2_num'          => '2004',
    'stat_2_text'         => 'founded in Kolkata',
    'stat_3_num'          => '2021',
    'stat_3_text'         => 'ATELIER',
    'stat_4_num'          => '4.9★',
    'stat_4_text'         => 'verified craft rating'
];

$defaultAwards = [
    [
        'id'          => 1,
        'title'       => 'Bengal Master Craftsperson Honor',
        'affiliation' => 'Directorate of Textiles & Handicrafts · 2022',
        'quote'       => 'Awarded for exceptional mastery in preserving indigenous Kantha needlework and pioneering sustainable livelihoods for rural women artisans across Bengal.',
        'image_path'  => '../landing/assets/images/IMG_0685.jpg',
        'sort_order'  => 1,
        'is_active'   => 1
    ],
    [
        'id'          => 2,
        'title'       => 'Women Artisan Empowerment Excellence',
        'affiliation' => 'Eastern India Heritage Craft Guild · 2021',
        'quote'       => "Recognizing two decades of grassroots leadership, expanding from a 2004 artisan training institute into a global stage for Bengal's female handloom weavers.",
        'image_path'  => '../landing/assets/images/IMG_0688.jpg',
        'sort_order'  => 2,
        'is_active'   => 1
    ],
    [
        'id'          => 3,
        'title'       => 'National Handloom & Textile Revival Award',
        'affiliation' => 'All India Artisanal Guild · 2023',
        'quote'       => 'Conferred for innovation in heirloom fabric restoration, marrying generational Bengal motifs with modern designer kurtis, blouses, and haute couture.',
        'image_path'  => '../landing/assets/images/IMG_0692.jpg',
        'sort_order'  => 3,
        'is_active'   => 1
    ],
    [
        'id'          => 4,
        'title'       => 'Kolkata Craft Atelier & Design Leadership',
        'affiliation' => 'Bengal Chamber of Creative Arts · 2020',
        'quote'       => 'Honoring the launch of the dedicated Kolkata manufacturing atelier, setting benchmarks in artisanal jewellery, home decor, and ethical craftsmanship.',
        'image_path'  => '../landing/assets/images/IMG_0696.jpg',
        'sort_order'  => 4,
        'is_active'   => 1
    ],
    [
        'id'          => 5,
        'title'       => 'Sustainable Craft & Rural Artisan Initiative',
        'affiliation' => 'National Guild for Sustainable Handcrafts · 2024',
        'quote'       => "Celebrating 20 years of Adrish Creative: empowering over 150+ master craftswomen and delivering Bengal's living heritage to doorsteps worldwide.",
        'image_path'  => '../landing/assets/images/IMG_0702.jpg',
        'sort_order'  => 5,
        'is_active'   => 1
    ]
];

// ---------------------------------------------------------
// POST ACTIONS ROUTER
// ---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedCsrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($postedCsrf)) {
        $_SESSION['abt_error'] = 'Security validation failed. Please refresh the page and try again.';
        header('Location: about-details.php?tab=' . urlencode($activeTab));
        exit;
    }

    $action = trim($_POST['action'] ?? '');

    // 1. SAVE BRAND STORY & HERO CONTENT
    if ($action === 'save_story') {
        try {
            $mainHeading  = trim($_POST['main_heading'] ?? '');
            $storyPara1   = trim($_POST['story_para_1'] ?? '');
            $storyPara2   = trim($_POST['story_para_2'] ?? '');
            $storyPara3   = trim($_POST['story_para_3'] ?? '');
            $storySubtext = trim($_POST['story_subtext'] ?? '');

            $stat1Num  = trim($_POST['stat_1_num'] ?? '20+');
            $stat1Text = trim($_POST['stat_1_text'] ?? 'years of heritage');
            $stat2Num  = trim($_POST['stat_2_num'] ?? '2004');
            $stat2Text = trim($_POST['stat_2_text'] ?? 'founded in Kolkata');
            $stat3Num  = trim($_POST['stat_3_num'] ?? '2021');
            $stat3Text = trim($_POST['stat_3_text'] ?? 'ATELIER');
            $stat4Num  = trim($_POST['stat_4_num'] ?? '4.9★');
            $stat4Text = trim($_POST['stat_4_text'] ?? 'verified craft rating');

            $existingImagePath = trim($_POST['existing_hero_image'] ?? $defaultStory['hero_image']);
            $heroImagePath = $existingImagePath;

            // Handle file upload for Hero Photo
            if (isset($_FILES['hero_image_file']) && $_FILES['hero_image_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['hero_image_file'];
                if ($file['size'] <= 8 * 1024 * 1024) {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($file['tmp_name']);
                    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                    if (isset($allowed[$mime])) {
                        $targetDir = __DIR__ . '/../uploads/about/';
                        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
                        $fileName = 'hero-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
                        if (move_uploaded_file($file['tmp_name'], $targetDir . $fileName)) {
                            $heroImagePath = 'uploads/about/' . $fileName;
                        }
                    }
                }
            } elseif (!empty($_POST['hero_image_url'])) {
                $heroImagePath = trim($_POST['hero_image_url']);
            }

            // Check if record 1 exists
            $stmt = $pdo->query("SELECT id FROM about_content ORDER BY id ASC LIMIT 1");
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $updateSql = "UPDATE about_content SET 
                    hero_image = :hero_image,
                    main_heading = :main_heading,
                    story_para_1 = :story_para_1,
                    story_para_2 = :story_para_2,
                    story_para_3 = :story_para_3,
                    story_subtext = :story_subtext,
                    stat_1_num = :stat_1_num,
                    stat_1_text = :stat_1_text,
                    stat_2_num = :stat_2_num,
                    stat_2_text = :stat_2_text,
                    stat_3_num = :stat_3_num,
                    stat_3_text = :stat_3_text,
                    stat_4_num = :stat_4_num,
                    stat_4_text = :stat_4_text,
                    updated_at = NOW()
                    WHERE id = :id";
                $stmt = $pdo->prepare($updateSql);
                $stmt->execute([
                    ':hero_image'   => $heroImagePath,
                    ':main_heading' => $mainHeading,
                    ':story_para_1' => $storyPara1,
                    ':story_para_2' => $storyPara2,
                    ':story_para_3' => $storyPara3,
                    ':story_subtext'=> $storySubtext,
                    ':stat_1_num'   => $stat1Num,
                    ':stat_1_text'  => $stat1Text,
                    ':stat_2_num'   => $stat2Num,
                    ':stat_2_text'  => $stat2Text,
                    ':stat_3_num'   => $stat3Num,
                    ':stat_3_text'  => $stat3Text,
                    ':stat_4_num'   => $stat4Num,
                    ':stat_4_text'  => $stat4Text,
                    ':id'           => $existing['id']
                ]);
            } else {
                $insertSql = "INSERT INTO about_content 
                    (hero_image, main_heading, story_para_1, story_para_2, story_para_3, story_subtext, 
                     stat_1_num, stat_1_text, stat_2_num, stat_2_text, stat_3_num, stat_3_text, stat_4_num, stat_4_text)
                    VALUES 
                    (:hero_image, :main_heading, :story_para_1, :story_para_2, :story_para_3, :story_subtext, 
                     :stat_1_num, :stat_1_text, :stat_2_num, :stat_2_text, :stat_3_num, :stat_3_text, :stat_4_num, :stat_4_text)";
                $stmt = $pdo->prepare($insertSql);
                $stmt->execute([
                    ':hero_image'   => $heroImagePath,
                    ':main_heading' => $mainHeading,
                    ':story_para_1' => $storyPara1,
                    ':story_para_2' => $storyPara2,
                    ':story_para_3' => $storyPara3,
                    ':story_subtext'=> $storySubtext,
                    ':stat_1_num'   => $stat1Num,
                    ':stat_1_text'  => $stat1Text,
                    ':stat_2_num'   => $stat2Num,
                    ':stat_2_text'  => $stat2Text,
                    ':stat_3_num'   => $stat3Num,
                    ':stat_3_text'  => $stat3Text,
                    ':stat_4_num'   => $stat4Num,
                    ':stat_4_text'  => $stat4Text
                ]);
            }

            $_SESSION['about_success'] = 'Brand Story & Hero content updated successfully!';
            header('Location: about-details.php?tab=story');
            exit;
        } catch (Throwable $e) {
            $_SESSION['about_error'] = 'Failed to save story content: ' . $e->getMessage();
            header('Location: about-details.php?tab=story');
            exit;
        }
    }

    // 2. SAVE / UPDATE AWARD (Add or Edit)
    if ($action === 'save_award') {
        try {
            $awardId     = !empty($_POST['award_id']) ? (int) $_POST['award_id'] : null;
            $title       = trim($_POST['title'] ?? '');
            $affiliation = trim($_POST['affiliation'] ?? '');
            $quote       = trim($_POST['quote'] ?? '');
            $sortOrder   = (int) ($_POST['sort_order'] ?? 0);
            $isActive    = isset($_POST['is_active']) ? 1 : 0;
            $imagePath   = trim($_POST['existing_image_path'] ?? '');

            if (empty($title) || empty($affiliation) || empty($quote)) {
                throw new Exception('Award title, issuing body/year, and citation quote are required.');
            }

            // Handle file upload for Award Photo
            if (isset($_FILES['award_image_file']) && $_FILES['award_image_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['award_image_file'];
                if ($file['size'] <= 8 * 1024 * 1024) {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($file['tmp_name']);
                    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                    if (isset($allowed[$mime])) {
                        $targetDir = __DIR__ . '/../uploads/awards/';
                        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
                        $fileName = 'award-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
                        if (move_uploaded_file($file['tmp_name'], $targetDir . $fileName)) {
                            $imagePath = 'uploads/awards/' . $fileName;
                        }
                    }
                }
            } elseif (!empty($_POST['award_image_url'])) {
                $imagePath = trim($_POST['award_image_url']);
            }

            if (empty($imagePath)) {
                $imagePath = '../landing/assets/images/IMG_0685.jpg'; // fallback
            }

            if ($awardId) {
                $stmt = $pdo->prepare("UPDATE about_awards SET 
                    title = :title, 
                    affiliation = :affiliation, 
                    quote = :quote, 
                    image_path = :image_path, 
                    sort_order = :sort_order, 
                    is_active = :is_active 
                    WHERE id = :id");
                $stmt->execute([
                    ':title'       => $title,
                    ':affiliation' => $affiliation,
                    ':quote'       => $quote,
                    ':image_path'  => $imagePath,
                    ':sort_order'  => $sortOrder,
                    ':is_active'   => $isActive,
                    ':id'          => $awardId
                ]);
                $_SESSION['about_success'] = 'Award "' . $title . '" updated successfully!';
            } else {
                $stmt = $pdo->prepare("INSERT INTO about_awards 
                    (title, affiliation, quote, image_path, sort_order, is_active) 
                    VALUES (:title, :affiliation, :quote, :image_path, :sort_order, :is_active)");
                $stmt->execute([
                    ':title'       => $title,
                    ':affiliation' => $affiliation,
                    ':quote'       => $quote,
                    ':image_path'  => $imagePath,
                    ':sort_order'  => $sortOrder,
                    ':is_active'   => $isActive
                ]);
                $_SESSION['about_success'] = 'New Award "' . $title . '" added to the carousel!';
            }

            header('Location: about-details.php?tab=awards');
            exit;
        } catch (Throwable $e) {
            $_SESSION['about_error'] = 'Failed to save award: ' . $e->getMessage();
            header('Location: about-details.php?tab=awards');
            exit;
        }
    }

    // 3. TOGGLE AWARD STATUS
    if ($action === 'toggle_award') {
        try {
            $awardId = (int) ($_POST['award_id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE about_awards SET is_active = IF(is_active=1, 0, 1) WHERE id = :id");
            $stmt->execute([':id' => $awardId]);
            $_SESSION['about_success'] = 'Award visibility toggled!';
            header('Location: about-details.php?tab=awards');
            exit;
        } catch (Throwable $e) {
            $_SESSION['about_error'] = 'Failed to toggle award: ' . $e->getMessage();
            header('Location: about-details.php?tab=awards');
            exit;
        }
    }

    // 4. DELETE AWARD
    if ($action === 'delete_award') {
        try {
            $awardId = (int) ($_POST['award_id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM about_awards WHERE id = :id");
            $stmt->execute([':id' => $awardId]);
            $_SESSION['about_success'] = 'Award removed from the honors collection.';
            header('Location: about-details.php?tab=awards');
            exit;
        } catch (Throwable $e) {
            $_SESSION['about_error'] = 'Failed to delete award: ' . $e->getMessage();
            header('Location: about-details.php?tab=awards');
            exit;
        }
    }
}

// ---------------------------------------------------------
// DATA FETCHING
// ---------------------------------------------------------
$storyData = $defaultStory;
try {
    $stmt = $pdo->query("SELECT * FROM about_content ORDER BY id ASC LIMIT 1");
    $dbStory = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($dbStory) {
        foreach ($defaultStory as $key => $defaultVal) {
            if (isset($dbStory[$key]) && $dbStory[$key] !== null && $dbStory[$key] !== '') {
                $storyData[$key] = $dbStory[$key];
            }
        }
    }
} catch (Throwable $e) {
    // fallback to defaults
}

$awardsList = [];
try {
    $stmt = $pdo->query("SELECT * FROM about_awards ORDER BY sort_order ASC, id ASC");
    $awardsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    // fallback to defaults
}

if (empty($awardsList)) {
    $awardsList = $defaultAwards;
}

$activeTab = $_GET['tab'] ?? 'story';
$editAwardId = isset($_GET['edit_award']) ? (int) $_GET['edit_award'] : null;
$awardToEdit = null;
if ($editAwardId) {
    foreach ($awardsList as $aw) {
        if ((int)$aw['id'] === $editAwardId) {
            $awardToEdit = $aw;
            break;
        }
    }
}

// Format display paths for image previews
function formatDisplayImage(string $path): string
{
    if (empty($path)) return '';
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) return $path;
    if (strpos($path, '../') === 0) return $path;
    if (strpos($path, 'uploads/') === 0) return '../' . $path;
    if (strpos($path, 'assets/') === 0) return '../../../ecom/about/' . $path;
    return $path;
}

$activeAwardsCount = count(array_filter($awardsList, fn($a) => !empty($a['is_active'])));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | Ssrini Handcrafts Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        :root {
            --abt-primary: #7627c9;
            --abt-primary-dark: #58169e;
            --abt-gold: #c99a3e;
            --abt-gold-light: #fdf8eb;
            --abt-bg-card: #ffffff;
            --abt-border: #e9e4f0;
            --abt-text: #1d1929;
            --abt-muted: #6b637b;
        }

        .abt-container {
            max-width: 1380px;
            margin: 0 auto;
            padding: 24px 20px;
        }

        /* Page Header */
        .abt-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }
        .abt-header h1 {
            font-size: 24px;
            font-weight: 800;
            color: var(--abt-text);
            margin: 0 0 6px 0;
            letter-spacing: -0.5px;
        }
        .abt-header p {
            color: var(--abt-muted);
            font-size: 14px;
            margin: 0;
        }

        /* Stats Row */
        .abt-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }
        .abt-stat-card {
            background: #fff;
            border: 1px solid var(--abt-border);
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 8px rgba(118, 39, 201, 0.03);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .abt-stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(118, 39, 201, 0.08);
        }
        .abt-stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            background: #f7f3fd;
            color: var(--abt-primary);
        }
        .abt-stat-num {
            font-size: 22px;
            font-weight: 800;
            color: var(--abt-text);
            line-height: 1.1;
        }
        .abt-stat-label {
            font-size: 12px;
            color: var(--abt-muted);
            margin-top: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        /* Tabs Nav */
        .abt-tabs {
            display: flex;
            gap: 8px;
            border-bottom: 2px solid #eeeaf5;
            margin-bottom: 28px;
        }
        .abt-tab-btn {
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 700;
            color: var(--abt-muted);
            border: none;
            background: none;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            text-decoration: none;
        }
        .abt-tab-btn:hover {
            color: var(--abt-primary);
        }
        .abt-tab-btn.is-active {
            color: var(--abt-primary);
            border-bottom-color: var(--abt-primary);
            background: #faf7ff;
            border-radius: 8px 8px 0 0;
        }
        .abt-tab-badge {
            background: #ede6f8;
            color: var(--abt-primary);
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 12px;
            font-weight: 800;
        }

        /* Two Column Layout */
        .abt-grid {
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 24px;
        }
        @media (max-width: 1024px) {
            .abt-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Card Elements */
        .abt-card {
            background: #fff;
            border: 1px solid var(--abt-border);
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            margin-bottom: 24px;
        }
        .abt-card-title {
            font-size: 17px;
            font-weight: 800;
            color: var(--abt-text);
            margin: 0 0 6px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .abt-card-desc {
            font-size: 13px;
            color: var(--abt-muted);
            margin: 0 0 20px 0;
            line-height: 1.5;
        }

        /* Guidance Banner */
        .abt-guide-box {
            background: #faf7fd;
            border: 1px solid #ebdff8;
            border-radius: 10px;
            padding: 14px 18px;
            margin-bottom: 22px;
            font-size: 13px;
            color: #4a1e6d;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            line-height: 1.5;
        }
        .abt-guide-box strong {
            color: #2b0b45;
        }

        /* Form Controls */
        .abt-form-group {
            margin-bottom: 20px;
        }
        .abt-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
            font-weight: 700;
            color: var(--abt-text);
            margin-bottom: 6px;
        }
        .abt-location-badge {
            font-size: 11px;
            font-weight: 600;
            background: #eef2ff;
            color: #3730a3;
            padding: 2px 8px;
            border-radius: 6px;
            border: 1px solid #c7d2fe;
        }
        .abt-hint {
            font-size: 12px;
            color: var(--abt-muted);
            margin: 0 0 8px 0;
            line-height: 1.4;
        }
        .abt-input, .abt-textarea {
            width: 100%;
            border: 1px solid #d9d3e3;
            border-radius: 8px;
            padding: 11px 14px;
            font-size: 14px;
            color: #222;
            background: #fdfdfe;
            box-sizing: border-box;
            transition: border-color 0.2s, box-shadow 0.2s;
            font-family: inherit;
        }
        .abt-input:focus, .abt-textarea:focus {
            outline: none;
            border-color: var(--abt-primary);
            box-shadow: 0 0 0 3px rgba(118, 39, 201, 0.12);
            background: #fff;
        }
        .abt-textarea {
            min-height: 110px;
            line-height: 1.6;
            resize: vertical;
        }

        /* Image Uploader Card */
        .abt-uploader {
            border: 2px dashed #d9d2e5;
            border-radius: 10px;
            padding: 16px;
            background: #fbfafc;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.2s;
        }
        .abt-uploader:hover {
            border-color: var(--abt-primary);
            background: #faf7fd;
        }
        .abt-thumb-preview {
            width: 100px;
            height: 90px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid #ddd;
            background: #eee;
            flex-shrink: 0;
        }
        .abt-upload-info {
            flex-grow: 1;
        }

        /* Two columns inside form */
        .abt-form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        @media (max-width: 640px) {
            .abt-form-row {
                grid-template-columns: 1fr;
            }
        }

        /* Stats Grid in Form */
        .abt-stats-inputs {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            background: #faf9fd;
            border: 1px solid #ede8f5;
            border-radius: 10px;
            padding: 16px;
            margin-top: 8px;
        }

        /* Primary Action Button */
        .abt-btn-save {
            background: linear-gradient(135deg, #7627c9 0%, #58169e 100%);
            color: #fff;
            border: none;
            padding: 13px 28px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(118, 39, 201, 0.25);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .abt-btn-save:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(118, 39, 201, 0.35);
        }

        /* Award Cards in Table/Grid */
        .abt-award-card {
            background: #ffffff;
            border: 1px solid #ede7f4;
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 16px;
            display: grid;
            grid-template-columns: 90px 1fr auto;
            gap: 18px;
            align-items: center;
            transition: all 0.2s;
        }
        .abt-award-card:hover {
            border-color: #c9b8e8;
            box-shadow: 0 4px 14px rgba(0,0,0,0.04);
        }
        .abt-award-thumb {
            width: 90px;
            height: 90px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid #e0d7ed;
            background: #eee;
        }
        .abt-award-body h4 {
            margin: 0 0 4px 0;
            font-size: 16px;
            font-weight: 700;
            color: var(--abt-text);
        }
        .abt-award-org {
            font-size: 13px;
            font-weight: 600;
            color: var(--abt-primary);
            margin-bottom: 8px;
        }
        .abt-award-quote {
            font-size: 12px;
            color: #555;
            line-height: 1.5;
            font-style: italic;
            background: #faf8fd;
            padding: 8px 12px;
            border-radius: 6px;
            border-left: 3px solid #c9b8e8;
            margin: 0;
        }
        .abt-award-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: flex-end;
        }

        /* Toggle Switch */
        .abt-switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
        }
        .abt-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .abt-slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #cbd5e1;
            transition: .3s;
            border-radius: 24px;
        }
        .abt-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
        }
        input:checked + .abt-slider {
            background-color: #10b981;
        }
        input:checked + .abt-slider:before {
            transform: translateX(20px);
        }

        /* Live Storefront Mockup Preview */
        .abt-mockup {
            background: #fbfaf8;
            border: 1px solid #e8e3d8;
            border-radius: 12px;
            padding: 20px;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .abt-mockup-header {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #8c8273;
            margin-bottom: 12px;
            font-weight: 700;
            display: flex;
            justify-content: space-between;
        }
        .abt-mockup-headline {
            font-size: 20px;
            font-weight: 800;
            color: #1a1715;
            line-height: 1.25;
            margin-bottom: 16px;
        }
        .abt-mockup-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }
        .abt-mockup-col {
            font-size: 11px;
            color: #595248;
            line-height: 1.5;
            background: #fff;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #eee8df;
        }
        .abt-mockup-badge-box {
            background: #1e1b18;
            color: #fff;
            padding: 12px;
            border-radius: 8px;
            font-size: 11px;
            line-height: 1.4;
            margin-top: 8px;
        }
        .abt-mockup-badge-title {
            color: #ef4444;
            font-weight: 800;
            font-size: 13px;
            margin-bottom: 2px;
        }

        /* Action Mini Buttons */
        .abt-mini-btn {
            border: 1px solid #d9d3e3;
            background: #fff;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            color: #4a1e6d;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.15s;
        }
        .abt-mini-btn:hover {
            border-color: var(--abt-primary);
            color: var(--abt-primary);
            background: #faf7fd;
        }
        .abt-mini-btn-danger {
            color: #b91c1c;
            border-color: #fecaca;
        }
        .abt-mini-btn-danger:hover {
            background: #fef2f2;
            border-color: #ef4444;
            color: #ef4444;
        }
    </style>
</head>
<body>

<div class="admin-wrapper">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-area">
        <?php require_once __DIR__ . '/../includes/header.php'; ?>

        <div class="abt-container">

            <!-- PAGE HEADER -->
            <div class="abt-header">
                <div>
                    <h1>About Ssrini & Honors Management</h1>
                    <p>Manage the atelier's brand story, editorial paragraphs, hero photograph, and prestigious awards displayed dynamically across the About page.</p>
                </div>
                <div>
                    <a href="../../../ecom/about/index.php" target="_blank" class="abt-mini-btn" style="padding:10px 18px; font-weight:700;">
                        <span>View Live Storefront ↗</span>
                    </a>
                </div>
            </div>

            <!-- ALERTS -->
            <?php if ($successMessage): ?>
                <div style="background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; padding:14px 18px; border-radius:10px; margin-bottom:20px; font-size:14px; font-weight:600; display:flex; align-items:center; gap:8px;">
                    <span>✓</span> <?= e($successMessage) ?>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:14px 18px; border-radius:10px; margin-bottom:20px; font-size:14px; font-weight:600; display:flex; align-items:center; gap:8px;">
                    <span>⚠</span> <?= e($errorMessage) ?>
                </div>
            <?php endif; ?>

            <!-- STATS ROW -->
            <div class="abt-stats-grid">
                <div class="abt-stat-card">
                    <div class="abt-stat-icon">📖</div>
                    <div>
                        <div class="abt-stat-num">3 Paragraphs</div>
                        <div class="abt-stat-label">Editorial Story Layout</div>
                    </div>
                </div>
                <div class="abt-stat-card">
                    <div class="abt-stat-icon">🏆</div>
                    <div>
                        <div class="abt-stat-num"><?= count($awardsList) ?> Honors</div>
                        <div class="abt-stat-label">In Accolades Collection</div>
                    </div>
                </div>
                <div class="abt-stat-card">
                    <div class="abt-stat-icon">✨</div>
                    <div>
                        <div class="abt-stat-num"><?= $activeAwardsCount ?> Visible</div>
                        <div class="abt-stat-label">Active on Slider</div>
                    </div>
                </div>
                <div class="abt-stat-card">
                    <div class="abt-stat-icon">🏛️</div>
                    <div>
                        <div class="abt-stat-num">Est. 2004</div>
                        <div class="abt-stat-label">Atelier Heritage</div>
                    </div>
                </div>
            </div>

            <!-- TABS -->
            <div class="abt-tabs">
                <a href="?tab=story" class="abt-tab-btn <?= $activeTab === 'story' ? 'is-active' : '' ?>">
                    <span>📖 Brand Story & Hero Section</span>
                </a>
                <a href="?tab=awards" class="abt-tab-btn <?= $activeTab === 'awards' ? 'is-active' : '' ?>">
                    <span>🏆 Honors & Awards Carousel</span>
                    <span class="abt-tab-badge"><?= count($awardsList) ?></span>
                </a>
                <a href="reviews.php" class="abt-tab-btn">
                    <span>💬 Testimonials & Google Reviews</span>
                    <span class="abt-tab-badge" style="background:var(--primary); color:#000; font-weight:700;">Cards ↗</span>
                </a>
            </div>

            <!-- TAB 1: STORY CONTENT -->
            <?php if ($activeTab === 'story'): ?>
            <div class="abt-grid">
                <div>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="action" value="save_story">
                        <input type="hidden" name="existing_hero_image" value="<?= e($storyData['hero_image']) ?>">

                        <!-- 1. HERO PHOTOGRAPH -->
                        <div class="abt-card">
                            <h3 class="abt-card-title">
                                <span>1. Main Hero Photograph</span>
                                <span class="abt-location-badge">Top of Page (SVG Geometric Cutout)</span>
                            </h3>
                            <p class="abt-card-desc">This is the photograph displayed inside the signature architectural notched cutout at the top of the About page.</p>

                            <div class="abt-uploader">
                                <img src="<?= e(formatDisplayImage($storyData['hero_image'])) ?>" alt="Current Hero Photo" class="abt-thumb-preview" id="heroPhotoPreview">
                                <div class="abt-upload-info">
                                    <label class="abt-label" style="margin-bottom:4px;">Upload New Hero Photograph</label>
                                    <p class="abt-hint">Supported formats: JPG, WebP, PNG (Max: 8MB). Recommended: 16:9 landscape (e.g. 2752&times;1536 or 1920&times;1080) matching the original photo. The SVG frame auto-centers and slices it smoothly.</p>
                                    <input type="file" name="hero_image_file" class="abt-input" accept="image/*" onchange="previewLocalImage(this, 'heroPhotoPreview')">
                                    <div style="margin-top:8px;">
                                        <span style="font-size:11px; color:#888;">Or custom relative path:</span>
                                        <input type="text" name="hero_image_url" class="abt-input" style="padding:6px 10px; font-size:12px; margin-top:4px;" value="<?= e($storyData['hero_image']) ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. MAIN EDITORIAL HEADLINE -->
                        <div class="abt-card">
                            <h3 class="abt-card-title">
                                <span>2. Main Editorial Headline</span>
                                <span class="abt-location-badge">Large Animated Title</span>
                            </h3>
                            <p class="abt-card-desc">The headline renders with a smooth word-by-word spring animation right above the story paragraphs.</p>

                            <div class="abt-form-group">
                                <label class="abt-label">Headline Text</label>
                                <input type="text" name="main_heading" class="abt-input" style="font-size:16px; font-weight:700;" value="<?= e($storyData['main_heading']) ?>" required>
                                <p class="abt-hint">Default: <em>Where Every Piece Tells A Story</em></p>
                            </div>
                        </div>

                        <!-- 3. THREE EDITORIAL PARAGRAPHS -->
                        <div class="abt-card">
                            <h3 class="abt-card-title">
                                <span>3. Editorial Brand Story Content (3 Distinct Locations)</span>
                            </h3>
                            <p class="abt-card-desc">The About page presents your story in three dedicated visual positions. Fill each section according to where it appears on the website.</p>

                            <div class="abt-guide-box">
                                <span style="font-size:20px;">💡</span>
                                <div>
                                    <strong>Layout Structure Guide:</strong><br>
                                    • <strong>Paragraph 1:</strong> Displays in the <em>Left Column</em> of the 2-column editorial grid.<br>
                                    • <strong>Paragraph 2:</strong> Displays in the <em>Right Column</em> of the 2-column editorial grid.<br>
                                    • <strong>Paragraph 3:</strong> Displays prominently inside the <em>Right Atelier Spotlight Card</em> beside the "Explore Atelier" button.
                                </div>
                            </div>

                            <!-- PARAGRAPH 1 -->
                            <div class="abt-form-group">
                                <div class="abt-label">
                                    <span>Story Paragraph 1 — Our Origins & Expansion</span>
                                    <span class="abt-location-badge">Editorial Column 1 (Left)</span>
                                </div>
                                <p class="abt-hint">Introduce the brand's roots in Kolkata (2004 institute) and opening the manufacturing atelier (2021).</p>
                                <textarea name="story_para_1" class="abt-textarea" required><?= e($storyData['story_para_1']) ?></textarea>
                            </div>

                            <!-- PARAGRAPH 2 -->
                            <div class="abt-form-group">
                                <div class="abt-label">
                                    <span>Story Paragraph 2 — Our Mission & Women Artisans</span>
                                    <span class="abt-location-badge">Editorial Column 2 (Middle)</span>
                                </div>
                                <p class="abt-hint">Describe the handcrafted kurtis, jewellery, home decor, and your mission empowering Bengal women artisans.</p>
                                <textarea name="story_para_2" class="abt-textarea" required><?= e($storyData['story_para_2']) ?></textarea>
                            </div>

                            <!-- PARAGRAPH 3 -->
                            <div class="abt-form-group">
                                <div class="abt-label">
                                    <span>Story Paragraph 3 — Artisanal Quality Statement</span>
                                    <span class="abt-location-badge">Right Atelier Showcase Card (Bold)</span>
                                </div>
                                <p class="abt-hint">A short, high-impact artisanal statement delivering confidence to patrons.</p>
                                <textarea name="story_para_3" class="abt-textarea" style="min-height:80px; font-weight:600;" required><?= e($storyData['story_para_3']) ?></textarea>
                            </div>

                            <!-- SUBTEXT -->
                            <div class="abt-form-group">
                                <div class="abt-label">
                                    <span>Right-Column Delivery Subtext</span>
                                    <span class="abt-location-badge">Under Paragraph 3</span>
                                </div>
                                <p class="abt-hint">Appears in lighter text right beneath Paragraph 3.</p>
                                <textarea name="story_subtext" class="abt-textarea" style="min-height:70px;"><?= e($storyData['story_subtext']) ?></textarea>
                            </div>
                        </div>

                        <!-- 4. HERITAGE STATS -->
                        <div class="abt-card">
                            <h3 class="abt-card-title">
                                <span>4. Heritage Stats Bar</span>
                                <span class="abt-location-badge">Below Hero Image</span>
                            </h3>
                            <p class="abt-card-desc">The 4 signature stat badges displayed below the hero photograph.</p>

                            <div class="abt-stats-inputs">
                                <div>
                                    <label class="abt-hint" style="font-weight:700;">Stat 1 Number & Text</label>
                                    <div style="display:flex; gap:6px;">
                                        <input type="text" name="stat_1_num" class="abt-input" style="width:70px; font-weight:700;" value="<?= e($storyData['stat_1_num']) ?>">
                                        <input type="text" name="stat_1_text" class="abt-input" value="<?= e($storyData['stat_1_text']) ?>">
                                    </div>
                                </div>
                                <div>
                                    <label class="abt-hint" style="font-weight:700;">Stat 2 Number & Text</label>
                                    <div style="display:flex; gap:6px;">
                                        <input type="text" name="stat_2_num" class="abt-input" style="width:70px; font-weight:700;" value="<?= e($storyData['stat_2_num']) ?>">
                                        <input type="text" name="stat_2_text" class="abt-input" value="<?= e($storyData['stat_2_text']) ?>">
                                    </div>
                                </div>
                                <div>
                                    <label class="abt-hint" style="font-weight:700;">Stat 3 Number & Text</label>
                                    <div style="display:flex; gap:6px;">
                                        <input type="text" name="stat_3_num" class="abt-input" style="width:70px; font-weight:700;" value="<?= e($storyData['stat_3_num']) ?>">
                                        <input type="text" name="stat_3_text" class="abt-input" value="<?= e($storyData['stat_3_text']) ?>">
                                    </div>
                                </div>
                                <div>
                                    <label class="abt-hint" style="font-weight:700;">Stat 4 Number & Text</label>
                                    <div style="display:flex; gap:6px;">
                                        <input type="text" name="stat_4_num" class="abt-input" style="width:70px; font-weight:700;" value="<?= e($storyData['stat_4_num']) ?>">
                                        <input type="text" name="stat_4_text" class="abt-input" value="<?= e($storyData['stat_4_text']) ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div style="margin-top:24px;">
                            <button type="submit" class="abt-btn-save">
                                <span>💾 Save Brand Story Changes</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- SIDEBAR: LIVE PREVIEW -->
                <div>
                    <div class="abt-card" style="position:sticky; top:20px;">
                        <h3 class="abt-card-title">
                            <span>Storefront Live Simulation</span>
                        </h3>
                        <p class="abt-card-desc">Visual layout of how the story looks on the visitor's screen:</p>

                        <div class="abt-mockup">
                            <div class="abt-mockup-header">
                                <span>About Ssrini Preview</span>
                                <span>✱ Adrish Creative</span>
                            </div>
                            <div class="abt-mockup-headline" id="previewHeadline">
                                <?= e($storyData['main_heading']) ?>
                            </div>
                            <div class="abt-mockup-grid">
                                <div class="abt-mockup-col" id="previewCol1">
                                    <strong style="color:#7627c9; display:block; margin-bottom:4px;">Column 1:</strong>
                                    <?= e($storyData['story_para_1']) ?>
                                </div>
                                <div class="abt-mockup-col" id="previewCol2">
                                    <strong style="color:#7627c9; display:block; margin-bottom:4px;">Column 2:</strong>
                                    <?= e($storyData['story_para_2']) ?>
                                </div>
                            </div>
                            <div class="abt-mockup-badge-box">
                                <div class="abt-mockup-badge-title">SSRINI</div>
                                <div style="font-size:10px; color:#aaa; margin-bottom:6px;">Brand of Adrish Creative · Est. 2004</div>
                                <div style="font-weight:600; margin-bottom:4px;" id="previewPara3">
                                    <?= e($storyData['story_para_3']) ?>
                                </div>
                                <div style="font-size:10px; color:#bbb;" id="previewSubtext">
                                    <?= e($storyData['story_subtext']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- TAB 2: HONORS & AWARDS SLIDER -->
            <?php if ($activeTab === 'awards'): ?>
            <div class="abt-grid">
                <div>
                    <!-- AWARDS LIST -->
                    <div class="abt-card">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                            <div>
                                <h3 class="abt-card-title" style="margin:0;">Honors & Accolades Carousel (<?= count($awardsList) ?>)</h3>
                                <p class="abt-card-desc" style="margin:4px 0 0 0;">These awards rotate dynamically in the interactive awards slider on the About page.</p>
                            </div>
                            <a href="#awardFormCard" class="abt-btn-save" style="padding:8px 16px; font-size:13px; text-decoration:none;" onclick="resetAwardForm();">
                                <span>➕ Add New Award</span>
                            </a>
                        </div>

                        <?php foreach ($awardsList as $idx => $award): ?>
                        <div class="abt-award-card">
                            <img src="<?= e(formatDisplayImage($award['image_path'])) ?>" alt="Award Photo" class="abt-award-thumb">
                            <div class="abt-award-body">
                                <h4><?= e($award['title']) ?></h4>
                                <div class="abt-award-org">🏛️ <?= e($award['affiliation']) ?></div>
                                <blockquote class="abt-award-quote">
                                    "<?= e($award['quote']) ?>"
                                </blockquote>
                            </div>
                            <div class="abt-award-actions">
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="action" value="toggle_award">
                                    <input type="hidden" name="award_id" value="<?= (int)$award['id'] ?>">
                                    <label class="abt-switch" title="Toggle visible / hidden">
                                        <input type="checkbox" <?= !empty($award['is_active']) ? 'checked' : '' ?> onchange="this.form.submit()">
                                        <span class="abt-slider"></span>
                                    </label>
                                </form>
                                <span style="font-size:11px; font-weight:700; color:<?= !empty($award['is_active']) ? '#10b981' : '#94a3b8' ?>;">
                                    <?= !empty($award['is_active']) ? 'Active' : 'Hidden' ?>
                                </span>
                                <div style="display:flex; gap:6px; margin-top:8px;">
                                    <a href="?tab=awards&edit_award=<?= (int)$award['id'] ?>#awardFormCard" class="abt-mini-btn">✏️ Edit</a>
                                    <form method="POST" style="margin:0;" onsubmit="return confirm('Are you sure you want to remove this award from the honors carousel?');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="action" value="delete_award">
                                        <input type="hidden" name="award_id" value="<?= (int)$award['id'] ?>">
                                        <button type="submit" class="abt-mini-btn abt-mini-btn-danger">🗑</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- ADD / EDIT FORM -->
                    <div class="abt-card" id="awardFormCard">
                        <h3 class="abt-card-title">
                            <span><?= $awardToEdit ? '✏️ Edit Award #' . e($awardToEdit['id']) : '➕ Add New Honor / Award' ?></span>
                        </h3>
                        <p class="abt-card-desc">Fill in the award details below. The citation quote will automatically display inside quotation marks <em>"..."</em> in the slider.</p>

                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="action" value="save_award">
                            <?php if ($awardToEdit): ?>
                                <input type="hidden" name="award_id" value="<?= (int)$awardToEdit['id'] ?>">
                                <input type="hidden" name="existing_image_path" value="<?= e($awardToEdit['image_path']) ?>">
                            <?php endif; ?>

                            <!-- 1. TITLE -->
                            <div class="abt-form-group">
                                <div class="abt-label">
                                    <span>Award Title / Honor Name *</span>
                                    <span class="abt-location-badge">Primary Heading</span>
                                </div>
                                <p class="abt-hint">Example: <em>Bengal Master Craftsperson Honor</em></p>
                                <input type="text" name="title" class="abt-input" value="<?= e($awardToEdit['title'] ?? '') ?>" required placeholder="e.g. Bengal Master Craftsperson Honor">
                            </div>

                            <!-- 2. AFFILIATION / ISSUING BODY & YEAR -->
                            <div class="abt-form-group">
                                <div class="abt-label">
                                    <span>Issuing Organization & Year *</span>
                                    <span class="abt-location-badge">Accolade Metadata</span>
                                </div>
                                <p class="abt-hint">Example: <em>Directorate of Textiles & Handicrafts · 2022</em></p>
                                <input type="text" name="affiliation" class="abt-input" value="<?= e($awardToEdit['affiliation'] ?? '') ?>" required placeholder="e.g. Directorate of Textiles & Handicrafts · 2022">
                            </div>

                            <!-- 3. CITATION QUOTE -->
                            <div class="abt-form-group">
                                <div class="abt-label">
                                    <span>Citation Description / Reason for Award (Quote) *</span>
                                    <span class="abt-location-badge">Curator's Citation (in "")</span>
                                </div>
                                <p class="abt-hint">Explains why the award was conferred. Displayed prominently as the curator's citation quote.</p>
                                <textarea name="quote" class="abt-textarea" required placeholder="Awarded for exceptional mastery in preserving indigenous Kantha needlework and pioneering sustainable livelihoods..."><?= e($awardToEdit['quote'] ?? '') ?></textarea>
                            </div>

                            <!-- 4. AWARD IMAGE -->
                            <div class="abt-form-group">
                                <div class="abt-label">
                                    <span>Award / Certificate / Workshop Photograph</span>
                                    <span class="abt-location-badge">Carousel Center Display</span>
                                </div>
                                <div class="abt-uploader">
                                    <img src="<?= e(formatDisplayImage($awardToEdit['image_path'] ?? '../landing/assets/images/IMG_0685.jpg')) ?>" alt="Award Preview" class="abt-thumb-preview" id="awardPhotoPreview">
                                    <div class="abt-upload-info">
                                        <label class="abt-label" style="margin-bottom:4px;">Choose Image File</label>
                                        <p class="abt-hint">Upload award certificate, medal, or craft photo (Max: 8MB).</p>
                                        <input type="file" name="award_image_file" class="abt-input" accept="image/*" onchange="previewLocalImage(this, 'awardPhotoPreview')">
                                        <div style="margin-top:8px;">
                                            <span style="font-size:11px; color:#888;">Or existing path / image URL:</span>
                                            <input type="text" name="award_image_url" class="abt-input" style="padding:6px 10px; font-size:12px; margin-top:4px;" value="<?= e($awardToEdit['image_path'] ?? '') ?>" placeholder="../landing/assets/images/IMG_0685.jpg">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 5. SORT ORDER & ACTIVE TOGGLE -->
                            <div class="abt-form-row">
                                <div class="abt-form-group">
                                    <label class="abt-label">Display Order (Sort Number)</label>
                                    <input type="number" name="sort_order" class="abt-input" value="<?= (int)($awardToEdit['sort_order'] ?? (count($awardsList) + 1)) ?>">
                                    <p class="abt-hint">Awards with smaller numbers appear first (e.g. 1, 2, 3...).</p>
                                </div>
                                <div class="abt-form-group">
                                    <label class="abt-label">Display on Website</label>
                                    <div style="margin-top:10px; display:flex; align-items:center; gap:12px;">
                                        <label class="abt-switch">
                                            <input type="checkbox" name="is_active" value="1" <?= (!isset($awardToEdit) || !empty($awardToEdit['is_active'])) ? 'checked' : '' ?>>
                                            <span class="abt-slider"></span>
                                        </label>
                                        <span style="font-size:13px; font-weight:600; color:#444;">Visible in Storefront Carousel</span>
                                    </div>
                                </div>
                            </div>

                            <div style="display:flex; gap:12px; margin-top:16px;">
                                <button type="submit" class="abt-btn-save">
                                    <span>💾 <?= $awardToEdit ? 'Update Award' : 'Add Award to Carousel' ?></span>
                                </button>
                                <?php if ($awardToEdit): ?>
                                    <a href="?tab=awards" class="abt-mini-btn" style="padding:12px 20px;">Cancel Edit</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- SIDEBAR GUIDE -->
                <div>
                    <div class="abt-card" style="position:sticky; top:20px;">
                        <h3 class="abt-card-title">
                            <span>Carousel Features</span>
                        </h3>
                        <p class="abt-card-desc">How the honors carousel works for website visitors:</p>

                        <div style="background:#faf8fe; border:1px solid #ebdff8; border-radius:10px; padding:16px; font-size:13px; color:#3b185f; line-height:1.6;">
                            <strong>1. Multi-Image Navigation Track:</strong><br>
                            Visitors can click through previous/next award arrows or tap the 3 active thumbnail buttons.<br><br>
                            <strong>2. Automatic Counter:</strong><br>
                            Displays dynamic pagination (e.g. <code>01 / 05</code>) adjusting automatically to the number of active awards.<br><br>
                            <strong>3. Quoted Citation:</strong><br>
                            Each award quote is rendered inside quotation marks with an ambient golden glow effect.<br><br>
                            <strong>4. Smooth Animations:</strong><br>
                            Images transition with vertical spring scaling while text glides horizontally.
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<!-- CLIENT PREVIEW SCRIPT -->
<script>
    function previewLocalImage(input, previewElementId) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById(previewElementId);
                if (img) img.src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function resetAwardForm() {
        const url = new URL(window.location.href);
        url.searchParams.delete('edit_award');
        window.location.href = url.toString();
    }
</script>

</body>
</html>