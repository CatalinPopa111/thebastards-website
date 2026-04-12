<?php

namespace JET_ABAF\Formbuilder_Plugin\Blocks;

use JET_ABAF\Form_Fields\Check_In_Out_Render_Trait;
use JET_ABAF\Vendor\Fields_Core\Smart_Block_Trait;
use Jet_Form_Builder\Blocks\Render\Base;

class Check_In_Out_Render extends Base {

	use Smart_Block_Trait;
	use Check_In_Out_Render_Trait;

	/**
	 * @return mixed
	 */
	public function get_name() {
		return 'check-in-out';
	}
}