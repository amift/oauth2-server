<?php

use \Mockery as m;

class Authentication_Server_test extends PHPUnit_Framework_TestCase
{
    private $client;
    private $session;
    private $scope;

    public function setUp()
    {
        $this->client = M::mock('OAuth2\Storage\ClientInterface');
        $this->session = M::mock('OAuth2\Storage\SessionInterface');
        $this->scope = M::mock('OAuth2\Storage\ScopeInterface');
    }

    private function returnDefault()
    {
        return new OAuth2\AuthServer($this->client, $this->session, $this->scope);
    }

    /**
     * @expectedException PHPUnit_Framework_Error
     */
    public function test__construct_NoStorage()
    {
        $a = new OAuth2\AuthServer;
    }

    public function test__contruct_WithStorage()
    {
        $a = $this->returnDefault();
    }

    public function test_getExceptionMessage()
    {
        $m = OAuth2\AuthServer::getExceptionMessage('access_denied');

        $reflector = new ReflectionClass($this->returnDefault());
        $exceptionMessages = $reflector->getProperty('exceptionMessages');
        $exceptionMessages->setAccessible(true);
        $v = $exceptionMessages->getValue();

        $this->assertEquals($v['access_denied'], $m);
    }

    public function test_hasGrantType()
    {
        $this->assertFalse(OAuth2\AuthServer::hasGrantType('test'));
    }

    public function test_addGrantType()
    {
        $a = $this->returnDefault();
        $grant = M::mock('OAuth2\Grant\GrantTypeInterface');
        $grant->shouldReceive('getResponseType')->andReturn('test');
        $a->addGrantType($grant, 'test');

        $this->assertTrue(OAuth2\AuthServer::hasGrantType('test'));
    }

    public function test_addGrantType_noIdentifier()
    {
        $a = $this->returnDefault();
        $grant = M::mock('OAuth2\Grant\GrantTypeInterface');
        $grant->shouldReceive('getIdentifier')->andReturn('test');
        $grant->shouldReceive('getResponseType')->andReturn('test');
        $a->addGrantType($grant);

        $this->assertTrue(OAuth2\AuthServer::hasGrantType('test'));
    }

    public function test_getScopeDelimeter()
    {
        $a = $this->returnDefault();
        $this->assertEquals(',', $a->getScopeDelimeter());
    }

    public function test_setScopeDelimeter()
    {
        $a = $this->returnDefault();
        $a->setScopeDelimeter(';');
        $this->assertEquals(';', $a->getScopeDelimeter());
    }

    public function test_getExpiresIn()
    {
        $a = $this->returnDefault();
        $a->setExpiresIn(7200);
        $this->assertEquals(7200, $a::getExpiresIn());
    }

    public function test_setExpiresIn()
    {
        $a = $this->returnDefault();
        $a->setScopeDelimeter(';');
        $this->assertEquals(';', $a->getScopeDelimeter());
    }

    public function test_setRequest()
    {
        $a = $this->returnDefault();
        $request = new OAuth2\Util\Request();
        $a->setRequest($request);

        $reflector = new ReflectionClass($a);
        $requestProperty = $reflector->getProperty('request');
        $requestProperty->setAccessible(true);
        $v = $requestProperty->getValue();

        $this->assertTrue($v instanceof OAuth2\Util\RequestInterface);
    }

    public function test_getRequest()
    {
        $a = $this->returnDefault();
        $request = new OAuth2\Util\Request();
        $a->setRequest($request);
        $v = $a::getRequest();

        $this->assertTrue($v instanceof OAuth2\Util\RequestInterface);
    }

    public function test_getStorage()
    {
        $a = $this->returnDefault();
        $this->assertTrue($a->getStorage('session') instanceof OAuth2\Storage\SessionInterface);
    }

    /**
     * @expectedException        OAuth2\Exception\ClientException
     * @expectedExceptionCode    0
     */
    public function test_checkAuthoriseParams_noClientId()
    {
        $a = $this->returnDefault();
        $a->checkAuthoriseParams();
    }

    /**
     * @expectedException        OAuth2\Exception\ClientException
     * @expectedExceptionCode    0
     */
    public function test_checkAuthoriseParams_noRedirectUri()
    {
        $a = $this->returnDefault();
        $a->checkAuthoriseParams(array(
            'client_id' =>  1234
        ));
    }

    /**
     * @expectedException        OAuth2\Exception\ClientException
     * @expectedExceptionCode    8
     */
    public function test_checkAuthoriseParams_badClient()
    {
        $this->client->shouldReceive('getClient')->andReturn(false);

        $a = $this->returnDefault();
        $a->checkAuthoriseParams(array(
            'client_id' =>  1234,
            'redirect_uri'  =>  'http://foo/redirect'
        ));
    }

