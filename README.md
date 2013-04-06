AxREST
======

A simple RESTful service to maintain a database table of users.

Requirements
------------

-   PHP v5.3+
-   [Tonic v3.1+](https://github.com/peej/tonic/tree/v3.1)
-   MySQL v5.1+

How To
------

###Local Set-up

Take the folder `/src/Tonic` from the Tonic github repository and place it in the root folder of this service `/Tonic`.

###MySQL Table

A user is comprised of the following fields:

-   `name` This is a string limited to 150 characters.
-   `email` This is a string limited to 255 characters. The email address is validated using PHP's `filter_var` function with the flag `FILTER_VALIDATE_EMAIL`.
-   `password` This is a string and must be at least 3 characters long. It is then hashed using SHA256.
-   `dateOfBirth` This is a string and must be in the format of `yyyy-mm-dd` e.g. `1975-03-24`. This field is not required and can be left out of the __PUT__ request.

The email address is the Primary Key and so must be Unique.

The following SQL will add the table to your database.

    CREATE TABLE IF NOT EXISTS `user` (
        `name` varchar(150) COLLATE utf8_bin NOT NULL,
        `email` varchar(255) COLLATE utf8_bin NOT NULL,
        `password` varchar(64) COLLATE utf8_bin NOT NULL,
        `dateOfBirth` date DEFAULT NULL,
        PRIMARY KEY (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

To connect to your database find the following on line 16 in `./dispatch.php`:

    // Get the Database details from the Cloud Service.
    $services_json = json_decode(getenv("VCAP_SERVICES"),true);
    $mysql_config = $services_json["mysql-5.1"][0]["credentials"];

    define('PDO_CONN_STRING', 'mysql:host=' . $mysql_config["hostname"] . ';port=' . $mysql_config["port"] . ';dbname=' . $mysql_config["name"] . ';');
    define('PDO_CONN_USER', $mysql_config["username"]);
    define('PDO_CONN_PASS', $mysql_config["password"]);

If you are using the AppFrog Cloud hosting you should not need to change anything. Otherwise change the `PDO_*` constants to suit your connection details.

###Use Service

####PUT

We use __PUT__ to create a new user in the database. Requests for this are sent to `/`

__Example Request__:

URL:

    /

Headers:

    Content-Type: application/json

Raw Data:

    {
        "name"          : "Test User",
        "email"         : "test@user.com",
        "password"      : "asdqwe"
    }

__Example Response__:

Headers:

    Content-Type: application/json
    Location: /test@user.com

Body:

    {
        "message": "User successfully created."
    }

####GET

There are two calls to __GET__:

1.  The first is `/`, this will return an object containing a message and an array containing all users within the database.

    __Example Request__:

    URL:

        /

    __Example Response__:

    Headers:

        Content-Type: application/json

    Body:

        {
            "message": "Success.",
            "users": [{
                "name": "Test User",
                "email": "test@user.com",
                "password": "501bb865d4a92532cfebb65ee059e4889363eeb28a22ca6fb82165bb17432724",
                "dateOfBirth": null
            }, {
                "name": "Test User 2",
                "email": "test@user2.com",
                "password": "074519e2ef816d6c5acc77af06206722301b5109ddeef9d2bac3f30ff7e8d7b3",
                "dateOfBirth": null
            }]
        }

2.  The second is `/:identity`, where `:identity` is the email address of the user you are looking for.

    This will return an object with a message and an object containing the user.

    __Example Request__:

    URL:

        /test@user.com

    __Example Response__:

    Headers:

        Content-Type: application/json

    Body:

        {
            "message"   : "Success.",
            "user"      : {
                "name"          : "Test User",
                "email"         : "test@user.com",
                "password"      : "05f4a1dd829dcf0f11617780634a22dbde6ad638cc2ee943ef9c258f1b1c4058",
                "dateOfBirth"   : null
            }
        }

####POST

We use __POST__ to update an existing user. This is done by specifying the `:identity` that we want to update and posting data.

__Example Request__:

URL:

    /test@user2.com

Headers:

    Content-Type: application/json

Raw Data:

    {
        "dateOfBirth"   : "1975-03-24"
    }

__Example Response__:

Headers:

    Content-Type: application/json
    Location: /test@user.com

Body:

    {
        "message": "The user has been successfully updated."
    }

####DELETE

When we want to delete a user from the table we use the __DELETE__ method. As with __POST__ we specify the `:identity` of the user to be deleted.

__Example Request__:

URL:

    /test@user.com

__Example Response__:

Headers:

    Content-Type: application/json

Body:

    {
        "message": "The user has been successfully deleted."
    }
