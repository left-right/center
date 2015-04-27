<div class="form-group field-{{ $field->type }} @if ($errors->has($field->name)) has-error @endif">
	<label class="control-label col-sm-2">{{ $field->title }}</label>
	<div class="col-sm-10">
		<div class="input-group datetime" data-date-format="MM/DD/YYYY hh:mm A">
			<span class="input-group-addon">{!! config('center.icons.date') !!}</span>
			<input type="text" class="form-control  @if ($field->required) required @endif" value="{{ @$row->{$field->name} }}" name="{{ $field->name }}">
		</div>
	</div>
</div>