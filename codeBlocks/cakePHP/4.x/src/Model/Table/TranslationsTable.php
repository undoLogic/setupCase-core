<?php
// src/Model/Table/ArticlesTable.php
namespace App\Model\Table;

use App\Util\Assets;
use App\Util\SetupCase;
use App\Util\Translate as Translate;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class TranslationsTable extends Table
{
    public function initialize(array $config):void
    {
        $this->addBehavior('Timestamp');
        parent::initialize($config);
        $this->setTable('translations');


        //create database
        /*
         * CREATE TABLE `translations` (`id` INT NOT NULL AUTO_INCREMENT , `keyword` VARCHAR(1000) NULL , `en` VARCHAR(1000) NULL , `fr` VARCHAR(1000) NULL , `created` DATETIME NOT NULL , `modified` DATETIME NOT NULL , PRIMARY KEY (`id`)) ENGINE = MyISAM;
         */

        //put this in the initialize in Appcontroller
        /*
         $this->Translate = new Translate([
            'lang' => $this->request->getAttribute('lang')
        ]);
        $this->translate = $this->Translate;
        $this->set('translate', $this->Translate);
         */




    }


    public function setup($lang) {
        $this->currentLang = $lang;
    }

    var $currentLang = false;
    var $translations = [];
    public function word($word) {

        $term = strtolower($word);


        $this->translations = $this->find('list', [
            'keyField' => 'keyword',
            'valueField' => $this->currentLang,
        ])->toArray();

        if (isset($this->translations[$term])) {
            //dd($this->translations[$term]);
            if (empty($this->translations[$term])) {
                return $word;
            } else {
                return $this->translations[$term];
            }

        } else {

            $newWord = $this->newEmptyEntity();

            $newWord['keyword'] = $term;
            $newWord['en'] = $word;
            $newWord['fr'] = '';

            if ($this->save($newWord)) {
                //saved
            } else {

            }


            return $word;

        }

    }

    public function import($csvFile)
    {

        //dd($csvFile);
        $this->count = 0;
        $this->data = SetupCase::parseCsv($csvFile);


        //dd($this->data);
        if(!empty($this->data)) {
            foreach ($this->data as $key => $row) {
                foreach ($row as $lang => $value) {
                    unset($this->data[$key][$lang]);
                    if(!empty($value)) {
                        if ($lang === 'en') {
                            $keyword = $value;
                        }

                        $this->data[$key][trim($lang)] = trim($value);

                        if ($lang === 'en') {
                            $this->data[$key]['keyword'] = trim($value);
                        }
                        if($lang === 'es'){
                            unset($this->data[$key][$lang]);
                        }
                    }//if not empty value
                }
            }//endforeach this-data
            $response = $this->import_process();


        }//if not empty
        else{
            $response = ['STATUS' => 400, 'MESSAGE' => 'No data found'];
        }
        return $response;
    }//import
    private function import_process(){
        $connection = ConnectionManager::get('default');
        $connection->begin();
        $errors = false;

        foreach($this->data as $key => $row) {
            if(isset($row['keyword'])) {
                if(!$this->ensureExists($row['keyword'])) {
                    $entity = $this->newEntity($row);
                    if($this->save($entity)) {
                        $this->count++;
                    }else{
                        $errors = true;
                    }
                }
            }

        }//endforeach to save

        if($errors) {
            $connection->rollback();
            $response = ['STATUS'=>200, 'MESSAGE'=>'Errors occured while importing data'];
        }else{
            if($this->count > 0){
                $connection->commit();
                $response = ['STATUS'=>200, 'MESSAGE'=> "$this->count Translations Imported"];
            }else{
                $response = ['STATUS'=>200, 'MESSAGE'=> "No new data to Import"];
            }

        }
        return $response;
    }

    private function ensureExists($keyword){
        $found = $this->find()->where(['Translations.keyword'=>$keyword]);
        if($found->count() > 0){
            return true;
        }
        return false;
    }

    public function getAllTranslations($data){
        $this->data = $data;
        $this->conditions = [];
        $this->getAllTranslations_setConditions();
        $query = $this->find()
            ->where($this->conditions)
            ->order('Translations.id DESC');

        if($query->count() > 0){
            return $query;
        }
        return false;
    }//getAllTranslations

    private function getAllTranslations_setConditions(){
        if(!empty($this->data)){
            //dd($this->data);
            if(!empty($this->data['search'])){
                $this->conditions[] = [
                    'OR'=>[
                        ['Translations.keyword LIKE' => '%'.$this->data['search'].'%'],
                        ['Translations.en LIKE' => '%'.$this->data['search'].'%'],
                        ['Translations.fr LIKE' => '%'.$this->data['search'].'%'],
                    ]
                ];
            }//search

            if(!empty($this->data['language'])){
                if($this->data['language'] === 'fr_EMPTY'){
                    $this->conditions[] = ['Translations.fr IS' => null];
                }
                //dd($this->data);

            }

           // pr($this->conditions); exit;
        }//if not empty this-data
    }





}// end
