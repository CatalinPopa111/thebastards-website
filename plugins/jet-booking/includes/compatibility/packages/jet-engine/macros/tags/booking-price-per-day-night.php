<?php

namespace JET_ABAF\Compatibility\Packages\Jet_Engine\Macros\Tags;

use \JET_ABAF\Macros\Traits\Booking_Price_Per_Day_Night_Trait;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Booking_Price_Per_Day_Night extends \Jet_Engine_Base_Macros {
	use Booking_Price_Per_Day_Night_Trait;
}
