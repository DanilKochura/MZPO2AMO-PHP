<?php

namespace MzpoAmo;

interface Users
{
	public const GREBENNIKOVA = 2375107;

	public const KUBYSHINA = 3835801;

	public const SIDOROVA = 8127907;

public const BULIGIN = 9823318;
	public const ALFEROVA_CORP = 2375131;

    public const KOCHURA_CORP = 9081002;

	public const PLATOVA = 8366488;

	public const AFANASYYEVA = 8670964;

	public const SIMKINA = 8505166;

	public const FEDKO = 8366494;

	public const MITROFANOVA = 9193650;
	public const ULYASHEVA = 8628763;
	public const VESELOVA = 9508182;

    public const KIREEVA = 2375116;
    public const FEDOSOVA = 6028753;
    public const ALFEROVA = 2375131;
    public const KOCHURA = 8348113;

    public const ADMIN = 2576764;
    public const ADMIN_DOSTUP = 7149397;

    public const ADMINS =
        [
            0,
//            self::KOCHURA,
            self::ADMIN,
            self::ADMIN_DOSTUP,
            self::GREBENNIKOVA
        ];

    public const CORP_LK_MANAGERS =
        [
            self::KIREEVA,
            self::ALFEROVA,
            self::FEDOSOVA,
            self::ULYASHEVA,
            self::VESELOVA
        ];


}