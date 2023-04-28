<?php

namespace MzpoAmo;

class Company1C implements Base1CInterface
{
	public ?string $company_id_1C;
	public array $amo_ids;
	public string $name;
	public ?string $email;
	public ?string $phone;
	public ?string $signee;

	public ?string $ogrn;
	public ?string $inn;
	public ?string $acc_no;
	public ?string $kpp;
	public ?string $bik;
	public ?string $address;
	public ?string $post_address;
//	public ?string $partner_type;


	private const COMPARER = [
		'company_id_1C' => CustomFields::COMPANY_ID_1C,
		'email' => CustomFields::EMAIL,
		'phone' => CustomFields::PHONE,
		'ogrn' => CustomFields::OGRN,
		'inn'=> CustomFields::INN,
		'acc_no' => CustomFields::ACC_NO,
		'kpp' => CustomFields::KPP,
		'bik' => CustomFields::BIC,
		'address' => CustomFields::ADDRESS,
		'post_address' => CustomFields::POST_ADDRESS,
	];


	public static function fromAMO($model, $type = null)
	{
		#region __construct()
		$company1c = new self();
		#endregion

		//region Получение модели компании
		if(!is_a($model, 'MzpoAmo\Company'))
		{
			$model = new Contact([], $type, $model);
		}
		//endregion

		//region Заполнение полей
		foreach (self::COMPARER as $prop => $amo) {
			if (!empty($amo[$model->getType()]) and property_exists(self::class, $prop)) {
				$company1c->{$prop} = $model->getCFValue($amo[$model->getType()]) ?: null;
			}
		}

		$company1c->name = $model->getName();

		$company1c->amo_ids = [
			[
				'account_id' => $model->getAccountId(),
				'entity_id' => $model->getId()
			]
		];
		//endregion

		return $company1c;

	}

	public static function from1C(array $array)
	{
		// TODO: Implement from1C() method.
	}
}