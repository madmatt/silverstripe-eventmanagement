<?php
/**
 * A table that allows a user to select the tickets to register for, as well as
 * displaying messages for tickets that are unavailable.
 *
 * @package silverstripe-eventmanagement
 */
class EventRegistrationTicketsTableField extends FormField {

	protected $datetime;
	protected $showUnavailableTickets = true;
	protected $showUnselectedTickets = true;
	protected $total;

	public function __construct($name, $datetime, $value = array()) {
		$this->datetime = $datetime;
		parent::__construct($name, '', $value);
	}

	public function Field() {
		return $this->renderWith('EventRegistrationTicketsTableField');
	}

	/**
	 * @return array
	 */
	public function dataValue() {
		return (array) $this->value;
	}

	/**
	 * @param bool $bool
	 */
	public function setShowUnavailableTickets($bool) {
		$this->showUnavailableTickets = $bool;
	}

	/**
	 * @param bool $bool
	 */
	public function setShowUnselectedTickets($bool) {
		$this->showUnselectedTickets = $bool;
	}

	/**
	 * @param Money $money
	 */
	public function setTotal(Money $money) {
		$this->total = $money;
	}

	/**
	 * @return EventRegistrationTicketsTableField
	 */
	public function performReadonlyTransformation() {
		$table = clone $this;
		$table->setReadonly(true);
		return $table;
	}

	/**
	 * @return DataObjectSet
	 */
	public function Tickets() {
		$result  = new DataObjectSet();
		$tickets = $this->datetime->Tickets('', '"RegisterableDateTime_Tickets"."Sort"');

		foreach ($tickets as $ticket) {
			$available = $ticket->getAvailableForDateTime($this->datetime);
			$endTime   = $ticket->getSaleEndForDateTime($this->datetime);

			if ($avail = $available['available']) {
				$name = "{$this->name}[{$ticket->ID}]";
				$min  = $ticket->MinTickets;
				$max  = $ticket->MaxTickets;

				$val = array_key_exists($ticket->ID, $this->value)
					? $this->value[$ticket->ID] : null;

				if (!$val && !$this->showUnselectedTickets) {
					continue;
				}

				if ($this->readonly) {
					$field = $val ? $val : '0';
				} elseif ($max) {
					$field = new DropdownField(
						$name, '',
						ArrayLib::valuekey(range($min, min($available, $max))),
						$val, null, true);
				} else {
					$field = new NumericField($name, '', $val);
				}

				$result->push(new ArrayData(array(
					'Title'     => $ticket->Title,
					'Available' => $avail === true ? 'Unlimited' : $avail,
					'Price'     => $ticket->PriceSummary(),
					'End'       => DBField::create('SS_Datetime', $endTime),
					'Quantity'  => $field
				)));
			} elseif ($this->showUnavailableTickets) {
				$availableAt = null;

				if (array_key_exists('available_at', $available)) {
					$availableAt = DBField::create('SS_Datetime', $available['available_at']);
				}

				$result->push(new ArrayData(array(
					'Title'       => $ticket->Title,
					'Available'   => false,
					'Reason'      => $available['reason'],
					'AvailableAt' => $availableAt
				)));
			}
		}

		return $result;
	}

	/**
	 * @return Money
	 */
	public function Total() {
		return $this->total;
	}

	/**
	 * @return RegisterableDateTime
	 */
	public function DateTime() {
		return $this->datetime;
	}

}