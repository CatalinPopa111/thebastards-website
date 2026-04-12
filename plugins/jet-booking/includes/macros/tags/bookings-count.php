<?php

namespace JET_ABAF\Macros\Tags;

use \Crocoblock\Base_Macros;
use \JET_ABAF\Macros\Traits\Bookings_Count_Trait;

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

class Bookings_Count extends Base_Macros {
 use Bookings_Count_Trait;
}
