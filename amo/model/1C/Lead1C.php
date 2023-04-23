<?php

namespace MzpoAmo;

use AmoCRM\Models\LeadModel;
use services\UserService;

class Lead1C implements Base1CInterface
{
		public ?string $lead_id_1C;
		public array $amo_ids;

//		public ?array $clients_id_1C;

//		public ?array $products_id_1C;

		public string $product_id_1c;
		public string $client_id_1c;
		public string $company_id_1c;
		public ?string $organization;
		public ?float $price;
		public bool $is_corporate;
		public string $lead_status = 'ВРАботе';
		public ?string $marketing_channel;
		public ?string $marketing_source;

		public ?string $responsible_user;
		public ?string $author;

		public ?array $payments;


		private const COMPARER =
		[
			"lead_id_1C" => CustomFields::LEAD1C,
			"marketing_channel" => CustomFields::TYPE,
			"marketing_source" => CustomFields::SOURCE,
			"organization" => CustomFields::OFICIAL_NAME,
		];


		public static function fromAMO($lead, $type = null)
		{
			$lead1c = new self();

			$catalogElements = $lead->getCatalogElements();
			if(!$catalogElements)
			{
				$lead->setNoteSave('Не удалось перенести сделку: отсуствуют товары!');
				Log::writeError(Log::LEAD, 'Отстутствуют товары в сделке');
				throw new \Exception('Отсутвтуют товары в сделке');
			}

			if(!is_a($lead, 'MzpoAmo\Leads'))
			{
				$lead = new Leads([], $type, $lead, [LeadModel::CATALOG_ELEMENTS]);
			}
			foreach (self::COMPARER as $prop => $amo) {
				if (!empty($amo[$lead->getType()]) and property_exists(self::class, $prop)) {
					$lead1c->{$prop} = $lead->getCFValue($amo[$lead->getType()]);
				}
			}
			if(isset($lead1c->organization) and $lead1c->organization == 'МЦПО')
			{
				$lead1c->organization = 'НОЧУ ДПО МЦПО';
			}

			$lead1c->price = $lead->getPrice();

			$lead1c->amo_ids = [
				[
					'account_id' => $lead->getAccountId(),
					'entity_id' => $lead->getId()
				]
			];

			if($lead->getType() == 1)
			{
				$lead1c->is_corporate = true;
			}


			$uc = new UserService($lead->getSubdomain());
			$name = $uc->getNameById($lead->getResponsible());
			$lead1c->responsible_user =  $name;
			$lead1c->author = $name;
//			$lead1c->client_id_1C = $lead->getContact()->getCustomFieldsValues()->getBy('fieldId', CustomFields::CLIENT_1C[$lead->getType()])->getValues()->first()->getValue();
			$lead1c->product_id_1c = $lead->getCatalogElements()[0]['uid'];

			return $lead1c;
		}
		public static function from1C(array $array)
		{
			// TODO: Implement from1C() method.
		}

}