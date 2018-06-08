<?php

class ExampleRemoveEntryFromAutomation {
	private $form_id = 9;
	private $action_id = 11;

	public function __construct() {
		add_action( 'frm_after_update_entry', array( $this, 'after_update_entry' ), 10, 2 );
	}

	public function after_update_entry( $entry_id, $form_id ) {
		//Ensure the code is trigger in the correct form
		if($this->form_id != $form_id){
			return;
		}
		//Check if the function exist
		if ( function_exists( _get_cron_array() ) ) {
			$cron = _get_cron_array();
			if ( isset( $cron ) ) {
				$queue   = array();
				//Create our array of entries in the queue
				foreach ( $cron as $timestamp => $events ) {
					if ( isset( $events['formidable_send_autoresponder'] ) ) {
						foreach ( $events['formidable_send_autoresponder'] as $id => $autoresponder ) {
							$queue[] = array(
								'timestamp'   => $timestamp,
								'pretty_time' => date( 'Y-m-d H:i:s', $timestamp ),
								'entry_id'    => $autoresponder['args'][0],
								'action_id'   => $autoresponder['args'][1],
							);
						}
					}
				}

				if ( ! empty( $queue ) ) {
					//Get all Notification actions in this form
					$actions = FrmFormAction::get_action_for_form( $form_id, 'email' );
					/**
					 * @var int      $action_id
					 * @var \WP_Post $action_data
					 */
					foreach ( $actions as $action_id => $action_data ) {
						//Check if the action is the correct
						if($action_id == $this->action_id) {
							//Iterate over the queue we create early
							foreach ( $queue as $item ) {
								//Check if the entry is the same and the action id is the same in the queue
								if ( ! empty( $item['entry_id'] ) && $entry_id === $item['entry_id'] && $item['action_id'] === $action_id ) {
									//Delete the item/entry from the wp cron queue, at the same time will be removed to the action
									wp_unschedule_event( $item['timestamp'], 'formidable_send_autoresponder', array( intval( $item['entry_id'] ), intval( $item['action_id'] ) ) );
								}
							}
						}
					}
				}
			}
		}
	}

}