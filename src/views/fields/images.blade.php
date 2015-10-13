<div class="form-group field-{{ $field->type }} @if ($errors->has($field->name)) has-error @endif">
	<label class="control-label col-sm-2" for="{{ $field->name }}">{{ $field->title }}</label>
	<div class="col-sm-10">
		<?php $ids = []; ?>
		@foreach ($row->{$field->name} as $image)
			<div class="image" data-table-name="{{ $table->name }}" data-field-name="{{ $field->name }}" data-file-id="{{ $image->id }}" data-action="{{ action('\LeftRight\Center\Controllers\FileController@image') }}" style="width:{{ $field->screen_width }}px; height:{{ $field->screen_height }}px; background-image: url({{ $image->url }});">
				<div class="dimensions">{{ $field->width }} &times; {{ $field->height }}</div>
			</div>
			<?php $ids[] = $image->id; ?>
		@endforeach
		{!! Form::hidden($field->name, implode(',', $ids)) !!}
		<div class="image new" data-table-name="{{ $table->name }}" data-field-name="{{ $field->name }}" data-action="{{ action('\LeftRight\Center\Controllers\FileController@image') }}" style="width:{{ $field->screen_width }}px; height:{{ $field->screen_height }}px;">
			<div class="dimensions">{{ $field->width or '&infin;' }} &times; {{ $field->height or '&infin;' }}</div>
		</div>
	</div>
</div>