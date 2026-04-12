<?php

namespace JET_ABAF\Compatibility\Packages\Jet_Engine\Macros\Tags;

use JET_ABAF\Macros\Traits\Bookings_Count_Trait;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Bookings_Count extends \Jet_Engine_Base_Macros {
	use Bookings_Count_Trait;
}
