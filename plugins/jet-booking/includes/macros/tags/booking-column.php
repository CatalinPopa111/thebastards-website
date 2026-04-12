<?php

namespace JET_ABAF\Macros\Tags;

use \Crocoblock\Base_Macros;
use \JET_ABAF\Macros\Traits\Booking_Column_Trait;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Booking_Column extends Base_Macros {
	use Booking_Column_Trait;
}
