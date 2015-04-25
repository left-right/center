<!DOCTYPE HTML>
<html>
	<head>
		<base href="/vendor/center/jscolor">
		<title>@yield('title')</title>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
		@foreach (config('center.js') as $script)
			{!! HTML::script($script) !!}
		@endforeach
		@foreach (config('center.css') as $style)
			{!! HTML::style($style) !!}
		@endforeach
	</head>
	<body>
		<div class="container">
			<div class="row">
				<div class="col-md-12 header">
					<a href="{{ URL::action('\LeftRight\Center\Controllers\TableController@index') }}"></a>
				</div>
			</div>
			<div class="row">
				<div class="col-md-9 main">	
					@yield('main')
				</div>
				<div class="col-md-3 side">
					<div class="inner">
						@yield('side')
					</div>
				</div>
			</div>
		</div>
		@yield('script')
	</body>
</html>