<?php
/*
 * The User Resource.
 */

namespace AxREST;

/**
 * User Resource.
 *
 * This Resource will allow users to be added, updated, viewed and deleted.
 *
 * @uri /
 * @uri /:identity
 *
 * @author Luke Mallon <mallon.luke@gmail.com>
 */
class User extends \Tonic\Resource
{
    private $db;
    private $output;
    private $responseCode = \Tonic\Response::OK;

    /**
     * User Contruct.
     *
     * Sets up the database connection to use in the class.
     */
    public function __construct(\Tonic\Application $app, \Tonic\Request $request, array $urlParams) {
        parent::__construct($app, $request, $urlParams);
        $this->output = new \stdClass();
        $this->output->message = null;
        $this->db = new \PDO(PDO_CONN_STRING, PDO_CONN_USER, PDO_CONN_PASS);
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * @method GET
     * @provides application/json
     * @json
     * @param  str $identity
     * @return \Tonic\Response
     */
    public function view($identity = null)
    {
        $this->output->users = array();
        $this->output->user = false;
        $sql = "SELECT * FROM `user`";

        if ($identity !== null) {
            $sql .= " WHERE `";

            if (true === is_int($identity)) {
                $sql .= "id";
            } else if (false !== filter_var($identity, FILTER_VALIDATE_EMAIL)) {
                $sql .= "email";
            } else {
                $sql .= "name";
            }

            $sql .= "` = :identity";

            $query = $this->db->prepare($sql);
            $query->bindValue(':identity', $identity);
        } else {
            $query = $this->db->prepare($sql . " ORDER BY `id` ASC");
        }

        $query->execute();

        if ($identity !== null) {
            $this->output->user = $query->fetch(\PDO::FETCH_OBJ);
        } else {
            $this->output->users = $query->fetchAll(\PDO::FETCH_OBJ);
        }

        if (false === $this->output->user && 0 === count($this->output->users)) {
            if ($identity !== null) {
                $this->output->message = "We have no user by that identification.";
            } else {
                $this->output->message = "We have no users in the database at the moment.";
            }

            $this->responseCode = \Tonic\Response::NOTFOUND;
        } else {
            $this->output->message = 'Success.';
        }

        return new \Tonic\Response($this->responseCode, $this->output);
    }

    /**
     * @method PUT
     * @accepts application/json
     * @provides application/json
     * @json
     * @return \Tonic\Response
     */
    public function add()
    {
        $error = false;

        if (false === isset($this->request->data->name)) {
            $this->output->error[] = "A name must be set for the user.";
            $error = true;
        } else if (150 < strlen($this->request->data->name)) {
            $this->output->error[] = "The name must be 150 characters or less.";
            $error = true;
        }

        if (false === isset($this->request->data->email)) {
            $this->output->error[] = "An email must be set for the user.";
            $error = true;
        } else if (255 < strlen($this->request->data->email)) {
            $this->output->error[] = "The email must be 255 characters or less.";
            $error = true;
        } else if (false === filter_var($this->request->data->email, FILTER_VALIDATE_EMAIL)) {
            $this->output->error[] = "The email must be valid.";
            $error = true;
        }

        if (false === isset($this->request->data->dateOfBirth)) {
            $this->request->data->dateOfBirth = null;
        } else if (false === \DateTime::createFromFormat('Y-m-d', $this->request->data->dateOfBirth)) {
            $this->output->error[] = "The date must be in the format of YYYY-mm-dd.";
            $error = true;
        }

        if (true === $error) {
            $this->output->message = "An error was encountered.";
            $this->responseCode = \Tonic\Response::NONAUTHORATIVEINFORMATION;
        } else {
            $query = $this->db->prepare("INSERT INTO `user` (`name`, `email`, `dateOfBirth`) VALUES (:name, :email, :dateOfBirth)");
            $query->bindValue(":name", $this->request->data->name);
            $query->bindValue(":email", $this->request->data->email);
            $query->bindValue(":dateOfBirth", $this->request->data->dateOfBirth);

            try {
                $query->execute();
                $userId = $this->db->lastInsertId();
            } catch (\PDOException $e) {
                $error = true;
                $this->output->message = "Unable to create user.";
                $this->output->error[] = $e->getMessage();
                $this->responseCode = \Tonic\Response::CONFLICT;
            }

            if (false === $error) {
                $this->output->user = new \stdClass();
                $this->output->user->id = $userId;
                $this->output->user->name = $this->request->data->name;
                $this->output->user->email = $this->request->data->email;
                $this->output->user->dateOfBirth = $this->request->data->dateOfBirth;
                $this->output->message = "User successfully created.";
                $this->responseCode = \Tonic\Response::CREATED;
            }
        }

        return new \Tonic\Response($this->responseCode, $this->output);
    }

    /**
     * @method POST
     * @accepts application/json
     * @provides application/json
     * @json
     * @return \Tonic\Response
     */
    public function update()
    {
        return new \Tonic\Response($this->responseCode, $this->request->data);
    }

    /**
     * @method DELETE
     * @accepts application/json
     * @provides application/json
     * @json
     * @return \Tonic\Response
     */
    public function delete()
    {
        return new \Tonic\Response($this->responseCode, $this->request->data);
    }

    /**
     * Condition method to turn output into JSON.
     *
     * This condition sets a before and an after filter for the request and response. The
     * before filter decodes the request body if the request content type is JSON, while the
     * after filter encodes the response body into JSON.
     */
    protected function json()
    {
        $this->before(function ($request) {
            if ($request->contentType == "application/json") {
                $request->data = json_decode($request->data);
            }
        });
        $this->after(function ($response) {
            $response->contentType = "application/json";
            $response->body = json_encode($response->body);
        });
    }
}