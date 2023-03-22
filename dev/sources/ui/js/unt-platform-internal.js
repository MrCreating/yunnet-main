(function () {
    unt.setupLinks = function () {
        let links = document.querySelectorAll('a');

        links.forEach(function (item) {
            item.addEventListener('click', function (event) {
                event.stopPropagation();
                event.preventDefault();

                if (this.href === '') return;

                let url = this.href.split(window.location.host)[1];

                unt.setModule(url, true);

                try {
                    window.history.pushState(null, null, this.href);
                } catch (e) {}}
            );
        });
    }

    unt.setModule = function (url, loadData = true) {
        if (loadData) {
            try {
                unt.Sidenav.getInstance(document.getElementsByClassName('sidenav')[0]).close();
            } catch (e) {}

            unt.request.post(url, {
                action: 'get_page'
            }).then(function (response) {
                let element = document.createElement('div');
                element.innerHTML = String(response);

                let mainInformationWindow = element.getElementsByClassName('main-information-window')[0];
                if (mainInformationWindow)
                    document.getElementsByClassName('main-information-window')[0].replaceWith(mainInformationWindow);
                else throw new Error('Not found class');

                let mainInterfaceWindow = element.getElementsByClassName('main-interface-window')[0];
                if (mainInterfaceWindow)
                    document.getElementsByClassName('main-interface-window')[0].replaceWith(mainInterfaceWindow);
                else throw new Error('Not found class');

                unt.setModule(url, false);
            }).catch(function (errorCode) {
                return window.location.reload();
            })
        } else {
            let moduleName = url.substring(1).split('?')[0];

            if (moduleName === '') {
                moduleName = unt.users.current ? 'news' : 'auth';
            }

            if (moduleName.substring(0, 4) === 'wall') moduleName = 'wall';
            if (moduleName.substring(0, 4) === 'poll') moduleName = 'poll';
            if (moduleName.substring(0, 5) === 'photo') moduleName = 'photo';

            try {
                unt.modules[moduleName].init();
            } catch (e) {
                unt.modules.profile.init();
            }

            unt.setupLinks();
            unt.AutoInit();
        }
    }
})(unt);