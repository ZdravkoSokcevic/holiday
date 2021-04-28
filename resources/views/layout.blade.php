<!DOCTYPE html>
<html>
<head>
	@include('header')
	@yield('header')
</head>
<body>
	@include('navigation')
	<div class="container">
		@yield('content')
	</div>
	@include('scripts')
	@yield('scripts')
</body>
</html>