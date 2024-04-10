<?php

namespace services;

use DB;
use MzpoAmo\Contact;
use MzpoAmo\CustomFields;
use MzpoAmo\Leads;
use MzpoAmo\MzpoAmo;

class PaykeeperService
{
    private string $host;
    private string $password;
    private string $login;

    public string $prefix;
    private const PREFIXES = [
        'ООО «МИРК»' => 'mirk',
        'ООО «МЦПО»' => 'ooo',
        'МЦПО' => 'nochu'
    ];

    public function __construct($org)
    {
        $credits = json_decode(file_get_contents(__DIR__.'/paykeeper.json'));
        $credits = $credits->$org;
        $this->host = $credits->host;
        $this->password = $credits->password;
        $this->login = $credits->login;
        $this->prefix = self::PREFIXES[$org];
        return $this;
    }



    public static function makeLink(Leads $lead, int $sum)
    {
        $service = new self($lead->getCFValue(CustomFields::OFICIAL_NAME[$lead->getType()]));

        $db = new DB();


        $oid = $service->prefix.$lead->getId();
        $uid = $lead->getCFValue(CustomFields::LEAD1C[$lead->getType()]);
        $client = new Contact([], MzpoAmo::SUBDOMAIN, $lead->getContact()->getId());
        $data = [
            "pay_amount" => $sum,
            "orderid" => $oid,
            "client_email" =>  $client->getEmail(),
            "clientid" =>  $client->getName(),
            "client_phone" =>  $client->getPhone()
        ];
        $json = json_encode($data);
        $db->query("INSERT INTO `amo_orders`(`order_id`, `price`, `lead_id`, `uid_1c`, `status`, `yookassa`, `query`) 
                VALUES ('$oid', '$sum', '{$lead->getId()}', '$uid', 0, null, '$json', '{$service->prefix}')");
        $link = $service->query($data);
        $lead->setCFStringValue(CustomFields::BILL_LINK[$lead->getType()], $link);
        $lead->newNote("Ссылка сформирована:".PHP_EOL.$link);
        $lead->save();

    }


    public function query($payment_data)
    {
        $base64=base64_encode($this->login.":".$this->password);
        $headers=Array();
        array_push($headers,'Content-Type: application/x-www-form-urlencoded');

        # Подготавливаем заголовок для авторизации
        array_push($headers,'Authorization: Basic '.$base64);

        # Укажите адрес ВАШЕГО сервера PayKeeper, адрес demo.paykeeper.ru - пример!
        $server_paykeeper=$this->host;



        # Готовим первый запрос на получение токена безопасности
        $uri="/info/settings/token/";

        # Для сетевых запросов в этом примере используется cURL
        $curl=curl_init();

        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl,CURLOPT_URL,$server_paykeeper.$uri);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'GET');
        curl_setopt($curl,CURLOPT_HTTPHEADER,$headers);
        curl_setopt($curl,CURLOPT_HEADER,false);

        # Инициируем запрос к API
        $response=curl_exec($curl);
        $php_array=json_decode($response,true);

        # В ответе должно быть заполнено поле token, иначе - ошибка
        if (isset($php_array['token'])) $token=$php_array['token']; else die();


        # Готовим запрос 3.4 JSON API на получение счёта
        $uri="/change/invoice/preview/";

        # Формируем список POST параметров
        $request = http_build_query(array_merge($payment_data, array ('token'=>$token)));

        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl,CURLOPT_URL,$server_paykeeper.$uri);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
        curl_setopt($curl,CURLOPT_HTTPHEADER,$headers);
        curl_setopt($curl,CURLOPT_HEADER,false);
        curl_setopt($curl,CURLOPT_POSTFIELDS,$request);


        $response=json_decode(curl_exec($curl),true);
        # В ответе должно быть поле invoice_id, иначе - ошибка
        if (isset($response['invoice_id'])) $invoice_id = $response['invoice_id']; else die();

        # В этой переменной прямая ссылка на оплату с заданными параметрами
        $link = "$server_paykeeper/bill/$invoice_id/";
        return $link;
    }


}