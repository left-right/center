<?php namespace LeftRight\Center\Libraries;

use Illuminate\Support\Str;
use Session;

class Table {

	private $rows;
	private $columns;
	private $deletable;
	private $draggable;
	private $grouped;
	
	function __construct() {
		$this->rows			= [];
		$this->columns		= [];
		$this->deletable	= false;
		$this->draggable	= false;
		$this->grouped		= false;
	}

	//add a column.  $trans is translation file key
	public function column($name, $type, $label=false, $width=false, $height=false) {
		if ($label === false) $label = $name;
		$this->columns[] = compact('name', 'type', 'label', 'width', 'height');
	}

	//set table to be deletable
	public function deletable() {
		$this->deletable = true;
	}

	//set table to be draggable
	public function draggable($url) {
		$this->draggable = $url;
	}

	//draw the table
	public function draw($id='untitled') {
		
		//start up
		if ($this->draggable) array_unshift($this->columns, ['label'=>'', 'type'=>'draggy', 'name'=>'draggy']);
		if ($this->deletable) self::column('delete', 'delete', '');
		if ($this->grouped) $last_group = '';
		$colspan = count($this->columns);
		$rowspan = count($this->rows);

		//build <thead>
		$columns = [];
		foreach ($this->columns as $column) {
			$columns[] = '<th class="type-' . $column['type'] . ' ' . $column['name'] . '">' . $column['label'] . '</th>';
		}
		$head = '<thead><tr>' . implode($columns) . '</tr></thead>';

		//build rows
		$bodies = $rows = [];
		foreach ($this->rows as $row) {
			$columns = [];
			$link = true;
			foreach ($this->columns as $column) {

				//handle groupings
				if ($this->grouped && ($last_group != $row->{$this->grouped})) {
					$last_group = $row->{$this->grouped};
					if (count($rows)) $bodies[] = '<tbody>' . implode($rows) . '</tbody>';
					$bodies[] = '<tr class="group"><td colspan=' . $colspan . '">' . $last_group . '</td></tr>';
					$rows = [];
				}

				//process value if necessary
				if ($column['type'] == 'draggy') {
					$value = config('center.icons.drag');
				} elseif ($column['type'] == 'delete') {
					$value = '<a href="' . $row->delete . '">' . ($row->deleted_at ? config('center.icons.deleted') : config('center.icons.undeleted')) . '</a>';
				} elseif ($column['type'] == 'image') {
					$value = '<img src="' . $row->{$column['name'] . '_url'} . '" width="' . $column['width'] . '" height="' . $column['height'] . '">';
					if (isset($row->link)) $value = '<a href="' . $row->link . '">' . $value . '</a>';						
				} elseif ($column['type'] == 'stripe_charge') {
					$value = $row->{$column['name']} ? '<a href="https://dashboard.stripe.com/payments/' . $row->{$column['name']} . '" target="_blank">' . config('center.icons.new_window') . ' ' . trans('center::site.stripe_open') . '</a>' : '';
				} else {
					$value = Str::limit(strip_tags($row->{$column['name']}));
					if ($column['type'] == 'updated_at') {
						$value = Dates::relative($value);
					} elseif ($column['type'] == 'time') {
						$value = Dates::time($value);
					} elseif ($column['type'] == 'date') {
						$value = Dates::absolute($value);
					} elseif ($column['type'] == 'date-relative') {
						$value = Dates::relative($value);
					} elseif (in_array($column['type'], ['datetime', 'timestamp'])) {
						$value = Dates::absolute($value);
					} elseif ($column['type'] == 'money') {
						$value = '$' . number_format($value, 2);
					}

					if (isset($row->link) && $link) {
						if ($column['type'] == 'color') {
							$value = '<a href="' . $row->link . '" style="background-color: ' . $value . '"></a>';
						} else {
							if ($value == '') $value = '&hellip;';
							$value = '<a href="' . $row->link . '">' . $value . '</a>';
							$link = false;
						}
					}
				}

				//create cell
				$columns[] = '<td class="type-' . $column['type'] . ' ' . $column['name'] . '">' . $value . '</td>';
			}

			//create row
			$rows[] = '<tr' . (empty($row->id) ? '' : ' id="' . $row->id . '"') . ($this->deletable && $row->deleted_at ? ' class="inactive"' : '') . '>' . implode($columns) . '</tr>';
		}

		$bodies[] = '<tbody>' . implode($rows) . '</tbody>';

		//output
		return '<table id="' . $id . '" class="table table-condensed' . ($this->draggable ? ' draggable" data-draggable-url="' . $this->draggable : '') . '" data-csrf-token="' . Session::token() . '">' .
					$head . 
					implode($bodies) . 
				'</table>';
	}

	//set a key to group by
	public function groupBy($key) {
		$this->grouped = $key;
	}

	//rows expects a resultset
	public function rows($rows) {
		$this->rows = $rows;
	}
}