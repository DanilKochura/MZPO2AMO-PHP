<?php


$secret_key = 'sdDF4$sfEbgTd24b@dfR';  //пароль для API
//$subdomain = 'mzpoeducationsale'; //Поддомен нужного аккаунта

require $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';
require $_SERVER['DOCUMENT_ROOT'].'/amo/model/MzpoAmo.php';
require $_SERVER['DOCUMENT_ROOT'].'/amo/dict/CustomFields.php';
require $_SERVER['DOCUMENT_ROOT'].'/amo/model/Course.php';
require $_SERVER['DOCUMENT_ROOT'].'/amo/model/Log.php';
require $_SERVER['DOCUMENT_ROOT'].'/amo/dict/Users.php';
require $_SERVER['DOCUMENT_ROOT'].'/amo/services/CoursesServise.php';



if($_GET['key'] == 'sdDF4$sfEbgTd24b@dfR')
{
    $amoRepo = new \MzpoAmo\MzpoAmo(\MzpoAmo\MzpoAmo::SUBDOMAIN_CORP);
    $ul = [];
//Поштучный работает медленнее жадного
//foreach (\MzpoAmo\Users::CORP_LK_MANAGERS as $man)
//{
//    $user = $amoRepo->apiClient->users()->getOne($man);
//    $ul[] =
//        [
//            'name' => $user->getName(),
//            'id' => $user->getId(),
//            'email' =>$user->getEmail()
//        ];
//}

    $users = $amoRepo->apiClient->users()->get();
    foreach ($users as $user) {
        if(in_array($user->getId(), \MzpoAmo\Users::CORP_LK_MANAGERS))
        {
            $ul[] = [
                'name' => $user->getName(),
                'id' => $user->getId(),
                'email' =>$user->getEmail()
            ];
        }
    }

    die(json_encode($ul));
} else
{
    header("HTTP/1.1 403 Forbidden");
    die(json_encode(['status' => 'access_denied']));
}