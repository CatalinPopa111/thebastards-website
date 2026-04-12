<?php

namespace JET_ABAF\Macros\Tags;

use \Crocoblock\Base_Macros;
use \JET_ABAF\Macros\Traits\Booking_Status_Trait;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Booking_Status extends Base_Macros {
	use Booking_Status_Trait;
}
