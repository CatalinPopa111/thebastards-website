<?php
namespace JET_ABAF\Macros\Tags;

use \Crocoblock\Base_Macros;
use \JET_ABAF\Macros\Traits\Booking_Vendor_Trait;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Booking_Vendor extends Base_Macros {
	use Booking_Vendor_Trait;
}
