<?php
/**
 * =========================================================
 * SSRINI HANDCRAFTS - ABOUT SECTION & AWARDS DATA PROVIDER
 * =========================================================
 * Fetches dynamic brand story, hero photograph, and honors/awards
 * from the database with graceful fallbacks to preserved atelier data.
 */

if (!isset($pdo)) {
    $dbPath = dirname(__DIR__, 2) . '/admin/ssrini-handicrafts-admin/config/database.php';
    if (file_exists($dbPath)) {
        require_once $dbPath;
    }
}

/**
 * Format image paths stored in database to be accessible from ecom/about/
 */
function resolve_about_image_url(string $path): string
{
    $path = trim($path);
    if (empty($path)) return '';
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        return $path;
    }
    // If it starts with uploads/, resolve from admin panel uploads
    if (strpos($path, 'uploads/') === 0) {
        return '/admin/ssrini-handicrafts-admin/' . $path;
    }
    // If it starts with ../landing/, it is already relative to ecom/about/
    if (strpos($path, '../landing/') === 0 || strpos($path, 'assets/') === 0) {
        return $path;
    }
    return $path;
}

/**
 * Fetch singleton brand story & hero data
 */
function get_about_story_data(): array
{
    global $pdo;
    static $cachedStory = null;
    if ($cachedStory !== null) {
        return $cachedStory;
    }

    $defaultStory = [
        'hero_image'    => 'assets/hero_brand_founder.jpg?v=2',
        'main_heading'  => 'Where Every Piece Tells A Story',
        'story_para_1'  => "Welcome to Ssrini, the brand of Adrish Creative. Our story began in Kolkata in 2004 with an institute dedicated to the art of handicrafts. By 2021, we expanded, opening our first manufacturing unit to create and share the beautiful work of Bengal's artisans with the world.",
        'story_para_2'  => "We specialize in a wide range of handcrafted items—from designer kurtis and blouses to handmade jewellery and home decor. Our mission is to empower women artisans in Bengal, giving them a global platform for their talent. Every product you purchase directly supports this community and helps preserve the rich legacy of Bengal's handicraft.",
        'story_para_3'  => "Each piece is a work of art, made with care and delivered right to your doorstep.",
        'story_subtext' => "Discover authentic, handcrafted products from anywhere in India and soon, around the world.",
        'stat_1_num'    => '20+',
        'stat_1_text'   => 'years of heritage',
        'stat_2_num'    => '2004',
        'stat_2_text'   => 'founded in Kolkata',
        'stat_3_num'    => '2021',
        'stat_3_text'   => 'ATELIER',
        'stat_4_num'    => '4.9★',
        'stat_4_text'   => 'verified craft rating'
    ];

    $story = $defaultStory;

    if (isset($pdo) && $pdo instanceof PDO) {
        try {
            $stmt = $pdo->query("SELECT * FROM about_content ORDER BY id ASC LIMIT 1");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                foreach ($defaultStory as $key => $defaultVal) {
                    if (isset($row[$key]) && $row[$key] !== null && trim((string)$row[$key]) !== '') {
                        $story[$key] = trim((string)$row[$key]);
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('About story query error: ' . $e->getMessage());
        }
    }

    // Resolve hero image url
    $story['hero_image_url'] = resolve_about_image_url($story['hero_image']);

    $cachedStory = $story;
    return $cachedStory;
}

/**
 * Fetch all active awards formatted for the React AwardSlider component
 * @return array<int, array{id: int, name: string, affiliation: string, quote: string, imageSrc: string, thumbnailSrc: string}>
 */
function get_about_awards_data(): array
{
    global $pdo;
    static $cachedAwards = null;
    if ($cachedAwards !== null) {
        return $cachedAwards;
    }

    $defaultAwards = [
        [
            'id'           => 1,
            'name'         => 'Bengal Master Craftsperson Honor',
            'affiliation'  => 'Directorate of Textiles & Handicrafts · 2022',
            'quote'        => 'Awarded for exceptional mastery in preserving indigenous Kantha needlework and pioneering sustainable livelihoods for rural women artisans across Bengal.',
            'imageSrc'     => '../landing/assets/images/IMG_0685.jpg',
            'thumbnailSrc' => '../landing/assets/images/IMG_0685.jpg'
        ],
        [
            'id'           => 2,
            'name'         => 'Women Artisan Empowerment Excellence',
            'affiliation'  => 'Eastern India Heritage Craft Guild · 2021',
            'quote'        => "Recognizing two decades of grassroots leadership, expanding from a 2004 artisan training institute into a global stage for Bengal's female handloom weavers.",
            'imageSrc'     => '../landing/assets/images/IMG_0688.jpg',
            'thumbnailSrc' => '../landing/assets/images/IMG_0688.jpg'
        ],
        [
            'id'           => 3,
            'name'         => 'National Handloom & Textile Revival Award',
            'affiliation'  => 'All India Artisanal Guild · 2023',
            'quote'        => 'Conferred for innovation in heirloom fabric restoration, marrying generational Bengal motifs with modern designer kurtis, blouses, and haute couture.',
            'imageSrc'     => '../landing/assets/images/IMG_0692.jpg',
            'thumbnailSrc' => '../landing/assets/images/IMG_0692.jpg'
        ],
        [
            'id'           => 4,
            'name'         => 'Kolkata Craft Atelier & Design Leadership',
            'affiliation'  => 'Bengal Chamber of Creative Arts · 2020',
            'quote'        => 'Honoring the launch of the dedicated Kolkata manufacturing atelier, setting benchmarks in artisanal jewellery, home decor, and ethical craftsmanship.',
            'imageSrc'     => '../landing/assets/images/IMG_0696.jpg',
            'thumbnailSrc' => '../landing/assets/images/IMG_0696.jpg'
        ],
        [
            'id'           => 5,
            'name'         => 'Sustainable Craft & Rural Artisan Initiative',
            'affiliation'  => 'National Guild for Sustainable Handcrafts · 2024',
            'quote'        => "Celebrating 20 years of Adrish Creative: empowering over 150+ master craftswomen and delivering Bengal's living heritage to doorsteps worldwide.",
            'imageSrc'     => '../landing/assets/images/IMG_0702.jpg',
            'thumbnailSrc' => '../landing/assets/images/IMG_0702.jpg'
        ]
    ];

    $awards = [];

    if (isset($pdo) && $pdo instanceof PDO) {
        try {
            $stmt = $pdo->query("SELECT id, title, affiliation, quote, image_path FROM about_awards WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                foreach ($rows as $row) {
                    $imgUrl = resolve_about_image_url((string)$row['image_path']);
                    $awards[] = [
                        'id'           => (int)$row['id'],
                        'name'         => (string)$row['title'],
                        'affiliation'  => (string)$row['affiliation'],
                        'quote'        => (string)$row['quote'],
                        'imageSrc'     => $imgUrl,
                        'thumbnailSrc' => $imgUrl
                    ];
                }
            }
        } catch (Throwable $e) {
            error_log('About awards query error: ' . $e->getMessage());
        }
    }

    if (empty($awards)) {
        $awards = $defaultAwards;
    }

    $cachedAwards = $awards;
    return $cachedAwards;
}

/**
 * Fetch all active testimonials/reviews formatted for the StaggerTestimonials component
 * @return array<int, array{id: int, tempId: int, testimonial: string, by: string, rating: int, imgSrc: string}>
 */
function get_about_reviews_data(): array
{
    global $pdo;
    static $cachedReviews = null;
    if ($cachedReviews !== null) {
        return $cachedReviews;
    }

    $defaultReviews = [
        [
            'id'          => 1,
            'testimonial' => "The craftsmanship of SSRINI is simply beyond compare. I ordered a bespoke oxidized silver choker and a handloom kurti for Durga Puja. The delicate detailing, pure fabric quality, and authentic Bengali heritage feel make every piece worth treasuring.",
            'by'          => "Ananya Chatterjee, Kolkata (Verified Patron)",
            'rating'      => 5,
            'imgSrc'      => '../landing/assets/images/IMG_0685.jpg'
        ],
        [
            'id'          => 2,
            'testimonial' => "Been visiting their Behala studio since their Adrish Creative institute days. The warmth and patience of the team are remarkable. Knowing that each kurti and home decor artifact directly empowers women artisans across Bengal makes buying from SSRINI so meaningful.",
            'by'          => "Rupali Sengupta, New Alipore (Art Enthusiast)",
            'rating'      => 5,
            'imgSrc'      => '../landing/assets/images/IMG_0686.jpg'
        ],
        [
            'id'          => 3,
            'testimonial' => "Ordered their terracotta antiquity vessel and brass filigree earrings online to Bengaluru. The packaging was immaculate, doorstep delivery swift, and the piece itself looks like a museum heirloom in our living room. Truly magnificent artisanal work.",
            'by'          => "Dr. Meenakshi Sundaram, Bengaluru (Art Collector)",
            'rating'      => 5,
            'imgSrc'      => '../landing/assets/images/IMG_0688.jpg'
        ],
        [
            'id'          => 4,
            'testimonial' => "Their collection of designer blouses and handloom kurtis is exquisite and exclusive. The cuts and stitching are impeccable, and the fabric breathes so naturally. You cannot find this level of handcrafted luxury in ordinary commercial stores.",
            'by'          => "Priyanka Banerjee, Kolkata (Boutique Patron)",
            'rating'      => 5,
            'imgSrc'      => '../landing/assets/images/IMG_0689.jpg'
        ],
        [
            'id'          => 5,
            'testimonial' => "The Tarakasi filigree and gold-plated rings are divine. The intricate wirework speaks of generational skill that is rare to find today. SSRINI is putting Bengal's finest master craftsmanship on the national map!",
            'by'          => "Shreya Mukherjee, Mumbai (Verified Buyer)",
            'rating'      => 5,
            'imgSrc'      => '../landing/assets/images/IMG_0690.jpg'
        ],
        [
            'id'          => 6,
            'testimonial' => "A pioneer brand in South Kolkata. Having seen their journey from an artisanal craft institute in 2004 to a full manufacturing atelier in 2021, their dedication to ethical craft and empowering rural artisans has remained steadfast and inspirational.",
            'by'          => "Debjani Roy, Behala (Guild Supporter)",
            'rating'      => 5,
            'imgSrc'      => '../landing/assets/images/IMG_0691.jpg'
        ],
        [
            'id'          => 7,
            'testimonial' => "Exceptional customer experience from WhatsApp inquiry to doorstep delivery. The Mulberry silk stole and handmade accessories are heirloom-tier. Ssrini is our go-to gifting partner for every family celebration.",
            'by'          => "Sunita Aggarwal, New Delhi (Heritage Connoisseur)",
            'rating'      => 5,
            'imgSrc'      => '../landing/assets/images/IMG_0692.jpg'
        ],
        [
            'id'          => 8,
            'testimonial' => "Every thread and motif tells an authentic story of rural Bengal. As someone passionate about ethical craft, SSRINI represents the highest standard of conscious luxury and empowering female artisans.",
            'by'          => "Moumita Sen, Kolkata (Patron & Historian)",
            'rating'      => 5,
            'imgSrc'      => '../landing/assets/images/IMG_0696.jpg'
        ],
        [
            'id'          => 9,
            'testimonial' => "We furnished our living gallery with SSRINI terracotta sculptures and Dokra brass wall art. The compliments from guests never cease. Unrivaled handcrafted authenticity.",
            'by'          => "Rajiv & Swati Das, Salt Lake (Gallery Patrons)",
            'rating'      => 5,
            'imgSrc'      => '../landing/assets/images/IMG_0702.jpg'
        ]
    ];

    $reviews = [];

    if (isset($pdo) && $pdo instanceof PDO) {
        try {
            $stmt = $pdo->query("SELECT id, author_name, author_location, rating, review_text, avatar_image FROM reviews WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $idx = 0;
                foreach ($rows as $row) {
                    $imgUrl = resolve_about_image_url((string)($row['avatar_image'] ?? ''));
                    if (empty($imgUrl)) {
                        $imgUrl = '../landing/assets/images/IMG_0685.jpg';
                    }

                    $byParts = [];
                    if (!empty($row['author_name'])) {
                        $byParts[] = trim($row['author_name']);
                    }
                    if (!empty($row['author_location'])) {
                        $byParts[] = trim($row['author_location']);
                    }
                    $byLine = implode(', ', $byParts);
                    if (empty($byLine)) {
                        $byLine = 'Verified Patron';
                    }

                    $reviews[] = [
                        'id'          => (int)$row['id'],
                        'tempId'      => $idx++,
                        'testimonial' => (string)$row['review_text'],
                        'by'          => $byLine,
                        'rating'      => max(1, min(5, (int)($row['rating'] ?? 5))),
                        'imgSrc'      => $imgUrl
                    ];
                }
            }
        } catch (Throwable $e) {
            error_log('About reviews query error: ' . $e->getMessage());
        }
    }

    if (empty($reviews)) {
        $idx = 0;
        foreach ($defaultReviews as $dr) {
            $reviews[] = array_merge($dr, ['tempId' => $idx++]);
        }
    }

    $cachedReviews = $reviews;
    return $cachedReviews;
}

