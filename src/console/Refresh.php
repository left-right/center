<?php namespace LeftRight\Center\Console;

use Illuminate\Console\Command;
use LeftRight\Center\Controllers\LoginController;
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
		//dd($tables);
		
		foreach ($tables as $table) {
			
			//create if doesn't exist, every table gets an id
			if (!Schema::hasTable($table->name)) {
				Schema::create($table->name, function($t) {
				    $t->increments('id');
				});
			}
			
			foreach ($table->fields as $field) {
				Schema::table($table->name, function($t) use($table, $field) {
					
					//set type
					switch ($field->type) {
						case 'checkbox':
							eval('$t->boolean($field->name)' . 
								($field->required ? '' : '->nullable()') .
								(!Schema::hasColumn($table->name, $field->name) ? '' : '->change()') .
								'; ');
							break;
							
						case 'color':
						case 'email':
						case 'password':
						case 'slug':
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
							
						case 'date':
							eval('$t->date($field->name)' . 
								($field->required ? '' : '->nullable()') .
								(!Schema::hasColumn($table->name, $field->name) ? '' : '->change()') .
								'; ');
							break;
							
						case 'datetime':
							eval('$t->datetime($field->name)' . 
								($field->required ? '' : '->nullable()') .
								(!Schema::hasColumn($table->name, $field->name) ? '' : '->change()') .
								'; ');
							break;
							
						case 'html':
						case 'text':
							eval('$t->text($field->name)' . 
								($field->required ? '' : '->nullable()') .
								(!Schema::hasColumn($table->name, $field->name) ? '' : '->change()') .
								'; ');
							break;
							
						case 'image':
						case 'integer':
						case 'select':
						case 'user':
							eval('$t->integer($field->name)' . 
								($field->required ? '' : '->nullable()') .
								(!Schema::hasColumn($table->name, $field->name) ? '' : '->change()') .
								'; ');
							break;
							
						case 'money':
							eval('$t->decimal($field->name, 5, 2)' . 
								($field->required ? '' : '->nullable()') .
								(!Schema::hasColumn($table->name, $field->name) ? '' : '->change()') .
								'; ');
							break;
							
						case 'permissions':
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

		//now can set permissions, had to wait for permissions table potentially to be created
		foreach ($tables as $table) {
			//set default permissions
			LoginController::setDefaultTablePermissions($table->name);
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
