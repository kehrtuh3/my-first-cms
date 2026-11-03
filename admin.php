<?php

require("config.php");
session_start();
$action = isset($_GET['action']) ? $_GET['action'] : "";
$username = isset($_SESSION['username']) ? $_SESSION['username'] : "";

if ($action != "login" && $action != "logout" && !$username) {
    login();
    exit;
}

switch ($action) {
    case 'login':
        login();
        break;
    case 'logout':
        logout();
        break;
    case 'newArticle':
        newArticle();
        break;
    case 'editArticle':
        editArticle();
        break;
    case 'deleteArticle':
        deleteArticle();
        break;
    case 'listCategories':
        listCategories();
        break;
    case 'newCategory':
        newCategory();
        break;
    case 'editCategory':
        editCategory();
        break;
    case 'deleteCategory':
        deleteCategory();
        break;
    case 'listUsers':
        listUsers();
        break;
    case 'newUser':
        newUser();
        break;
    case 'editUser':
        editUser();
        break;
    case 'deleteUser':
        deleteUser();
        break;
    default:
        listArticles();
}

/**
 * Авторизация пользователя (админа) -- установка значения в сессию
 */
function authenticate($username, $password)
{
    $wrongCredentials = "Неправильный логин или пароль, попробуйте ещё раз.";

    // Логин "admin": как раньше, по данным из config.php (БД не трогаем)
    if ($username === CmsConfig::$admin_username) {
        if (hash_equals((string) CmsConfig::$admin_password, $password)) {
            return array(CmsConfig::$admin_username, null);
        }
        return array(null, $wrongCredentials);
    }

    // Любой другой логин: проверяем по таблице users
    $user = User::getByLogin($username);
    if (!$user || !$user->checkPassword($password)) {
        return array(null, $wrongCredentials);
    }

    // Логин и пароль верны, но пользователь неактивен: не авторизуем, выводим уведомление
    if (!$user->active) {
        return array(null, "Ваша учётная запись неактивна, вход запрещён. Обратитесь к администратору.");
    }

    return array($user->login, null);
}

function login()
{
    $results = array();
    $results['pageTitle'] = "Admin Login | Widget News";

    if (isset($_POST['login'])) {
        $username = isset($_POST['username']) ? trim($_POST['username']) : "";
        $password = isset($_POST['password']) ? (string) $_POST['password'] : "";

        list($sessionUsername, $errorMessage) = authenticate($username, $password);

        if ($sessionUsername !== null) {
            session_regenerate_id(true);
            $_SESSION['username'] = $sessionUsername;
            header("Location: admin.php");
        } else {
            $results['errorMessage'] = $errorMessage;      // loginForm.php уже умеет это выводить
            require(CmsConfig::$template_path . "/admin/loginForm.php");
        }
    } else {
        require(CmsConfig::$template_path . "/admin/loginForm.php");
    }
}


function logout()
{
    unset($_SESSION['username']);
    header("Location: admin.php");
}


function newArticle()
{
    $results = array();
    $results['pageTitle'] = "New Article";
    $results['formAction'] = "newArticle";

    if (isset($_POST['saveChanges'])) {
        //            echo "<pre>";
        //            print_r($results);
        //            print_r($_POST);
        //            echo "<pre>";
        //            В $_POST данные о статье сохраняются корректно
        // Пользователь получает форму редактирования статьи: сохраняем новую статью
        $article = new Article();
        $article->storeFormValues($_POST);
        //            echo "<pre>";
        //            print_r($article);
        //            echo "<pre>";
        //            А здесь данные массива $article уже неполные(есть только Число от даты, категория и полный текст статьи)
        $article->insert();
        header("Location: admin.php?status=changesSaved");
    } elseif (isset($_POST['cancel'])) {

        // Пользователь сбросил результаты редактирования: возвращаемся к списку статей
        header("Location: admin.php");
    } else {

        // Пользователь еще не получил форму редактирования: выводим форму
        $results['article'] = null;
        $data = Category::getList();
        $results['categories'] = $data['results'];
        require(CmsConfig::$template_path . "/admin/editArticle.php");
    }
}


/**
 * Редактирование статьи
 *
 * @return null
 */