    /**
     * @expectedException        OAuth2\Exception\ClientException
     * @expectedExceptionCode    0
     */
    public function test_checkAuthoriseParams_missingResponseType()
    {
        $this->client->shouldReceive('getClient')->andReturn(array(
            'client_id' =>  1234,
            'client_secret' =>  5678,
            'redirect_uri'  =>  'http://foo/redirect',
            'name'  =>  'Example Client'
        ));

        $a = $this->returnDefault();
        $a->checkAuthoriseParams(array(
            'client_id' =>  1234,
            'redirect_uri'  =>  'http://foo/redirect'
        ));
    }

    /**
     * @expectedException        OAuth2\Exception\ClientException
     * @expectedExceptionCode    3
     */
    public function test_checkAuthoriseParams_badResponseType()
    {
        $this->client->shouldReceive('getClient')->andReturn(array(
            'client_id' =>  1234,
            'client_secret' =>  5678,
            'redirect_uri'  =>  'http://foo/redirect',
            'name'  =>  'Example Client'
        ));

        $a = $this->returnDefault();
        $a->checkAuthoriseParams(array(
            'client_id' =>  1234,
            'redirect_uri'  =>  'http://foo/redirect',
            'response_type' =>  'foo'
        ));
    }

    /**
     * @expectedException        OAuth2\Exception\ClientException
     * @expectedExceptionCode    0
     */
    public function test_checkAuthoriseParams_missingScopes()
    {
        $this->client->shouldReceive('getClient')->andReturn(array(
            'client_id' =>  1234,
            'client_secret' =>  5678,
            'redirect_uri'  =>  'http://foo/redirect',
            'name'  =>  'Example Client'
        ));

        $a = $this->returnDefault();
        $a->addGrantType(new OAuth2\Grant\AuthCode());

        $a->checkAuthoriseParams(array(
            'client_id' =>  1234,
            'redirect_uri'  =>  'http://foo/redirect',
            'response_type' =>  'code',
            'scope' =>  ''
        ));
    }

    /**
     * @expectedException        OAuth2\Exception\ClientException
     * @expectedExceptionCode    4
     */
    public function test_checkAuthoriseParams_badScopes()
    {
        $this->client->shouldReceive('getClient')->andReturn(array(
            'client_id' =>  1234,
            'client_secret' =>  5678,
            'redirect_uri'  =>  'http://foo/redirect',
            'name'  =>  'Example Client'
        ));

        $this->scope->shouldReceive('getScope')->andReturn(false);

        $a = $this->returnDefault();
        $a->addGrantType(new OAuth2\Grant\AuthCode());

        $a->checkAuthoriseParams(array(
            'client_id' =>  1234,
            'redirect_uri'  =>  'http://foo/redirect',
            'response_type' =>  'code',
            'scope' =>  'foo'
        ));
    }

    public function test_checkAuthoriseParams_passedInput()
    {
        $a = $this->returnDefault();
        $a->addGrantType(new OAuth2\Grant\AuthCode());

        $this->client->shouldReceive('getClient')->andReturn(array(
            'client_id' =>  1234,
            'client_secret' =>  5678,
            'redirect_uri'  =>  'http://foo/redirect',
            'name'  =>  'Example Client'
        ));

        $this->scope->shouldReceive('getScope')->andReturn(array(
            'id'    =>  1,
            'scope' =>  'foo',
            'name'  =>  'Foo Name',
            'description'   =>  'Foo Name Description'
        ));

        $v = $a->checkAuthoriseParams(array(
            'client_id' =>  1234,
            'redirect_uri'  =>  'http://foo/redirect',
            'response_type' =>  'code',
            'scope' =>  'foo'
        ));

        $this->assertEquals(array(
            'client_id' =>  1234,
            'redirect_uri'  =>  'http://foo/redirect',
            'client_details' => array(
                'client_id' => 1234,
                'client_secret' => 5678,
                'redirect_uri' => 'http://foo/redirect',
                'name' => 'Example Client'
            ),
            'response_type' =>  'code',
            'scopes'    =>  array(
                array(
                    'id'    =>  1,
                    'scope' =>  'foo',
                    'name'  =>  'Foo Name',
                    'description'   =>  'Foo Name Description'
                )
            )
        ), $v);
    }

    public function test_checkAuthoriseParams()
    {
        $this->client->shouldReceive('getClient')->andReturn(array(
            'client_id' =>  1234,
            'client_secret' =>  5678,
            'redirect_uri'  =>  'http://foo/redirect',
            'name'  =>  'Example Client'
        ));

        $this->scope->shouldReceive('getScope')->andReturn(array(
            'id'    =>  1,
            'scope' =>  'foo',
            'name'  =>  'Foo Name',
            'description'   =>  'Foo Name Description'
        ));

        $a = $this->returnDefault();
        $a->addGrantType(new OAuth2\Grant\AuthCode());

        $_GET['client_id'] = 1234;
        $_GET['redirect_uri'] = 'http://foo/redirect';
        $_GET['response_type'] = 'code';
        $_GET['scope'] = 'foo';

        $request = new OAuth2\Util\Request($_GET);
        $a->setRequest($request);

        $v = $a->checkAuthoriseParams();

        $this->assertEquals(array(
            'client_id' =>  1234,
            'redirect_uri'  =>  'http://foo/redirect',
            'client_details' => array(
                'client_id' => 1234,
                'client_secret' => 5678,
                'redirect_uri' => 'http://foo/redirect',
                'name' => 'Example Client'
            ),
            'response_type' =>  'code',
            'scopes'    =>  array(
                array(
                    'id'    =>  1,
                    'scope' =>  'foo',
                    'name'  =>  'Foo Name',
                    'description'   =>  'Foo Name Description'
                )
            )
        ), $v);
    }

