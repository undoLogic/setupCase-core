<?php
// src/Model/Table/ArticlesTable.php
namespace App\Model\Table;

use App\Util\SetupCase;
use Cake\Datasource\FactoryLocator;
use Cake\Log\Log;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use ConnectionManager;
use Cake\I18n\FrozenTime;

class FormAttemptsTable extends Table
{

    public function initialize(array $config): void
    {
        $this->addBehavior('Timestamp');
        parent::initialize($config);
        $this->setTable('form_attempts');
    }// initialize

    public function saveFailure($ip) {

        Log::debug('Saving Failure');

        $new = $this->newEmptyEntity();
        $new->ip = $ip;
        $new->date = date('Y-m-d H:i:s');

        if ($this->save($new)) {
            return true;
        } else {
            return false;
        }

    }

    private function removeOldRows($ip) {

        // Get the current time
        $currentTime = FrozenTime::now();

        // Calculate the time 5 minutes ago
        $fiveMinutesAgo = $currentTime->subMinutes(5);

        $conditions = [
            'ip' => $ip,
            'date <' => $fiveMinutesAgo
        ];

        return $this->deleteAll($conditions);
    }

    public function tooManyFailures($ip) {

        $this->removeOldRows($ip);

        $conditions = ['FormAttempts.ip' => $ip];

        $found = $this->find()
            ->where($conditions)
            ->count();

        Log::debug('tooManyFailures: '.$found);

        if ($found > 5) {
            return true;
        } else {
            return false;
        }
    }
}
