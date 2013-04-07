<?php

namespace spec\AxREST;

use PHPSpec2\ObjectBehavior;

define('PDO_CONN_STRING', 'mysql:host=localhost;dbname=axrest;');
define('PDO_CONN_USER', 'axrest');
define('PDO_CONN_PASS', 'BWKr7xLhWKpFaScu');

class User extends ObjectBehavior
{
    function it_should_be_initializable()
    {
        $this->beConstructedWith(new \Tonic\Application(), new \Tonic\Request(), array());
        $this->shouldHaveType('AxREST\User');
    }

    function it_should_return_a_Tonic_Response()
    {
        $this->beConstructedWith(new \Tonic\Application(), new \Tonic\Request(array(
            'uri'=>'/',
            'Content-Type' => 'application/json'
        )), array('{"name":"testing", "email":"te@st.ing", "password":"Passw0rd"}'));
        $this->exec()->shouldReturnAnInstanceOf("Tonic\Response");
    }
}