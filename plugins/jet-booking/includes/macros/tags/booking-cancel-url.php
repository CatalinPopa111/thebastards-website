<?php
namespace JET_ABAF\Macros\Tags;

use Crocoblock\Base_Macros;
use JET_ABAF\Macros\Traits\Booking_Cancel_URL_Trait;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Booking_Cancel_URL extends Base_Macros {
	use Booking_Cancel_URL_Trait;
}
