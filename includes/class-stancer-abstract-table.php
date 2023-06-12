<?php
/**
 * This file is a part of Stancer WordPress module.
 *
 * See readme for more informations.
 *
 * @link https://www.stancer.com
 * @license MIT
 * @copyright 2023 Stancer / Iliad 78
 *
 * @package stancer
 * @subpackage stancer/includes
 */

/**
 * Abstract table representation.
 *
 * @since 1.0.0
 *
 * @package stancer
 * @subpackage stancer/includes
 */
class WC_Stancer_Abstract_Table {
	/**
	 * Primary key value.
	 *
	 * @since 1.0.0
	 * @var integer
	 */
	protected $id;

	/**
	 * Creation ate and time.
	 *
	 * @since 1.0.0
	 * @var DateTime
	 */
	protected $datetime_created;

	/**
	 * Last modification ate and time.
	 *
	 * @since 1.0.0
	 * @var DateTime
	 */
	protected $datetime_modified;

	/**
	 * Return properties value.
	 *
	 * @since 1.0.0
	 * @param string $property Property name.
	 */
	public function __get( $property ) {
		if ( property_exists( $this, $property ) ) {
			return $this->$property;
		}

		return null;
	}

	/**
	 * Update a property value.
	 *
	 * @since 1.0.0
	 * @param string $property Property name.
	 * @param mixed $value New value.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			return $this->$property = $value;
		}

		return null;
	}

	/**
	 * Hydrate class.
	 *
	 * @since 1.0.0
	 * @param array $data Data to hydrate.
	 */
	public function hydrate( array $data ) {
		if ( isset( $data[ $this->primary ] ) ) {
			$this->id = (int) $data[ $this->primary ];
		}

		foreach ( $data as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->$key = $value;
			}
		}
	}

	/**
	 * Save to database.
	 *
	 * @since 1.0.0
	 */
	public function save() {
		global $wpdb;

		$properties = get_object_vars( $this );
		$defaults = [
			'id',
			'datetime_created',
			'datetime_modified',
			'primary',
			'table',
		];

		$data = array_diff_key( $properties, array_flip( $defaults ) );
		$key_callback = function ( $key ) {
			return '`' . esc_sql( $key ) . '`';
		};

		$keys = array_map( $key_callback, array_keys( $data ) );

		$values = [];
		foreach ( $data as $key => $value ) {
			$value_formatted = '"' . esc_sql( $value ) . '"';

			if ( 'last4' === $key ) {
				$value_formatted = '"' . esc_sql( $value ) . '"';
			}

			if ( is_bool( $value ) || is_numeric( $value ) ) {
				$value_formatted = (int) $value;
			}

			if ( is_null( $value ) ) {
				$value_formatted = 'NULL';
			}

			$values[ $key ] = $value_formatted;
		}

		if ( is_null( $this->id ) ) {
			$sql = 'INSERT INTO `' . $wpdb->prefix . $this->table . '`
						(' . implode( ', ', $keys ) . ', `datetime_created`)
					VALUES
						(' . implode( ', ', $values ) . ', NOW());';
		} else {
			$fields_callback = function ( $key, $value ) {
				return '`' . esc_sql( $key ) . '` = ' . $value;
			};

			$fields = array_map( $fields_callback, array_keys( $values ), $values );

			$sql = 'UPDATE `' . $wpdb->prefix . $this->table . '`
					SET ' . implode( ', ', $fields ) . '
					WHERE TRUE
					AND `' . $this->primary . '` = ' . intval( $this->id ) . '
					;';
		}

		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( $sql );
		// phpcs:enable

		return $this;
	}
}
