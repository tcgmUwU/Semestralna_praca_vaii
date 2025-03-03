<?php

namespace App\Controllers;

use App\Core\AControllerBase;
use App\Core\Responses\Response;
use App\Models\User;

/**
 * Class HomeController
 * Example class of a controller
 * @package App\Controllers
 */
class AdminController extends AControllerBase
{
    /**
     * Authorize controller actions
     * @param $action
     * @return bool
     */
    public function authorize($action)
    {
        if ($this->app->getAuth()->isLogged()) {
            $queryResult = User::getAll('`email` = ?', [$this->app->getAuth()->getLoggedUserId()]);
            $user = $queryResult[0];
            return (strcmp($user->getRole(), 'admin') === 0);
        }
        return false;
    }

    /**
     * Example of an action (authorization needed)
     * @return \App\Core\Responses\Response|\App\Core\Responses\ViewResponse
     */
    public function index(): Response
    {
        //die($this->app->getRequest()->getValue('result'));
        return $this->html();
    }
}