function editArticle()
{
    $results = array();
    $results['pageTitle'] = "Edit Article";
    $results['formAction'] = "editArticle";

    if (isset($_POST['saveChanges'])) {

        // Пользователь получил форму редактирования статьи: сохраняем изменения
        if (!$article = Article::getById((int)$_POST['articleId'])) {
            header("Location: admin.php?error=articleNotFound");
            return;
        }

        $article->storeFormValues($_POST);
        $article->update();
        header("Location: admin.php?status=changesSaved");
    } elseif (isset($_POST['cancel'])) {

        // Пользователь отказался от результатов редактирования: возвращаемся к списку статей
        header("Location: admin.php");
    } else {

        // Пользвоатель еще не получил форму редактирования: выводим форму
        $results['article'] = Article::getById((int)$_GET['articleId']);
        $data = Category::getList();
        $results['categories'] = $data['results'];
        require(CmsConfig::$template_path . "/admin/editArticle.php");
    }
}


function deleteArticle()
{
    if (!$article = Article::getById((int)$_GET['articleId'])) {
        header("Location: admin.php?error=articleNotFound");
        return;
    }

    $article->delete();
    header("Location: admin.php?status=articleDeleted");
}


function listArticles()
{
    $results = array();

    $data = Article::getList();
    $results['articles'] = $data['results'];
    $results['totalRows'] = $data['totalRows'];

    $data = Category::getList();
    $results['categories'] = array();
    foreach ($data['results'] as $category) {
        $results['categories'][$category->id] = $category;
    }

    $results['pageTitle'] = "Все статьи";

    if (isset($_GET['error'])) { // вывод сообщения об ошибке (если есть)
        if ($_GET['error'] == "articleNotFound") {
            $results['errorMessage'] = "Error: Article not found.";
        }
    }

    if (isset($_GET['status'])) { // вывод сообщения (если есть)
        if ($_GET['status'] == "changesSaved") {
            $results['statusMessage'] = "Your changes have been saved.";
        }
        if ($_GET['status'] == "articleDeleted") {
            $results['statusMessage'] = "Article deleted.";
        }
    }

    require(CmsConfig::$template_path . "/admin/listArticles.php");
}

function listCategories()
{
    $results = array();
    $data = Category::getList();
    $results['categories'] = $data['results'];
    $results['totalRows'] = $data['totalRows'];
    $results['pageTitle'] = "Article Categories";

    if (isset($_GET['error'])) {
        if ($_GET['error'] == "categoryNotFound") {
            $results['errorMessage'] = "Error: Category not found.";
        }
        if ($_GET['error'] == "categoryContainsArticles") {
            $results['errorMessage'] = "Error: Category contains articles. "
                . "Delete the articles, or assign them to another category, "
                . "before deleting this category.";
        }
    }

    if (isset($_GET['status'])) {
        if ($_GET['status'] == "changesSaved") {
            $results['statusMessage'] = "Your changes have been saved.";
        }
        if ($_GET['status'] == "categoryDeleted") {
            $results['statusMessage'] = "Category deleted.";
        }
    }

    require(CmsConfig::$template_path . "/admin/listCategories.php");
}


function newCategory()
{
    $results = array();
    $results['pageTitle'] = "New Article Category";
    $results['formAction'] = "newCategory";

    if (isset($_POST['saveChanges'])) {

        // User has posted the category edit form: save the new category
        $category = new Category;
        $category->storeFormValues($_POST);
        $category->insert();
        header("Location: admin.php?action=listCategories&status=changesSaved");
    } elseif (isset($_POST['cancel'])) {

        // User has cancelled their edits: return to the category list
        header("Location: admin.php?action=listCategories");
    } else {

        // User has not posted the category edit form yet: display the form
        $results['category'] = new Category;
        require(CmsConfig::$template_path . "/admin/editCategory.php");
    }
}


function editCategory()
{
    $results = array();
    $results['pageTitle'] = "Edit Article Category";
    $results['formAction'] = "editCategory";

    if (isset($_POST['saveChanges'])) {

        // User has posted the category edit form: save the category changes

        if (!$category = Category::getById((int)$_POST['categoryId'])) {
            header("Location: admin.php?action=listCategories&error=categoryNotFound");
            return;
        }

        $category->storeFormValues($_POST);
        $category->update();
        header("Location: admin.php?action=listCategories&status=changesSaved");
    } elseif (isset($_POST['cancel'])) {

        // User has cancelled their edits: return to the category list
        header("Location: admin.php?action=listCategories");
    } else {

        // User has not posted the category edit form yet: display the form
        $results['category'] = Category::getById((int)$_GET['categoryId']);
        require(CmsConfig::$template_path . "/admin/editCategory.php");
    }
}


