<?php


use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel;

$secret_key = 'sdDF4$sfEbgTd24b@dfR';  //пароль для API
//$subdomain = 'mzpoeducationsale'; //Поддомен нужного аккаунта

require $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';
require $_SERVER['DOCUMENT_ROOT'].'/amo/model/MzpoAmo.php';
require $_SERVER['DOCUMENT_ROOT'].'/amo/dict/CustomFields.php';
require $_SERVER['DOCUMENT_ROOT'].'/amo/dict/Pipelines.php';
require $_SERVER['DOCUMENT_ROOT'].'/amo/model/Course.php';
require $_SERVER['DOCUMENT_ROOT'].'/amo/model/Contact.php';
require $_SERVER['DOCUMENT_ROOT'].'/amo/model/Log.php';
require $_SERVER['DOCUMENT_ROOT'].'/amo/dict/Users.php';
require $_SERVER['DOCUMENT_ROOT'].'/amo/services/CoursesServise.php';





if($_GET['key'] == 'sdDF4$sfEbgTd24b@dfR')
{
   if($_SERVER['REQUEST_METHOD'] === 'POST')
   {
       if(!empty($_POST))
       {
           $amoRepo = new \MzpoAmo\MzpoAmo(\MzpoAmo\MzpoAmo::SUBDOMAIN_CORP);



           $companies_filter = new \AmoCRM\Filters\CompaniesFilter();
           $companies_filter->setQuery($_POST['inn']);
           $company_asked = null;
           try {
               $companies = $amoRepo->apiClient->companies()->get($companies_filter);
               foreach ($companies as $company)
               {
                   if ($company->getCustomFieldsValues()->getBy('fieldId', \MzpoAmo\CustomFields::INN[1]));
                   {
                       if($company->getCustomFieldsValues()->getBy('fieldId', \MzpoAmo\CustomFields::INN[1])->getValues()->first()->getValue() == $_POST['inn'])
                       {
                           $company_asked = $company;
                       }
                   }
               }
           } catch (Exception $e)
           {

           }
           if($company_asked == null)
           {
               $company_asked = new \AmoCRM\Models\CompanyModel();
               $cfvs = new CustomFieldsValuesCollection();
               $cfvs
                   ->add(
                       (new TextCustomFieldValuesModel())
                           ->setFieldId(\MzpoAmo\CustomFields::INN[1])
                           ->setValues(
                               (new TextCustomFieldValueCollection())
                                   ->add(
                                       (new TextCustomFieldValueModel())
                                           ->setValue($_POST['inn'])
                                   )
                           )
                   );
               $company_asked->setCustomFieldsValues($cfvs);
               $company_asked->setName($_POST['company']);
               $company_asked = $amoRepo->apiClient->companies()->addOne($company_asked);
           }
            $contact = new \MzpoAmo\Contact($_POST, \MzpoAmo\MzpoAmo::SUBDOMAIN_CORP);
           $contact->setCompany($company_asked)->setCFStringValue(\MzpoAmo\CustomFields::POST[1], $_POST['post'])->save();
           $lead = new \AmoCRM\Models\LeadModel();
           $lead->setCompany($company_asked);
           $lead->setContacts((new \AmoCRM\Collections\ContactsCollection())->add($contact->getContact()));
           $lead->setResponsibleUserId($_POST['manager_id']);
           $amoRepo->apiClient->leads()->addOne($lead);
       }else
       {
           header("HTTP/1.1 400 Bad Request");
           die(json_encode(['status' => 'empty_request']));
       }

   } else
   {
       header("HTTP/1.1 405 Method Not Allowed");
       die(json_encode(['status' => 'method_not_allowed']));
   }
} else
{
    header("HTTP/1.1 403 Forbidden");
    die(json_encode(['status' => 'access_denied']));
}