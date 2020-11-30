<?php
date_default_timezone_set('Asia/Yekaterinburg');
require_once('helpers.php');
require_once('includes/functions.inc.php');
require_once('includes/db_connect.inc.php');

session_start();
check_authentication();
$is_auth = isset($_SESSION);
$user_name = $_SESSION['user']['login'];
$avatar = $_SESSION['user']['avatar'];

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

// SELECT COUNT(*) FROM likes WHERE (post_id) IN (1, 2, 3, 4) GROUP BY post_id;
// SELECT p.*, u.login, u.avatar, ct.type_name, ct.class_name FROM posts p INNER JOIN likes l ON l.post_id = p.id INNER JOIN users u ON u.id = p.post_author_id INNER JOIN content_types ct ON ct.id = p.content_type_id ORDER BY p.views ASC;
// SELECT COUNT(*) FROM likes WHERE likes.post_id = 1;
// SELECT p.*, u.login, u.avatar, ct.type_name, ct.class_name FROM posts p INNER JOIN likes l ON l.post_id = p.id INNER JOIN users u ON u.id = p.post_author_id INNER JOIN content_types ct ON ct.id = p.content_type_id ORDER BY p.views ASC;

// Получает список постов (в зависимости от выбранного типа контента);
$posts = select_query($con, 'SELECT p.*, u.login, u.avatar, ct.type_name, ct.class_name FROM posts p INNER JOIN users u ON u.id = p.post_author_id INNER JOIN content_types ct ON ct.id = p.content_type_id ' . $post_type_query . ' ' . $sorting_order);

if (!$posts) {
    open_404_page($is_auth, $user_name);
}

// Передает данные из БД в шаблоны;
$page_content = include_template('main.php', [
    'posts' => $posts,
    'content_types' => $content_types,
    'post_type' => $post_type,
    'sorting_type' => $sorting_type,
    'sorting_direction' => $sorting_direction,
]);

$layout_content = include_template('layout.php', [
  'is_auth' => $is_auth,
  'user_name' => $user_name,
  'avatar' => $avatar,
  'title' => 'readme: популярное',
  'content' => $page_content,
]);

echo($layout_content);