    function test_newAuthoriseRequest()
    {
        $this->session->shouldReceive('deleteSession')->andReturn(null);
        $this->session->shouldReceive('createSession')->andReturn(1);
        $this->session->shouldReceive('associateScope')->andReturn(null);

        $a = $this->returnDefault();

        $params = array(
            'client_id' =>  1234,
            'redirect_uri'  =>  'http://foo/redirect',
            'client_details' => array(
                'client_id' => 1234,
                'client_secret' => 5678,
                'redirect_uri' => 'http://foo/redirect',
                'name' => 'Example Client'
            ),
            'response_type' =>  'code',
            'scopes'    =>  array(
                array(
                    'id'    =>  1,
                    'scope' =>  'foo',
                    'name'  =>  'Foo Name',
                    'description'   =>  'Foo Name Description'
                )
            )
        );

        $v = $a->newAuthoriseRequest('user', 123, $params);

        $this->assertEquals(40, strlen($v));
    }

    public function test_getGrantType()
    {
        $a = $this->returnDefault();
        $a->addGrantType(new OAuth2\Grant\AuthCode());

        $reflector = new ReflectionClass($a);
        $method = $reflector->getMethod('getGrantType');
        $method->setAccessible(true);

        $result = $method->invoke($a, 'authorization_code');

        $this->assertTrue($result instanceof OAuth2\Grant\GrantTypeInterface);
    }

    /**
     * @expectedException        OAuth2\Exception\ClientException
     * @expectedExceptionCode    0
     */
    public function test_issueAccessToken_missingGrantType()
    {
        $a = $this->returnDefault();
        $a->addGrantType(new OAuth2\Grant\AuthCode());

        $v = $a->issueAccessToken();
    }

    /**
     * @expectedException        OAuth2\Exception\ClientException
     * @expectedExceptionCode    7
     */
    public function test_issueAccessToken_badGrantType()
    {
        $a = $this->returnDefault();
        $a->addGrantType(new OAuth2\Grant\AuthCode());

        $v = $a->issueAccessToken(array('grant_type' => 'foo'));
    }

    /**
     * @expectedException        OAuth2\Exception\ClientException
     * @expectedExceptionCode    0
     */
    public function test_issueAccessToken_missingClientId()
    {
        $a = $this->returnDefault();
        $a->addGrantType(new OAuth2\Grant\AuthCode());

        $v = $a->issueAccessToken(array(
            'grant_type'    =>  'authorization_code'
        ));
    }

    /**
     * @expectedException        OAuth2\Exception\ClientException
     * @expectedExceptionCode    0
     */
    public function test_issueAccessToken_missingClientSecret()
    {
        $a = $this->returnDefault();
        $a->addGrantType(new OAuth2\Grant\AuthCode());

        $v = $a->issueAccessToken(array(
            'grant_type'    =>  'authorization_code',
            'client_id' =>  1234
        ));
    }

    /**
     * @expectedException        OAuth2\Exception\ClientException
     * @expectedExceptionCode    0
     */
    public function test_issueAccessToken_missingRedirectUri()
    {
        $a = $this->returnDefault();
        $a->addGrantType(new OAuth2\Grant\AuthCode());

        $v = $a->issueAccessToken(array(
            'grant_type'    =>  'authorization_code',
            'client_id' =>  1234,
            'client_secret' =>  5678
        ));
    }

    /**
     * @expectedException        OAuth2\Exception\ClientException
     * @expectedExceptionCode    8
     */
    public function test_issueAccessToken_badClient()
    {
        $this->client->shouldReceive('getClient')->andReturn(false);

        $a = $this->returnDefault();
        $a->addGrantType(new OAuth2\Grant\AuthCode());

        $v = $a->issueAccessToken(array(
            'grant_type'    =>  'authorization_code',
            'client_id' =>  1234,
            'client_secret' =>  5678,
            'redirect_uri'  =>  'http://foo/redirect'
        ));
    }

    /**
     * @expectedException        OAuth2\Exception\ClientException
     * @expectedExceptionCode    0
     */
    public function test_issueAccessToken_missingCode()
    {
        $this->client->shouldReceive('getClient')->andReturn(array());

        $a = $this->returnDefault();
        $a->addGrantType(new OAuth2\Grant\AuthCode());

        $v = $a->issueAccessToken(array(
            'grant_type'    =>  'authorization_code',
            'client_id' =>  1234,
            'client_secret' =>  5678,
            'redirect_uri'  =>  'http://foo/redirect'
        ));
    }

