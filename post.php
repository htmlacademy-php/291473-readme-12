<?php
require_once('helpers.php');
require_once('includes/functions.inc.php');
require_once('includes/db_connect.inc.php');

session_start();
check_authentication();
$user_name = $_SESSION['user']['login'];
$avatar = $_SESSION['user']['avatar'];

$current_post_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Проверяет наличие параметра запроса;
if (!$current_post_id) {
    open_404_page($user_name, $avatar);
}

// Плучает пост за БД по ID запроса;
$post = select_query($con, 'SELECT p.*, u.login, u.date_add, u.avatar, ct.type_name, ct.class_name FROM posts p INNER JOIN users u ON u.id = p.post_author_id INNER JOIN content_types ct ON ct.id = p.content_type_id WHERE p.id = ' . $current_post_id, 'assoc');
// Проверяет наличие запрошенных данных в ответе от БД;
if (!$post) {
    open_404_page($user_name, $avatar);
}

// Получает id хештегов по id поста;
$hashtags_id = select_query($con, 'SELECT hashtag_id FROM post_hashtags WHERE post_id = ' . $post['id']);
$post_hashtags = array();

if (!empty($hashtags_id)) {
    $post_hashtags_line = get_hashtag_name($con, $hashtags_id);
    foreach ($post_hashtags_line as $post_hashtag_number => $post_hashtag) {
        $post_hashtags[$post_hashtag_number] = $post_hashtag;
    }
}

// Получает время регистрации пользователя;
$registration_time = get_post_interval($post['date_add'], 'на сайте');
// Получает id автора поста;
$post_author_id = select_query($con, 'SELECT post_author_id FROM posts WHERE posts.id = ' . $current_post_id, 'row');
// Получает общее количества постов автора открытого поста;
$author_posts_count = select_query($con, 'SELECT COUNT(*) FROM posts WHERE post_author_id = ' . $post_author_id, 'row');
// Получает общее количество подписчиков автора открытого поста;
$subscribers_count = select_query($con, 'SELECT COUNT(*) FROM subscriptions WHERE author_id = ' . $post_author_id, 'row');
// Передает данные из БД в шаблоны;
$post_content = include_template('post-' . $post['class_name'] .'.php', ['post' => $post, 'registration_time' => $registration_time,]);

$comment = $_POST['comment'] ?? '';
$post_id = $_POST['post-id'] ?? '';

if (isset($comment) && $post_id == $post['id']) {

    $post_id_validity = select_query($con, "SELECT * FROM posts WHERE id = '$post_id'");

    if (isset($post_id_validity) && mb_strlen($comment) > 4) {
        $date = date("Y-m-d H:i:s");
        $user_id = $_SESSION['user']['id'];
        $comment_trim = trim($comment, ' ');

        $comment_query = "INSERT INTO comments (date_add, content, comment_author_id, post_id) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($con, $comment_query);
        mysqli_stmt_bind_param($stmt, 'ssii', $date, $comment_trim, $user_id, $post_id);
        mysqli_stmt_execute($stmt);
    }
}

$page_content = include_template('post.php', [
    'post' => $post,
    'post_content' => $post_content,
    'registration_time' => $registration_time,
    'author_posts_count' => $author_posts_count,
    'subscribers_count' => $subscribers_count,
    'post_hashtags' => $post_hashtags,
    'comment' => $comment,
]);

$layout_content = include_template('layout.php', [
  'user_name' => $user_name,
  'avatar' => $avatar,
  'title' => 'readme: публикация',
  'content' => $page_content,
]);

echo($layout_content);