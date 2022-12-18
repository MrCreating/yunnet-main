document.addEventListener('DOMContentLoaded', function ()
{
	let login = document.getElementById("reg-login");
	let password = document.getElementById("reg-password");
	let entryButton = document.getElementById("reg-button");

	entryButton.addEventListener('click', function () {
		auth(login.value, password.value);
	})
});

function auth (login, password) {
	$.ajax({
		url: '/',
		type: 'POST',
		data: {
			action: 'auth',
			login: login,
			password: password
		},
		success: function (response) {
			try {
				response = JSON.parse(response);
				if (response.error)
					return M.toast({html: 'Неверный логин или пароль'});

				return window.location.reload();
			} catch (e) {
				return M.toast({html: 'Ошибка при работе с данными с сервера'})
			}
		},
		error: function () {
			return M.toast({html: 'Ошибка при авторизации'});
		}
	});
}