    /**
     * @expectedException        OAuth2\Exception\ClientException
     * @expectedExceptionCode    9
     */
    public function test_issueAccessToken_badCode()
    {
        $this->client->shouldReceive('getClient')->andReturn(array());
        $this->session->shouldReceive('validateAuthCode')->andReturn(false);

        $a = $this->returnDefault();
        $a->addGrantType(new OAuth2\Grant\AuthCode());

        $v = $a->issueAccessToken(array(
            'grant_type'    =>  'authorization_code',
            'client_id' =>  1234,
            'client_secret' =>  5678,
            'redirect_uri'  =>  'http://foo/redirect',
            'code'  =>  'foobar'
        ));
    }

    public function test_issueAccessToken_passedInput()
    {
        $this->client->shouldReceive('getClient')->andReturn(array(
            'client_id' =>  1234,
            'client_secret' =>  5678,
            'redirect_uri'  =>  'http://foo/redirect',
            'name'  =>  'Example Client'
        ));

        $this->session->shouldReceive('validateAuthCode')->andReturn(1);
        $this->session->shouldReceive('updateSession')->andReturn(null);

        $a = $this->returnDefault();
        $a->addGrantType(new OAuth2\Grant\AuthCode());

        $v = $a->issueAccessToken(array(
            'grant_type'    =>  'authorization_code',
            'client_id' =>  1234,
            'client_secret' =>  5678,
            'redirect_uri'  =>  'http://foo/redirect',
            'code'  =>  'foobar'
        ));

        $this->assertArrayHasKey('access_token', $v);
        $this->assertArrayHasKey('token_type', $v);
        $this->assertArrayHasKey('expires', $v);
        $this->assertArrayHasKey('expires_in', $v);

        $this->assertEquals($a::getExpiresIn(), $v['expires_in']);
        $this->assertEquals(time()+$a::getExpiresIn(), $v['expires']);
    }

    public function test_issueAccessToken()
    {
        $this->client->shouldReceive('getClient')->andReturn(array(
            'client_id' =>  1234,
            'client_secret' =>  5678,
            'redirect_uri'  =>  'http://foo/redirect',
            'name'  =>  'Example Client'
        ));

        $this->session->shouldReceive('validateAuthCode')->andReturn(1);
        $this->session->shouldReceive('updateSession')->andReturn(null);

        $a = $this->returnDefault();
        $a->addGrantType(new OAuth2\Grant\AuthCode());

        $_POST['grant_type'] = 'authorization_code';
        $_POST['client_id'] = 1234;
        $_POST['client_secret'] = 5678;
        $_POST['redirect_uri'] = 'http://foo/redirect';
        $_POST['code'] = 'foobar';

        $request = new OAuth2\Util\Request(array(), $_POST);
        $a->setRequest($request);

        $v = $a->issueAccessToken();

        $this->assertArrayHasKey('access_token', $v);
        $this->assertArrayHasKey('token_type', $v);
        $this->assertArrayHasKey('expires', $v);
        $this->assertArrayHasKey('expires_in', $v);

        $this->assertEquals($a::getExpiresIn(), $v['expires_in']);
        $this->assertEquals(time()+$a::getExpiresIn(), $v['expires']);
    }

    public function test_issueAccessToken_with_refresh_token()
    {
        $this->client->shouldReceive('getClient')->andReturn(array(
            'client_id' =>  1234,
            'client_secret' =>  5678,
            'redirect_uri'  =>  'http://foo/redirect',
            'name'  =>  'Example Client'
        ));

        $this->session->shouldReceive('validateAuthCode')->andReturn(1);
        $this->session->shouldReceive('updateSession')->andReturn(null);

        $a = $this->returnDefault();
        $a->addGrantType(new OAuth2\Grant\AuthCode());
        $a->addGrantType(new OAuth2\Grant\RefreshToken());

        $_POST['grant_type'] = 'authorization_code';
        $_POST['client_id'] = 1234;
        $_POST['client_secret'] = 5678;
        $_POST['redirect_uri'] = 'http://foo/redirect';
        $_POST['code'] = 'foobar';

        $request = new OAuth2\Util\Request(array(), $_POST);
        $a->setRequest($request);

        $v = $a->issueAccessToken();

        $this->assertArrayHasKey('access_token', $v);
        $this->assertArrayHasKey('token_type', $v);
        $this->assertArrayHasKey('expires', $v);
        $this->assertArrayHasKey('expires_in', $v);
        $this->assertArrayHasKey('refresh_token', $v);

        $this->assertEquals($a::getExpiresIn(), $v['expires_in']);
        $this->assertEquals(time()+$a::getExpiresIn(), $v['expires']);
    }

    /**
     * @expectedException        OAuth2\Exception\ClientException
     * @expectedExceptionCode    0
     */
    public function test_issueAccessToken_refreshTokenGrant_missingClientId()
    {
        $a = $this->returnDefault();
        $a->addGrantType(new OAuth2\Grant\RefreshToken());

        $request = new OAuth2\Util\Request(array(), $_POST);
        $a->setRequest($request);

        $v = $a->issueAccessToken(array(
            'grant_type'    =>  'refresh_token'
        ));
    }

