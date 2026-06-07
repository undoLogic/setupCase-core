<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\Core\Configure;
use Cake\View\Helper;
use Cake\View\View;

/**
 * Html helper
 */
class HtmlHelper extends Helper\HtmlHelper
{

    public function link($title, $url = null, array $options = []): string
    {
        if (is_array($url)) {

            //add the language always
            $req = $this->getView()->getRequest();
            $lang = $req->getAttribute('lang');
            if (!isset($url['language'])) {
                $url['language'] = $lang;
            }

            //add our arguments to the link in case we are switching languages on a complex url
            if (!isset($url['pass'])) {
                $req = $this->getView()->getRequest();
                $params = $req->getAttribute('params');

                if (isset($params['pass'])) {
                    $url = $url + $params['pass'];
                }
            }
        }

        return parent::link($title, $url, $options);
    }

}
