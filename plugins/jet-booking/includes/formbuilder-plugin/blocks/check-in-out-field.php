<?php

namespace JET_ABAF\Formbuilder_Plugin\Blocks;

use Jet_Form_Builder\Blocks\Native_Block_Wrapper_Attributes;
use Jet_Form_Builder\Blocks\Types\Base;

class Check_In_Out_Field extends Base implements Native_Block_Wrapper_Attributes {

	public function get_name() {
		return 'check-in-out';
	}

	public function get_path_metadata_block() {
		return JET_ABAF_PATH . 'assets/js/admin/blocks-view/build/blocks/' . $this->get_name();
	}

	public function get_block_renderer( $wp_block = null ) {
		return ( new Check_In_Out_Render( $this ) )->getFieldTemplate();
	}

}