    /**
     * @expectedException        OAuth2\Exception\ClientException
     * @expectedExceptionCode    0
     */
    public function test_issueAccessToken_refreshTokenGrant_missingClientSecret()
    {
        $a = $this->returnDefault();
        $a->addGrantType(new OAuth2\Grant\RefreshToken());

        $request = new OAuth2\Util\Request(array(), $_POST);
        $a->setRequest($request);

        $v = $a->issueAccessToken(array(
            'grant_type'    =>  'refresh_token',
            'client_id' =>  1234
        ));
    }

    /**
     * @expectedException        OAuth2\Exception\ClientException
     * @expectedExceptionCode    8
     */
    public function test_issueAccessToken_refreshTokenGrant_badClient()
    {
        $this->client->shouldReceive('getClient')->andReturn(false);

        $a = $this->returnDefault();
        $a->addGrantType(new OAuth2\Grant\RefreshToken());

        $request = new OAuth2\Util\Request(array(), $_POST);
        $a->setRequest($request);

        $v = $a->issueAccessToken(array(
            'grant_type'    =>  'refresh_token',
            'client_id' =>  1234,
            'client_secret' =>  5678
        ));
    }

    /**
     * @expectedException        OAuth2\Exception\ClientException
     * @expectedExceptionCode    0
     */
    public function test_issueAccessToken_refreshTokenGrant_missingRefreshToken()
    {
        $this->client->shouldReceive('getClient')->andReturn(array());

        $a = $this->returnDefault();
        $a->addGrantType(new OAuth2\Grant\RefreshToken());

        $request = new OAuth2\Util\Request(array(), $_POST);
        $a->setRequest($request);

        $v = $a->issueAccessToken(array(
            'grant_type'    =>  'refresh_token',
            'client_id' =>  1234,
            'client_secret' =>  5678,
            //'refresh_token' =>
        ));
    }

    /**
     * @expectedException        OAuth2\Exception\ClientException
     * @expectedExceptionCode    0
     */
    public function test_issueAccessToken_refreshTokenGrant_badRefreshToken()
    {
        $this->client->shouldReceive('getClient')->andReturn(array());
        $this->client->shouldReceive('validateRefreshToken')->andReturn(false);

        $a = $this->returnDefault();
        $a->addGrantType(new OAuth2\Grant\RefreshToken());

        $request = new OAuth2\Util\Request(array(), $_POST);
        $a->setRequest($request);

        $v = $a->issueAccessToken(array(
            'grant_type'    =>  'refresh_token',
            'client_id' =>  1234,
            'client_secret' =>  5678,
            'refresh_token' =>  'abcdef'
        ));
    }

    public function test_issueAccessToken_refreshTokenGrant_passedInput()
    {
        $this->client->shouldReceive('getClient')->andReturn(array(
            'client_id' =>  1234,
            'client_secret' =>  5678,
            'redirect_uri'  =>  'http://foo/redirect',
            'name'  =>  'Example Client'
        ));

        $this->client->shouldReceive('validateRefreshToken')->andReturn(1);

        $this->session->shouldReceive('validateAuthCode')->andReturn(1);
        $this->session->shouldReceive('updateSession')->andReturn(null);
        $this->session->shouldReceive('updateRefreshToken')->andReturn(null);

        $a = $this->returnDefault();
        $a->addGrantType(new OAuth2\Grant\RefreshToken());

        $_POST['grant_type'] = 'refresh_token';
        $_POST['client_id'] = 1234;
        $_POST['client_secret'] = 5678;
        $_POST['refresh_token'] = 'abcdef';

        $request = new OAuth2\Util\Request(array(), $_POST);
        $a->setRequest($request);

        $v = $a->issueAccessToken();

        $this->assertArrayHasKey('access_token', $v);
        $this->assertArrayHasKey('token_type', $v);
        $this->assertArrayHasKey('expires', $v);
        $this->assertArrayHasKey('expires_in', $v);
        $this->assertArrayHasKey('refresh_token', $v);

        $this->assertEquals($a::getExpiresIn(), $v['expires_in']);
        $this->assertEquals(time()+$a::getExpiresIn(), $v['expires']);
    }

    public function test_issueAccessToken_refreshTokenGrant()
    {
        $this->client->shouldReceive('getClient')->andReturn(array(
            'client_id' =>  1234,
            'client_secret' =>  5678,
            'redirect_uri'  =>  'http://foo/redirect',
            'name'  =>  'Example Client'
        ));

        $this->client->shouldReceive('validateRefreshToken')->andReturn(1);

        $this->session->shouldReceive('validateAuthCode')->andReturn(1);
        $this->session->shouldReceive('updateSession')->andReturn(null);
        $this->session->shouldReceive('updateRefreshToken')->andReturn(null);

        $a = $this->returnDefault();
        $a->addGrantType(new OAuth2\Grant\RefreshToken());

        $v = $a->issueAccessToken(array(
            'grant_type'    =>  'refresh_token',
            'client_id' =>  1234,
            'client_secret' =>  5678,
            'refresh_token'  =>  'abcdef',
        ));

        $this->assertArrayHasKey('access_token', $v);
        $this->assertArrayHasKey('token_type', $v);
        $this->assertArrayHasKey('expires', $v);
        $this->assertArrayHasKey('expires_in', $v);
        $this->assertArrayHasKey('refresh_token', $v);

        $this->assertEquals($a::getExpiresIn(), $v['expires_in']);
        $this->assertEquals(time()+$a::getExpiresIn(), $v['expires']);
    }

