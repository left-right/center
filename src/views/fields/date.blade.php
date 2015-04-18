<div class="form-group field-{{ $field->type }} @if ($errors->has($field->name)) has-error @endif">
	<label class="control-label col-sm-2">{{ $field->title }}</label>
	<div class="col-sm-10">
		<div class="input-group date" data-date-format="MM/DD/YYYY">
			<span class="input-group-addon"><span class="glyphicon glyphicon-calendar"></span></span>
			<input type="text" class="form-control  @if ($field->required) required @endif" @if (isset($row->{$field->name})) value="{{ date('m/d/Y', strtotime($row->{$field->name})) }}" @endif name="{{ $field->name }}">
		</div>
	</div>
</div>