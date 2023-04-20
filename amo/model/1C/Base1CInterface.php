<?php

namespace MzpoAmo;

interface Base1CInterface
{


	/**
	 * Метод сборки модели для 1с из объекта в амо: принимает либо объект-наследник MzpoAmo, либо id сущности АМО
	 * @param MzpoAmo|int $model
	 * @return mixed
	 */
	public static function fromAMO($model, $type = null);

	/**
	 * Метод сборки объекта из 1с для дальнейшей конвертации. Принимает на вход Массив данных из 1С
	 * @param array $array
	 * @return mixed
	 */
	public static function from1C(array $array);
}