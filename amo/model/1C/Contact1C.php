<?php

namespace MzpoAmo;

class Contact1C implements Base1CInterface
{
	public ?string $client_id_1C;
	public array $amo_ids;
	public array $applications = [];
	public string $name;
	public ?string $email;
	public ?string $phone;
	public ?string $second_phone;

	public $dob;

	public ?int $pass_serie;
	public ?int $pass_number;
	public ?int $pass_dpt_code;
	public ?string $pass_issued_by;
	public  $pass_issued_at;
	public ?string $pass_dpt;
	public ?string $snils;
	public ?string $address;
	public array $diplomas = [];

	public array $groups = [];



	private const COMPARER = [
		"client_id_1C" => CustomFields::CLIENT_1C,
		"email" => CustomFields::EMAIL,
		"phone" => CustomFields::PHONE,
//		"second_phone" => CustomFields::PHONE,
		"dob" => CustomFields::BIRTHDAY,
		"pass_serie" => CustomFields::PASS_SERIE,
		"pass_number" => CustomFields::PASS_NUMBER,
		"pass_dpt_code" => CustomFields::PASS_CODE,
		"pass_issued_by" => CustomFields::PASS_WHERE,
		"pass_issued_at" => CustomFields::PASS_WHEN,
		"snils" => CustomFields::SNILS,
		"address" => CustomFields::PASS_ADDRESS
	];

	public static function fromAmo($contact, $type = null): Contact1C
	{
		//region __construct()
		$client = new self();
		//endregion

		//region Получение модели контакта
		if(!is_a($contact, 'MzpoAmo\Contact'))
		{
			$contact = new Contact([], $type, $contact);
		}
		//endregion

		//region Заполнение полей модели
		foreach (self::COMPARER as $prop => $amo) {
			if (!empty($amo[$contact->getType()]) and property_exists(self::class, $prop)) {
				$client->{$prop} = $contact->getCFValue($amo[$contact->getType()]);
			}
		}
		$client->name = $contact->getName();

		$client->amo_ids = [
			[
				'account_id' => $contact->getAccountId(),
				'entity_id' => $contact->getId()
			]
		];
		if($client->dob)
		{
			$client->dob = $client->dob->format('d.m.Y G:i:s');
		}
		if($client->pass_issued_at)
		{
			$client->pass_issued_at = $client->pass_issued_at->format('d.m.Y G:i:s');
		}
		//endregion

		return $client;
	}

	public static function from1C(array $json): Contact1C
	{
		$client = new self();
		foreach ($json as $key=>$value)
		{
			$client->{$key} = $value;
		}
		return $client;
	}





}