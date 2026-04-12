<?php

namespace JET_ABAF\Macros\Tags;

use \Crocoblock\Base_Macros;
use \JET_ABAF\Macros\Traits\Booking_Price_Per_Day_Night_Trait;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Booking_Price_Per_Day_Night extends Base_Macros {
	use Booking_Price_Per_Day_Night_Trait;
}
