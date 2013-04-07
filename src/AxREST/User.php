<?php
/**
 * The User Resource.
 *
 * The User Resource allows for managing of users in this system.
 *
 * @author Luke Mallon <mallon.luke@gmail.com>
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
    /**
     * This hold the connection to the database.
     *
     * @var \PDO $db
     */
    private $db;
    /**
     * This hold any information that we want to give in the response.
     *
     * @var \stdClass $output
     */
    private $output;
    /**
     * This is the response code that will be sent back in the response.
     *
     * @var integer $responseCode
     */
    private $responseCode = \Tonic\Response::OK;
    /**
     * If any specific headers are required they should be set in this.
     *
     * @var array $headers
     */
    private $headers;

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
    }

    /**
     * View a list of users or a specific user.
     *
     * @method GET
     * @provides application/json
     * @json
     * @param  string $identity
     * @return \Tonic\Response
     */
    public function view($identity = null)
    {
        $sql = "SELECT * FROM `user`";

        // Do we have an identity? If not then we want a list of all users.
        if (null === $identity) {
            $query = $this->db->prepare($sql . " ORDER BY `email` ASC");
        } else {// If so then we are getting one user.
            // Is the identity a valid email address? If so then get the user by the email address field.
            if (false === filter_var($identity, FILTER_VALIDATE_EMAIL)) {
                $this->output->message = "You must supply a valid email address.";
                $this->responseCode = \Tonic\Response::BADREQUEST;
                return new \Tonic\Response($this->responseCode, $this->output);
            }

            $sql .= " WHERE `email` = :identity";
            $query = $this->db->prepare($sql);
            $query->bindValue(':identity', $identity);
        }

        $query->execute();

        // Do we have an identity? If so then we are getting one user.
        if (null !== $identity) {
            $this->output->user = $query->fetch(\PDO::FETCH_OBJ);

            // Did we get a result back? If not unset the user variable.
            if (false === $this->output->user) {
                unset($this->output->user);
            }
        } else { // If not then we want a list of all users.
            $this->output->users = $query->fetchAll(\PDO::FETCH_OBJ);

            // Did we get a result back? If not unset the users variable.
            if (0 === count($this->output->users)) {
                unset($this->output->users);
            }
        }

        // If neither the user or users variable is set then we have no results do display.
        if (false === isset($this->output->user) && false === isset($this->output->users)) {
            if ($identity !== null) {
                $this->output->message = "We have no user by that identification.";
            } else {
                $this->output->message = "We have no users in the database at the moment.";
            }

            $this->responseCode = \Tonic\Response::NOTFOUND;
        } else { // If one is set then we have something to show the requester.
            $this->output->message = 'Success.';
        }

        return new \Tonic\Response($this->responseCode, $this->output);
    }

    /**
     * Add a new user to the table.
     *
     * @method PUT
     * @accepts application/json
     * @provides application/json
     * @json
     * @return \Tonic\Response
     */
    public function add()
    {
        // Validate the data before we go any futher.
        $error = $this->validate();

        // If the data is invalid then we want to let the requester know.
        if (true === $error) {
            $this->output->message = "An error was encountered.";
            $this->responseCode = \Tonic\Response::NOTFOUND;
        } else { // Else we want to PUT the data into our table.
            $query = $this->db->prepare("INSERT INTO `user` (`name`, `email`, `password`, `dateOfBirth`) VALUES (:name, :email, :password, :dateOfBirth)");
            $query->bindValue(":name", $this->request->data->name);
            $query->bindValue(":email", $this->request->data->email);
            $query->bindValue(":password", hash('sha256', $this->request->data->password));
            $query->bindValue(":dateOfBirth", $this->request->data->dateOfBirth);
            $query->execute();

            // Check that the new user was successfully inserted into the database. If not let the requester know what happened.
            if (0 === $query->rowCount()) {
                if ("00000" === $query->errorCode()) {
                    $this->output->message = "No rows affected by query.";
                    $this->responseCode = \Tonic\Response::BADREQUEST;
                } else {
                    $this->output->message = "There was an error running the query.";
                    $this->output->error[] = $query->errorInfo();
                    $this->responseCode = \Tonic\Response::CONFLICT;
                }
            } else { // Inserted successfully.
                $this->output->message = "User successfully created.";
                $this->responseCode = \Tonic\Response::CREATED;
                $this->headers["Location"] = "/" . $this->request->data->email;
            }
        }

        return new \Tonic\Response($this->responseCode, $this->output, $this->headers);
    }

    /**
     * Update a specified used.
     *
     * @method POST
     * @accepts application/json
     * @provides application/json
     * @json
     * @param  string $identity
     * @return \Tonic\Response
     */
    public function update($identity)
    {
        // Do we have an identity? No tell the requester that we need one.
        if (false === isset($identity) || false === filter_var($identity, FILTER_VALIDATE_EMAIL)) {
            $this->output->message = "You must specifiy a user to be updated.";
            $this->responseCode = \Tonic\Response::BADREQUEST;
        } else { // Yes.
            // Validate the data before continuing.
            $error = $this->validate(true);

            // If not valid let the requester know.
            if (true === $error) {
                $this->output->message = "You must provide valid data to be updated.";
                $this->responseCode = \Tonic\Response::BADREQUEST;
            } else { // We have valid data so continue.
                $sql = "Update `user` SET ";

                // Do we have a name in the data?
                if (true === isset($this->request->data->name)) {
                    $sql .= "`name` = :name";
                }

                // Do we have an email in the data?
                if (true === isset($this->request->data->email)) {
                    $sql .= "`email` = :email";
                }

                // Do we have a password in the data?
                if (true === isset($this->request->data->password)) {
                    $sql .= "`password` = :password";
                }

                // Do we have a dateOfBirth in the data.
                if (true === isset($this->request->data->dateOfBirth)) {
                    $sql .= "`dateOfBirth` = :dateOfBirth";
                }

                $sql .= " WHERE `email` = :identity";
                $query = $this->db->prepare($sql);
                $query->bindValue(':identity', $identity);

                // Do we have a name in the data?
                if (true === isset($this->request->data->name)) {
                    $query->bindValue(':name', $this->request->data->name);
                }

                // Do we have an email in the data?
                if (true === isset($this->request->data->email)) {
                    $query->bindValue(':email', $this->request->data->email);
                }

                // Do we have a password in the data?
                if (true === isset($this->request->data->password)) {
                    $query->bindValue(':password', $this->request->data->password);
                }

                // Do we have a dateOfBirth in the data?
                if (true === isset($this->request->data->dateOfBirth)) {
                    $query->bindValue(':dateOfBirth', $this->request->data->dateOfBirth);
                }

                $query->execute();

                // Check that the user was successfully updated. If not let the requester know what happened.
                if (0 === $query->rowCount()) {
                    if ("00000" === $query->errorCode()) {
                        $this->output->message = "No rows affected by query.";
                        $this->headers["Location"] = "/" . $identity;
                        $this->responseCode = \Tonic\Response::BADREQUEST;
                    } else {
                        $this->output->message = "There was an error running the query.";
                        $this->output->error[] = $query->errorInfo();
                        $this->responseCode = \Tonic\Response::INTERNALSERVERERROR;
                    }
                } else { // The user was updated.
                    $this->output->message = "The user has been successfully updated.";
                    $this->headers["Location"] = true === isset($this->request->data->email) ? "/" . $this->request->data->email : "/" . $identity;
                }
            }
        }

        return new \Tonic\Response($this->responseCode, $this->output, $this->headers);
    }

    /**
     * Delete a user from the table.
     *
     * @method DELETE
     * @provides application/json
     * @json
     * @param  string $identity
     * @return \Tonic\Response
     */
    public function delete($identity)
    {
        // Do we have an identity? If not tell the requester.
        if (false === isset($identity) || false === filter_var($identity, FILTER_VALIDATE_EMAIL)) {
            $this->output->message = "You must specifiy a user to be deleted.";
            $this->responseCode = \Tonic\Response::NOTFOUND;
        } else { // Yes
            $sql = "DELETE FROM `user` WHERE `email` = :identity";
            $query = $this->db->prepare($sql);
            $query->bindValue(':identity', $identity);
            $query->execute();

            // Check that the user was successfully removed from the database. If not let the requester know what happened.
            if (0 === $query->rowCount()) {
                if ("00000" === $query->errorCode()) {
                    $this->output->message = "No rows affected by query.";
                    $this->responseCode = \Tonic\Response::BADREQUEST;
                } else {
                    $this->output->message = "There was an error running the query.";
                    $this->output->error[] = $query->errorInfo();
                    $this->responseCode = \Tonic\Response::INTERNALSERVERERROR;
                }
            } else { // User removed successfully.
                $this->output->message = "The user has been successfully deleted.";
            }
        }
        return new \Tonic\Response($this->responseCode, $this->output);
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
                $request->data = json_decode($request->data); // Decode the JSON data.
            }
        });

        $this->after(function ($response) {
            $response->contentType = "application/json";
            $response->body = json_encode($response->body); // Encode the data into JSON.
        });
    }

    /**
     * Validate the data for the user.
     *
     * @param  boolean $update If true then we don't need to check if set.
     *
     * @return boolean
     */
    private function validate($update = false)
    {
        // We do not have any errors at this point.
        $error = false;

        // If we are not updating a user then make sure the name is set.
        if (false === isset($this->request->data->name) && false === $update) {
            $this->output->error[] = "A name must be set for the user.";
            $error = true;
        } else if (true === isset($this->request->data->name) && 150 < strlen($this->request->data->name)) {
            // Make sure the name is at most 150 characters long.
            $this->output->error[] = "The name must be 150 characters or less.";
            $error = true;
        }

        // If we are not updating a user then make sure the email is set.
        if (false === isset($this->request->data->email) && false === $update) {
            $this->output->error[] = "An email must be set for the user.";
            $error = true;
        } else if (true === isset($this->request->data->email) && 255 < strlen($this->request->data->email)) {
            // Make sure the email is at most 255 characters long.
            $this->output->error[] = "The email must be 255 characters or less.";
            $error = true;
        } else if (true === isset($this->request->data->email) && false === filter_var($this->request->data->email, FILTER_VALIDATE_EMAIL)) {
            // Make sure the email is valid.
            $this->output->error[] = "The email must be valid.";
            $error = true;
        }

        // If we are not updating a user then make sure the password is set.
        if (false === isset($this->request->data->password) && false === $update) {
            $this->output->error[] = "A password must be set for the user.";
            $error = true;
        } else if (true === isset($this->request->data->password) && 3 > strlen($this->request->data->password)) {
            // Make sure the password is at least 3 characters long.
            $this->output->error[] = "The password must be at least 3 characters long.";
            $error = true;
        }

        // If we are not updating a user and the dateOfBirth is not set then set it to null.
        if (false === isset($this->request->data->dateOfBirth) && false === $update) {
            $this->request->data->dateOfBirth = null;
        } else if (true === isset($this->request->data->dateOfBirth) && false === \DateTime::createFromFormat('Y-m-d', $this->request->data->dateOfBirth)) {
            // Make sure the dateOfBirth is in the format of yyyy-mm-dd.
            $this->output->error[] = "The date must be in the format of yyyy-mm-dd.";
            $error = true;
        }

        // Are we updating a user?
        if (true === $update) {
            // If so make sure at least one data field has been provided.
            if (false === isset($this->request->data->name) &&
                false === isset($this->request->data->email) &&
                false === isset($this->request->data->password) &&
                false === isset($this->request->data->dateOfBirth)) {
                $this->output->error[] = "You must provide a value for at least one field to update.";
                $error = true;
            }
        }

        return $error;
    }
}