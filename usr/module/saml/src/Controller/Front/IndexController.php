<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link            http://code.pialog.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://pialog.org
 * @license         http://pialog.org/license.txt New BSD License
 */
namespace Module\Saml\Controller\Front;

use Pi;
use Pi\Mvc\Controller\ActionController;

class IndexController extends ActionController
{
    /**
     * Do nothing
     *
     * @return void
     */
    public function indexAction()
    {
        $this->redirect()->toRoute('', array('action' => 'login'));
    }

    /**
     * Endpoint for SSO login
     */
    public function loginAction()
    {
        $redirect = Pi::url($this->params('redirect', Pi::url('www')), true);
        Pi::service('authentication')->login(array('redirect' => $redirect));
    }

    /**
     * Endpoint for SSO logout
     */
    public function logoutAction()
    {
        $redirect = Pi::url($this->params('redirect', Pi::url('www')), true);
        Pi::service('authentication')->logout(array('redirect' => $redirect));
    }

    /**
     * Endpoint for SSO ACS
     */
    public function acsAction()
    {
        $this->canonizeRequest();
        //$_SERVER['SCRIPT_NAME'] = $this->url('', array('action' => 'acs')) . 'sid';
        //$_SERVER['PATH_INFO']   = '-' . _get('sid');

        require_once Pi::path('vendor') . '/simplesamlphp/lib/_autoload.php';
        require_once Pi::path('vendor') . '/simplesamlphp/modules/saml/www/sp/saml2-acs.php';
    }

    /**
     * Endpoint for SSO ACS logout
     */
    public function acslogoutAction()
    {
        $this->canonizeRequest();
        //$_SERVER['SCRIPT_NAME'] = $this->url('', array('action' => 'acslogout')) . 'sid';
        //$_SERVER['PATH_INFO']   = '-' . _get('sid');

        require_once Pi::path('vendor') . '/simplesamlphp/lib/_autoload.php';
        require_once Pi::path('vendor') . '/simplesamlphp/modules/saml/www/sp/saml2-logout.php';
    }

    /**
     * Canonize `SCRIPT_NAME` and `PATH_INFO` for SSP URL check
     *
     * @return void
     * @see lib/vendor/simplesamlphp/modules/saml/www/sp/saml2-acs.php
     * @see lib/vendor/simplesamlphp/modules/saml/www/sp/saml2-logout.php
     */
    protected function canonizeRequest()
    {
        $requestUri = Pi::service('url')->getRequestUri();
        $sourceId = $this->params('sid');
        $sidPos = -1 * strlen($sourceId) - 1;
        $_SERVER['SCRIPT_NAME'] = substr($requestUri, 0, $sidPos);
        $_SERVER['PATH_INFO'] = substr($requestUri, $sidPos);
    }

    /**
     * Test for get data
     */
    public function getdataAction()
    {
        $data = Pi::service('authentication')->getData();
        var_dump($data);
        exit;
    }
}