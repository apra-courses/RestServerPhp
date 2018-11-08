<?php

namespace App\Models;

use PDO;

class Comment {

    protected $conn;
    
    private $email;
    private $comment;
    private $datecreated;
    private $post_id;
    private $id;

    public function __construct(\PDO $conn = null) {
        $this->conn = $conn;
    }

    public function all($postid) {
        $result = array();
        $sql = 'select * from postscomments where post_id=:postid ORDER BY datecreated DESC';

        $stm = $this->conn->prepare($sql);

        $stm->bindParam(':postid', $postid, \PDO::PARAM_INT);

        $stm->execute();
        $result = $stm->fetchAll(\PDO::FETCH_ASSOC);
        $comments = array();
        foreach ($result as $key => $value) {
            $comments[] = $this->rawDataToModel($value);
        }
        return $comments;
    }

    public function save($model = null) {
        $sql = 'INSERT INTO postscomments (email, comment,datecreated, post_id)';
        $sql .= 'values (:email, :comment,:datecreated, :post_id)';

        $stm = $this->conn->prepare($sql);

        $params = $this->modelToRawData($model);

        $stm->execute($params);

        return $stm->rowCount();
    }

    public function delete($id) {
        $sql = 'DELETE FROM  POSTS  WHERE id = :id';

        $stm = $this->conn->prepare($sql);
        $stm->bindParam(':id', $id, \PDO::PARAM_INT);
        $stm->execute();

        return $stm->rowCount();
    }

    private function rawDataToModel($rawData) {
        $post = new Comment();
        $post->setId($rawData['id']);
        $post->setComment($rawData['comment']);
        $post->setEmail($rawData['email']);
        $post->setDatecreated($rawData['datecreated']);
        $post->setPost_id($rawData['post_id']);
        return $post;
    }

    private function modelToRawData($model) {
        $toReturn = array(
            'comment' => $model->getComment(),
            'post_id' => $model->getPost_id(),
            'email' => $model->getEmail(),
            'datecreated' => $model->getDatecreated() ? $model->getDatecreated() : date('Y-m-d H:i:s')
        );

        if ($model->getId()) {
            $toReturn['id'] = $model->getId();
        }

        return $toReturn;
    }

    function getEmail() {
        return $this->email;
    }

    function getComment() {
        return $this->comment;
    }

    function getDatecreated() {
        return $this->datecreated;
    }

    function getPost_id() {
        return $this->post_id;
    }

    function getId() {
        return $this->id;
    }

    function setEmail($email) {
        $this->email = $email;
    }

    function setComment($comment) {
        $this->comment = $comment;
    }

    function setDatecreated($datecreated) {
        $this->datecreated = $datecreated;
    }

    function setPost_id($post_id) {
        $this->post_id = $post_id;
    }

    function setId($id) {
        $this->id = $id;
    }

}