    /**
     * @expectedException        OAuth2\Exception\ClientException
     * @expectedExceptionCode    0
     */
    public function test_issueAccessToken_clientCredentialsGrant_missingClientId()
    {
        $a = $this->returnDefault();
        $a->addGrantType(new OAuth2\Grant\ClientCredentials());

        $request = new OAuth2\Util\Request(array(), $_POST);
        $a->setRequest($request);

        $v = $a->issueAccessToken(array(
            'grant_type'    =>  'client_credentials'
        ));
    }

    /**
     * @expectedException        OAuth2\Exception\ClientException
     * @expectedExceptionCode    0
     */
    public function test_issueAccessToken_clientCredentialsGrant_missingClientPassword()
    {
        $a = $this->returnDefault();
        $a->addGrantType(new OAuth2\Grant\ClientCredentials());

        $request = new OAuth2\Util\Request(array(), $_POST);
        $a->setRequest($request);

        $v = $a->issueAccessToken(array(
            'grant_type'    =>  'client_credentials',
            'client_id' =>  1234
        ));
    }

    /**
     * @expectedException        OAuth2\Exception\ClientException
     * @expectedExceptionCode    8
     */
    public function test_issueAccessToken_clientCredentialsGrant_badClient()
    {
        $this->client->shouldReceive('getClient')->andReturn(false);

        $a = $this->returnDefault();
        $a->addGrantType(new OAuth2\Grant\ClientCredentials());

        $request = new OAuth2\Util\Request(array(), $_POST);
        $a->setRequest($request);

        $v = $a->issueAccessToken(array(
            'grant_type'    =>  'client_credentials',
            'client_id' =>  1234,
            'client_secret' =>  5678
        ));
    }

    function test_issueAccessToken_clientCredentialsGrant_passedInput()
    {
        $this->client->shouldReceive('getClient')->andReturn(array(
            'client_id' =>  1234,
            'client_secret' =>  5678,
            'redirect_uri'  =>  'http://foo/redirect',
            'name'  =>  'Example Client'
        ));

        $this->client->shouldReceive('validateRefreshToken')->andReturn(1);

        $this->session->shouldReceive('validateAuthCode')->andReturn(1);
        $this->session->shouldReceive('createSession')->andReturn(1);
        $this->session->shouldReceive('deleteSession')->andReturn(null);
        $this->session->shouldReceive('updateRefreshToken')->andReturn(null);

        $a = $this->returnDefault();
        $a->addGrantType(new OAuth2\Grant\ClientCredentials());

        $v = $a->issueAccessToken(array(
            'grant_type'    =>  'client_credentials',
            'client_id' =>  1234,
            'client_secret' =>  5678
        ));

        $this->assertArrayHasKey('access_token', $v);
        $this->assertArrayHasKey('token_type', $v);
        $this->assertArrayHasKey('expires', $v);
        $this->assertArrayHasKey('expires_in', $v);
        $this->assertArrayHasKey('refresh_token', $v);

        $this->assertEquals($a::getExpiresIn(), $v['expires_in']);
        $this->assertEquals(time()+$a::getExpiresIn(), $v['expires']);
    }

    function test_issueAccessToken_clientCredentialsGrant()
    {
        $this->client->shouldReceive('getClient')->andReturn(array(
            'client_id' =>  1234,
            'client_secret' =>  5678,
            'redirect_uri'  =>  'http://foo/redirect',
            'name'  =>  'Example Client'
        ));

        $this->client->shouldReceive('validateRefreshToken')->andReturn(1);

        $this->session->shouldReceive('validateAuthCode')->andReturn(1);
        $this->session->shouldReceive('createSession')->andReturn(1);
        $this->session->shouldReceive('deleteSession')->andReturn(null);
        $this->session->shouldReceive('updateRefreshToken')->andReturn(null);

        $a = $this->returnDefault();
        $a->addGrantType(new OAuth2\Grant\ClientCredentials());

        $_POST['grant_type'] = 'client_credentials';
        $_POST['client_id'] = 1234;
        $_POST['client_secret'] = 5678;

        $request = new OAuth2\Util\Request(array(), $_POST);
        $a->setRequest($request);

        $v = $a->issueAccessToken();

        $this->assertArrayHasKey('access_token', $v);
        $this->assertArrayHasKey('token_type', $v);
        $this->assertArrayHasKey('expires', $v);
        $this->assertArrayHasKey('expires_in', $v);
        $this->assertArrayHasKey('refresh_token', $v);

        $this->assertEquals($a::getExpiresIn(), $v['expires_in']);
        $this->assertEquals(time()+$a::getExpiresIn(), $v['expires']);
    }

