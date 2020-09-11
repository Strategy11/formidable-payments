<?php

class test_FrmTransActionsController extends FrmUnitTest {

	/**
	 * @covers FrmTransActionsController::get_amount_from_string
	 */
	public function test_get_amount_from_string() {
		$this->assertEquals( '100,00', $this->get_amount_from_string( '100,00 &#8364;' ) );
		$this->assertEquals( '100,00', $this->get_amount_from_string( '100,00 €' ) );
		$this->assertEquals( '10,00', $this->get_amount_from_string( '10,00 €' ) );
		$this->assertEquals( '10.00', $this->get_amount_from_string( '$10.00' ) );
	}

	/**
	 * @param string $amount
	 * @return string
	 */
	private function get_amount_from_string( $amount ) {
		return $this->run_private_method( array( 'FrmTransActionsController', 'get_amount_from_string' ), array( $amount ) );
	}
}
