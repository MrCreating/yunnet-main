(function (unt) {
    unt.modules.settings = {
        init: function () {
            document.title = unt.users.lang.settings;

            unt.changeWindowTo(0);
        }
    };
})(unt);