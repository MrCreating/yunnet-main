window.addEventListener('DOMContentLoaded', function () {
	(unt.modules ? unt.modules : unt.modules = {}).realtime.handler = function (event) {
		console.log(event);
	}
});