    /**
     * @expectedException        OAuth2\Exception\ClientException
     * @expectedExceptionCode    0
     */
    public function test_issueAccessToken_passwordGrant_missingClientId()
    {
        $a = $this->returnDefault();
        $a->addGrantType(new OAuth2\Grant\Password());

        $request = new OAuth2\Util\Request(array(), $_POST);
        $a->setRequest($request);

        $v = $a->issueAccessToken(array(
            'grant_type'    =>  'client_credentials'
        ));
    }

    /**
     * @expectedException        OAuth2\Exception\ClientException
     * @expectedExceptionCode    0
     */
    public function test_issueAccessToken_passwordGrant_missingClientPassword()
    {
        $a = $this->returnDefault();
        $a->addGrantType(new OAuth2\Grant\Password());

        $request = new OAuth2\Util\Request(array(), $_POST);
        $a->setRequest($request);

        $v = $a->issueAccessToken(array(
            'grant_type'    =>  'password',
            'client_id' =>  1234
        ));
    }

    /**
     * @expectedException        OAuth2\Exception\ClientException
     * @expectedExceptionCode    8
     */
    public function test_issueAccessToken_passwordGrant_badClient()
    {
        $this->client->shouldReceive('getClient')->andReturn(false);

        $a = $this->returnDefault();
        $a->addGrantType(new OAuth2\Grant\Password());

        $request = new OAuth2\Util\Request(array(), $_POST);
        $a->setRequest($request);

        $v = $a->issueAccessToken(array(
            'grant_type'    =>  'password',
            'client_id' =>  1234,
            'client_secret' =>  5678
        ));
    }

    /**
     * @expectedException        OAuth2\Exception\InvalidGrantTypeException
     */
    function test_issueAccessToken_passwordGrant_invalidCallback()
    {
        $this->client->shouldReceive('getClient')->andReturn(array(
            'client_id' =>  1234,
            'client_secret' =>  5678,
            'redirect_uri'  =>  'http://foo/redirect',
            'name'  =>  'Example Client'
        ));

        $this->client->shouldReceive('validateRefreshToken')->andReturn(1);

        $this->session->shouldReceive('validateAuthCode')->andReturn(1);
        $this->session->shouldReceive('createSession')->andReturn(1);
        $this->session->shouldReceive('deleteSession')->andReturn(null);
        $this->session->shouldReceive('updateRefreshToken')->andReturn(null);

        $testCredentials = null;

        $a = $this->returnDefault();
        $pgrant = new OAuth2\Grant\Password();
        $pgrant->setVerifyCredentialsCallback($testCredentials);
        $a->addGrantType($pgrant);

        $v = $a->issueAccessToken(array(
            'grant_type'    =>  'password',
            'client_id' =>  1234,
            'client_secret' =>  5678,
            'username'  => 'foo',
            'password'  => 'bar'
        ));
    }

    /**
     * @expectedException        OAuth2\Exception\ClientException
     * @expectedExceptionCode    0
     */
    function test_issueAccessToken_passwordGrant_missingUsername()
    {
        $this->client->shouldReceive('getClient')->andReturn(array(
            'client_id' =>  1234,
            'client_secret' =>  5678,
            'redirect_uri'  =>  'http://foo/redirect',
            'name'  =>  'Example Client'
        ));

        $this->client->shouldReceive('validateRefreshToken')->andReturn(1);

        $this->session->shouldReceive('validateAuthCode')->andReturn(1);
        $this->session->shouldReceive('createSession')->andReturn(1);
        $this->session->shouldReceive('deleteSession')->andReturn(null);
        $this->session->shouldReceive('updateRefreshToken')->andReturn(null);

        $testCredentials = function($u, $p) { return false; };

        $a = $this->returnDefault();
        $pgrant = new OAuth2\Grant\Password();
        $pgrant->setVerifyCredentialsCallback($testCredentials);
        $a->addGrantType($pgrant);

        $v = $a->issueAccessToken(array(
            'grant_type'    =>  'password',
            'client_id' =>  1234,
            'client_secret' =>  5678
        ));
    }

    /**
     * @expectedException        OAuth2\Exception\ClientException
     * @expectedExceptionCode    0
     */
    function test_issueAccessToken_passwordGrant_missingPassword()
    {
        $this->client->shouldReceive('getClient')->andReturn(array(
            'client_id' =>  1234,
            'client_secret' =>  5678,
            'redirect_uri'  =>  'http://foo/redirect',
            'name'  =>  'Example Client'
        ));

        $this->client->shouldReceive('validateRefreshToken')->andReturn(1);

        $this->session->shouldReceive('validateAuthCode')->andReturn(1);
        $this->session->shouldReceive('createSession')->andReturn(1);
        $this->session->shouldReceive('deleteSession')->andReturn(null);
        $this->session->shouldReceive('updateRefreshToken')->andReturn(null);

        $testCredentials = function($u, $p) { return false; };

        $a = $this->returnDefault();
        $pgrant = new OAuth2\Grant\Password();
        $pgrant->setVerifyCredentialsCallback($testCredentials);
        $a->addGrantType($pgrant);

        $v = $a->issueAccessToken(array(
            'grant_type'    =>  'password',
            'client_id' =>  1234,
            'client_secret' =>  5678,
            'username'  =>  'foo'
        ));
    }

