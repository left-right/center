<div class="form-group field-{{ $field->type }} @if ($errors->has($field->name)) has-error @endif">
	<label class="control-label col-sm-2">{{ $field->title }}</label>
	<div class="col-sm-10">
		@if (isset($table->url))
			<div class="input-group">
				<span class="input-group-addon">{{ url($table->url) }}/</span>
				{!! Form::text($field->name, $row->{$field->name}, ['class'=>'form-control ' . $field->type . ($field->required ? ' required' : '')]) !!}
				<span class="input-group-addon"><a href="{{ $table->url }}/{{ $row->slug }}" target="_blank"><i class="glyphicon glyphicon-new-window"></i></a></span>
			</div>
		@else
			{!! Form::text($field->name, $row->{$field->name}, ['class'=>'form-control ' . $field->type . ($field->required ? ' required' : '')]) !!}
		@endif
	</div>
</div>