<?php
namespace JET_ABAF\Compatibility\Packages\Jet_Engine\Macros\Tags;

use JET_ABAF\Macros\Traits\Booking_Vendor_Trait;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Booking_Vendor extends \Jet_Engine_Base_Macros {
	use Booking_Vendor_Trait;
}
