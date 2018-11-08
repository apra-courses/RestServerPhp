<?php

namespace App\models;

use PDO;

class Post {

    protected $conn;

    public function __construct(\PDO $conn) {
        $this->conn = $conn;
    }

    public function all() {
        $result = array();
        $stm = $this->conn->query("select * from posts order by datecreated desc");
        if ($stm) {
            $result = $stm->fetchAll(\PDO::FETCH_OBJ);
        }
        return $result;
    }

    public function find($id) {
        $result = array();
        $stm = $this->conn->prepare("select * from posts where id = :id");
        $stm->execute(array('id' => $id));
        if ($stm) {
            $result = $stm->fetch(\PDO::FETCH_OBJ);
        }
        return $result;
    }

    public function count() {
        $result = array();
        $stm = $this->conn->query("select count(*) as count from posts");
        if ($stm) {
            $result = $stm->fetchColumn();
        }
        return $result;
    }

    public function save(array $data = array()) {
        $sql = "insert into posts (email, title, message,datecreated)"
                . " values (:email, :title, :message,:datecreated)";

        $stm = $this->conn->prepare($sql);
        $params = array(
            'email' => $data['email'],
            'message' => $data['message'],
            'title' => $data['title'],
            'datecreated' => date('Y-m-d H:i:s')
        );
        $stm->execute($params);
        return $this->conn->lastInsertId();
    }

    public function update(array $data = array()) {
        $sql = "update posts set ";

        $virgola = '';
        if ($data['email']) {
            $sql .= $virgola . 'email=:email';
            $virgola = ',';
            $params['email'] = $data['email'];
        }
        if ($data['title']) {
            $sql .= $virgola . 'title=:title';
            $virgola = ',';
            $params['title'] = $data['title'];
        }
        if ($data['message']) {
            $sql .= $virgola . 'message=:message';
            $virgola = ',';
            $params['message'] = $data['message'];
        }

        $sql .= ' where id=:id ';
        $params['id'] = $data['id'];

        $stm = $this->conn->prepare($sql);
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

}
