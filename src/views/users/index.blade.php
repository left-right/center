@extends('center::template')

@section('title')
	@lang('center::users.plural')
@endsection

@section('main')

	{!! \LeftRight\Center\Libraries\Breadcrumbs::leave([
		URL::action('\LeftRight\Center\Controllers\ObjectController@index')=>trans('center::objects.plural'),
		trans('center::users.plural'),
		]) !!}

	<div class="btn-group">
		<a class="btn btn-default" id="create" href="{{ URL::action('\LeftRight\Center\Controllers\UserController@create') }}">
			<i class="glyphicon glyphicon-plus"></i> 
			@lang('center::users.plural_create')
		</a>
	</div>

	@include('center::notifications')

	{!! \LeftRight\Center\Libraries\Table::rows($users)
		->column('name', 'string', trans('center::users.plural_name'))
		->column('role', 'string', trans('center::users.plural_role'))
		->column('last_login', 'date-relative', trans('center::users.plural_last_login'))
		->deletable()
		->draw()
		!!}

@endsection

@section('side')
	<p>@lang('center::users.plural_help')</p>
@endsection

@section('script')
	<script>
	$(document).keypress(function(e){
		if (e.which == 99) {
			location.href = $("a#create").addClass("active").attr("href");
		}
	});

	@if (Session::has('user_id'))
		var $el = $("table tr#{{ Session::get('user_id') }}");
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