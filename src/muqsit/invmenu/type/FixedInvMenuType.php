<?php

declare(strict_types=1);

namespace muqsit\invmenu\type;

/**
 * An InvMenuType with a fixed menus size.
 */
interface FixedInvMenuType extends InvMenuType{

	/**
	 * Returns size (number of slots) of the menus.
	 *
	 * @return int
	 */
	public function getSize() : int;
}