    /**
     * @expectedException        OAuth2\Exception\ClientException
     * @expectedExceptionCode    0
     */
    function test_issueAccessToken_passwordGrant_badCredentials()
    {
        $this->client->shouldReceive('getClient')->andReturn(array(
            'client_id' =>  1234,
            'client_secret' =>  5678,
            'redirect_uri'  =>  'http://foo/redirect',
            'name'  =>  'Example Client'
        ));

        $this->client->shouldReceive('validateRefreshToken')->andReturn(1);

        $this->session->shouldReceive('validateAuthCode')->andReturn(1);
        $this->session->shouldReceive('createSession')->andReturn(1);
        $this->session->shouldReceive('deleteSession')->andReturn(null);
        $this->session->shouldReceive('updateRefreshToken')->andReturn(null);

        $testCredentials = function($u, $p) { return false; };

        $a = $this->returnDefault();
        $pgrant = new OAuth2\Grant\Password();
        $pgrant->setVerifyCredentialsCallback($testCredentials);
        $a->addGrantType($pgrant);

        $v = $a->issueAccessToken(array(
            'grant_type'    =>  'password',
            'client_id' =>  1234,
            'client_secret' =>  5678,
            'username'  => 'foo',
            'password'  => 'bar'
        ));
    }

    function test_issueAccessToken_passwordGrant_passedInput()
    {
        $this->client->shouldReceive('getClient')->andReturn(array(
            'client_id' =>  1234,
            'client_secret' =>  5678,
            'redirect_uri'  =>  'http://foo/redirect',
            'name'  =>  'Example Client'
        ));

        $this->client->shouldReceive('validateRefreshToken')->andReturn(1);

        $this->session->shouldReceive('validateAuthCode')->andReturn(1);
        $this->session->shouldReceive('createSession')->andReturn(1);
        $this->session->shouldReceive('deleteSession')->andReturn(null);
        $this->session->shouldReceive('updateRefreshToken')->andReturn(null);

        $testCredentials = function($u, $p) { return 1; };

        $a = $this->returnDefault();
        $pgrant = new OAuth2\Grant\Password();
        $pgrant->setVerifyCredentialsCallback($testCredentials);
        $a->addGrantType($pgrant);

        $v = $a->issueAccessToken(array(
            'grant_type'    =>  'password',
            'client_id' =>  1234,
            'client_secret' =>  5678,
            'username'  => 'foo',
            'password'  => 'bar'
        ));

        $this->assertArrayHasKey('access_token', $v);
        $this->assertArrayHasKey('token_type', $v);
        $this->assertArrayHasKey('expires', $v);
        $this->assertArrayHasKey('expires_in', $v);
        $this->assertArrayHasKey('refresh_token', $v);

        $this->assertEquals($a::getExpiresIn(), $v['expires_in']);
        $this->assertEquals(time()+$a::getExpiresIn(), $v['expires']);
    }

    function test_issueAccessToken_passwordGrant()
    {
        $this->client->shouldReceive('getClient')->andReturn(array(
            'client_id' =>  1234,
            'client_secret' =>  5678,
            'redirect_uri'  =>  'http://foo/redirect',
            'name'  =>  'Example Client'
        ));

        $this->client->shouldReceive('validateRefreshToken')->andReturn(1);

        $this->session->shouldReceive('validateAuthCode')->andReturn(1);
        $this->session->shouldReceive('createSession')->andReturn(1);
        $this->session->shouldReceive('deleteSession')->andReturn(null);
        $this->session->shouldReceive('updateRefreshToken')->andReturn(null);

        $testCredentials = function($u, $p) { return 1; };

        $a = $this->returnDefault();
        $pgrant = new OAuth2\Grant\Password();
        $pgrant->setVerifyCredentialsCallback($testCredentials);
        $a->addGrantType($pgrant);

        $_POST['grant_type'] = 'password';
        $_POST['client_id'] = 1234;
        $_POST['client_secret'] = 5678;
        $_POST['username'] = 'foo';
        $_POST['password'] = 'bar';

        $request = new OAuth2\Util\Request(array(), $_POST);
        $a->setRequest($request);

        $v = $a->issueAccessToken();

        $this->assertArrayHasKey('access_token', $v);
        $this->assertArrayHasKey('token_type', $v);
        $this->assertArrayHasKey('expires', $v);
        $this->assertArrayHasKey('expires_in', $v);
        $this->assertArrayHasKey('refresh_token', $v);

        $this->assertEquals($a::getExpiresIn(), $v['expires_in']);
        $this->assertEquals(time()+$a::getExpiresIn(), $v['expires']);
    }

    public function tearDown() {
        M::close();
    }
}