<?php
require_once('helpers.php');
require_once('includes/functions.inc.php');
require_once('includes/db_connect.inc.php');

session_start();
check_authentication();
$user_name = $_SESSION['user']['login'];
$avatar = $_SESSION['user']['avatar'];
get_like($con);

// Получает спиок типов контента для дальнейшего вывода на странице;
$content_types = select_query($con, 'SELECT * FROM content_types');
// Проверяет наличие параметра запроса: если параметр есть, фильтрует по нему данные из БД;
$post_type = filter_input(INPUT_GET, 'post-type', FILTER_VALIDATE_INT);
if ($post_type) {
    $post_type_query = 'WHERE p.content_type_id = ' . $post_type;
} else {
    $post_type = null;
    $post_type_query = null;
}

// Получает из параметра запроса тип сортировки;
$sorting_type = filter_input(INPUT_GET, 'sorting-type');
$sorting_direction = filter_input(INPUT_GET, 'sorting-direction');

if (!$sorting_direction) {
    $sorting_type = 'popular';
    $sorting_direction = 'desc';
}

if ($sorting_type == 'popular') {
    $sorting_order = 'ORDER BY p.views ' . $sorting_direction;
} elseif ($sorting_type == 'likes') {
    $sorting_order = 'ORDER BY p.likes_count ' . $sorting_direction;
} elseif ($sorting_type == 'date') {
    $sorting_order = 'ORDER BY p.date_add ' . $sorting_direction;
} else {
    $sorting_order = 'ORDER BY p.views ' . $sorting_direction;
}

$page_limit = 9;
$posts_count = select_query($con, "SELECT COUNT(*) FROM posts", 'row');
$current_page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);

if (empty($current_page)) {
    $current_page = 1;
}

if ($posts_count > 9) {
    $pages_count = ceil($posts_count/$page_limit);
    $page_offset = ($current_page - 1) * $page_limit;
    $page_prev = $current_page;
    $page_next = $current_page;
    --$page_prev;
    ++$page_next;
    $posts = select_query($con, 'SELECT *, p.id as post_id, u.*, ct.type_name, ct.class_name FROM posts p INNER JOIN users u ON u.id = p.post_author_id INNER JOIN content_types ct ON ct.id = p.content_type_id ' . $post_type_query . ' ' . $sorting_order . ' LIMIT ' . $page_limit . ' OFFSET ' . $page_offset);
} else {
    $pages_count = 0;
    $posts = select_query($con, 'SELECT p.*, u.*, ct.type_name, ct.class_name FROM posts p INNER JOIN users u ON u.id = p.post_author_id INNER JOIN content_types ct ON ct.id = p.content_type_id ' . $post_type_query . ' ' . $sorting_order);
}

if (!$posts) {
    open_404_page($user_name, $avatar);
}

// Передает данные из БД в шаблоны;
$page_content = include_template('main.php', [
    'posts' => $posts,
    'content_types' => $content_types,
    'post_type' => $post_type,
    'sorting_type' => $sorting_type,
    'sorting_direction' => $sorting_direction,
    'page_prev' => $page_prev,
    'page_next' => $page_next,
    'current_page' => $current_page,
    'pages_count' => $pages_count,
]);

$layout_content = include_template('layout.php', [
  'user_name' => $user_name,
  'avatar' => $avatar,
  'title' => 'readme: популярное',
  'content' => $page_content,
]);

echo($layout_content);
