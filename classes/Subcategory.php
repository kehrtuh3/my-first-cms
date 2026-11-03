<?php
class Subcategory
{
    public $id = null;
    public $name = null;
    public $categoryId = null;

    public function __construct($data = array())
    {
        if (isset($data['id']))         $this->id = (int) $data['id'];
        if (isset($data['name']))       $this->name = $data['name'];
        if (isset($data['categoryId'])) $this->categoryId = (int) $data['categoryId'];
    }

    public function storeFormValues($params) { $this->__construct($params); }

    private static function connect()
    {
        return new PDO(CmsConfig::$db_dsn, CmsConfig::$db_username, CmsConfig::$db_password);
    }

    public static function getById($id)
    {
        $conn = self::connect();
        $st = $conn->prepare("SELECT * FROM subcategories WHERE id = :id");
        $st->bindValue(":id", $id, PDO::PARAM_INT);
        $st->execute();
        $row = $st->fetch();
        return $row ? new Subcategory($row) : null;
    }

    /** $categoryId: вернуть подкатегории только этой категории */
    public static function getList($numRows = 1000000, $categoryId = null, $order = "name ASC")
    {
        $conn = self::connect();
        $where = $categoryId ? "WHERE categoryId = :categoryId" : "";

        $st = $conn->prepare("SELECT * FROM subcategories $where ORDER BY $order LIMIT :numRows");
        $st->bindValue(":numRows", $numRows, PDO::PARAM_INT);
        if ($categoryId) $st->bindValue(":categoryId", $categoryId, PDO::PARAM_INT);
        $st->execute();

        $list = array();
        while ($row = $st->fetch()) { $list[] = new Subcategory($row); }

        $st = $conn->prepare("SELECT COUNT(*) FROM subcategories $where");
        if ($categoryId) $st->bindValue(":categoryId", $categoryId, PDO::PARAM_INT);
        $st->execute();
        $totalRows = $st->fetch();

        return array("results" => $list, "totalRows" => $totalRows[0]);
    }

    public function insert()
    {
        $conn = self::connect();
        $st = $conn->prepare("INSERT INTO subcategories (name, categoryId) VALUES (:name, :categoryId)");
        $st->bindValue(":name", $this->name, PDO::PARAM_STR);
        $st->bindValue(":categoryId", $this->categoryId, PDO::PARAM_INT);
        $st->execute();
        $this->id = $conn->lastInsertId();
    }

    public function update()
    {
        $conn = self::connect();
        $st = $conn->prepare("UPDATE subcategories SET name=:name, categoryId=:categoryId WHERE id=:id");
        $st->bindValue(":name", $this->name, PDO::PARAM_STR);
        $st->bindValue(":categoryId", $this->categoryId, PDO::PARAM_INT);
        $st->bindValue(":id", $this->id, PDO::PARAM_INT);
        $st->execute();
    }

    public function delete()
    {
        $conn = self::connect();
        $st = $conn->prepare("DELETE FROM subcategories WHERE id = :id LIMIT 1");
        $st->bindValue(":id", $this->id, PDO::PARAM_INT);
        $st->execute();
    }
}