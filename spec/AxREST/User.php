<?php

namespace spec\AxREST;

use PHPSpec2\ObjectBehavior;

require_once __DIR__ . '/../config.php';

class User extends ObjectBehavior
{
    function it_should_be_initializable()
    {
        $this->beConstructedWith(new \Tonic\Application(), new \Tonic\Request(), array());
        $this->shouldHaveType('AxREST\User');
    }

    function it_should_say_no_users_in_the_database()
    {
        $this->beConstructedWith(new \Tonic\Application(), new \Tonic\Request(array(
            'uri'=>'/',
            'contentType' => 'application/json'
        )), array());

        $expectedResult = new \stdClass();
        $expectedResult->message = "We have no users in the database at the moment.";

        $response = $this->exec();
        $response->shouldReturnAnInstanceOf("Tonic\Response");
        $response->contentType->shouldBe("application/json");
        $response->code->shouldBe(\Tonic\Response::NOTFOUND);
        $response->body->shouldBe(json_encode($expectedResult));
    }

    function it_should_say_no_user_by_that_identity()
    {
        $this->beConstructedWith(new \Tonic\Application(), new \Tonic\Request(array(
            'uri'=>'/testing@test.com',
            'contentType' => 'application/json'
        )), array(
            'identity' => 'testing@test.com'
        ));

        $expectedResult = new \stdClass();
        $expectedResult->message = "We have no user by that identification.";

        $response = $this->exec();
        $response->shouldReturnAnInstanceOf("Tonic\Response");
        $response->contentType->shouldBe("application/json");
        $response->code->shouldBe(\Tonic\Response::NOTFOUND);
        $response->body->shouldBe(json_encode($expectedResult));
    }

    function it_should_say_the_email_address_is_not_valid()
    {
        $this->beConstructedWith(new \Tonic\Application(), new \Tonic\Request(array(
            'uri' => '/',
            'method' => 'PUT',
            'contentType' => 'application/json',
            'data' => '{"name":"testing", "email":"testing@@test.com", "password":"Passw0rd"}'
        )), array());

        $expectedResult = new \stdClass();
        $expectedResult->message = "An error was encountered.";
        $expectedResult->error[] = "The email must be valid.";

        $response = $this->exec();
        $response->shouldReturnAnInstanceOf("Tonic\Response");
        $response->contentType->shouldBe("application/json");
        $response->body->shouldBe(json_encode($expectedResult));
    }

    function it_should_add_a_user()
    {
        $this->beConstructedWith(new \Tonic\Application(), new \Tonic\Request(array(
            'uri'=>'/',
            'method' => 'PUT',
            'contentType' => 'application/json',
            'data' => '{"name":"testing", "email":"testing@test.com", "password":"Passw0rd"}'
        )), array());

        $expectedResult = new \stdClass();
        $expectedResult->message = "User successfully created.";

        $response = $this->exec();
        $response->shouldReturnAnInstanceOf("Tonic\Response");
        $response->contentType->shouldBe("application/json");
        $response->body->shouldBe(json_encode($expectedResult));
    }

    function it_should_update_a_user()
    {
        $this->beConstructedWith(new \Tonic\Application(), new \Tonic\Request(array(
            'uri'=>'/testing@test.com',
            'method' => 'POST',
            'contentType' => 'application/json',
            'data' => '{"dateOfBirth":"1975-03-24"}'
        )), array(
            'identity' => 'testing@test.com'
        ));

        $expectedResult = new \stdClass();
        $expectedResult->message = "The user has been successfully updated.";

        $response = $this->exec();
        $response->shouldReturnAnInstanceOf("Tonic\Response");
        $response->contentType->shouldBe("application/json");
        $response->body->shouldBe(json_encode($expectedResult));
    }

    function it_should_say_no_record_affected()
    {
        $this->beConstructedWith(new \Tonic\Application(), new \Tonic\Request(array(
            'uri'=>'/testing@test2.com',
            'method' => 'DELETE',
            'contentType' => 'application/json'
        )), array(
            'identity' => 'testing@test2.com'
        ));

        $expectedResult = new \stdClass();
        $expectedResult->message = "No rows affected by query.";

        $response = $this->exec();
        $response->shouldReturnAnInstanceOf("Tonic\Response");
        $response->contentType->shouldBe("application/json");
        $response->code->shouldBe(\Tonic\Response::BADREQUEST);
        $response->body->shouldBe(json_encode($expectedResult));
    }

    function it_should_delete_a_user()
    {
        $this->beConstructedWith(new \Tonic\Application(), new \Tonic\Request(array(
            'uri'=>'/testing@test.com',
            'method' => 'DELETE',
            'contentType' => 'application/json'
        )), array(
            'identity' => 'testing@test.com'
        ));

        $expectedResult = new \stdClass();
        $expectedResult->message = "The user has been successfully deleted.";

        $response = $this->exec();
        $response->shouldReturnAnInstanceOf("Tonic\Response");
        $response->contentType->shouldBe("application/json");
        $response->code->shouldBe(\Tonic\Response::OK);
        $response->body->shouldBe(json_encode($expectedResult));
    }
}