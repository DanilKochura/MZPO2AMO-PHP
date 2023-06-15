<?php

namespace MzpoAmo;

interface CustomFields
{
	public const SITE = [639081, 758213];
	public const CITY = [639087];
	public const RESULT = [644675];
	public const SOURCE = [639085, 748385];
	public const TYPE = [639075, 748383];
	public const LEAD1C = [710399, 748381];
	public const COURSE = [357005];
	public const ROISTAT = [639073, 758217];
	public const PAGE = [639083, 758215];
	public const ID_FORM = [644511];
	public const YM_UID = [715049];
	public const ROISTAT_MARKER = [645583];
	public const EVENT_NAME = [725709];
	public const EVENT_DATETIME = [724347];
	public const EVENT_ADRESS = [725711];
	public const ANALYTIC_ID = [643439];

	public const SKU = [647993, 751165];

	public const DURATION = [715507, 751185];

	public const PRICE = [647997, 751169];

	public const STUDY_FORM = [715509, 751187];

	public const COURSE_DESCR = [647995, 751167];

	public const COURSE_UID_1c = [710407, 751191];


	public const RET_ID = [null, 752191];
	public const ID_LEAD_RET = [null, 759479];
	public const LEAD_DOG = [null, 759477];

	public const CORP_MAN = [761425];

	public const CLIENT_1C = [710429, 748405];

	public const STUDY_FORM_RET = [643207];
	public const STUDY_TYPE = [644763];

	public const START_STUDY = [643199];
	public const END_STUDY =[643201];
	public const EXAM_DATE =[644915];

	public const OFICIAL_NAME = [645965, 162301];

	public const PREPODAVATEL = [730623];
	public const AUDITORY = [730625];
	public const PHONE = [264911, 33575];
	public const EMAIL = [264913, 33577];
	public const SNILS = [724399, 757933];
	public const SEX = [710417];
	public const PASS_SERIE = [715535, 748393];
	public const PASS_NUMBER = [715537, 748395];
	public const PASS_WHERE = [650841, 748399];
	public const PASS_CODE = [650841, 748401];
	public const PASS_ADDRESS = [650843, 748403];
	public const BIRTHDAY = [644285, 68819];

	public const CATALOG = [12463,5111];
	public const PASS_WHEN = [753403,753403];

	public const PRODUCT_1c = [710407, 751191];
	public const UID_GROUP = [731923, 751191];

	public const OGRN = [null, 69121];
	public const INN = [null, 69123];
	public const KPP = [null, 69125];
	public const BIC = [null, 69129];
	public const POST_ADDRESS = [null, 748389];
	public const ACC_NO = [null, 69127];
	public const ADDRESS = [null, 33583];
	public const COMPANY_ID_1C = [null, 748387];
	public const CORP_FIELDS = [
		self::SITE, self::TYPE, self::ROISTAT, self::PAGE, self::RET_ID
	];
}
