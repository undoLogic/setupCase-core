<?php

namespace App\Middleware;

use Authentication\Authenticator\ResultInterface;
use Authentication\Authenticator\Result;

use Cake\Log\Log;
use Cake\Routing\Router;

use Authentication\Authenticator\FormAuthenticator;

use Authentication\Identifier\IdentifierInterface;
use Authentication\UrlChecker\UrlCheckerTrait;
use Cake\Datasource\FactoryLocator;
use Cake\ORM\Locator\LocatorAwareTrait;
use Composer\Util\Url;
use Psr\Http\Message\ServerRequestInterface;

class FormLoginAttemptsAuthenticator extends FormAuthenticator
{
    use LocatorAwareTrait;

    public function authenticate(ServerRequestInterface $request): ResultInterface
    {
        Log::debug('authe');

        dd('hi');
        $sameRequest = Router::getRequest();

        if (!$this->_checkUrl($request)) {
            return $this->_buildLoginUrlErrorResult($request);
        }

        $data = $this->_getData($request);
        if ($data === null) {
            return new Result(null, Result::FAILURE_CREDENTIALS_MISSING, [
                'Login credentials not found',
            ]);
        }

        dd('before');
        $ip = $sameRequest->clientIp();
        dd($ip);
        if ($this->fetchTable('FormAttempts')->tooManyFailures()) {
            //dd('true');
            die('too many');
            //Log::debug('too many');
            //PHP redirect to static page
            header('Location: '.'/tooMany.php');
        } else {
            die('else');
        }


        $user = $this->_identifier->identify($data);

        dd($user);
        if (empty($user)) {


            //add to db here
            $this->fetchTable('FormAttempts')->saveFailure($sameRequest->clientIp());

            return new Result(null, Result::FAILURE_IDENTITY_NOT_FOUND, $this->_identifier->getErrors());
        }

        return new Result($user, Result::SUCCESS);
    }
}
