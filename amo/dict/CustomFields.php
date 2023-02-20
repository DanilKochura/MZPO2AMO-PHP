<?php

namespace MzpoAmo;

interface CustomFields
{
	public const SITE = [639081, 758213];
	public const CITY = [639087];
	public const RESULT = [644675];
	public const SOURCE = [639085, 748385];
	public const TYPE = [639075, 748383];
	public const LEAD1C = [710399];
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

	public const SKU = [647993];

	public const DURATION = [715507];

	public const PRICE = [647997];

	public const STUDY_FORM = [715509];

	public const COURSE_DESCR = [647995];

	public const COURSE_UID_1c = [710407];


	public const RET_ID = [null, 752191];
	public const ID_LEAD_RET = [null, 759479];
	public const LEAD_DOG = [null, 759477];

	public const CORP_MAN = [761425];

	public const CLIENT_1C = [710429];

	public const STUDY_FORM_RET = [643207];
	public const STUDY_TYPE = [644763];

	public const START_STUDY = [643199];
	public const END_STUDY =[643201];
	public const EXAM_DATE =[644915];

	public const OFICIAL_NAME = [645965];

	public const PREPODAVATEL = [730623];
	public const AUDITORY = [730625];

	public const CORP_FIELDS = [
		self::SITE, self::TYPE, self::ROISTAT, self::PAGE, self::RET_ID
	];
}
