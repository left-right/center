<div class="form-group field-{{ $field->type }} @if ($errors->has($field->name)) has-error @endif">
	<label class="control-label col-sm-2">
		{{ $field->title }}
		<!-- <a href="" class="btn btn-default btn-xs">None</a> -->
	</label>
	<div class="col-sm-10">
		<div class="row">
		@foreach ($field->tables as $table)
			<div class="col-md-6">
				<div class="form-group">
					<div class="col-md-6">
						{!! Form::select('permissions[' . $table->name . ']', $field->options, @$table->value, ['class'=>'form-control']) !!}
					</div>
					<label class="col-md-6 control-label" style="text-align:left;">{{ $table->title }}</label>
				</div>
			</div>
		@endforeach
		</div>
	</div>
</div>