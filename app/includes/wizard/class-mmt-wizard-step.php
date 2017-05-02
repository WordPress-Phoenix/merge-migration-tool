<?php
/**
 * Migration Merge Tool - Abstract - Wizard Step
 *
 * All other wizard steps should be extended from this class.
 *
 * @package    MMT
 * @subpackage Includes\Wizard
 * @since      0.1.0
 */

namespace MergeMigrationTool\Includes\Wizard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MMT_Wizard_Step
 *
 * @since 0.1.0
 */
abstract class MMT_Wizard_Step {

	/**
	 * Step Name
	 *
	 * @since 0.1.0
	 */
	protected $name;

	/**
	 * Wizard
	 *
	 * @since 0.1.0
	 */
	protected $wizard;

	/**
	 * Constructor
	 *
	 * @since 0.1.0
	 */
	public function __construct( $wizard ) {
		$this->wizard = $wizard;
	}

	/**
	 * ToString Method
	 *
	 * @since 0.1.0
	 *
	 * @return mixed
	 */
	public function toString() {
		return $this->name;
	}
}
