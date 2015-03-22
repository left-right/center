@extends('center::template')

@section('title')
	@lang('center::fields.plural')
@endsection

@section('main')

	{!! \LeftRight\Center\Libraries\Breadcrumbs::leave([
		URL::action('\LeftRight\Center\Controllers\ObjectController@index')=>trans('center::objects.plural'),
		URL::action('\LeftRight\Center\Controllers\InstanceController@index', $object->name)=>$object->title,
		trans('center::fields.plural'),
		]) !!}

	<div class="btn-group">
		<a class="btn btn-default" id="create" href="{{ URL::action('\LeftRight\Center\Controllers\FieldController@create', $object->name) }}"><i class="glyphicon glyphicon-plus"></i> {{ trans('center::fields.create') }}</a>
	</div>

	@if (count($fields))
		{{ Table::rows($fields)
			->draggable(URL::action('\LeftRight\Center\Controllers\FieldController@reorder', $object->name))
			->column('title', 'string', trans('center::fields.title'))
			->column('type', 'string', trans('center::fields.type'))
			->column('updated_at', 'updated_at', trans('center::site.updated_at'))
			->draw('fields')
			}}
	@else
	<div class="alert alert-warning">
		@lang('center::fields.empty')
	</div>
	@endif

@endsection

@section('side')
	<p>@lang('center::fields.list_help', ['title'=>$object->title])</p>
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