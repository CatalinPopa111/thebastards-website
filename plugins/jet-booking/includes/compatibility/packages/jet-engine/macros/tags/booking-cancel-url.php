<?php
namespace JET_ABAF\Compatibility\Packages\Jet_Engine\Macros\Tags;

use JET_ABAF\Macros\Traits\Booking_Cancel_URL_Trait;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Booking_Cancel_URL extends \Jet_Engine_Base_Macros {
	use Booking_Cancel_URL_Trait;
}
