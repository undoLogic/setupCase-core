<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\View\Helper;
use Cake\View\View;

/**
 * Lang helper
 */
class LangHelper extends Helper
{
    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected $_defaultConfig = [];

    function get(): string {
        $req = $this->getView()->getRequest();
        return $req->getAttribute('lang');
    }

    function is($lang): bool {
        $current = $this->get();
        if ($lang == $current) {
            return true;
        }
        return false;
    }
    function getActiveClass($langToCheck, $classname = 'active'): string {
        if ($this->is($langToCheck)) {
            return $classname;
        } else {
            return '';
        }
    }

}
