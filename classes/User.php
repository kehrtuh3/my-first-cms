<?php
class User
{
    public $id = null;
    public $login = null;
    public $password = null;   // здесь ХЭШ пароля
    public $active = 1;

    public function __construct($data = array())
    {
        if (isset($data['id']))       $this->id = (int) $data['id'];
        if (isset($data['login']))    $this->login = trim($data['login']);
        if (isset($data['password'])) $this->password = $data['password'];
        if (isset($data['active']))   $this->active = (int) $data['active'];
    }

    /** Значения из формы. Пустой пароль в форме = "пароль не менять". */
    public function storeFormValues($params)
    {
        $this->id     = !empty($params['userId']) ? (int) $params['userId'] : $this->id;
        $this->login  = isset($params['login']) ? trim($params['login']) : $this->login;
        $this->active = isset($params['active']) ? (int) $params['active'] : $this->active;

        if (isset($params['password']) && $params['password'] !== '') {
            $this->password = password_hash($params['password'], PASSWORD_DEFAULT);
        }
    }

    private static function connect()
    {
        return new PDO(CmsConfig::$db_dsn, CmsConfig::$db_username, CmsConfig::$db_password);
    }

    public static function getById($id)
    {
        $conn = self::connect();
        $st = $conn->prepare("SELECT * FROM users WHERE id = :id");
        $st->bindValue(":id", $id, PDO::PARAM_INT);
        $st->execute();
        $row = $st->fetch();
        return $row ? new User($row) : null;
    }

    public static function getByLogin($login)     // главный метод для входа
    {
        $conn = self::connect();
        $st = $conn->prepare("SELECT * FROM users WHERE login = :login");
        $st->bindValue(":login", $login, PDO::PARAM_STR);
        $st->execute();
        $row = $st->fetch();
        return $row ? new User($row) : null;
    }

    public static function getList($numRows = 1000000, $order = "login ASC")
    {
        $conn = self::connect();
        $st = $conn->prepare("SELECT * FROM users ORDER BY $order LIMIT :numRows");
        $st->bindValue(":numRows", $numRows, PDO::PARAM_INT);
        $st->execute();
        $list = array();
        while ($row = $st->fetch()) { $list[] = new User($row); }
        $totalRows = $conn->query("SELECT COUNT(*) FROM users")->fetch();
        return array("results" => $list, "totalRows" => $totalRows[0]);
    }

    public function checkPassword($plainPassword)
    {
        return password_verify($plainPassword, (string) $this->password);
    }

    public function insert()
    {
        $conn = self::connect();
        $st = $conn->prepare("INSERT INTO users (login, password, active) VALUES (:login, :password, :active)");
        $st->bindValue(":login", $this->login, PDO::PARAM_STR);
        $st->bindValue(":password", $this->password, PDO::PARAM_STR);
        $st->bindValue(":active", $this->active, PDO::PARAM_INT);
        $st->execute();
        $this->id = $conn->lastInsertId();
    }

    public function update()
    {
        $conn = self::connect();
        $st = $conn->prepare("UPDATE users SET login=:login, password=:password, active=:active WHERE id=:id");
        $st->bindValue(":login", $this->login, PDO::PARAM_STR);
        $st->bindValue(":password", $this->password, PDO::PARAM_STR);
        $st->bindValue(":active", $this->active, PDO::PARAM_INT);
        $st->bindValue(":id", $this->id, PDO::PARAM_INT);
        $st->execute();
    }

    public function delete()
    {
        $conn = self::connect();
        $st = $conn->prepare("DELETE FROM users WHERE id = :id LIMIT 1");
        $st->bindValue(":id", $this->id, PDO::PARAM_INT);
        $st->execute();
    }
}