function deleteCategory()
{
    if (!$category = Category::getById((int)$_GET['categoryId'])) {
        header("Location: admin.php?action=listCategories&error=categoryNotFound");
        return;
    }

    $articles = Article::getList(1000000, $category->id);

    if ($articles['totalRows'] > 0) {
        header("Location: admin.php?action=listCategories&error=categoryContainsArticles");
        return;
    }

    $category->delete();
    header("Location: admin.php?action=listCategories&status=categoryDeleted");
}
function listUsers()
{
    $results = array();
    $data = User::getList();
    $results['users'] = $data['results'];
    $results['totalRows'] = $data['totalRows'];
    $results['pageTitle'] = "Users";

    if (isset($_GET['error']) && $_GET['error'] == "userNotFound") {
        $results['errorMessage'] = "Пользователь не найден.";
    }
    if ($_GET['error'] == "cannotDeleteSelf") {
            $results['errorMessage'] = "Нельзя удалить пользователя, под которым вы сейчас вошли.";
    }
    if (isset($_GET['status'])) {
        if ($_GET['status'] == "changesSaved") $results['statusMessage'] = "Your changes have been saved.";
        if ($_GET['status'] == "userDeleted")  $results['statusMessage'] = "User deleted.";
    }
    require(CmsConfig::$template_path . "/admin/listUsers.php");
}

function validateUser(User $user, $passwordRequired)
{
    $errors = array();
    if ($user->login === null || $user->login === "") {
        $errors[] = "Логин не может быть пустым!";
    } elseif (mb_strtolower($user->login) === mb_strtolower(CmsConfig::$admin_username)) {
        $errors[] = "Этот логин принадлежит администратору";  
    } else {
        $existing = User::getByLogin($user->login);
        if ($existing && (int) $existing->id !== (int) $user->id) {
            $errors[] = "Пользователь с таким логином уже существует";
        }
    }
    if ($passwordRequired && !$user->password) {
        $errors[] = "Для нового пользователя нужно задать пароль";
    }
    return $errors;
}

function newUser()
{
    $results = array();
    $results['pageTitle'] = "New User";
    $results['formAction'] = "newUser";
    if (isset($_POST['saveChanges'])) {
        $user = new User;
        $user->storeFormValues($_POST);

        $errors = validateUser($user, true);
        if ($errors) {                                   
            $results['user'] = $user;
            $results['errorMessage'] = implode("<br>", $errors);
            require(CmsConfig::$template_path . "/admin/editUser.php");
            return;
        }
        $user->insert();
        header("Location: admin.php?action=listUsers&status=changesSaved");
    } elseif (isset($_POST['cancel'])) {
        header("Location: admin.php?action=listUsers");
    } else {
        $results['user'] = new User;
        require(CmsConfig::$template_path . "/admin/editUser.php");
    }
}

function editUser()
{
    $results = array();
    $results['pageTitle'] = "Edit User";
    $results['formAction'] = "editUser";

    if (isset($_POST['saveChanges'])) {
        if (!$user = User::getById((int)$_POST['userId'])) {
            header("Location: admin.php?action=listUsers&error=userNotFound");
            return;
        }
        $user->storeFormValues($_POST);                  
        $errors = validateUser($user, false);
        if ($errors) {
            $results['user'] = $user;
            $results['errorMessage'] = implode("<br>", $errors);
            require(CmsConfig::$template_path . "/admin/editUser.php");
            return;
        }
        $user->update();
        header("Location: admin.php?action=listUsers&status=changesSaved");
    } elseif (isset($_POST['cancel'])) {
        header("Location: admin.php?action=listUsers");
    } else {
        $results['user'] = User::getById((int)$_GET['userId']);
        if (!$results['user']) {
            header("Location: admin.php?action=listUsers&error=userNotFound");
            return;
        }
        require(CmsConfig::$template_path . "/admin/editUser.php");
    }
}

function deleteUser()
{
    if (!$user = User::getById((int)$_GET['userId'])) {
        header("Location: admin.php?action=listUsers&error=userNotFound");
        return;
    }
    if (isset($_SESSION['username']) && $user->login === $_SESSION['username']) {
        header("Location: admin.php?action=listUsers&error=cannotDeleteSelf");
        return;
    }
    $user->delete();
    header("Location: admin.php?action=listUsers&status=userDeleted");
}