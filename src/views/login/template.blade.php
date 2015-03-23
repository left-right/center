<!DOCTYPE HTML>
<html>
	<head>
		<title>@yield('title')</title>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
		{!! HTML::style('/vendor/center/css/main.min.css') !!}
		@if (config('center.css'))
			@foreach (config('center.css') as $stylesheet)
			{!! HTML::style($stylesheet) !!}
			@endforeach
		@endif
	</head>
	<body class="login">
		@yield('main')
		{!! HTML::script('/vendor/center/js/main.min.js') !!}
	</body>
</html>