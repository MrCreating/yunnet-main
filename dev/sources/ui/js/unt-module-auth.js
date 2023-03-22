(function (unt) {
    unt.modules.auth = {
        start: function (login, password, redirect_to = '/') {
            return new Promise(function (resolve, reject) {
                return unt.request.post('/login', {
                    action: 'login',
                    email: login,
                    password: password,
                    to: redirect_to
                }).then(function (response) {
                    try {
                        response = JSON.parse(String(response));
                        if (response.success) {
                            let url = response.success.redirect_url;

                            return resolve(url);
                        }

                        return reject(new ReferenceError('Login failed'));
                    } catch (e) {
                        return reject(new SyntaxError('Incorrect data received'));
                    }
                }).catch(function (error) {
                    return reject(new Error('Failed to connect'));
                })
            });
        },

        init: function () {
            let loginButton = document.getElementById('auth_button');
            if (loginButton)
                loginButton.classList.add('disabled');

            let loaderSpinner = document.getElementById('auth_loader');
            let loginField = document.getElementById('login');
            let passwordField = document.getElementById('password');

            let actionsList = document.getElementsByClassName('actions-list')[0];

            loginField.oninput = passwordField.oninput = function () {
                if (loginField.value.isEmpty() || passwordField.value.isEmpty()) {
                    loginButton.classList.add('disabled');
                } else {
                    loginButton.classList.remove('disabled');
                }
            }

            let form = document.getElementById('auth_form');
            let inProcess = false;

            form.addEventListener('submit', function (event) {
                if (inProcess) return;

                event.preventDefault();
                event.stopPropagation();

                loginField.classList.remove('wrong');
                passwordField.classList.remove('wrong');

                if (loginField.value.isEmpty()) {
                    return loginField.classList.add('wrong');
                }
                if (passwordField.value.isEmpty()) {
                    return passwordField.classList.add('wrong');
                }

                inProcess = true;
                loginField.setAttribute('disabled', 'true');
                passwordField.setAttribute('disabled', 'true');
                loginButton.style.display = 'none';
                loaderSpinner.style.display = '';
                if (actionsList)
                    actionsList.style.display = 'none';
                window.onbeforeunload = function () {
                    return false;
                }

                return unt.modules.auth.start(loginField.value, passwordField.value).then(function (redirect_url) {
                    loaderSpinner.style.display = 'none';

                    unt.toast({html: unt.users.lang.welcome});
                    window.onbeforeunload = null;
                    setTimeout(function () {
                        try {
                            redirect_url ? (window.location.href = String(redirect_url)) : window.location.reload();
                        } catch (e) {
                            return window.location.reload();
                        }
                    }, 1000)
                }).catch(function () {
                    inProcess = false;
                    loginField.removeAttribute('disabled');
                    passwordField.removeAttribute('disabled');
                    loginButton.style.display = '';
                    loaderSpinner.style.display = 'none';
                    if (actionsList)
                        actionsList.style.display = '';
                    window.onbeforeunload = null;
                    return unt.toast({html: unt.users.lang.wrong_login_or_password})
                });
            });

            unt.changeWindowTo(0);
        }
    };
})(unt);