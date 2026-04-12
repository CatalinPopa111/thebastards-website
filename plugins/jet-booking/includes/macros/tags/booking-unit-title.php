<?php

namespace JET_ABAF\Macros\Tags;

use \Crocoblock\Base_Macros;
use \JET_ABAF\Macros\Traits\Booking_Unit_Title_Trait;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Booking_Unit_Title extends Base_Macros {
	use Booking_Unit_Title_Trait;
}
