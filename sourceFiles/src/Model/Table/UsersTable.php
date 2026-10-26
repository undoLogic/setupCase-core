<?php
// src/Model/Table/ArticlesTable.php

namespace App\Model\Table;
use Cake\ORM\Table;
use Cake\Utility\Text;
//use Cake\Validation\Validator;

class UsersTable extends Table
{
    public function initialize(array $config):void
    {
        $this->addBehavior('Timestamp');
        $this->setTable('users');

        // associations optionally add this
//        $this->belongsTo('Groups', [
//            'foreignKey' => 'group_id'
//        ]);

    }

    //TO USE BEFORE $this->save(
//if ($entity->getErrors()) {
//
//    //dd($entity->getErrors());
//$response['STATUS'] = 400;
//$response['MSG'] = $entity->getErrors();
//return $response;
//}
//    public function validationDefault(Validator $validator): Validator
//    {
//        $validator
//            ->requirePresence('code_id')
//            ->notEmptyString('code_id', 'code_id is required')
//           ;
//
//
//        //custom validation
//        $validator->add('contested_dealer_amount', 'myRule', [
//            'rule' => function ($value, array $context) {
//                if ($value >  0) {
//
//                    return true;
//
//                }
//
//            }
//            );
//
//        return $validator;
//
//    }




    function getUsers(){
        $users = $this->find('all');
        return($users->toArray());
    }

    function createUser($email, $password, $userType) {
        $user = $this->newEmptyEntity();

        $user->email = $email;
        $user->password = $password;
        $user->user_type = $userType;


        //dd($user);exit;

        if ($this->save($user)) {
            return $user->toArray();
        } else {
            return false;
        }
    }
    function getUserByEmail($email){
        $user = $this->find('all',['conditions' => [
            'AND' => [
                [ 'Users.email' => $email]
            ]
        ]])->first();

        if(!empty($user)){

            return $user->toArray();
        }
        return false;
    }

    function saveUserPassword($email, $password) {
        $user = $this->find('all', [
            'conditions' => ['Users.email' => $email]
        ])->first();
        $user->password = $password;

        //dd($user);
        if ($this->save($user)) {
            return $user->toArray();
        } else {
            return false;
        }
    }

    function resetAddToken($email) {
        $user = $this->find('all', [
            'conditions' => ['Users.email' => $email]
        ])->first();
        $user->reset_token = Text::uuid();
        return $this->save($user);
    }
    function resetRemoveToken($email) {
        $user = $this->find('all', [
            'conditions' => ['Users.email' => $email]
        ])->first();


        $user->reset_token = '';
        return $this->save($user);
    }

    function getUserByEmailAndResetToken($email, $resetToken){
        $user = $this->find('all',['conditions' => [
            'AND' => [
                [ 'Users.email' => $email],
                [ 'Users.reset_token' => $resetToken]
            ]
        ]])->first();

        //dd($user);
        if(!empty($user)){
            return $user->toArray();
        }
        return false;
    }

}// end
