<?php

namespace JET_ABAF\Macros\Tags;

use \Crocoblock\Base_Macros;
use \JET_ABAF\Macros\Traits\Booking_Units_Count_Trait;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Booking_Units_Count extends Base_Macros {
	use Booking_Units_Count_Trait;
}
