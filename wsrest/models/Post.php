<?php

namespace App\models;

use PDO;

class Post {

    protected $conn;
    private $email;
    private $title;
    private $message;
    private $datecreated;
    private $id;

    public function __construct(\PDO $conn = null) {
        $this->conn = $conn;
    }

    public function all() {
        $result = array();
        $stm = $this->conn->query("select * from posts order by datecreated desc");
        if ($stm) {
            $result = $stm->fetchAll(\PDO::FETCH_ASSOC);
        }
        $posts = array();
        foreach ($result as $key => $value) {
            $posts[] = $this->rawDataToModel($value);
        }
        return $posts;
    }

    public function find($id) {
        $result = array();
        $stm = $this->conn->prepare("select * from posts where id = :id");
        $stm->execute(array('id' => $id));
        if ($stm) {
            $result = $stm->fetch(\PDO::FETCH_ASSOC);
        }
        return $this->rawDataToModel($result);
    }

    public function save($model = null) {
        $sql = "insert into posts (email, title, message,datecreated)"
                . " values (:email, :title, :message,:datecreated)";

        $stm = $this->conn->prepare($sql);
        $params = $this->modelToRawData($model);
        $stm->execute($params);
        return $this->conn->lastInsertId();
    }

    public function update($model = null) {
        $sql = "update posts set ";

        $sql .= $this->generateSql($model);
        $sql .= " where id=:id";

        $stm = $this->conn->prepare($sql);
        $params = $this->modelToRawData($model);
        $stm->execute($params);
        return $stm->rowCount();
    }

    public function delete($id) {
        if (!$id) {
            throw new Exception('Id mancante per la cancellazione');
        }
        $sql = "delete from posts where id=:id";

        $stm = $this->conn->prepare($sql);
        $stm->bindParam('id', $id, \PDO::PARAM_INT);
        $stm->execute();
        return $stm->rowCount();
    }

    private function rawDataToModel($rawData) {
        $post = new Post();
        $post->setId($rawData['id']);
        $post->setTitle($rawData['title']);
        $post->setMessage($rawData['message']);
        $post->setEmail($rawData['email']);
        $post->setDatecreated($rawData['datecreated']);
        return $post;
    }

    private function modelToRawData($model) {
        $toReturn['title'] = $model->getTitle();
        $toReturn['message'] = $model->getMessage();
        $toReturn['email'] = $model->getEmail();
        $toReturn['datecreated'] = $model->getDatecreated() ? $model->getDatecreated() : date('Y-m-d H:i:s');

        if ($model->getId()) {
            $toReturn['id'] = $model->getId();
        }

        return $toReturn;
    }

    private function generateSql($model) {
        $sql = "";
        $virgola = "";
        if ($model->getEmail()) {
            $sql .= $virgola . "email=:email";
            $virgola = ',';
        }
        if ($model->getTitle()) {
            $sql .= $virgola . "title=:title";
            $virgola = ',';
        }
        if ($model->getMessage()) {
            $sql .= $virgola . "message=:message";
            $virgola = ',';
        }
        if ($model->getDateCreated()) {
            $sql .= $virgola . "datecreated=:datecreated";
            $virgola = ',';
        }

        return $sql;
    }

    function getEmail() {
        return $this->email;
    }

    function getTitle() {
        return $this->title;
    }

    function getMessage() {
        return $this->message;
    }

    function getDatecreated() {
        return $this->datecreated;
    }

    function getId() {
        return $this->id;
    }

    function setEmail($email) {
        $this->email = $email;
    }

    function setTitle($title) {
        $this->title = $title;
    }

    function setMessage($message) {
        $this->message = $message;
    }

    function setDatecreated($datecreated) {
        $this->datecreated = $datecreated;
    }

    function setId($id) {
        $this->id = $id;
    }

}
