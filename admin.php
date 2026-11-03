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
    case 'listSubcategories':
        listSubcategories();
        break;
    case 'newSubcategory':
        newSubcategory();
        break;
    case 'editSubcategory':
        editSubcategory();
        break;
    case 'deleteSubcategory':
        deleteSubcategory();
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
    if ($username === CmsConfig::$admin_username) {
        if (hash_equals((string) CmsConfig::$admin_password, $password)) {
            return array(CmsConfig::$admin_username, null);
        }
        return array(null, $wrongCredentials);
    }
    $user = User::getByLogin($username);
    if (!$user || !$user->checkPassword($password)) {
        return array(null, $wrongCredentials);
    }
    if (!$user->active) {
        return array(null, "Учётная запись неактивна.");
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
            $results['errorMessage'] = $errorMessage;    
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

/**
 * Проверка данных статьи. Ошибки собираются в массив: новые правила добавляются по одному.
 */
function validateArticle(Article $article)
{
    $errors = array();

    if ($article->subcategoryId) {
        $subcategory = Subcategory::getById($article->subcategoryId);

        if (!$subcategory) {
            $errors[] = "Ошибка: Выбранная подкатегория не существует!";
        } elseif ((int) $subcategory->categoryId !== (int) $article->categoryId) {
            $errors[] = "Ошибка: Выбранная категория не соответствует подкатегории!";
        }
    }
    return $errors;
}

/** Вывод формы статьи: готовит списки категорий и подкатегорий для селектов */
function showArticleForm($results)
{
    $data = Category::getList();
    $results['categories'] = $data['results'];

    // подкатегории, сгруппированные по id категории, для <optgroup>
    $results['subcategoriesByCategory'] = array();
    $data = Subcategory::getList();
    foreach ($data['results'] as $subcategory) {
        $results['subcategoriesByCategory'][$subcategory->categoryId][] = $subcategory;
    }
    require(CmsConfig::$template_path . "/admin/editArticle.php");
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
        $errors = validateArticle($article);
        if ($errors) {
            $results['article'] = $article;                         // форма откроется с введёнными данными
            $results['errorMessage'] = implode("<br>", $errors);
            showArticleForm($results);
            return;
        }
        $article->insert();
        header("Location: admin.php?status=changesSaved");
    } elseif (isset($_POST['cancel'])) {

        // Пользователь сбросил результаты редактирования: возвращаемся к списку статей
        header("Location: admin.php");
    } else {

        // Пользователь еще не получил форму редактирования: выводим форму
        $results['article'] = null;
        showArticleForm($results);
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
        $errors = validateArticle($article);
        if ($errors) {
            $results['article'] = $article;
            $results['errorMessage'] = implode("<br>", $errors);
            showArticleForm($results);
            return;
        }
        $article->update();
        header("Location: admin.php?status=changesSaved");
    } elseif (isset($_POST['cancel'])) {

        // Пользователь отказался от результатов редактирования: возвращаемся к списку статей
        header("Location: admin.php");
    } else {

        // Пользвоатель еще не получил форму редактирования: выводим форму
        $results['article'] = Article::getById((int)$_GET['articleId']);
        showArticleForm($results);
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
        if ($_GET['error'] == "categoryContainsSubcategories") {
            $results['errorMessage'] = "Ошибка: в категории есть подкатегории. Сначала удалите их или перенесите в другую категорию.";
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

    $subcategories = Subcategory::getList(1, $category->id);
    if ($subcategories['totalRows'] > 0) {
        header("Location: admin.php?action=listCategories&error=categoryContainsSubcategories");
        return;
    }

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

function listSubcategories()
{
    $results = array();
    $data = Subcategory::getList(1000000, null, "categoryId ASC, name ASC");
    $results['subcategories'] = $data['results'];
    $results['totalRows'] = $data['totalRows'];
    $results['pageTitle'] = "Article Subcategories";

    // id => категория: чтобы в таблице показать название категории
    $data = Category::getList();
    $results['categories'] = array();
    foreach ($data['results'] as $category) { $results['categories'][$category->id] = $category; }

    if (isset($_GET['error'])) {
        if ($_GET['error'] == "subcategoryNotFound")
            $results['errorMessage'] = "Ошибка: подкатегория не найдена.";
        if ($_GET['error'] == "subcategoryContainsArticles")
            $results['errorMessage'] = "Ошибка: в подкатегории есть статьи. Сначала удалите их или перенесите в другую подкатегорию.";
    }
    if (isset($_GET['status'])) {
        if ($_GET['status'] == "changesSaved")       $results['statusMessage'] = "Your changes have been saved.";
        if ($_GET['status'] == "subcategoryDeleted") $results['statusMessage'] = "Subcategory deleted.";
    }
    require(CmsConfig::$template_path . "/admin/listSubcategories.php");
}

function validateSubcategory(Subcategory $subcategory)
{
    $errors = array();
    if (trim((string) $subcategory->name) === "") {
        $errors[] = "Ошибка: название подкатегории не может быть пустым!";
    }
    if (!$subcategory->categoryId || !Category::getById($subcategory->categoryId)) {
        $errors[] = "Ошибка: нужно выбрать существующую категорию!";
    }
    return $errors;
}

function showSubcategoryForm($results)
{
    $data = Category::getList();
    $results['categories'] = $data['results'];       // для <select> категорий
    require(CmsConfig::$template_path . "/admin/editSubcategory.php");
}

function newSubcategory()
{
    $results = array();
    $results['pageTitle'] = "New Article Subcategory";
    $results['formAction'] = "newSubcategory";

    if (isset($_POST['saveChanges'])) {
        $subcategory = new Subcategory;
        $subcategory->storeFormValues($_POST);

        $errors = validateSubcategory($subcategory);
        if ($errors) {
            $results['subcategory'] = $subcategory;
            $results['errorMessage'] = implode("<br>", $errors);
            showSubcategoryForm($results);
            return;
        }
        $subcategory->insert();
        header("Location: admin.php?action=listSubcategories&status=changesSaved");
    } elseif (isset($_POST['cancel'])) {
        header("Location: admin.php?action=listSubcategories");
    } else {
        $results['subcategory'] = new Subcategory;
        showSubcategoryForm($results);
    }
}

function editSubcategory()
{
    $results = array();
    $results['pageTitle'] = "Edit Article Subcategory";
    $results['formAction'] = "editSubcategory";

    if (isset($_POST['saveChanges'])) {
        if (!$subcategory = Subcategory::getById((int)$_POST['subcategoryId'])) {
            header("Location: admin.php?action=listSubcategories&error=subcategoryNotFound");
            return;
        }
        $subcategory->storeFormValues($_POST);

        $errors = validateSubcategory($subcategory);
        if ($errors) {
            $results['subcategory'] = $subcategory;
            $results['errorMessage'] = implode("<br>", $errors);
            showSubcategoryForm($results);
            return;
        }
        $subcategory->update();
        header("Location: admin.php?action=listSubcategories&status=changesSaved");
    } elseif (isset($_POST['cancel'])) {
        header("Location: admin.php?action=listSubcategories");
    } else {
        $results['subcategory'] = Subcategory::getById((int)$_GET['subcategoryId']);
        if (!$results['subcategory']) {
            header("Location: admin.php?action=listSubcategories&error=subcategoryNotFound");
            return;
        }
        showSubcategoryForm($results);
    }
}

function deleteSubcategory()
{
    if (!$subcategory = Subcategory::getById((int)$_GET['subcategoryId'])) {
        header("Location: admin.php?action=listSubcategories&error=subcategoryNotFound");
        return;
    }
    // есть ли статьи в подкатегории (последний аргумент: фильтр по подкатегории)
    $articles = Article::getList(1, null, "publicationDate DESC", false, $subcategory->id);
    if ($articles['totalRows'] > 0) {
        header("Location: admin.php?action=listSubcategories&error=subcategoryContainsArticles");
        return;
    }
    $subcategory->delete();
    header("Location: admin.php?action=listSubcategories&status=subcategoryDeleted");
}