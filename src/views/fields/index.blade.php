@extends('center::template')

@section('title')
	@lang('center::messages.fields')
@endsection

@section('main')

	{!! \LeftRight\Center\Libraries\Breadcrumbs::leave([
		URL::action('\LeftRight\Center\Controllers\ObjectController@index')=>trans('center::messages.objects'),
		URL::action('\LeftRight\Center\Controllers\InstanceController@index', $object->name)=>$object->title,
		trans('center::messages.fields'),
		]) !!}

	<div class="btn-group">
		<a class="btn btn-default" id="create" href="{{ URL::action('\LeftRight\Center\Controllers\FieldController@create', $object->name) }}"><i class="glyphicon glyphicon-plus"></i> {{ trans('center::messages.fields_create') }}</a>
	</div>

	@if (count($fields))
		{{ Table::rows($fields)
			->draggable(URL::action('\LeftRight\Center\Controllers\FieldController@reorder', $object->name))
			->column('title', 'string', trans('center::messages.fields_title'))
			->column('type', 'string', trans('center::messages.fields_type'))
			->column('updated_at', 'updated_at', trans('center::messages.site_updated_at'))
			->draw('fields')
			}}
	@else
	<div class="alert alert-warning">
		@lang('center::messages.fields_empty')
	</div>
	@endif

@endsection

@section('side')
	<p>@lang('center::messages.fields_list_help', ['title'=>$object->title])</p>
@endsection

@section('script')
	<script>
	$(document).keypress(function(e){
		if (e.which == 99) {
			location.href = $("a#create").addClass("active").attr("href");
		}
	});

	@if (Session::has('field_id'))
		var $el = $("table tr#{{ Session::get('field_id') }}");
		$el
			.after("<div class='highlight'/>")
			.next()
            .width($el.width())
            .height($el.height())
            .css("marginTop", -$el.height())
			.fadeOut(500, function(){
				$("div.highlight").remove();
			});
	@endif
	</script>
@endsection