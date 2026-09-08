<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     0.2.9
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */
namespace App\Controller\Admin;

//share the appcontroller between all the views
use App\Controller\AppController;

use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\FactoryLocator;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\View\Exception\MissingTemplateException;

/**
 * Static content controller
 *
 * This controller will render views from templates/Pages/
 *
 * @link https://book.cakephp.org/4/en/controllers/pages-controller.html
 */
class SetupPagesController extends AppController
{


    function home() {

    }
    function dashboard(){

        $this->writeToLog('debug' ,'Admin: dashboard', true);

        // pr('in admin pages dashboard');

       // pr ( $this->getLoggedInUser() );

        //pr ( $this->request->getAttributes());
    }

    function activityLogs() {

        $this->ActivityLogs = FactoryLocator::get('Table')->get('ActivityLogs');

        $cols = $this->ActivityLogs->getSchema()->columns();
        //unset here if you want to limit the view
        $this->set('cols', $cols);

        $logs = $this->ActivityLogs->find('all')->order('id DESC')->limit(10);
        $this->set('logs', $logs);
    }

    public function downloadBackup(): ?Response
    {

        die('Manually choose tables to exclude then enable');
        // Tables above: dump schema only, skip the (large/sensitive) row data
        $schemaOnlyTables = [
            //'users',
        ];

        $config = ConnectionManager::get('default')->config();

        $host = $config['host'];
        $port = $config['port'] ?? '3306';
        $username = $config['username'];
        $password = $config['password'];
        $database = $config['database'];

        $file = TMP . 'dump_' . date('Y-m-d_H-i-s') . '.sql';

        $ignoreTableArgs = '';
        foreach ($schemaOnlyTables as $table) {
            $ignoreTableArgs .= ' --ignore-table=' . escapeshellarg($database . '.' . $table);
        }

        // Pass 1: full schema + data for every table EXCEPT the schema-only ones
        $dataCommand = sprintf(
            'mysqldump --user=%s --password=%s --host=%s --port=%s%s %s > %s',
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($host),
            escapeshellarg($port),
            $ignoreTableArgs,
            escapeshellarg($database),
            escapeshellarg($file)
        );

        exec($dataCommand, $output, $dataReturnVar);

        // Pass 2: schema only (--no-data) for the tables above, appended to the same file
        $schemaOnlyTableArgs = implode(' ', array_map('escapeshellarg', $schemaOnlyTables));

        $schemaCommand = sprintf(
            'mysqldump --user=%s --password=%s --host=%s --port=%s --no-data %s %s >> %s',
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($database),
            $schemaOnlyTableArgs,
            escapeshellarg($file)
        );

        exec($schemaCommand, $output, $schemaReturnVar);

        if ($dataReturnVar === 0 && $schemaReturnVar === 0) {
            $this->response = $this->response->withFile($file, [
                'download' => true,
                'name' => basename($file),
            ]);

            return $this->response;
        }

        die('Could not create dump file');
    }
}
