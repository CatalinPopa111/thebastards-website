<?php

namespace JET_ABAF\Compatibility\Packages\Jet_Engine\Macros\Tags;

use JET_ABAF\Macros\Traits\Booking_Units_Count_Trait;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Booking_Units_Count extends \Jet_Engine_Base_Macros {
	use Booking_Units_Count_Trait;
}
