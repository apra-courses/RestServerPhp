<?php

require_once('RestController.php');
require_once(__DIR__ . '/../models/Post.php');
require_once(__DIR__ . '/../DB/DbFactory.php');

use App\models\Post;
use App\DB\DbFactory;
use PDO;

/**
 * Classe Controller per funzioni CRUD
 */
class postController extends RestController {

    /**
     * Caricamento di un singolo record per chiave
     * @param array $params Parametri
     */
    public function load($params) {
        $this->resetLastError();
        try {
            if (!$params['id']) {
                $this->handleError(self::ERROR_PRECONDITION, "Id mancante");
                return false;
            }
            $postModel = $this->initClass();
            $model = $postModel->find($params['id']);
            return $this->modelToRawData($model);
        } catch (ItaException $ex) {
            $this->handleError($ex->getNativeErrorCode(), $ex->getNativeErroreDesc());
            return false;
        } catch (Exception $ex) {
            $this->handleError($ex->getCode(), $ex->getMessage());
            return false;
        }
    }

    /**
     * Caricamento di tutti i record
     */
    public function listAll() {
        $this->resetLastError();
        try {
            $postModel = $this->initClass();
            $models = $postModel->all();
            $toReturn = array();
            foreach ($models as $key => $model) {
                $toReturn[] = $this->modelToRawData($model);
            }
            return $toReturn;
        } catch (ItaException $ex) {
            $this->handleError($ex->getNativeErrorCode(), $ex->getNativeErroreDesc());
            return false;
        } catch (Exception $ex) {
            $this->handleError($ex->getCode(), $ex->getMessage());
            return false;
        }
    }

    /**
     * Inserimento modello e dati relazionati
     * @param array $params Parametri
     */
    public function insert($params) {
        $this->resetLastError();
        try {
            $err = '';
            if (!$params['title']) {
                $err = 'Titolo mancante';
            }
            if (!$params['email']) {
                $err .= ' Mail mancante';
            }
            if (!$params['message']) {
                $err .= ' Messaggio mancante';
            }
            if ($err) {
                $this->handleError(self::ERROR_PRECONDITION, $err);
                return false;
            }

            $postModel = $this->initClass();
            return $postModel->save($this->rawDataToModel($params));
        } catch (ItaException $ex) {
            $this->handleError($ex->getNativeErrorCode(), $ex->getNativeErroreDesc());
            return false;
        } catch (Exception $ex) {
            $this->handleError($ex->getCode(), $ex->getMessage());
            return false;
        }
    }

    /**
     * Aggiornamento modello e dati relazionati
     * @param array $params Parametri
     */
    public function update($params) {
        $this->resetLastError();
        try {
            $err = '';
            if (!$params['id']) {
                $err = 'Id mancante';
            } else if (!$params['title'] && !$params['email'] && !$params['message']) {
                $err = 'Nessun dato da aggiornare';
            }

            if ($err) {
                $this->handleError(self::ERROR_PRECONDITION, $err);
                return false;
            }
            $postModel = $this->initClass();

            $model = $postModel->find($params['id']);

            if (!$model) {
                $this->handleError(self::ERROR_PRECONDITION, "Modello non trovato");
                return false;
            }

            if ($params['title']) {
                $model->setTitle($params['title']);
            }
            if ($params['email']) {
                $model->setEmail($params['email']);
            }
            if ($params['message']) {
                $model->setMessage($params['message']);
            }

            if ($postModel->update($model)) {
                return "Record aggiornato";
            }
            return false;
        } catch (ItaException $ex) {
            $this->handleError($ex->getNativeErrorCode(), $ex->getNativeErroreDesc());
            return false;
        } catch (Exception $ex) {
            $this->handleError($ex->getCode(), $ex->getMessage());
            return false;
        }
    }

    /**
     * Cancellazione modello e dati relazionati
     * @param array $params Parametri
     */
    public function delete($params) {
        $this->resetLastError();
        try {
            if (!$params['id']) {
                $this->handleError(self::ERROR_PRECONDITION, "Id mancante");
                return false;
            }
            $postModel = $this->initClass();
            if ($postModel->delete($params['id'])) {
                return 'Cancellazione eseguita';
            }
        } catch (ItaException $ex) {
            $this->handleError($ex->getNativeErrorCode(), $ex->getNativeErroreDesc());
            return false;
        } catch (Exception $ex) {
            $this->handleError($ex->getCode(), $ex->getMessage());
            return false;
        }
    }

    private function initClass() {
        $data = require __DIR__ . '/../config/database.php';
        $pdoConn = DbFactory::create($data);
        $postModel = new Post($pdoConn->getConn());
        return $postModel;
    }

    public function saveComment($postid) {
        $comment = new Comment();
        $comment->setComment($_POST['comment']);
        $comment->setPost_id($postid);
        $comment->setEmail($_POST['email']);
        $this->comment->save($comment);

        redirect('/post/' . $postid);
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
        $toReturn = array(
            'title' => $model->getTitle(),
            'message' => $model->getMessage(),
            'email' => $model->getEmail(),
            'datecreated' => $model->getDatecreated() ? $model->getDatecreated() : date('Y-m-d H:i:s')
        );

        if ($model->getId()) {
            $toReturn['id'] = $model->getId();
        }

        return $toReturn;
    }

}

?>