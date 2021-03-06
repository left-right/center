<?php namespace LeftRight\Center\Console;

use Illuminate\Console\Command;
use LeftRight\Center\Controllers\LoginController;
use LeftRight\Center\Controllers\RowController;
use Schema;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class Refresh extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'center:refresh';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Refresh table schema based on current configuration.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$tables = config('center.tables');

		$joining_tables = [];

		//dd($tables);
		
		foreach ($tables as $table) {

			//create if doesn't exist, every table gets an id
			if (!Schema::hasTable($table->name)) {
				Schema::create($table->name, function($t) {
				    $t->increments('id');
				});
			}
			
			foreach ($table->fields as $field) {

				if ($field->type == 'checkboxes') {
					//create linking table
					if (!Schema::hasTable($field->name)) {
						Schema::create($field->name, function($t) {
						    $t->increments('id');
						});
					}
					$column = RowController::formatKeyColumn($table->name);
					if (!Schema::hasColumn($field->name, $column)) {
						Schema::table($field->name, function($t) use($column) {
							$t->integer($column);
						});
					}
					$column = RowController::formatKeyColumn($field->source);
					if (!Schema::hasColumn($field->name, $column)) {
						Schema::table($field->name, function($t) use($column) {
							$t->integer($column);
						});
					}
				} else {
					//create column
					Schema::table($table->name, function($t) use($table, $field) {
						
						//set type
						switch ($field->type) {

							//boolean
							case 'checkbox':
								eval('$t->boolean($field->name)' . 
									($field->required ? '' : '->nullable()') .
									(!Schema::hasColumn($table->name, $field->name) ? '' : '->change()') .
									'; ');
								break;
							
							//strings
							case 'address':
							case 'color':
							case 'country':
							case 'email':
							case 'password':
							case 'phone':
							case 'slug':
							case 'stripe_charge':
							case 'stripe_customer':
							case 'string':
							case 'url':
							case 'us_state':
							case 'zip':
								if (!isset($field->maxlength)) $field->maxlength = 255;
								eval('$t->string($field->name, $field->maxlength)' . 
									($field->required ? '' : '->nullable()') .
									(!Schema::hasColumn($table->name, $field->name) ? '' : '->change()') .
									'; ');
								break;
							
							//date
							case 'date':
								eval('$t->date($field->name)' . 
									($field->required ? '' : '->nullable()') .
									(!Schema::hasColumn($table->name, $field->name) ? '' : '->change()') .
									'; ');
								break;
							
							//datetime
							case 'datetime':
								eval('$t->datetime($field->name)' . 
									($field->required ? '' : '->nullable()') .
									(!Schema::hasColumn($table->name, $field->name) ? '' : '->change()') .
									'; ');
								break;
							
							//time
							case 'time':
								eval('$t->time($field->name)' . 
									($field->required ? '' : '->nullable()') .
									(!Schema::hasColumn($table->name, $field->name) ? '' : '->change()') .
									'; ');
								break;
							
							//text
							case 'html':
							case 'text':
								eval('$t->text($field->name)' . 
									($field->required ? '' : '->nullable()') .
									(!Schema::hasColumn($table->name, $field->name) ? '' : '->change()') .
									'; ');
								break;
							
							//foreign key
							case 'image':
							case 'integer':
							case 'select':
							case 'user':
								eval('$t->integer($field->name)' . 
									($field->required ? '' : '->nullable()') .
									(!Schema::hasColumn($table->name, $field->name) ? '' : '->change()') .
									'; ');
								break;
							
							//decimal
							case 'money':
								eval('$t->decimal($field->name, 5, 2)' . 
									($field->required ? '' : '->nullable()') .
									(!Schema::hasColumn($table->name, $field->name) ? '' : '->change()') .
									'; ');
								break;
							
							//latitude
							case 'latitude':
								eval('$t->decimal($field->name, 11, 8)' . 
									($field->required ? '' : '->nullable()') .
									(!Schema::hasColumn($table->name, $field->name) ? '' : '->change()') .
									'; ');
								break;
							
							//latitude
							case 'longitude':
								eval('$t->decimal($field->name, 11, 8)' . 
									($field->required ? '' : '->nullable()') .
									(!Schema::hasColumn($table->name, $field->name) ? '' : '->change()') .
									'; ');
								break;
							
						    default:
								trigger_error($field->type . ' not supported yet!');
						}

						//remove unused columns?
						if ($table->keep_clean) {
							$columns = Schema::getColumnListing($table->name);
							$fields = array_keys((array) $table->fields);
							$columns = array_diff($columns, $fields, ['id']);
							foreach ($columns as $column) $t->dropColumn($column);
						}

					});
				}

			}
		}

		$this->comment(PHP_EOL . trans('center::site.refresh_success') . PHP_EOL);
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return [
		];
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return [
			['example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null],
		];
	}

}
