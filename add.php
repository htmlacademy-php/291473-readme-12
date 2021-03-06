<?php
require_once('helpers.php');
require_once('includes/functions.inc.php');
require_once('includes/db_connect.inc.php');

session_start();
check_authentication();
$user_name = $_SESSION['user']['login'];
$avatar = $_SESSION['user']['avatar'];

// Получает спиок типов контента для дальнейшего вывода на странице;
$content_types = select_query($con, 'SELECT * FROM content_types');

// Получает ID типа конента из параметра запроса;
$current_content_type_id = filter_input(INPUT_GET, 'post_type', FILTER_VALIDATE_INT);

// Получает ID типа контента, когда в адресной строке нет параметра запроса:
// при первом открытии add.php, после отправки формы и перехода на add.php;
if (!$current_content_type_id) {
    if (isset($_POST['content-type'])) {
        $current_content_type_id = intval($_POST['content-type']);
    } else {
        $current_content_type_id = 1;
    }
}

$fields_map = [
    'text-heading' => 'Заголовок. ',
    'text-content' => 'Публикация. ',
    'quote-heading' => 'Заголовок. ',
    'quote-content' => 'Цитата. ',
    'quote-author' => 'Автор. ',
    'photo-heading' => 'Заголовок. ',
    'photo-link' => 'Ссылка. ',
    'userpic-file-photo' => 'Изображение. ',
    'video-heading' => 'Заголовок. ',
    'video-link' => 'Ссылка. ',
    'link-heading' => 'Заголовок. ',
    'link-content' => 'Ссылка. ',
];

// Получает список ошибок для вывода в шаблоне формы;
$errors = check_validity($con, $current_content_type_id, $fields_map);

// Получает выбранный тип конента ;
$content_type = select_query($con, 'SELECT * FROM content_types WHERE id = ' . $current_content_type_id, 'assoc');

if (!$content_type) {
    open_404_page($user_name, $avatar);
}

// Передает данные из БД в шаблоны;
$add_content = include_template('add-' . $content_type['class_name'] . '.php', [
    'content_type' => $content_type,
    'errors' => $errors,
]);

// Передает данные из БД в шаблоны;
$page_content = include_template('add.php', [
    'add_content' => $add_content,
    'content_types' => $content_types,
    "current_content_type_id" => $current_content_type_id,
]);

$layout_content = include_template('layout.php', [
  'user_name' => $user_name,
  'avatar' => $avatar,
  'title' => 'readme: добавить публикацию',
  'content' => $page_content,
]);

echo($layout_content);
